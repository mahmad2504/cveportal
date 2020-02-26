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
		$products = new Products();
		$products->CacheUpdate();
        $cve = new CVE();
		$cve->CacheUpdate();
		
    }
}
