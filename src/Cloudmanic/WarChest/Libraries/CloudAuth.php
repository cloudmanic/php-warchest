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

use Laravel\Input as Input;
use Laravel\Session as Session;
use Laravel\URI as URI;
use Laravel\URL as URL;
use Laravel\Redirect as Redirect;
use Laravel\Response as Response;
use Laravel\Config as Config;

class CloudAuth
{
	public static $account = null;
	
	//
	// Authenticate this session. Set the "Me" object if 
	// this authentication was a success.
	//
	public static function sessioninit()
	{				
		// See if we have passed in a access_token and an account id.
		if(Input::get('access_token') && Input::get('account_id'))
		{
			$access_token = Input::get('access_token');
			$account_id = Input::get('account_id');
		} else
		{
			// See if we have a session. If Not do something about it.
			if((! Session::get('AccountId')) || (! Session::get('AccessToken')))
			{
				return Redirect::to(Config::get('cloudmanic.login_url'));
			} 
			
			$access_token = Session::get('AccessToken');
			$account_id = Session::get('AccountId');
		}

		// Is this a multi tenant setup? If so set the account.
		if(Config::get('cloudmanic.account'))
		{
			if(! self::$account = \Accounts::get_by_id($account_id))
			{
				$data = array('status' => 0, 'errors' => array());
				$data['errors'][] = 'Account not found.';
				return \Laravel\Response::json($data);		
			}
		}
		
		// Validate the access_token
		if(! $user = Users::get_by_access_token($access_token))
		{
			$data = array('status' => 0, 'errors' => array());
			$data['errors'][] = 'Access token not valid.';
			return \Laravel\Response::json($data);	
		} else
		{
			self::_do_user($user);
		}
	} 
	
	//
	// Setup the user. Set the "Me" object and anything else that has to 
	// happen when a user logs in.
	//
	private static function _do_user($user)
	{			
		Me::set($user);
	}
}

/* End File */