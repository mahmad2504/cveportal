<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use App\svm;
use App\Products;
use App;
use Artisan;
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
	public function rrmdir($dir) 
	{
		if (is_dir($dir)) 
		{
			$objects = scandir($dir);
			foreach ($objects as $object) 
			{
				if ($object != "." && $object != "..") 
				{
					if (filetype($dir."/".$object) == "dir") 
						$this->rrmdir($dir."/".$object); 
					else 
						unlink   ($dir."/".$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}
    public function Init()
    {
		ini_set("memory_limit","2000M");
		set_time_limit(2000);
		
		$this->rrmdir($this->cache_datafolder);
		
		if(!file_exists($this->datafolder))
			mkdir($this->datafolder, 0, true);
		if(!file_exists($this->cache_datafolder))
			mkdir($this->cache_datafolder, 0, true);
		
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
    public function handle()
    {
        $products = new Products();
		$products = $products->Get();
		$svm = new SVM();
		
		foreach($products as $product)
		{
			if($svm->ImportProduct($product->id)==-1)
				echo $product->name." with id=".$product->id." Does not exist";
		
		}
		
		
    }
}
