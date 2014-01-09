<?php
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 3/12/2013
//

namespace Cloudmanic\WarChest\Models\Accounts;

class OauthSess extends \Cloudmanic\WarChest\Models\BasicModel
{		
	public static $joins = [
		[ 'table' => 'Users', 'left' => 'OauthSessUserId', 'right' => 'UsersId' ]
	];
	
	//
	// Get By Access Token.
	//
	public static function get_by_access_token($token)
	{
		static::set_col('OauthSessToken', $token);
		$rt = static::get();
		return (isset($rt[0])) ? $rt[0] : false;
	}
}

/* End File */