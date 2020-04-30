<?php

namespace App\Http\Controllers;

use Auth;
use Exception;
use Klaviyo;
use App\Contact;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    private $apiKey;
    private $client;
    private $listId;
    private $eventName;

    public function __construct()
    {
        $this->middleware('auth');

        $this->apiKey = 'pk_568f97419d72c922267f912016e5659a75';
        $this->client = new Client(
            [
                'base_uri' => 'https://a.klaviyo.com',
                'timeout'  => 0,
            ]
        );
        $this->listId = 'STkHdZ';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $contacts = Contact::with('user')->oldest()->paginate(50);

        return view('contacts.index', compact('contacts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $contact = new Contact();

        return view('contacts.create', compact('contact'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {
        return view('contacts.edit', compact('contact'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required|max:10'
        ]);

        $contact = new Contact();
        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->user_id = Auth::user()->id;
        $contact->phone_number = $request->phone_number;
        $contact->save();

        // Klaviyo
        $profiles = [
            [
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => '1' . $request->phone_number,
            ]
        ];

        if ($this->addMembers($this->listId, $profiles)) {
            return redirect()->route('contacts.index')->with('success', 'You successfully added the contact(s)!');
        }

        return back()->with('error', 'Failed to add the contact!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Questions  $questions
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact)
    {
        $this->deleteMembers($this->listId, array($contact->email));

        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'You successfully deleted the contact!');

    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:4096',
        ]);

        $uploadedFile = $request->file('file');
        $extension = $uploadedFile->getClientOriginalExtension();

        if ($extension !== 'csv') {
            return back()->with('error', 'Invalid import file type! Must be CSV.');
        }

        $profiles = [];
        $contacts = array_map('str_getcsv', file($uploadedFile->path()));
        foreach ($contacts as $contact) {
            $profiles[] = [
                'name' => trim($contact[0]),
                'email' => trim($contact[1]),
                'phone_number' => trim($contact[2])
            ];
        }

        // Add to local contacts list
        foreach ($profiles as $profile) {
            $contact = new Contact();
            $contact->name = $profile['name'];
            $contact->email = $profile['email'];
            $contact->user_id = Auth::user()->id;
            $contact->phone_number = $profile['phone_number'];
            $contact->save();
        }

        // Add to klaviyo contacts list
        $membersAdded = $this->addMembers($this->listId, $profiles);

        if ($membersAdded) {
            return back()->with('success','You have successfully upload file.')->with('file', $uploadedFile->getClientOriginalName());
        }

        return back()->with('error', 'Failed to upload file');

    }

    /**
     * The main Events API endpoint is /api/track, which is used to track
     * when someone takes an action or does something. It encodes the following data in a dictionary or hash.
     * GET /api/track
     *
     * @param string $event              The event name. For example, 'register'.
     * @param array  $customerProperties An array containing the email (client email).
     * @param array  $properties         An array containing all extra data of the client, as
     *                                   name, surname, language, city, etc.
     * @param  mixed  $timestamp         The time in UNIX timestamp format. null by default.
     * @return object
     */
    public function track($email)
    {
        $data = [
            'token' => $this->apiKey,
            'event' => 'trackme',
            'customer_properties' => ['email' => $email],
            'time' => time()
        ];

        $response = $this->client->get(
            '/api/track',
            [
                'query' => [
                    'data' => base64_encode(json_encode($data))
                ]
            ]
        );

        if ($this->decode($response)) {
            return redirect()->route('contacts.index')->with('success', 'The user has been tracked!');
        }
    }

    /**
     * Adds a new person to the specified list. If a person with that
     * email address does not already exist, a new person is first added to Klaviyo.
     * POST /api/v2/list/{{ LIST_ID }}/members
     *
     * @param string $listId The id of the id.
     * @param array $profiles An array of properties such as names.
     * @return mixed Null if the request fails or an stdclass object if is successful
     */
    private function addMembers($listId, array $profiles)
    {
        foreach ($profiles as $profile) {
            if (!array_key_exists('email', $profile)) {
                throw new Exception('An "email" key was not found');
            }
        }

        $formParams = [
            'form_params' => [
                'api_key' => $this->apiKey,
                'profiles' => $profiles
            ],
        ];

        try {
            $response = $this->client->post("/api/v2/list/{$listId}/members", [
                RequestOptions::JSON => $formParams['form_params'],
                $formParams
            ]);
        } catch(\Throwable $t) {
            return back()->with('error', 'Klaviyo Import Failed. ' . $t->getMessage());
        }

        return $this->decode($response);
    }

    /**
     * Batch Removing People from a List
     * Removes multiple people from the specified list. For each person,
     * if a person with that email address is a member of that list,
     * they are removed.
     * DELETE /api/v2/list/{{ LIST_ID }}/members/batch
     *
     * @param  string $listId The list id.
     * @param  array  $emails The list of user emails to delete.
     * @return mixed Null if the request fails or an stdclass object if is successful.
     */
    private function deleteMembers($listId, array $emails)
    {
        $options = [
            'form_params' => [
                'api_key' => $this->apiKey,
                'emails'   => $emails
            ]
        ];

        try {
            $response = $this->client->delete("/api/v2/list/{$listId}/members", [
                RequestOptions::JSON => $options['form_params'],
                $options
            ]);
        } catch(\Throwable $t) {
            dd($t->getMessage());
        }

        return $this->decode($response);
    }

    /**
     * Sends the response object if 200 status code is retrieved.
     * In case of 400 or 500 status codes returns null.
     *
     * @return object the Klaviyo response.
     */
    private function decode($response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode === 200) {
            return json_decode((string) $response->getBody());
        }

        return null;
    }
}