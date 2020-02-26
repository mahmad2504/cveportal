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
	public static function Save($key,$data)
	{
		$folder = self::GetCacheFolder();
		$filename=$folder."/".$key;
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
