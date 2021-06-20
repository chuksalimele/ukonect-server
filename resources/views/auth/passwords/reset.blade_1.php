@extends('layouts.default')

@section('content')

<div class="ui stackable grid" style="height: 80%; padding: 20px;">
    <div class="three wide column"></div>
    <div class="ten wide column">
        <h1 class="ui block attached header">Reset Your Password</h1>
        <div class="ui attached segment">

            <form  method="POST" action="{{ route('password.update') }}" class="ui form">
                @csrf
                
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="field">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" required placeholder="Enter your email address">

                    @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror

                </div>

                <div class="field">
                    <label>Password</label>
                    <input type="password"  name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password" placeholder="Password">

                    @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="field">
                    <label>Confirm password</label>
                    <input  type="password" name="password_confirmation" class="form-control" required autocomplete="new-password" placeholder="Confirm password">
                </div>

                <button  class="large ui blue button" style="min-width: 200px;" type="submit">Reset <div class="ui active tiny inline loader"></div></button>

            </form>            
        </div>
    </div>
    <div class="three wide column"></div>
</div>

@endsection
