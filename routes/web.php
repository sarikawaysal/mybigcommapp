<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/loadApp', "App\Http\Controllers\MyBigcomController@loadApp");
Route::get('/callBack', "App\Http\Controllers\MyBigcomController@callBack");
Route::get('/verifySignedRequest', "App\Http\Controllers\MyBigcomController@verifySignedRequest");
