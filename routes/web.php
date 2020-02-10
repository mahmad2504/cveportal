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

Route::get('/cve', 'HomeController@GetLatestCVEs')->name('cve.all');
Route::get('/cve/{product_name}/{version_name?}', 'HomeController@GetProductCVEs')->name('cve.product'); 


Route::get('/triage/{product_name}', 'HomeController@TriageProduct')->name('triage.product.view'); 
Route::get('/triage/data/{product_name}', 'HomeController@ProductCveStatusData')->name('triage.product.data'); 