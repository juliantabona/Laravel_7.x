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

Route::namespace('Api')->prefix('auth')->group(function () {
    
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('register', 'AuthController@register')->name('register');
    Route::post('send-password-reset-link', 'AuthController@sendPasswordResetLink')->name('send-password-reset-link');
    Route::post('reset-password', 'AuthController@resetPassword')->name('reset-password');

    Route::post('logout', 'AuthController@logout')->middleware('auth:api')->name('logout');

});

//  Auth Routes
Route::middleware('auth:api')->namespace('Api')->group(function () {

    //  Me Resource Routes
    Route::prefix('me')->name('my-')->group(function () {

        Route::get('/', 'UserController@getUser')->name('profile');

        Route::get('/projects', 'UserController@getUserProjects')->name('projects');

    });

    //  Project Resource Routes
    Route::prefix('projects')->group(function () {

        Route::post('/', 'ProjectController@createProject')->name('project-create');
        
        //  Single project  /projects/{project_id}
        Route::get('/{project_id}', 'ProjectController@getProject')->name('project')->where('project_id', '[0-9]+');
        Route::put('/{project_id}', 'ProjectController@updateProject')->name('project-update')->where('project_id', '[0-9]+');
        Route::delete('/{project_id}', 'ProjectController@deleteProject')->name('project-delete')->where('project_id', '[0-9]+');
        
        //  Single project versions  /projects/{project_id}/versions
        Route::get('/{project_id}/versions', 'ProjectController@getProjectVersions')->name('project-versions')->where('project_id', '[0-9]+');

    });

    //  Version Resource Routes
    Route::prefix('versions')->group(function () {

        //  Single version  /versions/{version_id}
        Route::get('/{version_id}', 'VersionController@getVersion')->name('version')->where('version_id', '[0-9]+');
        Route::put('/{version_id}', 'VersionController@updateVersion')->name('version-update')->where('version_id', '[0-9]+');

    });

});

//  MISC
Route::put('/payment-methods', 'MiscController@getPaymentMehods')->name('payment-methods');

Route::prefix('ussd')->namespace('Api')->group(function () {
    Route::post('/builder', 'UssdServiceController@setup')->name('ussd-service-builder');
});
