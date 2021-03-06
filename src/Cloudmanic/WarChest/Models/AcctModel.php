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
use \Cloudmanic\WarChest\Libraries\Me as Me;

class AcctModel extends Eloquent
{	
	public static $joins = null;
	public static $checked = null;
	protected static $query = null;
	
	// ------------------------ Setters ------------------------------ //

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
	// Set Not Column.
	//
	public static function set_not_col($key, $value)
	{
		self::get_query()->where($key, '!=', $value);
	}
	
	//
	// Set Column OR.
	//
	public static function set_or_col($key, $value)
	{
		self::get_query()->or_where($key, '=', $value);
	}
	
	// 
	// Set Like.
	//
	public static function set_like_col($key, $value)
	{
		self::get_query()->where($key, 'LIKE', '%' . $value . '%');
	}	
	
	//
	// Set Or Where In
	//
	public static function set_or_where_in($col, $list)
	{
		self::get_query()->or_where_in($col, $list);
	}
	
	//
	// Set Where In
	//
	public static function set_where_in($col, $list)
	{
		self::get_query()->where_in($col, $list);
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
		self::set_col(self::$table . 'AccountId', Me::get_account_id());
		
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
		// Make sure we have a query started.
		self::get_query();
		
		// Set Id Column. 
		self::set_col(self::$table . 'Id', $id);
		
		// Get the data.
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
		// Checked checked.
		$data = self::check_checked($data);
	
		// Make sure we have a query started.
		self::get_query();
	
		// Add created at date
 		if(! isset($data[self::$table . 'CreatedAt'])) 
 		{
 			$data[self::$table  . 'CreatedAt'] = date('Y-m-d G:i:s');
 		}
	
		// Set the account.
		$data[self::$table . 'AccountId'] = Me::get_account_id();
	
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
		// Checked checked.
		$data = self::check_checked($data);
	
		// Make sure we have a query started.
		self::get_query();
	
		// Set the account.
		self::set_col(self::$table . 'AccountId', Me::get_account_id());
		
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
		
		// Set the account.
		self::set_col(self::$table . 'AccountId', Me::get_account_id());
		
 		// Delete entry and clear query.
		self::get_query()->where(self::$table . 'Id', '=', $id)->delete();
		self::clear_query();
	}
	
	//
	// Delete all data.
	//
	public static function delete_all()
	{
		// Make sure we have a query started.
		self::get_query();
		
		// Set the account.
		self::set_col(self::$table . 'AccountId', Me::get_account_id());
		
		self::get_query()->delete();
		self::clear_query();
	}
	
	// ----------------- Helper Function  -------------- //
	
	//
	// Check to see if we have any checked dates. In html
	// if a checkbox is not checked we do not POST it. So here
	// we override it. 
	//
	private static function check_checked($data)
	{
		if((! is_null(static::$checked)) && is_array(static::$checked))
		{
			foreach(static::$checked AS $key => $row)
			{
				if(! isset($data[$row]))
				{
					$data[$row] = "0";
				}
			}
		}
		
		return $data;
	}
	
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