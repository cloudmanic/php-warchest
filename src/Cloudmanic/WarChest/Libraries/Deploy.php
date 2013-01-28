<?php 
//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
// Date: 10/21/2012
//

namespace Cloudmanic\WarChest\Libraries;

class Deploy
{
	public $branch = 'master';
	public $hosts = array('web2.cloudmanic.com');
	public $remote_dir = '/var/www/dev.elevationfit.com';
	public $ssh_port = '9022';
	public $app_path = '../';
	public $laravel_migrate = true;

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
				echo exec("ssh -p $this->ssh_port $row 'cd $this->remote_dir && git pull origin $this->branch && php artisan migrate && composer.phar update && cd scripts && php pkg.php'") . "\n\n";
			} else
			{
				echo exec("ssh -p $this->ssh_port $row 'cd $this->remote_dir && git pull origin $this->branch && composer.phar update && cd scripts && php pkg.php'") . "\n\n";				
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
}

/* End File */