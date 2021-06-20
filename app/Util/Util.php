<?php

namespace App\Util;

use Carbon\Carbon;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class Util {
    
	static function millitime(){
		return round(microtime(true) * 1000);
	}
	
    /*
      Get the distance in meters between two points (latitude/longitude) in km using Haversine formula
     */

    static function distanceOnEarthInInMeters($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo) {
        return distanceOnEarthInKm($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo) / 1000;
    }

    /*
      Get the distance in kilometers between two points (latitude/longitude) in km using Haversine formula
     */

    static function distanceOnEarthInKilometers($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo) {
        $long1 = deg2rad($longitudeFrom);
        $long2 = deg2rad($longitudeTo);
        $lat1 = deg2rad($latitudeFrom);
        $lat2 = deg2rad($latitudeTo);

        //Haversine Formula 
        $dlong = $long2 - $long1;
        $dlati = $lat2 - $lat1;

        $val = pow(sin($dlati / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($dlong / 2), 2);

        $res = 2 * asin(sqrt($val));

        $radius = 3958.756;

        return ($res * $radius);
    }

    
    static function isInCircleOnEarth($latitudeFrom, $longitudeFrom, $radius_in_meters, $latitudeTo, $longitudeTo) {
        $distance = distanceOnEarthInInMeters($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo);
        return $distance <= $radius_in_meters;
    }

    static function allValidationErrors($validator) {
        $msg = '';
        $errors = $validator->errors();
        foreach ($errors->all() as $message) {
            $msg .= $message . '\n';
        }
        return trim($msg, '\n'); //remove the trailing new line
    }

    static function upload(Request $request, string $request_input, string $folder = null, string $filename = null) {

        // Check if a profile image has been uploaded
        if (!$request->has($request_input)) {
            return null;
        }

        // Get image file
        $image = $request->file($request_input);

        if (!$image) {
            return null;
        }

        // Make a image name based on user id whiche is unique for all users
        $name = $filename == null ? static::unique() : $filename;
        // Define folder path

        if (!$folder) {
            $folder = Constants::DEFAULT_UPLOAD_DIR;
        }

		if (!file_exists(public_path($folder))) {
			mkdir(public_path($folder), 0777, true);
		}

        // Make a file path where image will be stored [ folder path + file name + file extension]
        $filePath = $folder . $name . '.' . $image->getClientOriginalExtension();

        // Upload image
        $image->move(public_path($folder), $filePath);

        return $filePath;
    }

    static function uploadBase64(string $base64, string $folder=null, string $filename=null, string $file_extention='') {

        // Make a image name based on user id whiche is unique for all users
        $name = $filename == null ? static::unique() : $filename;
        // Define folder path

        if (!$folder) {
            $folder = Constants::DEFAULT_UPLOAD_DIR;
        }

		if (!file_exists(public_path().$folder)) {
			mkdir(public_path().$folder, 0777, true);
		}

        // Make a file path where image will be stored [ folder path + file name + file extension]
        $filePath = $folder . $name . ($file_extention? ('.' . $file_extention): '');
		
		file_put_contents(public_path().$filePath, base64_decode($base64), LOCK_EX );

        return $filePath;
    }

    static function unique() {

        return md5(uniqid() . '' . random_bytes(16));
    }

    static function isEmpty($value) {

        if (is_string($value) && strlen($value) == 0) {
            return true;
        } else if (is_array($value) && count($value) == 0) {
            return true;
        }
        return false;
    }

    static function isEmptyOrNull($value) {
        return Util::isEmpty($value) || $value === null;
    }

    static function uniquelyMerge($c1, $c2) {
        $uniques = [];
        $merged = array_merge($c1, $c2);
        $count = count($merged);
        $index = -1;

        for ($i = 0; $i < $count; $i++) {
            for ($k = $i + 1; $k < $count; $k++) {
                if ($merged[$i] == $merged[$k]) {
                    continue 2; // break outer
                }
            }
            $uniques[++$index] = $merged[$i];
        }

        return $uniques;
    }

    static function timeAgo($date) {
        $dt = Carbon::parse($date);
        return $dt->diffForHumans();
    }

    static function monthDayYear($date) {
        return Carbon::parse($date)->format('F d, Y');
    }

    static function monthDay($date) {
        return Carbon::parse($date)->format('F d');
    }

    static function appDayDate($date) {
        $dt = Carbon::parse($date);
        $days_diff = $dt->diffInDays(Carbon::now());
        $str = '';
        if ($dt->isToday()) {
            $str = 'Today, ' . $dt->format('H:i');
        } else if ($dt->isYesterday()) {
            $str = 'Yesterday, ' . $dt->format('H:i');
        } else if ($days_diff <= 7) {
            $str = $dt->shortDayName . ', ' . static::monthDay($date);
        } else if ($dt->isSameYear($date)) {
            $str = static::monthDay($date);
        } else {
            $str = static::monthDayYear($date);
        }
        return $str;
    }

}
