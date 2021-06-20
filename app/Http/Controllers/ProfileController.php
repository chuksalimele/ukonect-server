<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Order;
use App\Util\Util;
use App\Util\DBUtil;
use App\Util\Result;
use App\Util\Constants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProfileController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        //$this->middleware(['auth', 'verified']);
    }
    
    /**
     *
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function handle(Request $request, $content = null) {

        if ($content == 'update_last_seen') {
            return $this->updateLastSeen($request);
        } else if ($content == 'update_profile') {
            return $this->updateProfile($request);
        } else if ($content == 'search_users') {
            return $this->searchUsers($request);
        } else if ($content == 'last_seen') {
            return $this->getLastSeen($request);
        }

    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    function updateLastSeen(Request $request) {

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            $result = new Result;
            return $result->error(Util::allValidationErrors($validator))->json();
        }

        return DBUtil::simpleUpdateUser($request, ['last_seen' => now()]);
    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    function getLastSeen(Request $request) {
        return DBUtil::projectUser($request, 'last_seen');
    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    function updateProfile(Request $request) {
        $result = new Result;


        try {

            $validator = Validator::make($request->all(), [
                        'profile_detail' => 'required|string',
                        'profile_photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $result->error(Util::allValidationErrors($validator))->json();
            }

            $profile_detail = json_decode($request->profile_detail);

            if (!$profile_detail) {
                throw new Exception('profile detail must be a valid json');
            }

            if (is_array($profile_detail)) {
                throw new Exception('profile detail must be a valid json object - but found array');
            }

            $validator = Validator::make($profile_detail, [
                        'user_id' => 'required|string',
                        'first_name' => 'max:200',
                        'last_name' => 'max:200',
                        'work_phone_no' => 'max:200',
                        'personal_email' => 'max:200',
                        'work_email' => 'max:200',
                        'website' => 'max:200',
                        'home_address' => 'max:200',
                        'office_address' => 'max:200',
                        'status_message' => 'max:1024',
                        'dob' => 'max:200'
            ]);

            if ($validator->fails()) {
                return $result->error(Util::allValidationErrors($validator))->json();
            }


            $filename = $profile_detail->user_id;

            $photo_url = Util::upload($request, 'profile_photo', Constants::DEFAULT_UPLOAD_PROFILE_DIR, $filename);

            return DBUtil::simpleUpdateUser($request, [
                        'profile_photo_url' => $photo_url,
                        'first_name' => $profile_detail->first_name,
                        'last_name' => $profile_detail->last_name,
                        'work_phone_no' => $profile_detail->work_phone_no,
                        'personal_email' => $profile_detail->personal_email,
                        'work_email' => $profile_detail->work_email,
                        'website' => $profile_detail->website,
                        'home_address' => $profile_detail->home_address,
                        'office_address' => $profile_detail->office_address,
                        'status_message' => $profile_detail->status_message,
                        'dob' => $profile_detail->dob
                            ], true); //true -> send the user object 
        } catch (Exception $e) {
            $result = new Result;
            return $result->debugJson($e);
        }
    }

    /**
     *  WHERE CustomerName LIKE 'a%'	Finds any values that start with "a"
     *  WHERE CustomerName LIKE '%a'	Finds any values that end with "a"
     *  WHERE CustomerName LIKE '%or%'	Finds any values that have "or" in any position
     *  WHERE CustomerName LIKE '_r%'	Finds any values that have "r" in the second position
     *  WHERE CustomerName LIKE 'a_%'	Finds any values that start with "a" and are at least 2 characters in length
     *  WHERE CustomerName LIKE 'a__%'	Finds any values that start with "a" and are at least 3 characters in length
     *  WHERE ContactName LIKE 'a%o'	Finds any values that start with "a" and ends with "o" 
     *
     * @param  Request  $request
     * @return Response
     */
    function searchUsers(Request $request) {

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'search_string' => 'required|string|min:1',
                    'offset' => 'required|integer|min:0',
                    'limit' => 'required|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return $result->error(Util::allValidationErrors($validator))->json();
        }

        try {
            $strStartsWith = '%' . $request->search_string . '%'; // find a row that starts with the search string
            $strContians = '%' . $request->search_string . '%'; // find a row that contain the search string

            $users = User::where('first_name', 'LIKE', $strStartsWith)
                    ->orWhere('first_name', 'LIKE', $strContians)
                    ->orWhere('last_name', 'LIKE', $strStartsWith)
                    ->orWhere('last_name', 'LIKE', $strContians)
                    ->orWhere('user_id', 'LIKE', $strStartsWith)
                    ->orWhere('user_id', 'LIKE', $strContians)
                    ->orWhere('mobile_phone_no', 'LIKE', $strStartsWith)
                    ->orWhere('mobile_phone_no', 'LIKE', $strContians)
                    ->orWhere('work_phone_no', 'LIKE', $strStartsWith)
                    ->orWhere('work_phone_no', 'LIKE', $strContians)
                    ->offset($request->offset)
                    ->limit($request->limit)
                    ->get();

            if (Util::isEmptyOrNull($users)) {
                return $result->dataJson([]);
            }

            return $result->dataJson($users->toArray());
        } catch (QueryException $e) {
            return $result->debugJson($e);
        } catch (Exception $e) {
            return $result->debugJson($e);
        }
    }

}
