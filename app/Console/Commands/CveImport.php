<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use App\svm;
use App\Products;
use App\CVE;
use App\Cache;
use App;
use Artisan;
class CveImport extends Command
{

	protected $signature = 'cve:import';

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
        $cve = new CVE();
	$cve->import();
	Cache::Clean();
    }
}
