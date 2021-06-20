<?php

namespace App\Util;

use Illuminate\Http\Request;
use App\User;
use App\Util\Util;
use App\Util\Result;
use App\Util\Constants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class DBUtil {
    
    static function projectUser($request, $columns) {

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $result->error(Util::allValidationErrors($validator))->json();
        }

        try {

            $user = User::find($request->user_id);

            if (!$user) {
                return $result->error(Constants::USER_NOT_FOUND)->json();
            }

            $data = ['user_id' => $user->user_id];
            if (!is_array($columns)) {
                $arr = array();
                $arr[0] = $columns;
                $columns = $arr;
            }

            foreach ($columns as $col) {
                $data[$col] = $user->$col;
            }

            return $result->dataJson($data);
        } catch (QueryException $e) {
            return $result->debugJson($e);
        } catch (Exception $e) {
            return $result->debugJson($e);
        }
    }

    /**
     * Updates the user model with the specified attributes 
     * 
     * @param type $request
     * @param type $props
     * @param type $send_user if true, the updated user model will be sent. default is false
     * @param type $update_empty_fields if true then attributes with empty
     *               value will update the corresponding table field.
     *                The default behaviour is false meaning empty values are
     *                ignored (do not alter the corresponding table field)  
     * @return type
     * @throws Exception
     */
    static function simpleUpdateUser($request, $props, $send_user = false, $update_empty_fields = false) {

        $result = new Result;

        try {

            $user = User::find($request->user_id);

            if (!$user) {
                return $result->error(Constants::USER_NOT_FOUND)->json();
            }

            if (!is_array($props)) {
                throw new Exception('Illegal argument passed to method - must be an associative array');
            }

            foreach ($props as $key => $value) {
                if (is_int($key)) {
                    throw new Exception('Illegal argument passed to method - must be an associative array but found sequential array key [integer type] ');
                }

                if ($update_empty_fields || !Util::isEmptyOrNull($value)) {
                    $user->$key = $value;
                }
            }

            $user->save();
            
            if ($send_user) {
                return $result->dataJson($user);
            } else {
                return $result->successJson();
            }
        } catch (QueryException $e) {
            return $result->debugJson($e);
        } catch (Exception $e) {
            return $result->debugJson($e);
        }
    }

}
