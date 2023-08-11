<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// $app->get('/', function () use ($app) {
//     return $app->version();
// });

$app->post('/create-callflow', 'CreateCallflowController@getDetails');

$app->put('/update-callflow', 'UpdateCallflowController@updateDetails');

$app->delete('/delete-callflow', 'UpdateCallflowController@delete');

$app->post('/upload-file', 'FileUploadController@getFileDetails');

$app->post('/originate', 'CallOriginateController@getDetails');

$app->post('/black-list', 'BlackListController@getDetails');

$app->post('/originate-bangalore', 'BangaloreCallOriginateController@getDetails');

$app->post('/tml-outbound','TML\CallOutboundHandler@getDetails');

$app->post('/live-call-remove','TML\LiveCallRemove@getCallback');

