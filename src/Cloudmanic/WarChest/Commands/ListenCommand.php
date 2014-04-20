<?php

namespace Cloudmanic\WarChest\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ListenCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cloudmanic:listen';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Listen for items in the message queue.';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->info('Starting message queue.');
		$this->info('Listening to tube: ' . \Config::get('queue.connections.beanstalkd.queue'));
		
		$string = 'php artisan queue:work --queue=%s --env=%s --sleep';
		$command = sprintf($string, \Config::get('queue.connections.beanstalkd.queue'), $this->option('env'));
		$process = new \Symfony\Component\Process\Process($command, './', null, null, 60);

		while(true)
		{
			$process->run(function ($type, $buffer) {
				echo $buffer;
			});
		}
	}
	
	// ------------------ Args ------------------------- //

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
		);
	}

}