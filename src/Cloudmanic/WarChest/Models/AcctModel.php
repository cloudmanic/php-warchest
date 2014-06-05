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

use \Config;
use Illuminate\Support\Facades\DB as DB;
use Cloudmanic\WarChest\Libraries\Me as Me;
use Cloudmanic\WarChest\Models\DeleteLog as DeleteLog;

class AcctModel
{	
	public static $joins = null;
	public static $_table = null;
	public static $connection = 'mysql';
	public static $delete_log = false;
	protected static $query = null;
	protected static $_extra = true;
	private static $_with = array();
	protected static $_is_api = false;	
	
	// ------------------------ Setters ------------------------------ //

	//
	// Set API call.
	//
	public static function set_api($action)
	{
		static::$_is_api = $action;
	}

	//
	// Make it so it does not load the other models.
	//
	public static function set_no_extra()
	{
		self::$_extra = false;
	}
	
	//
	// We want all the extra data.
	//
	public static function set_extra()
	{
		self::$_extra = true;
	}
 
 	//
 	// Set since a particular date.
 	//
 	public static function set_since($timestamp)
 	{
	 	$stamp = date('Y-m-d H:i:s', strtotime($timestamp));
	 	self::get_query()->where(static::$_table . 'UpdatedAt', '>=', $stamp);
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
	public static function set_col($key, $value, $action = '=')
	{
		self::get_query()->where($key, $action, $value);
	}
	
	//
	// Set Column OR.
	//
	public static function set_or_col($key, $value)
	{
		self::get_query()->orWhere($key, '=', $value);
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
		self::get_query()->join($table, $left, '=', $right);
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
		
		// Set the account.
		self::set_col(static::$_table . 'AccountId', Me::get_account_id());
		
		// Query
		$data = self::get_query()->get();
		
		// Convert to an array because we like arrays better.
		$data = static::_obj_to_array($data);
		
		// Clear query.	
		$table = static::$_table;
		static::clear_query();
		
		// Remove any unwanted columns.
		if(isset(static::$remove) && is_array(static::$remove))
		{
			// Loop through data and format.
			foreach($data AS $key => $row)
			{
				foreach($row AS $key2 => $row2)
				{
					if(in_array($key2, static::$remove))
					{
						unset($data[$key][$key2]);
					}
				}
			}
		}
		
		// An option formatting function call.
		if(method_exists($table, '_format_get'))
		{	
			// Loop through data and format.
			foreach($data AS $key => $row)
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
	
		// Add updated at date
 		if(! isset($data[self::$_table . 'UpdatedAt'])) 
 		{
 			$data[self::$_table  . 'UpdatedAt'] = date('Y-m-d G:i:s');
 		}
 		
		// Add created at date
 		if(! isset($data[self::$_table . 'CreatedAt'])) 
 		{
 			$data[self::$_table  . 'CreatedAt'] = date('Y-m-d G:i:s');
 		}
 		
		// Set the account.
		$data[self::$_table . 'AccountId'] = Me::get_account_id();
	
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
		// Make sure we have a query started.
		self::get_query();
	
		// Add updated at date
 		if(! isset($data[self::$_table . 'UpdatedAt'])) 
 		{
 			$data[self::$_table  . 'UpdatedAt'] = date('Y-m-d G:i:s');
 		}
 		
 		// Set the account.
		self::set_col(self::$_table . 'AccountId', Me::get_account_id());
	
 		// Run and clear query.
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
		
		// Set the account.
		self::set_col(self::$_table . 'AccountId', Me::get_account_id());
		
 		// Delete entry and clear query.
 		$table = self::$_table;
		self::get_query()->where(self::$_table . 'Id', '=', $id)->delete();
		self::clear_query();
		
		// Do we add this to the delete log.
		if(static::$delete_log)
		{
			DeleteLog::insert(array(
				'DeleteLogTable' => $table,
				'DeleteLogTableId' => $id
			));
		}
	}
	
	//
	// Delete all data.
	//
	public static function delete_all()
	{
		// We do this so we can set the table.
		self::get_query();
	
		// Set the account.
		self::set_col(self::$_table . 'AccountId', Me::get_account_id());
	
		self::get_query()->delete();
		self::clear_query();
	}
	
	//
	// Get count.
	//
	public static function get_count()
	{
		// Do we have joins?
		if(! is_null(static::$joins))
		{
			foreach(static::$joins AS $key => $row)
			{
				static::set_join($row['table'], $row['left'], $row['right']);
			}
		}
	
		$count = self::get_query()->count();
		self::clear_query();
		return $count; 
	}
	
	// ----------------- Helper Function  -------------- //
		
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
	
	//
	// Get last query.
	//
	public static function get_last_query()
	{
		$queries = DB::getQueryLog();
		return end($queries);
	}
	
	//
	// Clear a query. Typically run this after the 
	// database action is complete.
	//
	protected static function clear_query()
	{
		static::$query = null;
		static::$_table = null;
	}
	
	//
	// If we have a query already under way return it. If not 
	// build the query and return a new object.
	//	
	protected static function get_query()
	{
		if(is_null(static::$query))
		{
			if(is_null(static::$_table))
			{
				$table = explode('\\', get_called_class());
				static::$_table = end($table);			
			}
			
			static::$query = DB::connection(static::$connection)->table(static::$_table);
			return static::$query;
		} else
		{
			return static::$query;
		}
	}
	
 	//
 	// This will take the post data and filter out the non table cols.
 	//
	private static function _set_data($data)
 	{
 		$q = array();
 		$fields = DB::connection(static::$connection)->select('SHOW COLUMNS FROM ' . static::$_table);
 		
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