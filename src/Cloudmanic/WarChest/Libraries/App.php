<?php 
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 03/23/2012
//

namespace Cloudmanic\WarChest\Libraries;

class App
{
	private static $data = [];
	private static $configs_file = '';
	private static $configs_loaded = false;

	//
	// Set config file.
	//
	public static function set_config_file($path)
	{
		static::$configs_file = $path;
	}

	//
	// Load app configs.
	//
	public static function load_configs()
	{
		if(! is_file(self::$configs_file))
		{
			self::$configs_loaded = true;
			return false;
		}
	
		$configs = include(self::$configs_file);
		
		foreach($configs AS $key => $row)
		{
			self::$data[$key] = $row;
		}
		
		self::$configs_loaded = true;
	}
	
	//
	// Get database configs.
	//
	public static function get_db($domain, $key)
	{
		// If first time load configs.
		if(! self::$configs_loaded)
		{
			self::load_configs();
		}
	
		if(isset(self::$data[$domain][$key]))
		{
			return self::$data[$domain][$key];
		}	
		
		return '';
	}

	//
	// Get logged in user.
	//
	public static function get($key = null)
	{
		if(! is_null($key))
		{
			return (isset(self::$data[$key])) ? self::$data[$key] : '';
		}		
	
		return self::$data;
	}

	//
	// Set logged in user.
	//
	public static function set($key, $data)
	{	
		self::$data[$key] = $data;
	}
}

/* End File */