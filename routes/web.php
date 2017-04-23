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
$app->group(['middleware' => 'rabbitMq','prefix' => 'rabbitMq','namespace' => 'App\Http\Controllers'], function($app)
{
    $app->post('mail/create', 'MailController@createMessage');
    $app->get('mail/consume', 'MailController@consumeMessage');
});