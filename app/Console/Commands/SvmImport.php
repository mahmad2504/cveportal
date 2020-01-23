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
	private $db = null;
	private $collectionname = "svm";
	private $collection = null;
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
		echo "Done";
		
    }
}
