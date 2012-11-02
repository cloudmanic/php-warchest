<?php
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 11/1/2012
// Note: Non-Cloudmanic Product Version.
//

namespace Cloudmanic\WarChest\Libraries;

class Users extends MyModel 
{
	//
	// Set access token.
	//
	public static function set_access_token($token)
	{
		self::set_col('UsersAccessToken', $token);
	}

	//
	// Get by access token.
	//
	public static function get_by_access_token($token)
	{
		self::set_access_token($token);
		$user = self::get();
		return (isset($user[0])) ? $user[0] : false;
	}
}

/* End File */