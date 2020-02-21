<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;
use App;
use App\CVEStatus;

class CVEStatus
{
	private $db = null;
	private $collectionname = 'cvestatus';
	private $defaultstatus='Investigate';
	function __construct()
	{
		/*$projection = [
		];
		$this->InitDb();
		$cursor = $this->db->$collectionname->find(['product_id'=> $product_id],$projection);
		$this->data = $cursor->toArray();*/
	}
	public function InitDb()
	{	$dbname = config('database.connections.mongodb.database');
		$mongoClient=new Client("mongodb://".config('database.connections.mongodb.host'));
		$this->db = $mongoClient->$dbname;
	}
	public function UpdateStatus($status)
	{
		$this->InitDb();
		$collection = $this->collectionname;
		$this->db->$collection->updateOne(
            [
				'status.cve'=>$status['cve'],
				'status.productid'=>$status['productid']
			],
            ['$set' => [
				'status' => $status,
				]
			],
            ['upsert' => true]
        );
	}
	
	public function GetStatus($cve,$productid)
	{
		$this->InitDb();
		$collection = $this->collectionname;
		$record = $this->db->$collection->findOne(
			[
				'status.cve'=>$cve,
				'status.productid'=>$productid
			]	
		);
		if($record == null)
		{
			
			$ret = new \StdClass();
			$ret->cve=$cve;
			$ret->productid=$productid;
			$ret->state=$this->defaultstatus;
			$ret->publish=false;
			return $ret;
			//dump($record);
		}
		
		return $record->status;
	}
	
}