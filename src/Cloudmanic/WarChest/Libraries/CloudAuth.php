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

use \Users as Users;
use Laravel\Hash as Hash;
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
				die(header('location: ' . Config::get('site.login_url')));
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
	// Logout.
	//
	public static function logout()
	{
		Session::flush();
	}
	
	//
	// Authenicate a session.
	//
	public static function auth($email, $pass)
	{	
		// Get user by email.
		if(! $user = Users::get_by_email($email))
		{	
			return false;
		}
		
		// Make sure the password is correct.
		if(Hash::check($pass, $user['UsersPassword']))
		{
			self::_do_user($user);
			Session::put('AccessToken', Users::get_access_token($user['UsersId']));
		
			// Get the default AccountId
			\AcctsUsersLu::set_col('AcctsUsersLuUserId', $user['UsersId']);
			\AcctsUsersLu::set_order('AcctsUsersLuAcctId');
			\AcctsUsersLu::set_limit(1);
			$lp = \AcctsUsersLu::get();
			
			// Make sure we have at least one account.
			if(! isset($lp[0]))
			{
				return false;
			}

			// Set default account
			Session::put('AccountId', $lp[0]['AcctsUsersLuAcctId']);
		
			return true;
		} 
		
		return false;
	}
	
	//
	// Setup the user. Set the "Me" object and anything else that has to 
	// happen when a user logs in.
	//
	private static function _do_user($user)
	{		
		// Set the user.
		unset($user['UsersPassword']);	
		Me::set($user);
		
		// Set the account.
		$account_id = Session::get('AccountId');
		Me::set_account(\Accounts::get_by_id($account_id));
	}
}

/* End File */