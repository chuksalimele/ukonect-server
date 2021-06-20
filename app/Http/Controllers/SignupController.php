<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Util\Util;
use App\Util\Result;
use App\Util\Constants;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class SignupController extends Controller {

    
    /**
     *
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function handle(Request $request, $content = null) {

        if ($content == 'verify_phone_number') {
            return $this->verifyPhoneNumber($request);
        } else if ($content == 'confirm_phone_number') {
            return $this->confirmPhoneNumber($request);
        } else if ($content == 'name') {
            return $this->name($request);
        } else if ($content == 'profile_photo') {
            return $this->profilePhoto($request);
        }
    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    function verifyPhoneNumber(Request $request) {

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string', //which is essentially the verified E164 mobile phone number
                    'mobile_phone_no' => 'required',
                    'dialling_code' => 'required|integer',
                    'country_code' => 'required|string|min:2|max:2', //length must be 2
                    'country' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $result->error(Util::allValidationErrors($validator))->json();
        }

        try {

            $user = User::find($request->user_id);

            if (!$user) {
                $user = new User;
                $user->user_id = $request->user_id;
            }

            $user->dialling_code = $request->dialling_code;
            $user->mobile_phone_no = $request->mobile_phone_no;
            $user->country_code = $request->country_code;
            $user->country = $request->country;

            $user->save();

            return $result->dataJson($user);
            
        }  catch (QueryException $e) {
            return $result->debugJson($e);
        }
    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    function confirmPhoneNumber(Request $request) {

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string', //which is essentially the verified E164 mobile phone number -must be included in every signup step
                    'confirm_verification_code'=>'required|numeric'
        ]);

        if ($validator->fails()) {
            return $result->error(Util::allValidationErrors($validator))->json();
        }

        try {

            $user = User::find($request->user_id);

            if (!$user) {
                return $result->error(Constants::USER_NOT_FOUND)->json();
            }

            $user->phone_number_verified_at = now();
            $user->save();

            return $result->dataJson($user);
            
        }  catch (QueryException $e) {
            return $result->debugJson($e);
        }
    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    function name(Request $request) {

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string', //which is essentially the verified E164 mobile phone number -must be included in every signup step
                    'first_name' => 'string|max:200',
                    'last_name' => 'string|max:200',
        ]);

        if ($validator->fails()) {
            return $result->error(Util::allValidationErrors($validator))->json();
        }

        try {

            $user = User::find($request->user_id);

            if (!$user) {
                return $result->error(Constants::USER_NOT_FOUND)->json();
            }

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->save();

            return $result->dataJson($user);
            
        }  catch (QueryException $e) {
            return $result->debugJson($e);
        }
    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    function profilePhoto(Request $request) {

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string', //which is essentially the verified E164 mobile phone number -must be included in every signup step
                    'profile_photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048', //not required anyways - image converted to base64 string
                    'status_message' => 'string', //not required anyways 
        ]);

        if ($validator->fails()) {
            return $result->error(Util::allValidationErrors($validator))->json();
        }

        try {

            $user = User::find($request->user_id);

            if (!$user) {
                return $result->error(Constants::USER_NOT_FOUND)->json();
            }
            
            $filename = $request->user_id;

            $photo_url = Util::upload($request, 'profile_photo', Constants::DEFAULT_UPLOAD_PROFILE_DIR, $filename);
                        
            if (!Util::isEmptyOrNull($photo_url)) {
                $user->profile_photo_url = $photo_url;
            }
            
            if ($request->status_message) {
                $user->status_message = $request->status_message;
            }

            $user->save();

            return $result->dataJson($user);
            
        }  catch (QueryException $e) {
            return $result->debugJson($e);
        }
    }

}
