<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use App\svm;
use App\Products;

class SvmImport extends Command
{
	private $datafolder = "data/svm";
	private $cache_datafolder = "data/cache";
	private $db = null;
	private $collectionname = "svm";
	private $collection = null;
	private $cves = [];
	private $nvd_collection ="nvd";
	private $products = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'svm:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
	public function __construct()
	{
		 parent::__construct();
	}
    public function Init()
    {
		ini_set("memory_limit","2000M");
		set_time_limit(2000);
		
		if(!file_exists($this->datafolder))
			mkdir($this->datafolder, 0, true);
		if(!file_exists($this->cache_datafolder))
			mkdir($this->cache_datafolder, 0, true);
		
		$dbname = config('database.connections.mongodb.database');
		$mongoClient=new Client("mongodb://".config('database.connections.mongodb.host'));
		$this->db = $mongoClient->$dbname;
		$collectionname = $this->collectionname;
		$this->collection = $this->db->$collectionname;
		$products = new Products();
		$this->products = $products->Get();
		
		
    }
	public function GetList($foldername)
	{
		$path=$this->datafolder."/".$foldername."/components_cve.json";
		if(!file_exists($path))
			return null;
		
		$list = json_decode(file_get_contents($path),true);
		return $list;
	}
    /**
     * Execute the console command.
     *
     * @return mixed
     */
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
			//dump($component);
			foreach($component->cve as $cve)
			{
				$component_id = $component->id;
				$component_name = $component->component_name;
				$component_version = $component->version;
			
				if(!array_key_exists($cve,$this->cves))
				{
					$this->cves[$cve] = new \StdClass();
					$this->cves[$cve]->cve = $cve;
					$this->cves[$cve]->product[$product_id] = new \StdClass();
					$this->cves[$cve]->product[$product_id]->id = $product_id;
					$product = $this->GetProductDetails($product_id);
					$this->cves[$cve]->product[$product_id]->name = $product->name;
					$this->cves[$cve]->product[$product_id]->version = $product->version;
					$this->cves[$cve]->product[$product_id]->component[$component_id]=new \StdClass();
					$this->cves[$cve]->product[$product_id]->component[$component_id]->id = $component_id;
					$this->cves[$cve]->product[$product_id]->component[$component_id]->name = $component_name;
					$this->cves[$cve]->product[$product_id]->component[$component_id]->version = $component_version;
					//$cve = 'CVE-2018-10754';
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
					//dump($cve_nvd_data);
					//exit();
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
							$this->cves[$cve]->nvd->cvss  =  iterator_to_array($cve_nvd_data['impact']['baseMetricV3']['cvssV3']);
						else if(isset($cve_nvd_data['impact']['baseMetricV2']))
							$this->cves[$cve]->nvd->cvss  =  iterator_to_array($cve_nvd_data['impact']['baseMetricV2']['cvssV2']);
						
						//echo $cve_nvd_data['cve']['CVE_data_meta']['ID'];
						//dump($this->cves[$cve]->nvd);
					}
					else
					{
						$this->cves[$cve]->nvd = null;
					}
					
					
					//exit();
					//dump($cve_nvd_data);
				}
				else
				{
					if(!array_key_exists($product_id,$this->cves[$cve]->product))
					{
						$this->cves[$cve]->product[$product_id] = new \StdClass();
						$this->cves[$cve]->product[$product_id]->id = $product_id;
						$product = $this->GetProductDetails($product_id);
						$this->cves[$cve]->product[$product_id]->name = $product->name;
						$this->cves[$cve]->product[$product_id]->version = $product->version;
					
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
				}
			}
		}
	}
	public function GetProductDetails($product_id)
	{
		foreach($this->products as $product)
		{
			if($product->id == $product_id)
				return $product;
		
		}
		return null;
	}
	public function CacheProducts()
	{
		
		$product_data = [];
		foreach($this->products as $product)
		{
			$p =  new \StdClass();
			if(array_key_exists($product->name,$product_data))
			{
				$product_data[$product->name]->version[$product->version]=new \StdClass();
				$product_data[$product->name]->version[$product->version]->version = $product->version;
				$product_data[$product->name]->version[$product->version]->id = $product->id;
			}
			else
			{
				$product_data[$product->name] =  new \StdClass();
				$product_data[$product->name]->name = $product->name;
				$product_data[$product->name]->version[$product->version]=new \StdClass();
				$product_data[$product->name]->version[$product->version]->version = $product->version;
				$product_data[$product->name]->version[$product->version]->id = $product->id;
				
			}
		}
		$product_data = array_values($product_data);
		foreach($product_data as $product)
			$product->version = array_values($product->version);
			
		//dump($product_data);
		file_put_contents($this->cache_datafolder."/products.json",json_encode($product_data));
	}
	public function CacheLatestCves()
	{
		$options = [
			//'limit' => 50,
			'sort' => ['nvd.lastModifiedDate' => -1],
		];
		$cves = $this->db->cves->find([],$options)->toArray();
		$cvedate = [];
		foreach($cves as $record)
		{
			
			$cve = new \StdClass();
			$cve->cve = $record->cve;
			if($record->nvd == null)
					continue;
				
			$cve->description = $record->nvd->description;
			$cve->modified = $record->nvd->lastModifiedDate;
			$cvedate[] = $cve;
		}
		file_put_contents($this->cache_datafolder."/latestcves.json",json_encode($cvedate));
	}
	public function CacheProductCves()
	{
		$options = [
			'sort' => ['nvd.lastModifiedDate' => -1],
			
		];
		foreach($this->products as $product)
		{
			$query = ['product.name'=>$product->name];
			$cves = $this->db->cves->find($query,$options)->toArray();
			
			$cvedate = [];
			foreach($cves as $record)
			{
				
				$cve = new \StdClass();
				$cve->cve = $record->cve;
				if($record->nvd == null)
					continue;
				
				$cve->description = $record->nvd->description;
				$cve->modified = $record->nvd->lastModifiedDate;
				$cve->status = "Vulnerable";
				$cvedate[] = $cve;
			}
			file_put_contents($this->cache_datafolder."/".$product->name.".json",json_encode($cvedate));
		}
		//dump($cves);
	}
    public function handle()
    {
        //
		//dd($this->products);
		$this->Init();
		foreach($this->products as $product)
		{
			$list = $this->GetList($product->id);
			if($list == null)
				continue;
			$list = array_values($list);
			$collectionname = $product->id;
			$this->db->$collectionname->drop();
			$this->db->$collectionname->insertMany($list);		
		}
		
		$this->db->products->drop();
		$this->db->products->insertMany(array_values($this->products));	
		
		
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
		
		$this->CacheLatestCves();
		$this->CacheProductCves();
		$this->CacheProducts();
		
		echo "Done";
		
    }
}
