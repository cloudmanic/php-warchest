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
	public $before_filter = 'api_auth';
	public $rules_create = array();
	 
	//
	// Construct.
	//
	public function __construct()
	{
		parent::__construct();
		\Cloudmanic\WarChest\Libraries\Start::laravel_init();
		$this->filter('before', $this->before_filter);

		// Guess the model.
		$tmp = explode('_', get_called_class()); 
		$this->model = $tmp[2];
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
		
		// Validate this request. 
		if($rt = $this->_validate_request($this->rules_create))
		{
			return $rt;
		}
		
		// Load model and insert data.
		$m = $this->model;
		$data['Id'] = $m::insert(Input::get());	
		
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
		
		// Validate this request. 
		if($rt = $this->_validate_request($this->rules_create))
		{
			return $rt;
		}
		
		// Load model and update data.
		$m = $this->model; 
		$data['Id'] = $id;
		$m::update(Input::get(), $id);	
		
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
		$m::delete($id);	
		return $this->api_response(array());
	}
	 
	//
	// Get.
	//
	public function get_index()
	{		
		if($this->_is_allowed(__FUNCTION__))
		{
			return $this->_method_not_allowed();
		}
		
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
			return $this->api_response(array(), 0, array('Entry not found.'));
		}
	}
	
	//
	// Return a response based on the get "format" param.
	//
	public function api_response($data = null, $status = 1, $errors = NULL)
	{
		// First we see if we should redirect instead of returning the data.
		if(Input::get('redirect'))
		{
			$base = URL::base() . '/' . Input::get('redirect');
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
			
			default:
				return Response::json($rt);
			break;
		}
	}
	
	// --------------- Private Functions ----------------- //
	
	//
	// Validate requests.
	//
	private function _validate_request($rules)
	{
		if(is_array($rules) && (count($rules > 0)))
		{
			$validation = Validator::make(Input::get(), $this->rules_create);
			if($validation->fails())
			{
				if(Input::get('redirect'))
				{
		    	return Redirect::to(Request::server('http_referer'))->with_errors($validation)->with('data', Input::get());
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
		return $this->api_response(array(), 0, array('Method not allowed.'));
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