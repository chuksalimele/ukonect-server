<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::post('chat/{content?}', 'ChatController@handle');
Route::post('support_chat/{content?}', 'SupportChatController@handle');
Route::post('/signup/{content?}', 'SignupController@handle');
Route::post('/contacts/{content?}', 'ContactsController@handle');
Route::post('/location/{content?}', 'LocationController@handle');
Route::post('/profile/{content?}', 'ProfileController@handle');



Route::get('/mongo', 'TestController@testMongo');
Route::get('/test', 'TestController@test');
Route::get('/create_test_users', 'TestController@createTestUsers');


