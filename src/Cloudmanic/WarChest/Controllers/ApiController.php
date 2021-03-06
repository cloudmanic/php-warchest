<?php
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 10/7/2012
//

namespace Cloudmanic\WarChest\Controllers;

use Laravel\Input as Input;
use Laravel\Response as Response;
use Laravel\Validator as Validator;
use Laravel\Redirect as Redirect;
use Laravel\Request as Request;
use Laravel\Routing\Router as Route;
use Laravel\Url as Url;

class ApiController extends \Laravel\Routing\Controller
{
	public $restful = true;
	public $model = '';
	public $not_allowed = array();
	public $no_auth = false;
	public $before_filter = 'api_auth';
	public $rules_create = array();
	public $rules_update = array();
	public $rules_message = array();
	 
	//
	// Construct.
	//
	public function __construct()
	{
		parent::__construct();
		\Cloudmanic\WarChest\Libraries\Start::laravel_init();
		$this->filter('before', $this->before_filter);
		
		// Guess the model.
		if(empty($this->model))
		{
			$tmp = explode('_', get_called_class()); 
			$this->model = $tmp[2];
		}
	}

	//
	// Insert.
	//
	public function post_create()
	{		
		// Validate that we are allowed to access this method
		if($this->_is_allowed(__FUNCTION__))
		{
			return $this->_method_not_allowed();
		}
		
		// A hook before we go any further.
		if(method_exists($this, '_before_validate'))
		{
		  $this->_before_validate();
		}
		
		// Validate this request. 
		if($rt = $this->_validate_request('create'))
		{
			return $rt;
		}

		// A hook before we go any further.
		if(method_exists($this, '_before_create_or_update'))
		{
		  $this->_before_create_or_update();
		}
		
		// A hook before we go any further.
		if(method_exists($this, '_before_insert'))
		{
		  $this->_before_insert();
		}
		
		// Load model and insert data.
		$m = $this->model;
		$data['Id'] = $m::insert(Input::get());	
		
		// A hook before we go any further.
		if(method_exists($this, '_after_insert'))
		{
		  $this->_after_insert();
		}
		
		return $this->api_response($data);
	}
	
	//
	// Update.
	//
	public function post_update($id)
	{				
		// Validate that we are allowed to access this method
		if($this->_is_allowed(__FUNCTION__))
		{
			return $this->_method_not_allowed();
		}
		
		// A hook before we go any further.
		if(method_exists($this, '_before_validate'))
		{
		  $this->_before_validate();
		}
		
		// Validate this request. 
		if($rt = $this->_validate_request('update'))
		{
			return $rt;
		}
		
		// A hook before we go any further.
		if(method_exists($this, '_before_create_or_update'))
		{
		  $this->_before_create_or_update();
		}
		
		// A hook before we go any further.
		if(method_exists($this, '_before_update'))
		{
		  $this->_before_update($id);
		}
		
		// Load model and update data.
		$m = $this->model; 
		$data['Id'] = $id;
		$m::update(Input::get(), $id);	
		
		// A hook before we go any further.
		if(method_exists($this, '_after_update'))
		{
		  $this->_after_update($id);
		}
		
		return $this->api_response($data);
	}
	 
	//
	// Delete
	//
	public function get_delete($id)
	{		
		if($this->_is_allowed(__FUNCTION__))
		{
			return $this->_method_not_allowed();
		}
		
		$m = $this->model;
		$m::delete_by_id($id);	
		return $this->api_response(array());
	}
	 
	//
	// Get.
	//
	public function get_index()
	{		
		// Is this action allowed?
		if($this->_is_allowed(__FUNCTION__))
		{
			return $this->_method_not_allowed();
		}
		
		// Setup query. Apply any filters we might have passed in.
		$this->_setup_query();
		
		// Load model and run the query.
		$m = $this->model;		
		$data = $m::get();	
		return $this->api_response($data);
	}
	
	//
	// Get by id. 
	// Returns status 0 if there was no data found.
	//
	public function get_id($id)
	{
		if($this->_is_allowed(__FUNCTION__))
		{
			return $this->_method_not_allowed();
		}

		$m = $this->model;
		if($data = $m::get_by_id($id))
		{	
			return $this->api_response($data);
		} else
		{
			return $this->api_response(array(), 0, array('system' => array('Entry not found.')));
		}
	}
	
