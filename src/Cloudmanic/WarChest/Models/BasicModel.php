<?php
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 11/1/2012
// Note: Non-Cloudmanic Product Version.
//

namespace Cloudmanic\WarChest\Models;

use \DB as DB;
use \Config as Config;
use \Eloquent as Eloquent;

class BasicModel extends Eloquent
{	
	public static $joins = null;
	public static $with = array();
	protected static $query = null;
	
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
	// Set Column OR.
	//
	public static function set_or_col($key, $value)
	{
		self::get_query()->or_where($key, '=', $value);
	}
	
	//
	// Set Or Where In
	//
	public static function set_or_where_in($col, $list)
	{
		self::get_query()->or_where_in($col, $list);
	}
	
	//
	// Set Columns to select.
	//
	public static function set_select($selects)
	{
		self::get_query()->select($selects);
	}
	
	//
	// Set join
	//
	public static function set_join($table, $left, $right)
	{
		self::get_query()->left_join($table, $left, '=', $right);
	}	
	
	//
	// Set with
	//
	public static function set_with($with)
	{
		self::$with[] = $with;
	}	
	
	//
	// Clear with
	//
	public static function clear_with()
	{
		self::$with = array();
	}

	// ------------------------ CRUD Functions ----------------------- //
	
	//
	// Get.
	// 
	public static function get()
	{	
		$data = array();
				
		// Make sure we have a query started.
		self::get_query();
		
		// Do we have joins?
		if(! is_null(static::$joins))
		{
			foreach(static::$joins AS $key => $row)
			{
				static::set_join($row['table'], $row['left'], $row['right']);
			}
		}
		
		// Query
		$d = self::get_query()->get();
		
		// Clear query.		
		self::clear_query();
		
		// Loop through data and format.
		foreach($d AS $key => $row)
		{
			$data[] = $row->to_array();
			
			// An option formatting function call.
			if(method_exists(self::$table, '_format_get'))
			{
				static::_format_get($data[$key]);
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
	public static function delete_by_id($id)
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
			self::$query = new \Laravel\Database\Eloquent\Query(self::$table);
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