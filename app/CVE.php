<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;
use App;
use App\CVEStatus;
use App\Products;
class CVE
{
	private $datafolder = "data/svm";
	private $cache_datafolder = "data/cache";
	private $nvd_collection ="nvd";
	private $cves = [];
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
	public function GetCVETriageStatus($cve,$productid)
	{
		$cvestatus = new CVEStatus();
		$status = $cvestatus->GetStatus($cve,$productid);
		return $status;
	}
	private function ProcessCveRecord($record,$id=null)
	{
		$cve = new \StdClass();
		$cve->cve = $record->cve;
		if(!isset($record->nvd))
			return null;
		if($record->nvd==null)
			return null;
		
		$cve->description = $record->nvd->description;
		$cve->modified = $record->nvd->lastModifiedDate;
		$cve->published = $record->nvd->publishedDate;
		$cve->cvss = $record->nvd->cvss;
		/*if(isset($record->nvd->cvssv3))
			$cve->cvssv3 = $record->nvd->cvssv3;
		
		if(isset($record->nvd->cvssv2))
			$cve->cvssv2 = $record->nvd->cvssv2;*/
		
		$cve->product = $record->product;
		$remove = [];
		$i=0;
		$valid = 0;
		$product_id = "-1";
		$p = new Products();
		if(count($id)==1)
			$product_id = $id[0];
		$cve->status = 'Not Applicable';
		foreach($cve->product as $product)
		{
			$details = $p->GetProduct($product->id);
			$product->group = $details->group;
			$product->name = $details->name;
			$product->version = $details->version;
			$product->status = $this->GetCVETriageStatus($cve->cve,$product->id);
			if($product_id == $product->id)
			{
				$cve->status = $product->status;
			}
		}
		return $cve;
	}
	function Get($ids)
	{		
		$this->InitDb();
		$options = [
			'sort' => ['nvd.lastModifiedDate' => -1],
			'projection'=>
					["_id"=>0,
					"nvd.description"=>1,
					"nvd.lastModifiedDate"=>1,
					"nvd.publishedDate"=>1,
					"nvd.cvss"=>1,
					"product.id"=>1,
					"product.component.name"=>1,
					"product.component.version"=>1,
					"cve"=>1]
		];
		$query  = ['product.id'=>['$in'=>$ids]];
		$cves = $this->db->cves->find($query,$options)->toArray();
		
		$cvedata = [];
		foreach($cves as $record)
		{
			$r = $this->ProcessCveRecord($record,$ids);
			if($r != null)
				$cvedata[] = $r;
		}
		return $cvedata;
	}
	function BuildCVEs($product_id)
	{
		$projection = [
			'projection'=>
			["_id"=>0,
			"component_name"=>1,
			"version"=>1,
			"id"=>1,
			"cve"=>1]
		];
		$collectionname = $product_id;
		$cursor = $this->db->$collectionname->find([],$projection);	
		
		foreach($cursor as $component)
		{
			foreach($component->cve as $cve)
			{
				$component_id = $component->id;
				$component_name = $component->component_name;
				$component_version = $component->version;
				
				if(!array_key_exists($cve,$this->cves))
				{
					$this->cves[$cve] = new \StdClass();
					$this->cves[$cve]->cve = $cve;
					$this->cves[$cve]->product = [];
					
					$query = ['cve.CVE_data_meta.ID'=>$cve];
					$projection = ['projection'=>[ 
						'_id'=>0,
						'cve.CVE_data_meta.ID'=>1,
						'lastModifiedDate'=>1,
						'publishedDate'=>1,
						'cve.description.description_data.value'=>1,
						'impact.baseMetricV3.cvssV3'=>1,
						'impact.baseMetricV2.cvssV2'=>1
						]];
					
					//$projection=[];
					$nvd_collection = $this->nvd_collection;
					$cve_nvd_data = $this->db->$nvd_collection->findOne($query,$projection);
					if($cve_nvd_data != null)
					{
						$this->cves[$cve]->nvd = new \StdClass();
						$lastModifiedDate = $cve_nvd_data['lastModifiedDate']->toDateTime()->format(DATE_ATOM);
						$this->cves[$cve]->nvd->lastModifiedDate = substr($lastModifiedDate,0,10);
						
						$publishedDate = $cve_nvd_data['publishedDate']->toDateTime()->format(DATE_ATOM);
						$this->cves[$cve]->nvd->publishedDate = substr($publishedDate,0,10);
						
						$this->cves[$cve]->nvd->description = $cve_nvd_data['cve']['description']['description_data'][0]['value'];
						
						
						$this->cves[$cve]->nvd->cvss = null;
						if(isset($cve_nvd_data['impact']['baseMetricV3']))
						{
							$this->cves[$cve]->nvd->cvss  =  iterator_to_array($cve_nvd_data['impact']['baseMetricV3']['cvssV3']);
							//$this->cves[$cve]->nvd->cvssv3 = iterator_to_array($cve_nvd_data['impact']['baseMetricV3']['cvssV3']);
						}
						else if(isset($cve_nvd_data['impact']['baseMetricV2']))
						{
							$this->cves[$cve]->nvd->cvss  =  iterator_to_array($cve_nvd_data['impact']['baseMetricV2']['cvssV2']);
							//$this->cves[$cve]->nvd->cvssv2 = iterator_to_array($cve_nvd_data['impact']['baseMetricV2']['cvssV2']);
						}
						
						//echo $cve_nvd_data['cve']['CVE_data_meta']['ID'];
						//dump($this->cves[$cve]->nvd);
					}
					else
					{
						$this->cves[$cve]->nvd = null;
					}
					
					
				}
				if(!array_key_exists($product_id,$this->cves[$cve]->product))
				{
					$this->cves[$cve]->product[$product_id] = new \StdClass();
					$this->cves[$cve]->product[$product_id]->id = $product_id;
					$this->cves[$cve]->product[$product_id]->component[$component_id]=new \StdClass();
					$this->cves[$cve]->product[$product_id]->component[$component_id]->id = $component_id;
					$this->cves[$cve]->product[$product_id]->component[$component_id]->name = $component_name;
					$this->cves[$cve]->product[$product_id]->component[$component_id]->version = $component_version;
				}
				else
				{
					if(!array_key_exists($component_id,$this->cves[$cve]->product[$product_id]->component))
					{
						$this->cves[$cve]->product[$product_id]->component[$component_id]=new \StdClass();
						$this->cves[$cve]->product[$product_id]->component[$component_id]->id = $component_id;
						$this->cves[$cve]->product[$product_id]->component[$component_id]->name = $component_name;
						$this->cves[$cve]->product[$product_id]->component[$component_id]->version = $component_version;
					}
				}
				//$status = $this->GetCVETriageStatus($cve,$product_id);
				//$this->cves[$cve]->product[$product_id]->status = $status;
			}
		}
	}

	public function Import()
	{
		ini_set("memory_limit","2000M");
		set_time_limit(0);
		$this->InitDb();
		$products = new Products();
		$ids = $products->GetIds();
		foreach($ids as $id)
		{
			$this->BuildCVEs($id);
		}
		foreach($this->cves as $cve)
		{
			foreach($cve->product as $product)
				$product->component = array_values($product->component);
			$cve->product = array_values($cve->product);
		}
		$this->db->cves->drop();
		$this->db->cves->insertMany(array_values($this->cves));
	}
}