<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Expose-Headers: X-Total-Pages');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'api'], function() {
    Route::group(['prefix' => 'auth'], function() {
        Route::post('login', 'API\UsersController@login');
    });
});
