<?php

namespace App\Http\Controllers;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;

use Auth;
use Illuminate\Http\Request;
use Response;
use App\Products;
use App\CVEStatus;
use App\CVE;
use App\Cache;
use App\Ldap;
use Artisan;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
		
    }
	
	public function GetCVEs($group='all',$product='all',$version='all',$admin='all')
	{
		$p = new Products();
		$group = $group=='all'?null:$group;
		$product = $product=='all'?null:$product;
		$version = $version=='all'?null:$version;
		$admins = $admin=='all'?null:$admin;
		$ids = $p->GetIds($group,$product,$version,$admin);
		sort($ids);
		$key = md5(implode(",",$ids));
		$data = Cache::Load($key);
		if($data==null)
		{
			$c =  new CVE();
			$data = $c->Get($ids);
			Cache::Save($key,json_encode($data));
		}
		return $data;

	}
	public function CveStatusUpdate(Request $request)
	{
		$data = $request->session()->get('data');
		if($data == null)
			return Response::json(['error' => 'Un Authorized access'], 404); 
		if(!isset($data->user_name))
			return Response::json(['error' => 'Un Authorized access'], 404); 
		
		$p = new Products();
		$group = $request->group=='all'?null:$request->group;
		$product = $request->product=='all'?null:$request->product;
		$version = $request->version=='all'?null:$request->version;
		$ids = $p->GetIds($group,$product,$version);
		sort($ids);
		$key = md5(implode(",",$ids));
		
		$cvestatus = new CVEStatus();
		$cvestatus->UpdateStatus($request->status);
		
		Cache::Clean($key);
		return ["status"=>"success"];
	}
	public function Index()
	{
		$p = new Products();
		$group_names = $p->GetGroupNames();
		$product_names = [];
		$version_names = [];
		foreach($group_names as $group_name)
		{
			$productnames = $p->GetProductNames($group_name);
			foreach($productnames as $productname)
			{
				$version_names[] = $p->GetVersionNames($group_name,$productname);
			}
			$product_names[] = $productnames;
		}
		return view('home',compact('group_names','product_names','version_names'));
	}
	public function Logout(Request $request)
	{
		$request->session()->forget('data');
		echo "Your are logged out of system";
	}
	public function Login(Request $request)
	{
		return view('login');
	}
	public function Authenticate(Request $request)
	{
		//dump($request->data);
		if(!isset($request->data['USER'])||!isset($request->data['PASSWORD']))
			return Response::json(['error' => 'Invalid Credentials'], 404); 
		$ldap =  new Ldap();
		$data = $ldap->Login($request->data['USER'],$request->data['PASSWORD']);
		if($data== null)
		{
			$request->session()->forget('data');
			return Response::json(['error' => 'Invalid Credentials'], 404); 
		}
		else
			$request->session()->put('data', $data);
		return [];
		//return $data->user_displayname;
	}
	public function Triage(Request $request)
	{
		
		$data = $request->session()->get('data');
		if($data == null)
			return view('login');
		if(!isset($data->user_name))
			return view('login');
		
		$p = new Products($data->user_name);
		$group_names = $p->GetGroupNames();
		$product_names = [];
		$version_names = [];
		foreach($group_names as $group_name)
		{
			$productnames = $p->GetProductNames($group_name);
			foreach($productnames as $productname)
			{
				$version_names[] = $p->GetVersionNames($group_name,$productname);
			}
			$product_names[] = $productnames;
		}
		$displayname=$data->user_displayname;
		return view('triage',compact('displayname','group_names','product_names','version_names'));
	}
}
