<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;
use App;

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
	public function GetCVETriageStatus($cve,$product_id)
	{
		return 'Vulnerable';
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
				$status = $this->GetCVETriageStatus($cve,$product_id);
				$this->cves[$cve]->product[$product_id]->status = $status;
			}
		}
	}
	private function CreateCveRecord($record,$product_by_ids)
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
		foreach($cve->product as $product)
		{
			$details = $product_by_ids[$product->id];
			$product->name = $details->name;
			$product->version = $details->version;
		}
		return $cve;
	}
	public function CacheProductCves()
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
					"nvd.cvssv3"=>1,
					"nvd.cvssv2"=>1,
					"product.id"=>1,
					"product.status"=>1,
					"product.component.name"=>1,
					"product.component.version"=>1,
					"cve"=>1]
		];
		$donelist = [];
		$products = new Products();
		$product_ids = $products->GetIds();
		$product_by_ids = $products->GetByIds();
		
		/*foreach($this->products as $name,$product_array)
		{
			foreach($product_array as $id=>$product)
			{
				$ids = 
			}
		}
		$query  = [ 'product.id'=>['in'= [  ] } }
		$in: [ 5, ObjectId("507c35dd8fada716c89d0013")*/
		
		foreach($product_ids as $name=>$ids)
		{
			$ids = array_values($ids);
			$query  = ['product.id'=>['$in'=>$ids]];
			$cves = $this->db->cves->find($query,$options)->toArray();
			$cvedata = [];
			foreach($cves as $record)
			{
				$r = $this->CreateCveRecord($record,$product_by_ids);
				if($r != null)
					$cvedata[] = $r;
			}
			$folder_name = $this->cache_datafolder."/".$name;
			if(!file_exists($folder_name))
				mkdir($folder_name, 0, true);
				
			file_put_contents($folder_name."/cve.json",json_encode($cvedata));
			
			foreach($ids as $id)
			{
				$query = ['product.id'=>$id];
				$cves = $this->db->cves->find($query,$options)->toArray();
				$cvedata = [];
				foreach($cves as $record)
				{
					$r = $this->CreateCveRecord($record,$product_by_ids);
					if($r != null)
						$cvedata[] = $r;
				}
				$p = $product_by_ids[$id];
				$folder_name = $this->cache_datafolder."/".$name."/".$p->version;
				if(!file_exists($folder_name))
					mkdir($folder_name, 0, true);
				file_put_contents($folder_name."/cve.json",json_encode($cvedata));
			}
		}
		//dump($product_list);
	}
	public function CacheAllCves()
	{
		$this->InitDb();
		$products = new Products();
		$product_by_ids = $products->GetByIds();
		
		$options = [
			//'limit' => 50,
			'sort' => ['nvd.lastModifiedDate' => -1],
		];
		$cves = $this->db->cves->find([],$options)->toArray();
		$cvedata = [];
		foreach($cves as $record)
		{
			
			$r = $this->CreateCveRecord($record,$product_by_ids);
			if($r != null)
				$cvedata[] = $r;
		}
		file_put_contents($this->cache_datafolder."/allcve.json",json_encode($cvedata));
	}
	public function CacheUpdate()
	{
		$this->CacheAllCves();
		$this->CacheProductCves();
	}
	public function Import()
	{
		ini_set("memory_limit","2000M");
		set_time_limit(0);
		$this->InitDb();
		$products = new Products();
		$products = $products->Get();
		foreach($products as $product)
		{
			$this->BuildCVEs($product->id);
		}
		foreach($this->cves as $cve)
		{
			foreach($cve->product as $product)
				$product->component = array_values($product->component);
			$cve->product = array_values($cve->product);
		}
		$this->db->cves->drop();
		$this->db->cves->insertMany(array_values($this->cves));
		$this->CacheUpdate();
		
	}
}