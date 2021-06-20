
@extends('layouts.default')

@section('top')
{{--Display Nothing--}}    
@endsection

@section('content')

<h1 class="ui block header">Registration</h1>

<div class="ui stackable grid">
    <div class="five wide column"></div>
    <div class="six wide column">

        <form method="post" action="register" class="ui form" style="margin: 0 auto; ; padding: 20px;"  onsubmit="document.getElementById('register-form-submit-btn').className = 'large ui loading blue button';">
            @csrf
            <div class="field">
                <label>Staff ID</label>
                <input type="text" name="staff_id" class="big ui input @error('email') is-invalid @enderror" placeholder="Staff ID">

                @error('staff_id')
                <div class="ui pointing red basic label">
                    {{ $message }}
                </div>
                @enderror
            </div>
            <div class="field">
                <label>Department</label>

                <select name="department"  data-el="register_dept" data-url="{{ url('/') }}/account/unit/units_in_dept"  class="big ui @error('department') red @enderror">                        
                    <?php
                    $departments = App\Department::all();
                    echo '<option value="">Select Department</option>';
                    foreach ($departments as $t) {
                        echo '<option value="' . $t->dept . '">' . $t->dept . '</option>';
                    }
                    ?>
                </select> 
                @error('department')
                <div class="ui pointing red basic label">
                    {{ $message }}
                </div>
                @enderror
            </div>
            <div class="field">
                <label>Unit</label>

                <select name="unit"   data-el="register_unit"  class="big ui @error('unit') red @enderror" data-placeholder="Select Unit">                        
                    echo '<option value="">Select Unit</option>';
                </select> 
                @error('unit')
                <div class="ui pointing red basic label">
                    {{ $message }}
                </div>
                @enderror
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" class="big ui input @error('email') is-invalid @enderror" placeholder="Email">

                @error('email')
                <div class="ui pointing red basic label">
                    {{ $message }}
                </div>
                @enderror
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password"  name="password" class="big ui input @error('password') is-invalid @enderror" required autocomplete="new-password" placeholder="Password">

                @error('password')
                <div class="ui pointing red basic label">
                    {{ $message }}
                </div>
                @enderror
            </div>
            <div class="field">
                <label>Confirm password</label>
                <input  type="password" name="password_confirmation" class="big ui input" required autocomplete="new-password" placeholder="Confirm password">
            </div>
            <div class="field">
                <label>First name</label>
                <input type="text" name="first_name" class="big ui input @error('first_name') is-invalid @enderror" placeholder="First name">

                @error('first_name')
                <div class="ui pointing red basic label">
                    {{ $message }}
                </div>
                @enderror
            </div>
            <div class="field">
                <label>Last name</label>
                <input type="text" name="last_name" class="big ui input @error('last_name') is-invalid @enderror" placeholder="Last name">

                @error('last_name')
                <div class="ui pointing red basic label">
                    {{ $message }}
                </div>
                @enderror
            </div>
            <div class="field">
                <label>Phone number</label>
                <input type="text" name="phone_no" class="big ui input @error('phone_no') is-invalid @enderror" placeholder="Phone number">

                @error('phone_no')
                <div class="ui pointing red basic label">
                    {{ $message }}
                </div>
                @enderror
            </div>
            <button  id= "register-form-submit-btn" class="large ui blue button" type="submit" style="min-width: 200px;">I'm Done</button>
        </form>
    </div>
    <div class="five wide column"></div>
</div>

@endsection


