@extends('layouts.default')

@section('content')

<div class="ui stackable grid" style="height: 80%; padding: 20px;">
    <div class="three wide column"></div>
    <div class="ten wide column">
        <h1 class="ui block attached header">Recover Password</h1>
        <div class="ui attached segment">

            <form  method="POST" action="{{ route('password.email') }}" class="ui form">
                @csrf
                @if (session('status'))
                <div class="ui info message" id="reset_password_msg">
                    <i class="close icon" onclick="document.getElementById('reset_password_msg').style.display = 'none';"></i>
                    <p>{{ session('status') }}.</p>
                </div>
                @endif
                <div class="field">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" required placeholder="Enter your email address">

                    @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror

                </div>

                <button  class="large ui blue button" style="min-width: 200px;" type="submit">Send Password Recovery Link</div></button>

            </form>            
        </div>
    </div>
    <div class="three wide column"></div>
</div>

@endsection
