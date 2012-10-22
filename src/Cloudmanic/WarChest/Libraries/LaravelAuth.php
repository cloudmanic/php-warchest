<?php
//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
// Date: 10/17/2012
// Description: Most of this code is taking from the Laravel "Fluent" Auth Driver.
//

namespace Cloudmanic\WarChest\Libraries;

use Laravel\Database as DB;
use Laravel\Config as Config;
use Laravel\Hash as Hash;

class LaravelAuth extends \Laravel\Auth\Drivers\Driver
{
	/**
	 * Get the current user of the application.
	 *
	 * If the user is a guest, null should be returned.
	 *
	 * @param  int  $id
	 * @return mixed|null
	 */
	public function retrieve($id)
	{
		if (filter_var($id, FILTER_VALIDATE_INT) !== false)
		{
			// We want to return the object as an array.
			$current = Config::get('database.fetch');
			Config::set('database.fetch', \PDO::FETCH_ASSOC);
			
			// Grab user and return fetch to what it was before the query.
			$user = DB::table(Config::get('auth.table'))->where('UsersId', '=', $id)->first();
			Config::set('database.fetch', $current);
			
			return $user;
		}
	}

	/**
	 * Attempt to log a user into the application.
	 *
	 * @param  array $arguments
	 * @return void
	 */
	public function attempt($arguments = array())
	{
		$user = $this->get_user($arguments);

		// If the credentials match what is in the database we will just
		// log the user into the application and remember them if asked.
		$password = $arguments['password'];

		$password_field = Config::get('auth.password', 'password');

		if ( ! is_null($user) and Hash::check($password, $user->{$password_field}))
		{
			return $this->login($user->UsersId, array_get($arguments, 'remember'));
		}

		return false;
	}

	/**
	 * Get the user from the database table.
	 *
	 * @param  array  $arguments
	 * @return mixed
	 */
	protected function get_user($arguments)
	{
		$table = Config::get('auth.table');

		return DB::table($table)->where(function($query) use($arguments)
		{
			$username = Config::get('auth.username');
			
			$query->where($username, '=', $arguments['username']);

			foreach(array_except($arguments, array('username', 'password', 'remember')) as $column => $val)
			{
			    $query->where($column, '=', $val);
			}
		})->first();
	}
}
