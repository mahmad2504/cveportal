<?php

namespace App\Http\Controllers;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;

use Auth;
use App\User;

use Illuminate\Http\Request;
use App\Products;
use Artisan;
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
		if(!file_exists($this->cache_datafolder."/products.json"))
			Artisan::call('cache:update');
		return  json_decode(file_get_contents($this->cache_datafolder."/products.json"));
	}
	public function GetProductCVEs($product_name,$version_name='all')
	{
		ob_start('ob_gzhandler');
		if($version_name == 'all')
		{
			if(!file_exists($this->cache_datafolder."/".$product_name."/cve.json"))
			{
				$products = $this->GetProducts();
				$found = 0;
				foreach($products as $product)
				{
					if(	$product_name == $product->name)
					{
						$found = 1;
						break;
					}
				}
				if($found)
					Artisan::call('cache:update');
				else
					return [];
			}
			return file_get_contents($this->cache_datafolder."/".$product_name."/cve.json");
		}
		else
		{
			if(!file_exists($this->cache_datafolder."/".$product_name."/".$version_name."/cve.json"))
			{
				$products = $this->GetProducts();
				$found = 0;
				foreach($products as $product)
				{
					foreach($product->version as $version)
					{
						if(	$product_name == $product->name)
						{
							$found = 1;
							break;
						}
					}
				}
				if($found)
					Artisan::call('cache:update');
				else
					return [];
			}
			return file_get_contents($this->cache_datafolder."/".$product_name."/".$version_name."/cve.json");
		}
	}
	public function GetLatestCVEs()
	{
		ob_start('ob_gzhandler');
		if(!file_exists($this->cache_datafolder."/allcve.json"))
			Artisan::call('cache:update');
			
		return file_get_contents($this->cache_datafolder."/allcve.json");
	}
	public function TriageProduct($product_name)
	{
		$products = new Products();
		$products = $products->Get();
		$product = null;
		foreach($products as $p)
		{
			if($p->name == $product_name)
			{
				$product = $p;
			}
		}
		if($product == null)
			abort(403, 'Product Not Found');
		
		
		return view('triage.product',compact(['product']));
	}
	public function ProductCveStatusData($product_name)
	{
		//return view('triage_product',compact('product'));
		$options = [
			'sort' => ['nvd.lastModifiedDate' => -1],
			'projection'=>
					["_id"=>0,
					"product.name"=>1,
					"product.version"=>1,
					"product.status"=>1,
					"cve"=>1]
		];
		$donelist = [];
		$query = ['product.name'=>$product_name];
		
		$dbname = config('database.connections.mongodb.database');
		$mongoClient=new Client("mongodb://".config('database.connections.mongodb.host'));
		$db = $mongoClient->$dbname;
		
		$cves = $db->cves->find($query,$options)->toArray();
		$data = [];
		
		foreach($cves as $cve)
		{
			foreach($cve->product as $p)
			{
				if($p->name == $product_name)
				{
					$version = $p->version." ";
					if(!array_key_exists($version,$data))
					{
						$data[$version] = new \StdClass();
						$data[$version]->product = $product_name;
						$data[$version]->version = $version;
					}
					$status = $p->status;
					if(!isset($data[$version]->$status))
						$data[$version]->$status=0;
					$data[$version]->$status++;
				}
			}
		}
		return array_values($data);
	}
	public function Index()
	{
		$products = $this->GetProducts();
		return view('home',compact('products'));
	}
}
