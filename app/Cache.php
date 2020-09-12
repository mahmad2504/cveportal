<?php
namespace App;
use App;
class Cache
{
	private static $cache_datafolder = "data/cache";

	public static function GetCacheFolder()
	{
		$folder = self::$cache_datafolder;
		if(!App::runningInConsole())
		{
			$folder = "../".self::$cache_datafolder;
		}
		if(!file_exists($folder))
			mkdir($folder, 0, true);
		return $folder;
	}
	public static function SaveStaticProduct($data)
	{
		$folder = self::GetCacheFolder();
		$filename=$folder."/static/product.json";
		if(!file_exists($folder."/static"))
			mkdir($folder."/static", 0, true);
		
		file_put_contents($filename,$data);
	}
	public static function SaveStaticProductData($data)
	{
		$folder = self::GetCacheFolder();
		$filename=$folder."/products.json";
		file_put_contents($filename,"var groups=".$data);
	}
	public static function SaveStaticPage($key,$data)
	{
		$folder = self::GetCacheFolder();
		$filename=$folder."/".$key.".html";
		$html = file_get_contents("index.template");
		
		$html = str_replace('PRODUCT_SPECIFIC_CODE','var data='.$data.";",$html);
		//dump($html);
		//$html = str_replace("\r\n",'',$html);
		//$html = str_replace("\t",'',$html);
		file_put_contents($filename,$html);
	}
	public static function Save($key,$data)
	{
		$folder = self::GetCacheFolder();
		$filename=$folder."/".$key;
		file_put_contents($filename,$data);
	}
	public static function SaveStatic($key,$data)
	{
		$folder = self::GetCacheFolder();
		$filename=$folder."/static/".$key;
		if(!file_exists($folder."/static"))
			mkdir($folder."/static", 0, true);
		
		file_put_contents($filename,$data);
	}
	public static function Load($key)
	{
		$folder = self::GetCacheFolder();
		$filename=$folder."/".$key;
	
		if(file_exists($filename))
			return file_get_contents($filename);
		else
			return null;
	}
	public static function Clean($key=null)
	{
		$folder = self::GetCacheFolder();
		/*if($key != null)
		{
			$filename = $folder."/".$key;
			echo $filename."\r\n";
			if(file_exists($filename))
				unlink($filename);
		}
		else
		{*/
			foreach(glob($folder.'/*') as $v)
			{
			   @unlink($v);
			}
		//}
	}
}
