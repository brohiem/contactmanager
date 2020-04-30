@extends('layouts.app')

@section('content')

<div class="content">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-user">
                <div class="card-header">
                    <h5 class="card-title">Add Contact</h5>
                </div>
                <div class="card-body">
                    @include ('layouts._messages')

                    <form action="{{ route('contacts.store') }}" method="post" autocomplete="off">
                        {{csrf_field()}}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label> First Name </label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" placeholder="Name" name="name">
                                    @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label> Email </label>
                                    <input type="text" class="form-control @error('email') is-invalid @enderror" placeholder="Email" name="email">
                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label> Phone Number </label>
                                    <input type="text" class="form-control @error('phone_number') is-invalid @enderror" placeholder="Phone Number" name="phone_number">
                                    @error('phone_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="update ml-3 mr-auto">
                                <button type="submit" class="btn btn-primary btn-round">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
