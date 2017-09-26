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

Route::post('/boards/{id}/cards', 'BoardCardController@create');
Route::post('/boards', 'BoardController@create');
Route::put('/boards', 'BoardController@update');
Route::get('/boards', 'BoardController@index');
