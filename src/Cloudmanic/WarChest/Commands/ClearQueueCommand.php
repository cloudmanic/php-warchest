<?php

namespace Cloudmanic\WarChest\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ClearQueueCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cloudmanic:clearqueue';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Clear all items in the message queue.';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->info('Clearing queue.');
		$this->info('Listening to tube: ' . \Config::get('queue.connections.beanstalkd.queue'));
		$this->info('Wait a bit and then hit control C.');
		
		// Setup and send the message to the message queue.
		$pheanstalk = new \Pheanstalk_Pheanstalk('127.0.0.1');
		while($job = $pheanstalk->watch(\Config::get('queue.connections.beanstalkd.queue'))
														->ignore('default')
														->reserve())
		{
			$pheanstalk->delete($job);
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