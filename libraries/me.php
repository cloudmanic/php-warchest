<?php 
//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
// Date: 10/7/2012
//

namespace Cloudmanic\Libraries;

class Me
{
	private static $data = array();

	//
	// Get one index in the data array.
	//
	public static function val($key)
	{
		return (isset(self::$data[$key])) ? self::$data[$key] : '';
	}

	//
	// Get logged in user.
	//
	public static function get()
	{
		return self::$data;
	}

	//
	// Set logged in user.
	//
	public static function set($data)
	{
		// Remove password hashes
		if(isset($data['UsersPassword']))
		{
			unset($data['UsersPassword']);
		}
	
		// Remove password salts
		if(isset($data['UsersSalt']))
		{
			unset($data['UsersSalt']);
		}
	
		self::$data = $data;
	}
}

/* End File */