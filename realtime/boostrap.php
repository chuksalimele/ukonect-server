<?php
/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/
require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

require_once __DIR__.'/../bootstrap/app.php';

require  "../app/Util/Util.php";
require  "../app/Util/Constants.php";
require  "../realtime/microservices/Channel.php";
require  "../realtime/client/CollectionInit.php";
require  "../realtime/client/Validator.php";
require  "../realtime/client/CallResult.php";
require  "../realtime/client/DBCommonActions.php";
require  "../realtime/client/AbstractManager.php";
require  "../realtime/client/AuthManager.php";
require  "../realtime/client/UserManager.php";
require  "../realtime/client/GroupManager.php";
require  "../realtime/client/MessageManager.php";
require  "../realtime/transport/ListenAddress.php";
require  "../realtime/transport/ScaleOutHandler.php";
require  "../realtime/transport/ScaleOutTransportProvider.php";
