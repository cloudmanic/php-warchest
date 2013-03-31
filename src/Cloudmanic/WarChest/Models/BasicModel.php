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

use Illuminate\Support\Facades\DB as DB;
use \Config as Config;

class BasicModel
{	
	public static $joins = null;
	public static $_table = null;
	private static $_with = array();
	public static $_connection = 'mysql';
	protected static $query = null;

	
	// ------------------------ Setters ------------------------------ //

	//
	// Set connection.
	//
	public static function set_connection($con)
	{
		self::$_connection = $con;
	}

	//
	// Set limit
	//
	public static function set_limit($limit)
	{
		self::get_query()->take($limit);
	}	
	
	//
	// Set offset
	//
	public static function set_offset($offset)
	{
		self::get_query()->skip($offset);
	}	
	
	//
	// Set order
	//
	public static function set_order($order, $sort = 'desc')
	{
		self::get_query()->orderBy($order, $sort);
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
		self::$_with[] = $with;
	}	
	
	//
	// Clear with
	//
	public static function clear_with()
	{
		self::$_with = array();
	}
	
	//
	// Set search.
	//
	public static function set_search($str)
	{
		// Place holder we should override this.
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
		$data = self::get_query()->get();
		
		// Convert to an array because we like arrays better.
		$data = self::_obj_to_array($data);
		
		// Clear query.		
		self::clear_query();
		
		// An option formatting function call.
		if(method_exists(self::$_table, '_format_get'))
		{
			$new = array();
			
			// Loop through data and format.
			foreach($data AS $key => $row)
			{
				$new[] = $row;
				static::_format_get($data[$key]);
			}
			
			// Return new formated data.
			return $new;
		}
		
		return $data;
	} 
	
	//
	// Get by id.
	// 
	public static function get_by_id($id)
	{
		self::get_query();
		self::set_col(self::$_table . 'Id', $id);
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
 		if(! isset($data[self::$_table . 'CreatedAt'])) 
 		{
 			$data[self::$_table  . 'CreatedAt'] = date('Y-m-d G:i:s');
 		}
 		
		// Add update at date
 		if(! isset($data[self::$_table . 'UpdatedAt'])) 
 		{
 			$data[self::$_table  . 'UpdatedAt'] = date('Y-m-d G:i:s');
 		}
	
 		// Insert the data / clear the query and return the ID.
 		$id = self::get_query()->insertGetId(self::_set_data($data));
 		self::clear_query();
 		return $id;
	}
	
	//
	// Update.
	//
	public static function update($data, $id)
	{	
		// Add update at date
 		if(! isset($data[self::$_table . 'UpdatedAt'])) 
 		{
 			$data[self::$_table  . 'UpdatedAt'] = date('Y-m-d G:i:s');
 		}
	
		$rt = self::get_query()->where(self::$_table . 'Id', '=', $id)->update(self::_set_data($data));
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
		self::get_query()->where(self::$_table . 'Id', '=', $id)->delete();
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
			self::$_table = end($table);
			self::$query = DB::connection(static::$_connection)->table(self::$_table);
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
 		$fields = DB::connection(static::$_connection)->select('SHOW COLUMNS FROM ' . self::$_table);
 		
 		foreach($fields AS $key => $row)
 		{ 
 			if(isset($data[$row->Field])) 
 			{
 				$q[$row->Field] = $data[$row->Field];
 			}
 		}
 		
 		return $q;
 	}
 	
 	//
 	// Convert the object the database returns to an array.
 	// Yes, PDO can return arrays, but Laravel really counts
 	// on objects instead of arrays.
 	//
 	private static function _obj_to_array($data)
 	{
	 	if(is_array($data) || is_object($data))
	 	{
		 	$result = array();
		 	foreach($data as $key => $value)
		 	{
			 	$result[$key] = self::_obj_to_array($value);
			}

			return $result;
		}
    
		return $data;
	}
}

/* End File */