	//
	// Return a response based on the get "format" param.
	//
	public function api_response($data = null, $status = 1, $errors = NULL)
	{
		// First we see if we should redirect instead of returning the data.
		if(Input::get('redirect_success'))
		{
			$base = URL::base() . '/' . Input::get('redirect_success');
			$url = $this->_filter_redirect_url($base, $data);
			return Redirect::to($url);
		}
	
		// Setup the return array
		$rt = array();
		$rt['status'] = $status;
		$rt['data'] = (! is_null($data)) ? $data : array();
		$rt['filtered'] = 0;
		$rt['total'] = 0;
		
		// Set errors.
		if(is_null($errors))
		{
			$rt['errors'] = array();
		} else
		{
			// Format the errors
			foreach($errors AS $key => $row)
			{
				// You can have more than one error per field.
				foreach($row AS $key2 => $row2)
				{
					$rt['errors'][] = array('field' => $key, 'error' => $row2);
				}
			}
		}
		
		// Format the return in the output passed in.
		switch(Input::get('format'))
		{
			case 'php':
				return '<pre>' . print_r($rt, TRUE) . '</pre>';
			break;
			
			case 'data':
				return $rt;
			break;
			
			default:
				return Response::json($rt);
			break;
		}
	}
	
	// --------------- Private Functions ----------------- //
	
	//
	// Setup the query. Apply any filters we might have passed in.
	//
	private function _setup_query()
	{
		$m = $this->model;
	
		// A hook so we can add more query attributes.
		if(method_exists($this, '_before_setup_query'))
		{
		  $this->_before_setup_query();
		}
	
		// Setup column selectors
		$cols = array_keys(Input::get());
		foreach($cols AS $key => $row)
		{
			if(preg_match('/^(col_)/', $row))
			{
				if(Input::get($row))
				{
					$col = str_replace('col_', '', $row);
					$m::set_col($col, Input::get($row));
				}
			}
		}
		
		// Select custom functions
		foreach($cols AS $key => $row)
		{
			if(preg_match('/^(cust_)/', $row))
			{
				if(Input::get($row))
				{
					$cust = str_replace('cust_', '', $row);					
					$f = "set_custom_$cust";
					$m::$f(Input::get($row));
				}
			}
		}
	
		// Order by...
		if(Input::get('order'))
		{
			if(Input::get('sort'))
			{
				$m::set_order(Input::get('order'), Input::get('sort'));
			} else
			{
				$m::set_order(Input::get('order'));				
			}
		}
		
		// Select columns...
		if(Input::get('select'))
		{
			$m::set_select(explode(',', Input::get('select')));
		}
		
		// Set limit...
		if(Input::get('limit'))
		{
			$m::set_limit(Input::get('limit'));
		}
		
		// Set offset...
		if(Input::get('offset') && Input::get('limit'))
		{
			$m::set_offset(Input::get('offset'));
		}
		
		// Set search....
		if(Input::get('search'))
		{
			$m::set_search(Input::get('search'));
		}
		
		// Set start....
		if(Input::get('start'))
		{
			$m::set_start(Input::get('start'));
		}
		
		// Set end....
		if(Input::get('end'))
		{
			$m::set_end(Input::get('end'));
		}
	}
	
	//
	// Validate requests.
	//
	private function _validate_request($type)
	{	
		// A hook before we go any further.
		if(method_exists($this, '_before_validation'))
		{
		  $this->_before_validation();
		}
		
		// Set rules.
		if($type == 'create')
		{
		  $rules = $this->rules_create;
		} else
		{
		  $rules = $this->rules_update;				
		}
		
		// If we have rules we validate.
		if (is_array($rules) && (count($rules) > 0)) {
		{
			// Time to validate.
			$validation = Validator::make(Input::get(), $rules, $this->rules_message);
			if($validation->fails())
			{
				if(Input::get('redirect_fail'))
				{
		    	return Redirect::to(Input::get('redirect_fail'))->with_errors($validation)->with('data', Input::get());
		    } else
		    {
					return $this->api_response(null, 0, $validation->errors->messages);
				}
			}
		}
		
		return false;
	}
	
	//
	// Filter the redirect url.
	//
	private function _filter_redirect_url($url, $data)
	{
		// Id.
		if(isset($data['Id']))
		{
		  $url = str_ireplace(':id', $data['Id'], $url);
		}
		
		return $url;
	}
	
	//
	// Return and tell the user this method is not allowed.
	//
	private function _method_not_allowed()
	{
		return $this->api_response(array(), 0, array('system' => array('Method not allowed.')));
	}
	
	//
	// Is this api called allowed?
	//
	private function _is_allowed($function)
	{
		return in_array($function, $this->not_allowed);
	}
}

/* End File */
