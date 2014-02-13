<?php
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 2/12/2014
//

namespace Cloudmanic\WarChest\Controllers;

use \App;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class Api extends Controller
{
	public $model = null;
	public $model_name = null;
	public $cached = false;
	public $cached_time = 60;
	public $no_auth = false;
	public $accept_update = null;
	public $accept_insert = null;
	public $rules_create = [];
	public $rules_update = [];
	public $rules_message = [];
	
	//
	// Construct.
	//
	public function __construct()
	{		
		// Guess the model.
		if(is_null($this->model_name))
		{
			$tmp = explode('\\', get_called_class()); 		
			$this->model_name = 'Models\\' . end($tmp);
			$this->model = App::make($this->model_name);
		}
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
		$this->model->set_api(true);		
		$data = $this->model->get();
		
		// Store the cache of this response
		if($this->cached)
		{
			Cache::put($hash, $data, $this->cached_time);
		}
		
		return $this->api_response($data);
	}
	
/*
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
	
		// Set model
		$m = $this->model;
		$m::set_api(true);	
		
		// Set extra
		if(Input::get('extra'))
		{
			$m::set_extra(Input::get('extra'));
		}
		
		// Run query.
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
		
		// Set the input that we accept. 
		if($this->accept_insert)
		{
			$input = Input::only(implode(',', $this->accept_insert));
		} else
		{
			$input = Input::get();
		}
		
		// Load model and insert data.
		$m = $this->model;
		$m::set_api(true);
		$data['Id'] = $m::insert($input);	
		
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
		
		// Set the input that we accept. 
		if($this->accept_update)
		{
			$input = Input::only($this->accept_update);
		} else
		{
			$input = Input::get();
		}
		
		// Load model and update data.
		$m = $this->model;
		$m::set_api(true); 
		$data['Id'] = $id;
		$m::update($input, $id);	
		
		// A hook before we go any further.
		if(method_exists($this, '_after_update'))
		{
		  $this->_after_update($id);
		}
		
		return $this->api_response($data);
	}
	
	//
	// Delete a record by id.
	//
	public function delete($_id = null)
	{	
		// So we can support posts as well.
		if(is_null($_id))
		{
			$_id = Input::get('Id');
		}
	
		$m = $this->model;
		$m::set_api(true);
		$m::delete_by_id($_id);
		return $this->api_response();
	}
*/
	
	//
	// Return a response based on the get "format" param.
	//
	public function api_response($data = null, $status = 1, $errors = NULL, $cust_errors = NULL, $summary = true)
	{	
		// Setup the return array
		$rt = [];
		$rt['status'] = $status;
		$rt['data'] = (! is_null($data)) ? $data : [];
		$rt['count'] = count($rt['data']);
		$rt['errors'] = [];
		
		// Sometimes we do not want to include all this summary information.
		if($summary)
		{
			$rt['filtered'] = 0;
			$rt['total'] = ($rt['data']) ? $this->model->get_count() : 0;
			$rt['offset'] = (\Input::get('offset')) ? \Input::get('offset') : 0;
			$rt['limit'] = (\Input::get('limit')) ? \Input::get('limit') : 0;
			$rt['range_start'] = 1;
			$rt['range_end'] = $rt['count'];
			$rt['hash'] = md5(json_encode($data));
			
			// Get the pageination.
			if($rt['limit'])
			{
				$this->_setup_query(false);
				$rt['filtered'] = $this->model->get_count();
				
				$rt['range_start'] = $rt['offset'] + 1;
				
				if(($rt['offset'] + $rt['limit']) < $rt['filtered'])
				{
					$rt['range_end'] = ($rt['offset'] + $rt['limit']);
				} else
				{
					$rt['range_end'] = $rt['filtered'];
				}
			} 
		}		
		
		// Set errors.
		if(is_null($errors))
		{
			// See if we passed in any custom errors.
			if(is_null($cust_errors))
			{
				$rt['errors'] = [];
			} else
			{
				$rt['errors'] = $cust_errors;
			}
		} else
		{
			// Format the errors
			foreach(Input::all() AS $key => $row)
			{
			  if($errors->has($key))
			  {
			    $rt['errors'][] = [ 'field' => $key, 'error' => $errors->first($key, ':message') ];
			  }
			}
		}
		
		// Sometimes we just want to return just the hash of the data.
		if(Input::get('only_hash') && isset($rt['hash']))
		{
			$rt = [ 'status' => 1, 'hash' => $rt['hash'] ];
		}
		
		// Format the return in the output passed in.
		switch(Input::get('format'))
		{
			case 'php':
				return '<pre>' . print_r($rt, TRUE) . '</pre>';
			break;
			
			case 'jsonp':
				if(Input::get('callback'))
				{
				  return Input::get('callback') . '(' . json_encode($rt) . ')';
				}
		
				return 'callback(' . json_encode($rt) . ')';
			break;
			
			default:
				return Response::json($rt);
			break;
		}
	}
	
/*
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
*/
	
	// --------------- Private Functions ----------------- //
	
	//
	// Setup the query. Apply any filters we might have passed in.
	//
	private function _setup_query($limit = true)
	{	
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
				if(Input::get($row) || (Input::get($row) == '0'))
				{
					$col = str_replace('col_', '', $row);
					$this->model->set_col($col, Input::get($row));
				}
			}
		}
	
		// Order by...
		if(Input::get('order'))
		{
			if(Input::get('sort'))
			{
				$this->model->set_order(Input::get('order'), Input::get('sort'));
			} else
			{
				$this->model->set_order(Input::get('order'));				
			}
		}
		
		// Select columns...
		if(Input::get('select'))
		{
			$this->model->set_select(explode(',', Input::get('select')));
		}
		
		// Set limit...
		if($limit && Input::get('limit'))
		{
			$this->model->set_limit(Input::get('limit'));
		}
		
		// Set offset...
		if($limit && Input::get('offset') && Input::get('limit'))
		{
			$this->model->set_offset(Input::get('offset'));
		}
		
		// Set search....
		if(Input::get('search'))
		{
			$this->model->set_search(Input::get('search'));
		}
		
		// Set extra
		if(Input::get('extra'))
		{
			$this->model->set_extra(Input::get('extra'));
		}
	}
}

/* End File */