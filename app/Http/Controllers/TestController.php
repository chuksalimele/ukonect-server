<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Util\Util;
use App\Util\Result;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\MongoTest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Date;
use Jenssegers\Mongodb\Collection;
use Jenssegers\Mongodb\Connection;
use Jenssegers\Mongodb\Eloquent\Model;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;

class TestController extends Controller {

    
    public function testMongo(Request $request) {
        
        
        $test = new MongoTest;
        $conn = $test->getConnection();
        if($conn){
            return 'connected';
        }else{
            return 'not connected';
        }
        
    }
    
    public function createTestUsers(Request $request) {

        $user_id = 2347032710628;
        $lat = 1074.07363;
        for ($i = 0; $i < 10; $i++) {
            $user = new User;
            $user->user_id = $user_id + $i;
            $user->user_type = 'user_type' . $i;
            $user->phone_number_verified_at = now();
            $user->password = 'password' . $i;
            $user->api_token = 'api_token' . $i;
            $user->country_code = 'NG';
            $user->dialling_code = 234;
            $user->country = 'Nigeria';
            $user->first_name = 'first_name' . $i;
            $user->last_name = 'last_name' . $i;
            $user->mobile_phone_no = $user_id + $i;
            $user->work_phone_no = $user_id + $i + 10;
            $user->profile_photo_url = 'profile_photo_url' . $i;
            $user->personal_email = 'personal_email' . $i . '@gmail.com';
            $user->work_email = 'work_email' . $i . '@gmail.com';
            $user->website = 'www.website' . $i.'.com';
            $user->home_address = 'home_address' . $i;
            $user->office_address = 'office_address' . $i;
            $user->status_message = 'status_message' . $i;
            $user->location_address = 'location_address' . $i;
            $user->latitude = $lat + $i;
            $user->longitude = $lat + $i + 1000;
            $user->contacts = 'contacts' . $i;
            $user->dob = now();
            $user->last_seen = now();

            $user->save();
        }

        return 'Created test users';
    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    public function test(Request $request) {

        $arr1 = [['a' => '1'], ['b' => '2'], ['c' => '3']];

        $arr2 = [['a' => '1', 'a0' => '11'], ['b' => '2'], ['c' => '4']];

        //$arr3 = array_merge($arr1, $arr2);
        //$arr1 = [1,2,3];
        //$arr2 = [1,2,4];
        $n = null;
        echo Util::isEmptyOrNull($n);

        $result = new Result;
        $d = 'data';
        $result->$d = 'this is data ';
        echo json_encode($result);

        $arr3 = Util::uniquelyMerge($arr1, $arr2);
        $value = 1888;
        $d = "this is value $value ";
        echo $d;
        echo json_encode($arr3);

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string', //which is essentially the verified E164 mobile phone number -must be included in every signup step
        ]);

        if ($validator->fails()) {
            return $result->errorJson('invalid input');
        }

        try {
            $user = User::find($request->user_id);
            if (!$user) {
                return $result->errorJson('user not found');
            }
            $user->phone_number_verified_at = now();
            $user->save();
            return $result->success()->json();
        } catch (QueryException $e) {
            return $result->debugJson($e);
        }
    }

}
