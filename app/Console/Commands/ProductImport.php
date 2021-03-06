<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SVM;
use App\Products;
use App;
class ProductImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Product Name and Version in database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
		parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
	
    public function handle()
    {
		$products = new Products();
		$products->Import();
		//$products->CacheUpdate();
    }
}
