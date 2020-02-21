<?php

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

Route::get('/', 'HomeController@index'); 
Route::get('/cve/{group?}/{product?}/{version?}/{admin?}', 'HomeController@GetCVEs')->name('cve.get');

Route::get('/triage', 'HomeController@Triage')->name('triage'); 
Route::get('/login', 'HomeController@Login')->name('login'); 
Route::post('/authenticate', 'HomeController@Authenticate')->name('authenticate');  
Route::get('/logout', 'HomeController@Logout')->name('logout'); 
Route::put('/triage/cve/update', 'HomeController@CveStatusUpdate')->name('cve.status.update'); 

