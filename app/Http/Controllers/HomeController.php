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
	public function GetProductCVEs($product_name,$version_name='all')
	{
		ob_start('ob_gzhandler');
		if($version_name == 'all')
		{
			if(!file_exists($this->cache_datafolder."/".$product_name."/cve.json"))
				return [];
			return file_get_contents($this->cache_datafolder."/".$product_name."/cve.json");
		}
		else
		{
			if(!file_exists($this->cache_datafolder."/".$product_name."/".$version_name."/cve.json"))
				return [];
			return file_get_contents($this->cache_datafolder."/".$product_name."/".$version_name."/cve.json");
		}
	}
	public function GetLatestCVEs()
	{
		ob_start('ob_gzhandler');
		return file_get_contents($this->cache_datafolder."/allcve.json");
	}
	public function Index()
	{

		$products = $this->GetProducts();
		return view('home',compact('products'));
	}
}
