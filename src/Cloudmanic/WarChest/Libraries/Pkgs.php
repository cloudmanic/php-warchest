<?php
//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
// Date: 11/28/2013
//

namespace Cloudmanic\WarChest\Libraries;

error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');

class Pkgs
{
	//
	// Run.
	//
	public function run($path)
	{
		// Change directories.
		chdir($path);
		
		// Get configs
		$config = require('pkg_list.php');
		$dir = $config['dir'];
		
		// Check to see if our package dir is created.
		if(! is_dir($dir))
		{
			mkdir($dir);
		}
		
		// Loop through our repos and install or update.
		foreach($config['repos'] AS $key => $row)
		{
			$name = str_ireplace('.git', '', basename($key));
		
			// Check to see if we need to checkout or update.
			if(! is_dir("$dir/$name"))
			{
				echo exec("cd $dir && git clone $key && git checkout $row");	
			} else
			{
				echo exec("cd $dir/$name && git pull origin $row");
			}
		}
		
		// We are done.
		echo "\nSuccess!!\n";	
	}
}

/* End File */