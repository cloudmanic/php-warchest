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

use \Users;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Input;
use Cloudmanic\WarChest\Models\Accounts\Accounts;
use Cloudmanic\WarChest\Models\Accounts\OauthSess;

class CloudAuth
{
	public static $account = null;
	public static $error = '';
	
	//
	// Authenicate an API call. Set the "Me" object if 
	// this authentication was a success.
	//
	public static function apiinit()
	{		
		// Setup vars.
		$access_token = null;
		$account_id = null;
			
		// First we see if we have passed in access token
		// and account_id. If not we check for a session.
		if(Input::get('access_token') && Input::get('account_id'))
		{
			$access_token = Input::get('access_token');
			$account_id = Input::get('account_id');
			App::set('connection', 'access_token');
		} else if(Session::has('access_token') && Session::has('account_id'))
		{
			$access_token = Session::get('access_token');
			$account_id = Session::get('account_id');
			App::set('connection', 'web');			
		} else if(isset($_COOKIE[Config::get('session.cookie') . '-ci']))
		{
			// NOTE: We only check for CI sessions on API calls (aka ajaxs calls from 
			// the CI app). In a normal controller we just use the sessioninit flow.
		
			// Check to see if we have a session.
			if($ci_sess = static::_get_ci_session())
			{
				$access_token = $ci_sess['access_token'];
				$account_id = $ci_sess['account_id'];
				App::set('connection', 'web');
			} else
			{
				static::$error = 'failed access_token (api): no session found.';
				return false;				
			}	
		} else 
		{
			static::$error = 'failed access_token (api): no session found.';
			return false;			
		}

		// Validate and get the user based on the access_token / account_id
		if(! static::validate_access_token($access_token, $account_id))
		{
			static::$error = 'validate_access_token (api): failed to validate.';
			return false;
		}
		
		// Set last activity
		static::set_last_activity();

		// If we made it this far we know we are good.
		return true;
	} 	
	
	//
	// Set last activity.
	//
	public static function set_last_activity()
	{
		// Update the user last activty.	
		$q = [ 'UsersLastActivity' => date('Y-m-d G:i:s') ];
    DB::table('Users')->where('UsersId', Me::get('UsersId'))->update($q);
		
		// Here we log the application usage. Just update the timestamp
		// in the applications database of the last time this app was accessed.
		$r = [ 'AccountsLastActivity' => $q['UsersLastActivity'] ];
		Accounts::update($r, Me::get_account_id());
	}
	
	//
	// Validate the access token and set the "Me" object.
	//
	public static function validate_access_token($access_token, $account_id)
	{	
		// Make sure the access token is still valid.
		OauthSess::set_col('OauthSessStage', 'Granted');
		OauthSess::set_col('OauthSessTokenExpires', time(), '>');
		OauthSess::set_col('OauthSessToken', $access_token);
		if(! $sess = OauthSess::get())
		{
			Session::flush();
			return false;
		}
		
		// Make sure this user still has access to the account being requested.
		if($sess[0]['UsersAccountId'] != $account_id)
		{
			Session::flush();
			return false;			
		}
		
/*
		AcctUsersLu::set_col('AcctUsersLuUserId', $sess[0]['OauthSessUserId']);
		AcctUsersLu::set_col('AcctUsersLuAcctId', $account_id);
		if(! $lu = AcctUsersLu::get())
		{
			Session::flush();
			return false;
		}
*/
		
		// Make sure this is still a valid user.
		$user = (array) DB::table('Users')->where('UsersId', $sess[0]['OauthSessUserId'])->first();
		if(! $user)
		{
			Session::flush();
			return false;
		}
		
		// Make sure the account is still active.
		if(! $account = Accounts::get_by_id($account_id))
		{
			Session::flush();
			return false;
		}
		
		// Set the "Me" Object.		
		$user['access_token'] = $access_token;
		Me::set($user);
		Me::set_account($account);
		
		// If we made it this far we are good.
		return true;
	}	
	
	// -------------------- Private Helper Functions ------------- //
	
	//
	// Check to see if we have a session on a Codeigniter app. 
	//
	private static function _get_ci_session()
	{
		if(isset($_COOKIE[Config::get('session.cookie') . '-ci']))
		{
			$ci = unserialize($_COOKIE[Config::get('session.cookie') . '-ci']);
	
			if(isset($ci['session_id']))
			{
				$ci_sess = $ci['session_id'];
				
				// Query the database and get the session data.
				if($sess = DB::table('CiSessions')->where('session_id', '=', $ci_sess)->first())
				{
					$user_data = unserialize($sess->user_data);
					
					// Look in a few places for the access token.
					if(isset($user_data['AccessToken']))
					{
						$access_token = $user_data['AccessToken'];
					} 
					
					if(isset($user_data['LoggedIn']) && isset($user_data['LoggedIn']['AccessToken']))
					{
						$access_token = $user_data['LoggedIn']['AccessToken'];					
					}
					
					if(isset($user_data['LoggedIn']) && isset($user_data['LoggedIn']['UsersAccessToken']))
					{
						$access_token = $user_data['LoggedIn']['UsersAccessToken'];					
					}
					
					// Grab the account id.
					if(isset($user_data['AccountsId']))
					{
						$account_id = $user_data['AccountsId'];
					}
					
					// Return the access token and account id.
					if(isset($account_id) && isset($access_token))
					{
						return [
							'account_id' => $account_id,
							'access_token' => $access_token
						];
					}
				}
			}
		}
		
		// If we made it this far we have no session.
		return false;
	}	
}

/* End File */