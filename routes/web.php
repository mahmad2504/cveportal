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

Route::get('/cve/latest', 'HomeController@GetLatestCVEs')->name('cve.latest');
Route::get('/cve/product/{product_name}', 'HomeController@GetProductCVEs')->name('cve.product');; 