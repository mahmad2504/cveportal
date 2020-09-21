<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;

class NvdSearch extends Command
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
    protected $signature = 'nvd:search {--package=null} {--ver=null}';

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
		ini_set("memory_limit","3000M");
		set_time_limit(0);
		
		if(!file_exists($this->datafolder))
			mkdir($this->datafolder, 0, true);
		$dbname = config('database.connections.mongodb.database');

		$mongoClient=new Client("mongodb://".config('database.connections.mongodb.host'));
		
		
		$this->db = $mongoClient->$dbname;
		$collectionname = $this->collectionname;
		$this->collection = $this->db->$collectionname;
		
		$this->urls = config('app.nvd.urls');
		
       
    }
	public function GetCveOfPackage($package)
	{
		$searchdata = '';
		if(isset($this->aliases[$package]))
		{
			foreach($this->aliases[$package] as $alias)
			{
				$searchdata .= $alias." ";	
			}
		}
		else
			$searchdata = '"'.$package.'"';
	
		//dd($searchdata);
		//$searchdata = 'camimages camlimages';
		//echo $searchdata;
		$query = ['$text' => ['$search' => $searchdata]];
		
		$cursor = $this->collection->find($query,['projection'=>['cve.CVE_data_meta.ID'=>1]]);
		$cvelist = [];
		foreach($cursor->toArray() as $cve)
		{
			$cvelist[] = $cve->cve->CVE_data_meta->ID; 
		}
		return $cvelist;
	}
	public function GetCVEs($package,$version=null)
	{
		
		$aliases = [];
		$cves = $this->GetCveOfPackage($package);
	
		//$cves = $this->GetPackage($package);
		$query = ['cve.CVE_data_meta.ID' => ['$in' => $cves]];
		$cursor = $this->collection->find($query,['projection'=>['cve.CVE_data_meta.ID'=>1,'configurations'=>1,'impact'=>1]]);
		$data_array =  array();
		foreach($cursor as $cve)
		{
			$data = new \StdClass();
			$data->cve = $cve->cve->CVE_data_meta->ID;
			if(isset($cve->impact->baseMetricV3))
			{
				$data->cvssVersion = 3.0;
				$data->baseScore = $cve->impact->baseMetricV3->cvssV3->baseScore;
				$data->baseSeverity = $cve->impact->baseMetricV3->cvssV3->baseSeverity;
			}
			else
			{
				$data->cvssVersion = 2.0;
				$data->baseScore = $cve->impact->baseMetricV2->cvssV2->baseScore;
				$data->baseSeverity = $cve->impact->baseMetricV2->severity;
			}
			
			$data->type = $this->DetermineVulType($cve,$package,$version,$aliases);
			if($data->type == null)
				continue;
			
			//if($data->type->version_match != '')
			if($version == null)
				$data_array[$data->cve] = $data;
			else
			{
				if($data->type->version_match != '')
					$data_array[$data->cve] = $data;
			}
		}
		//dd($data_array);
		return $data_array;
		//$query = ['$text' => ['$search' => $searchdata]];
		//$cursor = $this->collection->find($query,['configurations','cve.CVE_data_meta.ID','impact']);

	}
	private function ProcessImpactNode($cpe_match,$package,$version,$obj,$aliases)
	{
		foreach($cpe_match as $cpe)
		{
			if($cpe->vulnerable == true)
			{
				$cpe_array = explode(":",$cpe->cpe23Uri);
				$cpepart = $cpe_array[2];
				$cpevendor = $cpe_array[3];
				$cpeproduct = $cpe_array[4];
				$cpeversion = $cpe_array[5];
				$cpeupdate =  $cpe_array[6];
				
				//echo "-->".$cpevendor." ".$cpeproduct." ".$package."<br>";
				/*var_dump($package);
				var_dump($cpeproduct);
				var_dump($aliases);*/
				
				if(($package==$cpevendor)||($package == $cpeproduct)||in_array($cpeproduct, $aliases))
				{
					$failed = 0;
					$passed = 0;
					$matched_versions = '';
					$rangecheckpresent = 0;
					$obj->vendor_match  = $cpevendor;
					$obj->package_match = $cpeproduct;
					$obj->version_found = $cpeversion;
					//var_dump($cpe);
					if(isset($cpe->versionStartExcluding))
					{
						$rangecheckpresent = 1;
						$obj->version_found = 'versionStartExcluding:'.$cpe->versionStartExcluding;
						if($this->version_compare2($version,$cpe->versionStartExcluding)>0)
						{
							$matched_versions = 'versionStartExcluding:'.$cpe->versionStartExcluding;
							//echo "First\r\n";
							$passed++;
						}
						else
							$failed++;
					}
					if(isset($cpe->versionStartIncluding))
					{
						$rangecheckpresent = 1;
						$obj->version_found = 'versionStartIncluding:'.$cpe->versionStartIncluding;
						if( ($this->version_compare2($version,$cpe->versionStartIncluding)==0)||
							($this->version_compare2($version,$cpe->versionStartIncluding)>0))
						{
							$matched_versions = 'versionStartIncluding:'.$cpe->versionStartIncluding;
							//echo "Second\r\n";
							$passed++;
						}
						else
							$failed++;
					}
					if(isset($cpe->versionEndExcluding))
					{
						$rangecheckpresent = 1;
						$obj->version_found = 'versionEndExcluding:'.$cpe->versionEndExcluding;
						//echo "-".$version."-".$cpe->versionEndExcluding."-<br>";
						//echo $this->version_compare2($version,$cpe->versionEndExcluding)."<br>";
						//echo version_compare($version,$cpe->versionEndExcluding)."<br>";
						
						if($this->version_compare2($version,$cpe->versionEndExcluding)<0)
						{
							$matched_versions = 'versionEndExcluding:'.$cpe->versionEndExcluding;
							//echo "Third\r\n";
							$passed++;
						}
						else
							$failed++;
					}
					if(isset($cpe->versionEndIncluding))
					{
						$flag=0;
						//echo $version."--".$cpe->versionEndIncluding."<br>"; 
						//if('1.30' == $cpe->versionEndIncluding)
						//	$flag=1;
						$rangecheckpresent = 1;
						$obj->version_found = 'versionEndIncluding:'.$cpe->versionEndIncluding;
						$check = $this->version_compare2($version,$cpe->versionEndIncluding,$flag);

						if(($check==0)|| ($check<0))
						{
							$matched_versions = 'versionEndIncluding:'.$cpe->versionEndIncluding;
							//echo " Fourth\r\n";
							$passed++;
						}
						else
						{
							//echo " Failed ".$check."\r\n";
							$failed++;
						}
						//if($flag == 1)
						//	dd("break");
					}
					//echo "===========>".$failed." ".$passed." ".$version."<br>";
					
					if($failed > 0)
					{
						$obj->package_match = $cpeproduct;
						$obj->version_match = '';
						//return $obj;
					}
					else if($passed > 0)
					{
						$obj->package_match = $cpeproduct;
						$obj->version_match = $matched_versions;
						return $obj;
					}
					if($rangecheckpresent == 0)
					{
						if($this->version_compare2($version,$cpeversion)==0)
						{
							$obj->package_match = $cpeproduct;
							$obj->version_match = $cpe->cpe23Uri;
							//$cpe->cpe23Uri
							return $obj;
						}
						if($cpeversion == '*')
						{		
							$obj->package_match = $cpeproduct;
							$obj->version_match = '*';
							//$cpe->cpe23Uri
							return $obj;
						}
						if($cpeversion == '-')
						{
							
							$obj->package_match = $cpeproduct;
							//$obj->version_match = '-';
							//$cpe->cpe23Uri
							//return $obj;
						}
					}
					//$obj->package_match = $cpeproduct;
					//$obj->version_match = '';
					//dvultype.package = 'MATCH';
					//dvultype.version = 'NOT_MATCH;
					//if($obj->version_match != '')
					//	return $obj;
				}
			}
		}
		return $obj;
	}
	private function DetermineVulType($cve,$packagename,$versionnumber,$aliases)
	{
		
		$obj = new \StdClass();
		$obj->vendor_match = '';
		$obj->package_match = '';
		$obj->version_match = '';
		$debug=0;
		//dd($versionnumber);
		//if($cve->cve->CVE_data_meta->ID == 'CVE-2009-2044')
		//	dd($cve);
		for($i=0;$i < count($cve->configurations->nodes);$i++)
		{
			//if($cve->cve->CVE_data_meta->ID == 'CVE-2009-2044')
			//	dd( $cve->configurations->nodes[$i]);

			$node = $cve->configurations->nodes[$i];
			
			if($node->operator == 'OR')
			{
				$obj = $this->ProcessImpactNode($node->cpe_match,$packagename,$versionnumber,$obj,$aliases);
				if($obj->version_match != '')
					return $obj;
			}
			else if($node->operator == 'AND')
			{
				if(isset($node->cpe_match))
				{
					$obj = $this->ProcessImpactNode($node->cpe_match,$packagename,$versionnumber,$obj,$aliases);
					if($obj->version_match != '')
						return $obj;
				}
				else
				{
					for($j=0;$j<count($node->children);$j++)
					{
						if(isset($node->children[$j]->cpe_match))
						{
							$obj = $this->ProcessImpactNode($node->children[$j]->cpe_match,$packagename,$versionnumber,$obj,$aliases);
							if($obj->version_match != '')
								return $obj;
						}
					}
				}
			}
		}
		if($obj->vendor_match=='' and $obj->package_match=='')
			return null;
		return $obj;
	}	
	function version_compare2($a, $b,$debug=0) 
	{ 
		
		$msg = "Comparing ".$a." with ".$b;
		//echo $msg;
		//SendConsole(time(),$msg); 
		//$a = explode(".", str_replace(".0",'',$a)); //Split version into pieces and remove trailing .0 
		//$b = explode(".", str_replace(".0",'',$b)); //Split version into pieces and remove trailing .0 
	
		$a = explode(".", str_replace(".0",'',$a)); //Split version into pieces and remove trailing .0 
		$b = explode(".", str_replace(".0",'',$b)); //Split version into pieces and remove trailing .0 

		//$a = explode(".", rtrim($a, ".0")); //Split version into pieces and remove trailing .0 
		//$b = explode(".", rtrim($b, ".0")); //Split version into pieces and remove trailing .0 
		//if($debug)
		//	dd($b);
		//dd($b);
		//SendConsole(time(),print_r($a)."--".print_r($b)); 
						
		foreach ($a as $depth => $aVal) 
		{ //Iterate over each piece of A 
			$aVal = trim($aVal);
			if (isset($b[$depth])) 
			{ //If B matches A to this depth, compare the values 
				$b[$depth] = trim($b[$depth]);
				if ($aVal > $b[$depth]) 
				{
					//echo "[".$aVal."]".">"."[".$b[$depth]."]\r\n";
					//echo gettype($aVal).">".gettype($b[$depth])."\r\n";
					return 1; //Return A > B 
				}
				else if ($aVal < $b[$depth]) return -1; //Return B > A 
				//An equal result is inconclusive at this point 
			} 
			else 
			{ //If B does not match A to this depth, then A comes after B in sort order 
	
				return 1; //so return A > B 
			} 
		} 
		//At this point, we know that to the depth that A and B extend to, they are equivalent. 
		//Either the loop ended because A is shorter than B, or both are equal. 
		return (count($a) < count($b)) ? -1 : 0; 
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
	function Import()
	{
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
		$package = $this->option('package');
		$version = $this->option('ver');
		$this->Init();
		
		$cves = $this->GetCVEs($package,$version);
		foreach($cves as $cve)
		{
			echo $cve->cve." ".$cve->type->version_match."\n";
		}
		
		//$this->Import();
    }
}
