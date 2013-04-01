<?php
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 10/7/2012
//

namespace Cloudmanic\WarChest\Controllers;

use Illuminate\Support\Facades\Request as Request;
use Illuminate\Support\Facades\Cache as Cache;
use Illuminate\Support\Facades\Input as Input;
use Illuminate\Support\Facades\Response as Response;
use Illuminate\Support\Facades\Validator as Validator;

class ApiController extends \Illuminate\Routing\Controllers\Controller
{
	public $model = '';
	public $cached = false;
	public $cached_time = 60;
	public $no_auth = false;
	public $rules_create = array();
	public $rules_update = array();
	public $rules_message = array();
	
	//
	// Construct.
	//
	public function __construct()
	{		
		// Guess the model.
		$tmp = explode('\\', get_called_class()); 		
		$this->model = end($tmp);
	}
	
	//
	// Index (get).
	//
	public function index()
	{		
		// Request hash.
		$hash = 'api-' . md5(Request::getUri());
	
		// Is this a cached response?	
		if($this->cached)
		{
			if($data = Cache::get($hash))
			{
				return $this->api_response($data);
			}
		}
	
		// Setup query. Apply any filters we might have passed in.
		$this->_setup_query();
		
		// Load model and run the query.
		$m = $this->model;		
		$data = $m::get();	
		
		// Store the cache of this response
		if($this->cached)
		{
			Cache::put($hash, $data, $this->cached_time);
		}
		
		return $this->api_response($data);
	}
	
	//
	// Get by id. 
	// Returns status 0 if there was no data found.
	//
	public function id($_id)
	{
		// Request hash.
		$hash = 'api-' . md5(Request::getUri());
		
		// Is this a cached response?	
		if($this->cached)
		{
			if($data = Cache::get($hash))
			{
				return $this->api_response($data);
			}
		}
	
		$m = $this->model;
		if($data = $m::get_by_id($_id))
		{	
			// Store the cache of this response
			if($this->cached)
			{
				Cache::put($hash, $data, $this->cached_time);
			}
		
			return $this->api_response($data);
		} else
		{
			return $this->api_response(array(), 0, array('system' => array('Entry not found.')));
		}
	}
	
	//
	// Insert.
	//
	public function create()
	{		
		// A hook before we go any further.
		if(method_exists($this, '_before_validate'))
		{
		  $this->_before_validate();
		}
		
		// Validate this request. 
		if($rt = $this->validate_request('create'))
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
	public function update($id)
	{							
		// A hook before we go any further.
		if(method_exists($this, '_before_validate'))
		{
		  $this->_before_validate();
		}
		
		// Validate this request. 
		if($rt = $this->validate_request('update'))
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
	// Return a response based on the get "format" param.
	//
	public function api_response($data = null, $status = 1, $errors = NULL, $cust_errors = NULL)
	{	
		// Setup the return array
		$rt = array();
		$rt['status'] = $status;
		$rt['data'] = (! is_null($data)) ? $data : array();
		$rt['filtered'] = 0;
		$rt['total'] = 0;
		
		// Set errors.
		if(is_null($errors))
		{
			// See if we passed in any custom errors.
			if(is_null($cust_errors))
			{
				$rt['errors'] = array();
			} else
			{
				$rt['errors'] = $cust_errors;
			}
		} else
		{
			// Format the errors
			foreach(Input::get() AS $key => $row)
			{
			  if($errors->has($key))
			  {
			    $rt['errors'][] = array('field' => $key, 'error' => $errors->first($key, ':message'));
			  }
			}
		}
		
		// Format the return in the output passed in.
		switch(Input::get('format'))
		{
			case 'php':
				return '<pre>' . print_r($rt, TRUE) . '</pre>';
			break;
			
			default:
				return Response::json($rt);
			break;
		}
	}
	
	//
	// Validate requests.
	//
	public function validate_request($type)
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
		if(is_array($rules) && (count($rules > 0)))
		{
			$validation = Validator::make(Input::get(), $rules, $this->rules_message);
		
			if($validation->fails())
			{
			  $messages = $validation->messages();
			  return $this->api_response(null, 0, $messages);
			}
		}
		
		// We consider true a state with errors.
		return false;
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