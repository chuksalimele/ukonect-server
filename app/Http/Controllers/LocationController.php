<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Util\Util;
use App\Util\DBUtil;
use App\Util\Result;
use App\Util\Constants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class LocationController extends Controller {

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

        if ($content == 'update_location') {
            return $this->updateLocation($request);
        } else if ($content == 'users_by_location_address') {
            return $this->getUsersByLocationAddress($request);
        } else if ($content == 'users_within_boundary') {
            return $this->getUsersWithinBoundary($request);
        } else if ($content == 'location_address') {
            return $this->getLocationAddress($request);
        } else if ($content == 'location') {
            return $this->getLocation($request);
        }

    }


    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    public function updateLocation(Request $request) {

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string',
                    'location' => 'required|string', //must be a json array containing the latitude, longitude 
                        //, and location address
        ]);

        if ($validator->fails()) {
            $result = new Result;
            return $result->error(Util::allValidationErrors($validator))->json();
        }


        $location = json_decode($request->location);

        if (!$location) {
            throw new Exception('Location must be a valid json');
        }

        $prop_latitude = 'latitude';
        $prop_longitude = 'longitude';
        $prop_location_address = 'location_address';

        if (!isset($location->$prop_latitude)) {
            throw new Exception("invalid location format - expected property '$prop_latitude' with a valid value");
        }

        if (!isset($location->$prop_longitude)) {
            throw new Exception("invalid location format - expected property '$prop_longitude' with a valid value");
        }

        if (!isset($location->$prop_location_address)) {
            throw new Exception("invalid location format - expected property '$prop_location_address' with a valid value");
        }


        return DBUtil::simpleUpdateUser($request, [
                    'latitude' => $location->$prop_latitude,
                    'longitude' => $location->$prop_longitude,
                    'location_address' => $location->$prop_location_address,
                    'location_time' => now()
        ]);
    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    function getLocationAddress(Request $request) {
        return DBUtil::projectUser($request, 'location_address');
    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    function getLocation(Request $request) {
        return $this->projectUser($request, ['latitude', 'longitude', 'location_address', 'location_time']);
    }

    function getUsersByLocationAddress(Request $request) {

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'location_address' => 'string',
                    'limit' => 'required|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return $result->error(Util::allValidationErrors($validator))->json();
        }


        try {

            $users = User::where('location_address', $request->location_address)
                    ->limit($request->limit)
                    ->get(); // come back		

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

    /*
      calculate the distance between the pivot and the give piont (latitude and longitude)
      of the user and check if it is less than the given radius.
      is true then the user is within the geographical boundary (circle)
      this is the psuedo code used

      Using Haversine formula

      $distance_in_km = 6371 * 2 * asin(sqrt(pow(sin(($lat2 - $lat1) / 2), 2)
      + cos($lat1) * cos($lat2) * pow(sin(($long2 - $long1) / 2), 2)))

     */

    function getUsersWithinBoundary(Request $request) {

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'pivot_latitude' => 'required|numeric', //measure from this point
                    'pivot_longitude' => 'required|numeric', //measure from this point
                    'radius' => 'required|numeric|min:100', //according to geofencing docs radius should be mininum of 100
                    'limit' => 'required|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return $result->error(Util::allValidationErrors($validator))->json();
        }


        try {

            //calculate the distance between the pivot and the give piont (latitude and longitude)
            //of the user and check if it is less than the given radius.
            //is true then the user is within the geographical boundary (circle)
            //this is the psuedo code used
            //
             
             /*
              Using Haversine formula

              $distance_in_km = 6371 * 2 * asin(sqrt(pow(sin(($lat2 - $lat1) / 2), 2)
              + cos($lat1) * cos($lat2) * pow(sin(($long2 - $long1) / 2), 2)))

             */

            //TODO - include check for location country , state and city. so our 
            //location object should carry country state and city of user 
            //for faster query since full scan for this kind of computation could
            //seriously impact on performance

            $users = User::selectRaw("("
                            . " 6371 * 2"
                            . " * ASIN(SQRT(POW(SIN(('latitude' - $request->pivot_latitude) / 2), 2)"
                            . " + COS($request->pivot_latitude) "
                            . " * COS('latitude') "
                            . " * POW(SIN(('longitude' - $request->pivot_longitude) / 2), 2)))"
                            . ") As distance")
                    ->where('latitude', '<>', 0) //must not be zero
                    ->where('longitude', '<>', 0)//must not be zero                    
                    ->where('distance', '<=', $request->radius)//our main condition to know is the piont is within range
                    ->orderBy('distance')
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
