
@extends('layouts.default')

@section('top')
{{--Display Nothing--}}    
@endsection

@section('content')

<div class="ui stackable grid" style="min-height: 80%; padding: 20px;">
    <div class="three wide column"></div>
    <div class="ten wide column">
        <h1 class="ui block attached header">Email verification</h1>

        @if (session('resent'))
        <div class="ui info message" id="resend_verify_email_msg">
            <i class="close icon" onclick="document.getElementById('resend_verify_email_msg').style.display='none';"></i>
            <p>We have resent you another verification link to your email address.</p>
        </div>
        @endif

        <div class="ui attached segment">
            <p>Please check your email for a verification link we just sent to you.</p>
            <p>If you did not receive the email, please click<a href="{{ route('verification.resend') }}"> here</a> to request another.</p>
        </div>
    </div>
    <div class="three wide column"></div>
</div>


@endsection
