<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use App\Utility;
use Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\Widgets\MilestoneController;
use App\ProjectTree;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }
	public function Index()
	{
		$data = [
		 [
		  "id"=>"1",
		  "name"=>"MEL Flex",
		  "parent_id"=>"0"
		 ], 
		 [
		  "id"=>"2",
		  "name"=>"MEL Omni",
		  "parent_id"=>"0"
		 ], 
		 [
		  "id"=>"3",
		  "name"=>"MEL Nucleus",
		  "parent_id"=>"0"
		 ], 
		 [
		  "id"=>"4",
		  "name"=>"1.0",
		  "parent_id"=>"1"
		 ], 
		 [
		  "id"=>"5",
		  "name"=>"1.1",
		  "parent_id"=>"1"
		 ], 
		 [
		  "id"=>"6",
		  "name"=>"1.0.0",
		  "parent_id"=>"2"
		 ], 
		 [
		  "id"=>"7",
		  "name"=>"1.1.2",
		  "parent_id"=>"2"
		 ], 
		 [
		  "id"=>"8",
		  "name"=>"4.0",
		  "parent_id"=>"3"
		 ], 
		 [
		  "id"=>"9",
		  "name"=>"4.1",
		  "parent_id"=>"3"
		 ],
		 
		];
		return view('home',compact('data'));
	}
}
