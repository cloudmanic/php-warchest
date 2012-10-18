<?php
//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
// Date: 10/7/2012
//

error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');

include_once './libs/curl.php';
include_once '../api-clients/skyclerk/v2/Skyclerk.php';
include_once '../api-clients/evermanic/v1/Evermanic.php';

class Cloudmanic_Unit_Tests
{
	protected $_methods = array();
	protected $_called_class = '';
	protected $curl = '';
	private static $_configs = array();
	private static $_paths = array('../../tests/system-pre/*', '../../tests/api/*', '../../tests/system-post/*');
	
	//
	// Construct.
	//
	function __construct()
	{
		$this->_called_class = get_called_class();
		$this->_methods = get_class_methods($this->_called_class);
		$this->curl = new Curl();
		$this->_run_tests();
	}
	
	//
	// Loop through all the methods in the test and run them.
	//
	private function _run_tests()
	{
		foreach($this->_methods AS $key => $row)
		{
			if(strpos($row, 'test_') === 0)
			{
				$this->{$row}();
			}
		}
	}
	
	// ---------------- Static Functions ------------------ //
	
	//
	// Run all tests.
	//
	public static function run_all()
	{
		// Run through the different unit testing paths.
		foreach(self::$_paths AS $key => $row)
		{
			$files = glob($row);
			foreach($files AS $key2 => $row2)
			{
				include($row2);
			}
		}		
	}

	//
	// Test to see if two vars are equal.
	//
	public static function assert_equal($var1, $var2, $msg)
	{
		if($var1 == $var2)
		{
			Cloudmanic_Unit_Tests::log_status('ok', $msg);
		} else
		{
			Cloudmanic_Unit_Tests::log_status('fail', $msg);
		}
	}	
	

	//
	// Test to see if a number is greater than zero.
	//
	public static function assert_greater_than_zero($num, $msg)
	{		
		if($num > 0)
		{
			Cloudmanic_Unit_Tests::log_status('ok', $msg);
		} else
		{
			Cloudmanic_Unit_Tests::log_status('fail', $msg);
		}
	}
	
	//
	// Test the number of fields returned in the user profile.
	//
	public static function assert_array_count($data, $count, $msg)
	{		
		if(count($data) == $count)
		{
			Cloudmanic_Unit_Tests::log_status('ok', $msg);
		} else
		{
			Cloudmanic_Unit_Tests::log_status('fail', $msg);
		}
	}
	
	//
	// Test to see if an array index exists.
	//
	public static function assert_array_key($data, $key, $msg)
	{
		if(isset($data[$key]))
		{
			Cloudmanic_Unit_Tests::log_status('ok', $msg);
		} else
		{
			Cloudmanic_Unit_Tests::log_status('fail', $msg);
		}
	}	
	
	//
	// Set a config value.
	//
	public static function set_config($key, $value)
	{
		self::$_configs[$key] = $value;
	}
	
	//
	// Get a config value.
	//
	public static function get_config($key)
	{
		return (isset(self::$_configs[$key])) ? self::$_configs[$key] : '';
	}
	
	//
	// Log success / failure.
	//
	public static function log_status($status, $msg)
	{
		echo "[$status]: $msg\n";
	}
}

// See if we are calling this directly from the CLI
$options = getopt('', array('run::'));
if(isset($options['run']))
{
	Cloudmanic_Unit_Tests::run_all();
}

/* End File */