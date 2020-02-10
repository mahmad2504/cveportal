<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;
use App;

class Products
{
	private $products = [];
	private $datafolder = "data/svm";
	private $cache_datafolder = "data/cache";
	private $db = null;
	function __construct()
	{
		
		if(!App::runningInConsole())
		{
			$this->datafolder = "../".$this->datafolder;
			$this->cache_datafolder = "../".$this->cache_datafolder;
		}
		if(!file_exists($this->datafolder))
			mkdir($this->datafolder, 0, true);
		if(!file_exists($this->cache_datafolder))
			mkdir($this->cache_datafolder, 0, true);
	}
	public function InitDb()
	{	$dbname = config('database.connections.mongodb.database');
		$mongoClient=new Client("mongodb://".config('database.connections.mongodb.host'));
		$this->db = $mongoClient->$dbname;
	}
	public function Get()
	{
		$this->InitDb();
		$query = [];
		$cursor = $this->db->products->find($query);
		$products = $cursor->toArray();
		return $products; 		
	}
	public function GetIds()
	{
		$products = $this->Get();
		$product_by_name = [];
		foreach($products as $product)
		{
			$product_by_name[$product->name][$product->id] = $product->id;
		}
		return $product_by_name;
	}
	public function GetByIds()
	{
		$products = $this->Get();
		$product_by_ids = [];
		foreach($products as $product)
		{
			$product_by_ids[$product->id] = $product;
		}
		return $product_by_ids;
	}
	public function CacheUpdate()
	{
		$this->InitDb();
		$query = [];
		$cursor = $this->db->products->find($query);
		$products = $cursor->toArray();

		$product_data = [];
		foreach($products as $product)
		{
			
			if(array_key_exists($product->name,$product_data))
			{
				$index = "-".$product->version;
				$product_data[$product->name]->version[$index]=new \StdClass();
				$product_data[$product->name]->version[$index]->version = $product->version;
				$product_data[$product->name]->version[$index]->id = $product->id;
			}
			else
			{
				$index = "-".$product->version;
				$product_data[$product->name] =  new \StdClass();
				$product_data[$product->name]->name = $product->name;
				$product_data[$product->name]->version[$index]=new \StdClass();
				$product_data[$product->name]->version[$index]->version = $product->version;
				$product_data[$product->name]->version[$index]->id = $product->id;
				
			}
		}
		$product_data = array_values($product_data);
		foreach($product_data as $product)
			$product->version = array_values($product->version);
			
		file_put_contents($this->cache_datafolder."/products.json",json_encode($product_data));
	}
	public function Import()
	{
		ini_set("memory_limit","2000M");
		set_time_limit(0);
		
		$this->InitDb();
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->datafolder.'/products.xlsm'); 
		$data = $spreadsheet->getActiveSheet()->toArray(null,true,true,true); 

		$i=0;
		$products = [];
		foreach($data as $row)
		{
			
			$i++;
			if($i==1)
				continue;
			
			$row = $data[$i];
			
			
			$row['id'] = $row['A'];
			$row['name'] = $row['B'];
			$row['version'] = $row['C'];
			$row['valid'] = 0;
			
			unset($row['A']);unset($row['B']);unset($row['C']);
			$product = (object)($row);
			$products[$product->id] = $product;
		}
		$this->db->products->drop();
		$this->db->products->insertMany(array_values($products));	
		$this->CacheUpdate();
	}
}