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

class ContactsController extends Controller {

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


        if ($content == 'backup_contacts') {
            return $this->backupContacts($request);
        } else if ($content == 'restore_contacts') {
            return $this->restoreContacts($request);
        } else if ($content == 'contact_users') {
            return $this->getContactUsers($request);
        }

    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    public function backupContacts(Request $request) {


        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string',
                    'contacts' => 'required|string', //must be a json array
        ]);

        if ($validator->fails()) {
            return $result->error(Util::allValidationErrors($validator))->json();
        }

        try {

            $user = User::find($request->user_id);

            if (!$user) {
                return $result->error(Constants::USER_NOT_FOUND)->json();
            }

            $contacts = json_decode($request->contacts);

            if (!$contacts) {
                throw new Exception('Contacts must be a valid json array');
            }

            $prop_name = 'name';
            $prop_phone_no = 'phone_no';

            if (!is_array($contacts)) {
                throw new Exception("invalid contact backup format - must be json array of contacts with propertes '$prop_name', '$prop_phone_no' on each element");
            }

            foreach ($contacts as $contact) {
                if (!isset($contact->$prop_name)) {
                    throw new Exception("invalid contact backup format - expected property '$prop_name' with a valid value on each element");
                }
                if (!isset($contact->$prop_phone_no)) {
                    throw new Exception("invalid contact backup format - expected property '$prop_phone_no' with a valid value on each element");
                }
            }

            $stored_contacts = $user->contacts ? json_decode($user->contacts) : array();

            if (!$stored_contacts) {
                $stored_contacts = array();
            }

            //merge and remove duplicates
            $new_contacts = Util::uniquelyMerge($contacts, $stored_contacts);

            $user->contacts = json_encode($new_contacts);
            $user->save();

            return $result->success()->json();
        } catch (QueryException $e) {
            return $result->debugJson($e);
        } catch (Exception $e) {
            return $result->debugJson($e);
        }
    }

    /**
     * 
     *
     * @param  Request  $request
     * @return Response
     */
    public function restoreContacts(Request $request) {
        return DBUtil::projectUser($request, 'contacts');
    }

    public function getContactUsers(Request $request) {

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'contacts' => 'required|string', // json array of E164 phone numbers		
        ]);

        if ($validator->fails()) {
            return $result->error(Util::allValidationErrors($validator))->json();
        }


        try {

            $contact_array = json_decode($request->contacts);

            if (!$contact_array) {
                throw new Exception('Contacts must be a valid json array');
            }

            foreach ($contact_array as $contact_number) {
                if (!is_string($contact_number) && !is_finite($contact_number)) {
                    throw new Exception('Invalid contacts - excepted array of phone numbers');
                }
            }

            $users = User::whereIn('user_id', $contact_array)->get();

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
