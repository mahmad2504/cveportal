<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;
use \MongoDB\BSON\Regex;
use App;

class Products
{
	private $datafolder = "data";
	private $db = null;
	private $admin=null;
	function __construct($admin=null)
	{
		if(!App::runningInConsole())
		{
			$this->datafolder = "../".$this->datafolder;
		}
		$this->admin = $admin;
	}
	public function InitDb()
	{	$dbname = config('database.connections.mongodb.database');
		$mongoClient=new Client("mongodb://".config('database.connections.mongodb.host'));
		$this->db = $mongoClient->$dbname;
	}

	public function GetVersionNames($groupsname=null,$productname)
	{
		$products = $this->GetProducts($groupsname,$productname);
		$versions = [];
		foreach($products as $product)
		{
			$versions[$product->version] = $product->version;	
		}
		return array_values($versions);
	}
	public function GetProductNames($groupsname=null)
	{
		$products = $this->GetProducts($groupsname);
		$names = [];
		foreach($products as $product)
		{
			$names[$product->name] = $product->name;	
		}
		return array_values($names);
	}
	public function GetGroupNames()
	{
		$products = $this->GetProducts();
		$groups = [];
		foreach($products as $product)
		{
			$groups[$product->group] = $product->group;	
		}
		return array_values($groups);
	}
	public function GetIds($groupname=null,$productname=null,$versionname=null)
	{
		$products = $this->GetProducts($groupname,$productname,$versionname);
		$ids = [];
		foreach($products as $product)
		{
			$ids[$product->id] = $product->id;	
		}
		return array_values($ids);
	}
	public function GetProduct($id)
	{
		$this->InitDb();
		$query['id'] = new Regex(preg_quote($id), 'i');
		$options = [
			'projection'=>
					["_id"=>0,
					]
		];
		return $this->db->products->findOne($query,$options);
	}
	public function GetProducts($groupname=null,$productname=null,$versionname=null)
	{
		$query = [];
		$this->InitDb();
		if($groupname!=null)
			$query['group'] = new Regex(preg_quote($groupname), 'i');
		if($productname!=null)
			$query['name'] = new Regex(preg_quote($productname), 'i');
		if($versionname!=null)
			$query['version'] = $versionname;//new Regex(preg_quote("".$versionname), 'i');	
		if($this->admin!=null)
			$query['admin'] = new Regex(preg_quote("".$this->admin), 'i');
		
		$options = [
			'projection'=>
					["_id"=>0,
					]
		];
		
		$list = $this->db->products->find($query,$options)->toArray();
		return $list;
	}
	public function Import()
	{
		ini_set("memory_limit","2000M");
		set_time_limit(0);
		$this->InitDb();
		$handle = fopen($this->datafolder.'/products.txt', "r");
		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				// process the line read.
				$line = str_replace("\t"," ",$line);
				$line = str_replace("\r"," ",$line);
				$line = str_replace("\n"," ",$line);
				$fields = explode(",",$line);
				if( count($fields) != 5)
		{
					echo "Error parsing --->".$line;
					exit();
				}
			$product=new \StdClass();
				$product->id = trim($fields[0]);
				$product->group = trim($fields[1]);
				$product->name =  trim($fields[2]);
				$product->version = "".trim($fields[3]);
				$product->admin = explode("|",trim($fields[4]));
			$database[] = $product;
				
		}
			fclose($handle);
		} 
		else 
		{
			echo "Error in opening products.txt";
			exit();
		} 
		$this->db->products->drop();
		$this->db->products->insertMany($database);
	}
}
