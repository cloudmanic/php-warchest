<?php 
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 3/12/2012
//

namespace Cloudmanic\WarChest\Libraries;

use Illuminate\Http\Request;

class Events
{
	private static $_queue = 'centcom';
	private static $_queue_ip = '127.0.0.1';
	private static $_appcode = null;
	private static $_account_id = null;

	//
	// Set the queue IP address.
	//
	public static function set_queue_ip($ip)
	{
		static::$_queue_ip = $ip;
	}

	//
	// Set the application code.
	//
	public static function set_app_code($code)
	{
		static::$_appcode = $code;
	}
	
	//
	// Set the account id.
	//
	public static function set_account_id($id)
	{
		static::$_account_id = $id;
	}

	//
	// Send a realtime datestamp.
	//
	public static function send_realtime($model)
	{
		$pheanstalk = new \Pheanstalk_Pheanstalk('127.0.0.1');
		
		// We can override the account id. 
		if(is_null(static::$_account_id))
		{
			static::$_account_id = Me::get_account_id();
		}		
		
		$data = [
			'time' => time(),
			'name' => $model, 
			'AccountsId' => static::$_account_id
		];
		
		$pheanstalk->useTube('realtime')->put(json_encode($data));
		
		return true;
	}

	//
	// Send our event and its properties out via our message queue.
	//
	public static function send($event, $props = array())
	{
		// Log the type of connection in the props.
		if(App::get('connection'))
		{
			$props['connection'] = App::get('connection');
		}
	
		// Event data.
		$event = [
			'EventsName' => trim($event),
			'IpAddressesName' => (isset($_SERVER["REMOTE_ADDR"])) ? $_SERVER["REMOTE_ADDR"] : '127.0.0.1',
			'EventLogAccountId' => Me::get_account_id(),
			'Props' => static::_merge_common($props)
		];
		
		// The application code is per platform.
		if(! is_null(static::$_appcode))
		{
			$event['ApplicationsCode'] = static::$_appcode;
		} else if(class_exists('\Config'))
		{
			$event['ApplicationsCode'] = \Config::get('site.app_code');
		}
		
		// Get the ip address of the queue server. First we check to see if the 
		// default queue_ip has not already changed via set_queue_ip() then
		// we check to see if this is an instance of Laravel. If so we 
		// can pull the ip address from site.centom_queue_ip.
		if((self::$_queue_ip == '127.0.0.1') && class_exists('\Config') && \Config::get('site.centom_queue_ip'))
		{
			self::$_queue_ip = \Config::get('site.centom_queue_ip');
		}
		
		// We can override the account id. 
		if(! is_null(static::$_account_id))
		{
			$event['EventLogAccountId'] = static::$_account_id;
		}
		
		// Setup the message we are going to send to the message queue.
		$msg = [
			'job' => 'Queue\Events', 
			'data' => [ 'task' => 'add-event', 'event' => $event ]
		];
	
		// Setup and send the message to the message queue.
		$pheanstalk = new \Pheanstalk_Pheanstalk(self::$_queue_ip);
		$pheanstalk->useTube(self::$_queue)->put(json_encode($msg));
	
		return true;
	}
	
	// ------------------ Private Functions ---------------- //
	
	//
	// Merge common get / post params.
	//
	private static function _merge_common($parms)
	{
		$input = new Request($_GET, $_POST);		
		
		if($input->get('app_version'))
		{
		  $parms['app_version'] = $input->get('app_version');
		}
		
		if($input->get('plat_model'))
		{
		  $parms['plat_model'] = $input->get('plat_model');
		}
		
		if($input->get('plat_name'))
		{
		  $parms['plat_name'] = $input->get('plat_name');
		}
		
		if($input->get('plat_version'))
		{
		  $parms['plat_version'] = $input->get('plat_version');
		}
		
		if($input->get('plat_osname'))
		{
		  $parms['plat_osname'] = $input->get('plat_osname');
		}
		
		if($input->get('plat_type'))
		{
		  $parms['plat_type'] = $input->get('plat_type');
		}	else if(! $input->get('access_token'))
		{
			$parms['plat_type'] = 'website';
		}
		
		return $parms;
	}
}

/* End File */