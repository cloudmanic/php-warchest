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
use Laravel\Redirect as Redirect;
use Laravel\Routing\Router as Route;
use Laravel\Url as Url;

class ApiController extends \Laravel\Routing\Controller
{
	public $restful = true;
	public $model = '';
	public $not_allowed = array();
	public $before_filter = 'api_auth';
	 
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
	public function post_insert()
	{		
		if($this->_is_allowed(__FUNCTION__))
		{
			return $this->_method_not_allowed();
		}
		
		$m = $this->model;
		$data['Id'] = $m::insert(Input::get());	
		return $this->_response($data);
	}
	 
	//
	// Delete.
	//
	public function post_delete()
	{		
		if($this->_is_allowed(__FUNCTION__))
		{
			return $this->_method_not_allowed();
		}
		
		$m = $this->model;
		$m::delete(Input::get('Id'));	
		return $this->_response(array());
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
		return $this->_response($data);
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
			return $this->_response($data);
		} else
		{
			return $this->_response(array(), 0, array('Entry not found.'));
		}
	}
	
	//
	// Return a response based on the get "format" param.
	//
	protected function _response($data, $status = 1, $errors = NULL)
	{
		$rt['status'] = $status;
		$rt['data'] = $data;
		
		// Set errors.
		if(is_null($errors))
		{
			$rt['errors'] = array();
		} else
		{
			$rt['errors'] = $errors;
		}
		
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
	// Return and tell the user this method is not allowed.
	//
	private function _method_not_allowed()
	{
		return $this->_response(array(), 0, array('Method not allowed.'));
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