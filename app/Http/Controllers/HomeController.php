<?php

namespace App\Http\Controllers;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;

use Auth;
use App\User;

use Illuminate\Http\Request;
use App\Products;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
	private $cache_datafolder = "../data/cache";
    public function __construct()
    {
		
    }
	public function GetProducts()
	{
		return  json_decode(file_get_contents($this->cache_datafolder."/products.json"));
	}
	public function GetProductCVEs($product_name)
	{
		return  json_decode(file_get_contents($this->cache_datafolder."/".$product_name.".json"));

	}
	public function GetLatestCVEs()
	{
		return  json_decode(file_get_contents($this->cache_datafolder."/latestcves.json"));
	}
	public function Index()
	{
		$data = [
		 [
		  "id"=>"1",
		  "name"=>"MEL Flex",
		  "parent_id"=>"0"
		 ], 
		 [
		  "id"=>"2",
		  "name"=>"MEL Omni",
		  "parent_id"=>"0"
		 ], 
		 [
		  "id"=>"3",
		  "name"=>"MEL Nucleus",
		  "parent_id"=>"0"
		 ], 
		 [
		  "id"=>"4",
		  "name"=>"1.0",
		  "parent_id"=>"1"
		 ], 
		 [
		  "id"=>"5",
		  "name"=>"1.1",
		  "parent_id"=>"1"
		 ], 
		 [
		  "id"=>"6",
		  "name"=>"1.0.0",
		  "parent_id"=>"2"
		 ], 
		 [
		  "id"=>"7",
		  "name"=>"1.1.2",
		  "parent_id"=>"2"
		 ], 
		 [
		  "id"=>"8",
		  "name"=>"4.0",
		  "parent_id"=>"3"
		 ], 
		 [
		  "id"=>"9",
		  "name"=>"4.1",
		  "parent_id"=>"3"
		 ],
		 
		];
		$products = $this->GetProducts();
		return view('home',compact('products'));
	}
}
