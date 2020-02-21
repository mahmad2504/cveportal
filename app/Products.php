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
	private $datafolder = "data/svm";
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
			$query['version'] = new Regex(preg_quote("".$versionname), 'i');	
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
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->datafolder.'/products.xlsm'); 
		$data = $spreadsheet->getActiveSheet()->toArray(null,true,true,true); 

		$i=0;
		$products = [];
		$database = [];
		foreach($data as $row)
		{
			
			$i++;
			if($i==1)
				continue;
			
			$row = $data[$i];
			
			
			$id = $row['A'];
			$group = $row['B'];
			$name = $row['C'];
			$version = $row['D'];
			$admins = explode(",",$row['E']);
			
			$product=new \StdClass();
			$product->id = $id;
			$product->group = $group;
			$product->name =  $name;
			$product->version = "".$version;
			$product->admin = $admins;
			$database[] = $product;
		}
		$this->db->products->drop();
		$data = [];
		$data[] = $products;
		$this->db->products->insertMany($database);
		/*dump($this->GetProducts(null,null,'1.0'));
		dump(implode(",",$this->GetIds(null,null,'1.0')));
		dump($this->GetGroupNames());
		dump($this->GetProductNames('MEL Omni OS'));
		dump($this->GetVersionNames('MEL flex OS','BSP IMX6'));*/
	}
}
