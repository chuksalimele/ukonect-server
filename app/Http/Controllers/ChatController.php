<?php

namespace App\Http\Controllers;

use App\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Util\Util;
use App\Util\Result;
use App\Util\Constants;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Events\MessageSent;

class ChatController extends Controller {

    public function __construct() {
        // $this->middleware('auth'); //may not be needed in the Android app
    }

    /**
     *
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function handle(Request $request, $content = null) {

        if ($content == 'send') {
            return $this->sendMessage($request);
        } else if ($content == 'fetch') {
            return $this->fetchMessages($request);
        }

    }

    /**
     * Show chats
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view('chat');
    }

    /**
     * Fetch all messages
     *
     * @return Message
     */
    public function fetchMessages(Request $request) {
        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string', //which is essentially the verified E164 mobile phone number -must be included in every signup step
                    'chatmate_id' => 'required|string',
        ]);

        try {

            if ($validator->fails()) {
                return $result->error(Util::allValidationErrors($validator))->json();
            }

            $messages = Message::where([
                        ['user_id' , $request->user_id],
                        ['chatmate_id' , $request->chatmate_id]
                    ])
                    ->orWhere([
                        ['user_id' , $request->chatmate_id],
                        ['chatmate_id' , $request->user_id]
                    ])->get();

            if(!$messages){
                $result->dataJson([]);
            }
            
            return $result->dataJson($messages->toArray());
        } catch (QueryException $e) {
            return $result->debugJson($e);
        } catch (Exception $e) {
            return $result->debugJson($e);
        }
    }

    /**
     * Persist message to database
     *
     * @param  Request $request
     * @return Response
     */
    public function sendMessage(Request $request) {

        $result = new Result;

        $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string', //which is essentially the verified E164 mobile phone number -must be included in every signup step
                    'message' => 'required|string'//
        ]);

        try {

            if ($validator->fails()) {
                return $result->error(Util::allValidationErrors($validator))->json();
            }

            $messageObj = json_decode($request->message);

            if (!$messageObj) {
                return $result->error("Message must be a valid json")->json();
            }

            if (!isset($messageObj->from_user_id) || Util::isEmptyOrNull($messageObj->from_user_id)) {
                return $result->error("Message must contain the 'from_user_id'")->json();
            }

            if (!isset($messageObj->to_user_id) || Util::isEmptyOrNull($messageObj->to_user_id)) {
                return $result->error("Message must contain the 'to_user_id'")->json();
            }

            if (!isset($messageObj->content) || Util::isEmptyOrNull($messageObj->content)) {
                return $result->error("Message must contain the 'content'")->json();
            }

            if (!isset($messageObj->content_type) || Util::isEmptyOrNull($messageObj->content_type)) {
                return $result->error("Message must contain the 'content_type'")->json();
            }

            if ($request->user_id != $messageObj->from_user_id) {
                return $result->error("Message must be from the current user - Message origin user id mismatch current user id")->json();
            }

            if (Util::isEmptyOrNull($messageObj->to_user_id)) {
                return $result->error("Message must contain a valid chatmate id")->json();
            }

            $user = User::find($request->user_id);

            if (!$user) {
                return $result->error(Constants::USER_NOT_FOUND)->json();
            }

            $messageObj->time = now(); //set the message time on the message object
            
            $message = new Message;
            $message->user_id = $request->user_id;
            $message->chatmate_id = $messageObj->to_user_id;            
            $message->message = json_encode($messageObj);
            $message->save();
            
            broadcast(new MessageSent($user, $messageObj))->toOthers();

            return $result->dataJson(Constants::MESSAGE_SENT);
        } catch (QueryException $e) {
            return $result->debugJson($e);
        } catch (Exception $e) {
            return $result->debugJson($e);
        }
    }

}
