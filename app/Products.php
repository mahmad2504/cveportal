<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;


class Products
{
	private $products = [];
	private $datafolder = "data/svm";
	private $db = null;
	function __construct()
	{
		$dbname = config('database.connections.mongodb.database');
		$mongoClient=new Client("mongodb://".config('database.connections.mongodb.host'));
		$this->db = $mongoClient->$dbname;
	}
	function Get()
	{
		$query = [];
		$cursor = $this->db->products->find($query);
		$products = $cursor->toArray();
		
		return $products;
	}
	public function Import()
	{
		
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
	}
}