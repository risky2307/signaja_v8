<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(['namespace' => 'API'], function () {

    Route::match(['get', 'post'], 'login', 'AuthsController@login');
    // Route::match(['get', 'post'], 'embedtoken', 'APIController@getEmbedUrl');
    // Route::match(['get', 'post'], 'webhooks','APIController@webhooks');
    // Route::get('sendmail','MailController@sendMail');
    // Route::get('gettoken','APIController@getToken');


    // Route::group(['middleware' => ['authJWT']], function(){

    // });

    Route::apiResource('documents','DocumentController');
    Route::apiResource('users.transactions', 'UserTransactionsController');
    Route::apiResource('users','UserController');
    Route::apiResource('transactions','TransactionController');
    Route::apiResource('transactions.users', 'TransactionUsersController');
    Route::apiResource('transactions.documents', 'TransactionDocumentsController');
    Route::apiResource('documents.transactions', 'DocumentTransactionsController');
    Route::apiResource('documents.users', 'DocumentUsersController');
    Route::apiResource('users.documents', 'UserDocumentsController');
    Route::post('send','TransactionController@store')->middleware('check.role:iscreator');
    Route::get('transactions/{id}/pdf','TransactionController@download');
    Route::get('transactions/{id}/trail','TransactionController@trail');
    Route::get('whoami','UserController@whoAmI');

    //Route::apiResource('webhooks', 'WebhookController');

});


// Route::match(['get', 'post'], 'login', 'AuthController@login');

	//Route::group(['middleware' => 'auth:api'], function(){
		//Route::get('profile','AuthController@profile');
        //Route::post('logout','AuthController@logout');
