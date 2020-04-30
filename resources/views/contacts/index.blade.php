@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h2>
                            My Contacts
                        </h2>
                        <div class="ml-auto">
                            <a href="{{ route('contacts.create') }}" class="btn btn-outline-secondary">Create Contact</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @include ('layouts._messages')

                    @foreach ($contacts as $contact)
                        <div class="media">
                            <div class="media-body">
                                <div class="d-flex align-items-center">
                                    <h3 class="mt-0">{{ $contact->name }}: <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></h3>
                                    <div class="ml-auto">
                                        <a href="{{ route('contacts.edit', $contact->id) }}" class="btn btn-small btn-outline-info">Edit</a>
                                        <form method="post" action="{{ route('contacts.destroy', $contact->id) }}" class="d-inline-block">
                                            @method('delete')
                                            @csrf
                                            <button type="submit" class="btn btn-small btn-outline-danger" onclick="return confirm('Are you sure?')">X</button>
                                        </form>
                                    </div>
                                </div>
                                <p class="lead">
                                    Phone: <small class="text-muted">{{ $contact->phone_number }}</small>
                                </p>
                                <div class="mx-auto">
                                    <form method="post" action="{{ route('contacts.track', $contact->email) }}">
                                        @csrf
                                       <button type="submit" class="btn btn-small btn-outline-primary">Track Me</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <hr>
                    @endforeach
                    <div class="mx-auto">
                        <form action="{{ route('file.upload.post') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-10">
                                    <input type="file" name="file" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-outline-success">Upload</button>
                                </div>
                            </div>
                            <small class="text-muted">* Select an CSV file to import</small>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
