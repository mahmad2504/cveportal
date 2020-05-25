<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SVM;
use App\Products;

class SvmSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'svm:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Monitoring List Data from SVM';

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
		$products = $products->GetProducts();
		$svm = new SVM();
		set_time_limit(0);
		foreach($products as $product)
		{
			$components = $svm->Sync($product->id);
		}
    }
}
