<?php

namespace App\Util;

define('UPLOAD_DIR', '/uploads/');

class Constants {

    const ACTION_SUCCESSFUL = 'Action successful!';
    const NOTHING_CHANGED = 'Nothing changed!';
    const SOMETHING_WENT_WRONG = 'Something went wrong!';
    const USER_NOT_FOUND = 'User not found!';
    const NOT_FOUND = 'Not found!';
    const MESSAGE_SENT = 'Message sent';
    const INVALID_INPUT = 'Invalid input!';
    const DEFAULT_UPLOAD_DIR = UPLOAD_DIR;
    const DEFAULT_UPLOAD_USER_PROFILE_DIR = UPLOAD_DIR . 'users/profiles/';
	const DEFAULT_UPLOAD_GROUP_PROFILE_DIR = UPLOAD_DIR . 'groups/profiles/';
	const DEFAULT_UPLOAD_MESSAGE_MEDIA_DIR = UPLOAD_DIR . 'message/media/';
    const DEFAULT_REALM = 'realm1';

    const MSG_STATUS_SENT = 2; // sent to the server but not to the recipient
    const MSG_STATUS_SEEN = 3; //recipient has seen the message sent to him
    const MSG_STATUS_READ = 4; //recipient has read the message sent to him

}
