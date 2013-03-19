<?php 
//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
// Date: 10/21/2012
//

// Please install https://github.com/mishoo/UglifyJS

namespace Cloudmanic\WarChest\Libraries;

define('RAXSDK_OBJSTORE_NAME','cloudFiles');
define('RAXSDK_OBJSTORE_REGION','DFW');

class Deploy
{
	public $branch = 'master';
	public $hosts = array('web2.cloudmanic.com');
	public $remote_dir = '/var/www/dev.elevationfit.com';
	public $ssh_port = '9022';
	public $css_js_file_dev = '../app/views/layouts/app-dev-css-js.php';
	public $css_js_file_prod = '../app/views/layouts/app-prod-css-js.php';
	public $uglifyjs = '../vendor/cloudmanic/php-warchest/src/Cloudmanic/WarChest/scripts/libs/node_modules/uglify-js/bin/uglifyjs';
	public $public_cache = '../public/cache';
	public $app_path = '../';
	public $laravel_migrate = true;
	public $cdn_key = '';
	public $cdn_user = '';
	public $cdn_container = '';
	public $cdn_url = '';
	public $prod_file_lines = array();

	//
	// Construct.
	//
	function __construct()
	{
		// Start deploying
		echo "\n###### Start Deploy #####\n";
	}

	// --------------- GIT Dealings ------------------ //
	
	//
	// Deploy to production servers. SSHs to each server and
	// does a git pull, followed by a composer.phar update
	//
	public function push()
	{
		// Minify the CSS / JS
		$this->combine_js();
		$this->build_prod_css_js();
	
		// Delete any files from git repo.
		$this->delete_files_from_commit();
	
		// Commit any changes
		echo "\n###### GIT Commiting #####\n";
		$comment = 'Deploy commit - ' . time() . ' - ' . exec("whoami") . ' - ' . exec("hostname");
		echo exec("cd $this->app_path && git add . && git commit -m '$comment' && git push origin $this->branch && cd scripts") . "\n";
		
		// Deploy to the servers.
		foreach($this->hosts AS $key => $row)
		{
			echo "\n#### Deploying $row #####\n";
			
			if($this->laravel_migrate)
			{
				echo exec("ssh -p $this->ssh_port $row 'cd $this->remote_dir && git pull origin $this->branch && php artisan migrate && composer.phar update && cd scripts && php pkgs.php'") . "\n\n";
			} else
			{
				echo exec("ssh -p $this->ssh_port $row 'cd $this->remote_dir && git pull origin $this->branch && composer.phar update && cd scripts && php pkgs.php'") . "\n\n";				
			}
		}
		
		// Done deploying
		echo "\n###### End Deploy #####\n";
	}

	//
	// Remove any files that were deleted but not removed from git.
	//
	public function delete_files_from_commit()
	{
		echo "\n###### GIT Deleting #####\n";
		
		$ob = exec("cd $this->app_path && git status | grep deleted");
		$files = explode("\n", $ob);
		
		foreach($files AS $key => $row)
		{
			if(empty($row))
			{
				continue;
			}	
		
			$file = str_ireplace('#	deleted:    ', '', $row);
			exec("cd $this->app_path && git rm '$file'");
		} 
	}
	
	// --------------- Deploy Functions ------------------- //
	
	// 
	// Build the production css / js file.
	//
	public function build_prod_css_js()
	{
		$str = '';

		foreach($this->prod_file_lines AS $key => $row)
		{
			$str .= $row . "\n";
		}
		
		file_put_contents($this->css_js_file_prod, $str);
	}
	
	// --------------- JS Dealings ------------------- //
	
	//
	// Combine the javascript
	//
	public function combine_js()
	{
		$master_js = '';
		$css_js = file_get_contents($this->css_js_file_dev);
		$lines = explode("\n", $css_js);
		
		foreach($lines AS $key => $row)
		{
			preg_match('<script.+src=\"(.+.js)\".+>', $row, $matches);
			if(isset($matches[1]) && (! empty($matches[1])))
			{
				$master_js .= file_get_contents('../public' . $matches[1]) . "\n\n"; 
			}
		}
		
		// If we have any new JS we build a new hash file for the JS.
		$hash = md5($master_js);
		if(! is_file("$this->public_cache/$hash.min.js"))
		{			
			echo "\n###### Combining JS Files ######\n";
			file_put_contents("$this->public_cache/$hash.js", $master_js);
			unset($master_js);
			
			echo "\n###### Compressing JS File ######\n";
			exec("$this->uglifyjs $this->public_cache/$hash.js -m -o $this->public_cache/$hash.min.js");
			unlink("$this->public_cache/$hash.js");
			
			// Upload to rackspace.
			echo "\n###### Uploading Combined JS To Rackspace ######\n";
			$this->rs_upload("$this->public_cache/$hash.min.js", "assets/js/$hash.min.js", 'application/x-javascript');
		}
		
		// Add javascript to the prod file. 
		$this->prod_file_lines[] = '<script type="text/javascript" src="' . $this->cdn_url . "assets/js/$hash.min.js" . '"></script>';
		//file_put_contents($this->css_js_file_prod, $tag);
		
		echo "\n";
	}
	
	// -------------------- CDN Functions ----------------- //
	
	//
	// Send file to rackspace.
	//
	public function rs_upload($file, $name, $type)
	{
		$connection = new \OpenCloud\Rackspace(RACKSPACE_US, array('username' => 'triadmin', 'apiKey' => '5b0bb7e23f5b3bb88cd35595c89d8167'));
		
		$ostore = $connection->ObjectStore();
		
		$cont = $ostore->Container('nationaldb.org');
		
		$obj = $cont->DataObject();
		
		$obj->Create(array('name' => $name, 'content_type' => $type), $file);
	}
	
	// -------------------- Framework Functions ----------------- //
	
	//
	// Load laravel. Pass in the path the config directory.
	//
	public function load_laravel($config_dir)
	{
		// If not directory error out.
		if(! is_dir($config_dir))
		{
			die('Laravel Config Directory Not Found');
		}
		
		// Load the site config.
		$config = include($config_dir . '/site.php');
		
		// Setup the configs.
		$this->cdn_key = (isset($config['rackspace_key'])) ? $config['rackspace_key'] : $this->cdn_key;
		$this->cdn_user = (isset($config['rackspace_username'])) ? $config['rackspace_username'] : $this->cdn_user;
		$this->cdn_container = (isset($config['rackspace_container'])) ? $config['rackspace_container'] : $this->cdn_container;
		$this->cdn_url = (isset($config['rackspace_url'])) ? $config['rackspace_url'] : $this->cdn_url;
	}
}

/* End File */