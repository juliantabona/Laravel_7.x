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


//  API Home
Route::get('/', 'Api\HomeController@home')->name('api-home');

//  Auth Routes
Route::middleware('auth:api')->namespace('Api')->group(function () {

    //  Me Resource Routes
    Route::prefix('me')->group(function () {

        Route::get('/', 'UserController@getUser')->name('profile');

    });

});

Route::namespace('Api')->prefix('auth')->group(function () {
    
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('register', 'AuthController@register')->name('register');
    Route::post('logout', 'AuthController@logout')->middleware('auth:api')->name('logout');

});
