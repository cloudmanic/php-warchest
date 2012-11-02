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

use \DB as DB;
use \Config as Config;

class MyModel
{	
	protected static $table = '';
	protected static $query = null;

	//
	// Construct.
	//
	function __construct()
	{
		// Set table
		$table = explode('\\', get_called_class());
		static::$table = end($table);
		
		parent::__construct();
	}
	
	// ------------------------ Setters ------------------------------ //
	
	//
	// Set order
	//
	public static function set_order($order, $sort = 'desc')
	{
		self::get_query()->order_by($order, $sort);
	}	
	
	//
	// Set Column.
	//
	public static function set_col($key, $value)
	{
		self::get_query()->where($key, '=', $value);
	}
	
	//
	// Set Columns to select.
	//
	public static function set_select($selects)
	{
		self::get_query()->select($selects);
	}
	
	// ------------------------ CRUD Functions ----------------------- //
	
	//
	// Get.
	// 
	public static function get()
	{	
 		// We want to return the object as an array.
		$current = Config::get('database.fetch');
		Config::set('database.fetch', \PDO::FETCH_ASSOC);
		
		// Make sure we have a query started.
		self::get_query();
		
		// Query
		$data = self::get_query()->get();
		
		// Clear query.		
		self::clear_query();
		Config::set('database.fetch', $current);
		
		// If the model has a _format_get function run it.
		if($data && method_exists(self::$table, '_format_get'))
		{
			$m = self::$table;
			foreach($data AS $key => $row)
			{
				$m::_format_get($data[$key]);
			}
		}
		
		return $data;
	} 
	
	//
	// Get by id.
	// 
	public static function get_by_id($id)
	{
		self::get_query();
		self::set_col(self::$table . 'Id', $id);
		$d = self::get();
		$data = (isset($d[0])) ? (array) $d[0] : false;
		self::clear_query();		
		return $data;
	} 
	
	//
	// Insert.
	//
	public static function insert($data)
	{
		// Make sure we have a query started.
		self::get_query();
	
		// Add created at date
 		if(! isset($data[self::$table . 'CreatedAt'])) 
 		{
 			$data[self::$table  . 'CreatedAt'] = date('Y-m-d G:i:s');
 		}
	
 		// Insert the data / clear the query and return the ID.
 		$id = self::get_query()->insert_get_id(self::_set_data($data));
 		self::clear_query();
 		return $id;
	}
	
	//
	// Update.
	//
	public static function update($data, $id)
	{	
		$rt = self::get_query()->where(self::$table . 'Id', '=', $id)->update(self::_set_data($data));
		self::clear_query();
		return $rt;
	}	

	//
	// Delete by id.
	//
	public static function delete($id)
	{
		// Make sure we have a query started.
		self::get_query();
		
 		// Delete entry and clear query.
		self::get_query()->where(self::$table . 'Id', '=', $id)->delete();
		self::clear_query();
	}
	
	//
	// Delete all data.
	//
	public static function delete_all()
	{
		self::get_query()->delete();
		self::clear_query();
	}
	
	//
	// Delete all the data. (we are filtering by account typically).
	//
	public static function delete_account()
	{
		// Make sure we have a query started.
		self::get_query();
	
		// Query.
		self::get_query()->delete();
 		
 		// Clear query.
 		self::clear_query();
	}
	
	// ----------------- Helper Function  -------------- //
	
	//
	// Get last query.
	//
	public static function get_last_query()
	{
		return end(DB::profile());
	}
	
	//
	// Clear a query. Typically run this after the 
	// database action is complete.
	//
	protected static function clear_query()
	{
		self::$query = null;
	}
	
	//
	// If we have a query already under way return it. If not 
	// build the query and return a new object.
	//
	protected static function get_query()
	{
		if(is_null(self::$query))
		{
			$table = explode('\\', get_called_class());
			self::$table = end($table);
			self::$query = DB::table(self::$table, 'mysql');
			return self::$query;
		} else
		{
			return self::$query;
		}
	}
	
 	//
 	// This will take the post data and filter out the non table cols.
 	//
	private static function _set_data($data)
 	{
 		$q = array();
 		$fields = DB::query('SHOW COLUMNS FROM ' . self::$table);
 		
 		foreach($fields AS $key => $row)
 		{ 
 			if(isset($data[$row->Field])) 
 			{
 				$q[$row->Field] = $data[$row->Field];
 			}
 		}
 		
 		return $q;
 	}
}

/* End File */