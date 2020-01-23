<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;

class NvdImport extends Command
{
	
	private $urls = null;// Read from config
	private $datafolder = "data/nvd";
	private $db = null;
	private $collectionname = "nvd";
	private $collection = null;
	
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nvd:import';

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
		$dbname = config('database.connections.mongodb.database');

		$mongoClient=new Client("mongodb://".config('database.connections.mongodb.host'));
		
		
		$this->db = $mongoClient->$dbname;
		$collectionname = $this->collectionname;
		$this->collection = $this->db->$collectionname;
		
		$this->urls = config('app.nvd.urls');
		
       
    }
	function Update()
	{
		$updatecvedb = true;
		$collections = $this->db->listCollections();
		$collectionNames = [];
		foreach ($collections as $collection) 
		{
			$name = $collection->getName();
			if($name == $this->collectionname)
			{
				$updatecvedb = false;
			}
		}
		foreach($this->urls  as $url)
		{
			echo "Checking ".basename($url)." feed\n"; 
			$contentsize = $this->GetContentSize($url);
			$filename = basename($url);
			$filename = $this->datafolder."/".$filename;
			$oldcontentsize = 0;
			if(file_exists($filename))
			{
				$oldcontentsize = filesize($filename);
			}
			if($oldcontentsize!=$contentsize)
			{
				$this->Download($url,$filename);
				$updatecvedb = true;
			}
		}
		
		if($updatecvedb)
		{
			echo "Updating cve database\n"; 
			$this->collection->Drop();
			foreach($this->urls as $nvdurl)
				$this->UpdateDatabase($nvdurl);
				
			echo "Updating Search Indexes\n"; 
			//Create Text Index
			$this->collection->createIndex(["configurations.nodes.cpe_match.cpe23Uri"=>'text',"configurations.nodes.children.cpe_match.cpe23Uri"=>'text']);
			//Create Index
			$this->collection->createIndex(["cve.CVE_data_meta.ID"=>1]);
			echo 'Imported NVD Data successfully';
		}
		else
		   echo 'NVD Data already updated';
	}
	function GetContentSize($url) 
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FILETIME, true);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		$header = curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);
		return $info['download_content_length'];
	}
	function Download($url,$filename)
	{
		$zip = new \ZipArchive;
		$ch = curl_init(); 
		echo 'Downloading '.basename($url)."\n";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($ch, CURLOPT_SSLVERSION,3);
		$data = curl_exec ($ch);
		$error = curl_error($ch); 
		curl_close ($ch);
		
		$file = fopen($filename, "w");
		fputs($file, $data);
		fclose($file);
		//SendConsole(time(),'Unzipping '); 
		if ($zip->open($filename ) === TRUE) 
		{
			$zip->extractTo($this->datafolder."/");
			$zip->close();
			//SendConsole(time(),'Done '.basename($url) ); 
		} 
		else 
		{
			echo 'Failed '.basename($url)."\n";
			echo "Nothing is updated\n";
			exit();
		}	
	}
	private function UpdateDatabase($nvdurl)
	{
		$filename = str_replace('.zip','',basename($nvdurl));
		//echo memory_get_usage() . "\n";
		
		$data = $this->PreProcess($this->datafolder."/".$filename);
		$data_array = [];
		foreach($data->CVE_Items as $cve)
		{
			$data_array[] =  $cve;
			if(count($data_array) > 2000)
			{
				$this->collection->insertMany($data_array);
				$data_array = [];
			}
		}
		//echo $filename." ".$this->db;
		//echo memory_get_usage() . "\n";
		//Utility::Console(time(),"...."); 
		if(count($data_array) > 0)
		{
			$this->collection->insertMany($data_array);
			$data_array = [];
		}
		//$this->collection->insertMany($data);	
	}
	private function  PreProcess($filename)
	{
		$data = file_get_contents($filename);
		//echo memory_get_usage()."\n";
		$json = json_decode($data);
		//echo memory_get_usage()."\n";
		foreach($json->CVE_Items as $cve)
		{
			$date = new \DateTime($cve->publishedDate);
			$date->setTime(0,0,0);
			$ts = $date->getTimestamp();
			$cve->publishedDate = new UTCDateTime($ts*1000);
			$date = new \DateTime($cve->lastModifiedDate);
			$date->setTime(0,0,0);
			$ts = $date->getTimestamp();
			$cve->lastModifiedDate = new UTCDateTime($ts*1000);
			//$cve->publishedDate = new MongoDB\BSON\Timestamp(1, $ts);
			//echo $date->__toString();
			//echo $cve->publishedDate;
			//exit();
		}
		echo "Updating ".$filename." data in database\n"; 
		return $json;	
	}
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
		$this->Init();
		$this->Update();
    }
}
