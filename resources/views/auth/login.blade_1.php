
@extends('layouts.default')

@section('top')
{{--Display Nothing--}}    
@endsection

@section('content')

<div class="ui stackable grid" style="min-height: 80%">
    <div class="five wide column"></div>
    <div class="six wide column">
        <div style="padding: 20px;">
            <h4>Don't have an account yet?<a href="registration"></h4>
            <a href="register"><button class="large ui blue button">Click To Register</button></a>
            <h1 style="color: #444">Login</h1>

            <form  method="POST" action="login" class="ui form" onsubmit="document.getElementById('login-form-submit-btn').className = 'large ui loading blue button';">
                @csrf

                <div class="field">
                    <!--<label>First Name</label>-->
                    <input type="text" name="staff_id" class="big ui input @error('staff_id') is-invalid @enderror" required placeholder="Staff ID">

                    @error('staff_id')
                    <div class="ui pointing red basic label">
                        {{ $message }}
                    </div>
                    @enderror

                </div>
                <div class="field">
                    <!---<label>Last Name</label>-->
                    <input type="password" name="password" class="big ui input @error('password') is-invalid @enderror" required placeholder="Password">

                    @error('password')
                    <div class="ui pointing red basic label">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
                <div class="field">
                    <div class="ui checkbox">
                        <input type="checkbox" id="remember" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label for="remember">Remember me</label>
                    </div>
                </div>
                <button id="login-form-submit-btn" class="large ui blue button" style="width: 200px; margin: 2px;" type="submit">Login</button>
               
                <!--@if (Route::has('password.request'))
                <button class="large basic ui button" style="width: 200px; margin: 2px;"><a style="color: inherit" href="{{ route('password.request') }}">Forgot password?</a></button>
                @endif-->
            </form>
        </div>
    </div>
    <div class="five wide column"></div>
</div>

@endsection


