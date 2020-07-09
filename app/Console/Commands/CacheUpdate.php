<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use App\SVM;
use App\Products;
use App\CVE;
use App;
use Artisan;
use App\Cache;
class CacheUpdate extends Command
{

	protected $signature = 'cache:update';

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
	
    public function handle()
    {
		$data = [];
		$p = new Products();
		$group_names = $p->GetGroupNames();
		$product_names = [];
		$version_names = [];
		foreach($group_names as $group_name)
		{
			$group =  new \StdClass();
			$group->name = $group_name;
			$group->products = [];
			$productnames = $p->GetProductNames($group_name);
			foreach($productnames as $productname)
			{
				$product =  new \StdClass();
				$product->name = $productname;
				$product->versions = [];
				$group->products[] = $product;
				$version_names[] = $p->GetVersionNames($group_name,$productname);
				foreach($version_names as $version_name)
				{
					foreach($version_name as $name)
					{
						$version =  new \StdClass();
						$version->name = $name;
						$product->versions[] = $version;
						$this->GetCVEs($group->name,$product->name,$version->name);
					}
				}
			}
			$data[] = $group;
		}
		Cache::SaveStaticProductData(json_encode($data));
		
		//$products = new Products();
		//$products->CacheUpdate();
        //$cve = new CVE();
		//$cve->CacheUpdate();
		
    }
	public function GetCVEs($group='all',$product='all',$version='all',$admin='all')
	{
		$static_file_name = $group."_".$product."_".$version;
		$p = new Products();
		$group = $group=='all'?null:$group;
		$product = $product=='all'?null:$product;
		$version = $version=='all'?null:$version;
		$admins = $admin=='all'?null:$admin;
		$ids = $p->GetIds($group,$product,$version,$admin);
		sort($ids);
		$key = md5(implode(",",$ids));
		$data = null;
		$c =  new CVE();
		$data = $c->GetPublished($ids);
		
		Cache::Save($key,json_encode($data));
		Cache::SaveStaticPage($static_file_name,json_encode($data));
		
		return $data;
    }
}
