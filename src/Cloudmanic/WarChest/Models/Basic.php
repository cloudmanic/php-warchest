<?php
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 2/12/2014
// Note: Non-Cloudmanic Product Version.
//

namespace Cloudmanic\WarChest\Models;

use Illuminate\Support\Facades\DB;

class Basic
{	
	public $table = '';
	public $connection = 'mysql';
	public $joins = null;	
	protected $db = null;	
	protected $_extra = false;		
	protected $_is_api = false;	

	//
	// Construct.
	//
	public function __construct()
	{
		// Get the table.
		if(empty($this->table))
		{
			$table = explode('\\', get_called_class());
			$this->table = end($table);			
		}
			
		// Setup the database connection.
		$this->_setup_query();
	}
	
	// ------------------------ Setters ------------------------------ //

	//
	// Set API call.
	//
	public function set_api($action = true)
	{
		$this->_is_api = $action;
	}	
	
	//
	// Set join
	//
	public function set_join($table, $left, $right)
	{
		$this->db->join($table, $left, '=', $right);
	}		
	
	//
	// Make it so it does not load the other models.
	//
	public function set_no_extra()
	{
		$this->_extra = false;
	}
	
	//
	// We want all the extra data.
	//
	public function set_extra()
	{
		$this->_extra = true;
	}
 
 	//
 	// Set since a particular date.
 	//
 	public function set_since($timestamp)
 	{
	 	$stamp = date('Y-m-d H:i:s', strtotime($timestamp));
	 	$this->db->where($this->table . 'UpdatedAt', '>=', $stamp);
 	}

	//
	// Set limit
	//
	public function set_limit($limit)
	{
		$this->db->take($limit);
	}	
	
	//
	// Set offset
	//
	public function set_offset($offset)
	{
		$this->db->skip($offset);
	}	
	
	//
	// Set order
	//
	public function set_order($order, $sort = 'desc')
	{
		$this->db->orderBy($order, $sort);
	}	
	
	//
	// Set group
	//
	public function set_group($group)
	{
		$this->db->groupBy($group);
	}	
	
	//
	// Set Column.
	//
	public function set_col($key, $value, $action = '=')
	{
		$this->db->where($key, $action, $value);
	}
	
	//
	// Set Column OR.
	//
	public function set_or_col($key, $value)
	{
		$this->db->orWhere($key, '=', $value);
	}
	
	//
	// Set Or Where In
	//
	public function set_or_where_in($col, $list)
	{
		$this->db->orWhereIn($col, $list);
	}
	
	//
	// Set Where In
	//
	public function set_where_in($col, $list)
	{
		$this->db->whereIn($col, $list);
	}	
	
	//
	// Set Columns to select.
	//
	public function set_select($selects)
	{
		$this->db->select($selects);
	}	
	
	//
	// Set search.
	//
	public function set_search($str)
	{
		// Place holder we should override this.
	}
	
	
	// ------------------------ Actions ------------------------------ //	
	
	//
	// Get...
	// 
	public function get()
	{	
		$data = [];
		
		// Do we have joins?
		if(! is_null($this->joins))
		{
			foreach($this->joins AS $key => $row)
			{
				$this->set_join($row['table'], $row['left'], $row['right']);
			}
		}		
		
		// Query
		$data = $this->db->get();
		
		// Convert to an array because we like arrays better.
		$data = $this->_obj_to_array($data);
		
		// Reset the query.
		$this->_setup_query();		
		
		// An option formatting function call.
		if(method_exists($this, '_format_get'))
		{	
			// Loop through data and format.
			foreach($data AS $key => $row)
			{
				$this->_format_get($data[$key]);
			}
		}
		
		return $data;
	} 	
	
	//
	// Get count.
	//
	public function get_count()
	{	
		// Do we have joins?
		if(! is_null($this->joins))
		{
			foreach($this->joins AS $key => $row)
			{
				$this->set_join($row['table'], $row['left'], $row['right']);
			}
		}
		
		// Get count.
		$r = $this->db->count();
		
		// Reset the query.
		$this->_setup_query();		
		
		return $r;
	}	
	
	// ----------------- Helper Function  -------------- //
		
	//
	// Setup the connection. We need to do this after every query.
	//
	private function _setup_query()
	{
		$this->db = DB::connection($this->connection)->table($this->table);		
	}
		
 	//
 	// Convert the object the database returns to an array.
 	// Yes, PDO can return arrays, but Laravel really counts
 	// on objects instead of arrays.
 	//
 	private function _obj_to_array($data)
 	{
	 	if(is_array($data) || is_object($data))
	 	{
		 	$result = [];
		 	foreach($data as $key => $value)
		 	{
			 	$result[$key] = $this->_obj_to_array($value);
			}

			return $result;
		}
    
		return $data;
	}
	
	//
	// Get last query.
	//
	public function get_last_query()
	{
		return end(DB::profile());
	}	
}

/* End File */