<?php 
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Date: 10/13/2012
//

namespace Cloudmanic\libraries;

class Start
{
	//
	// Laravel Init. Stuff to setup that is useful 
	// for the Laravel framework. 
	// 
	public static function laravel_init()
	{
		// Load AutoLoad Aliases
		\Laravel\Autoloader::alias('\Cloudmanic\Libraries\LaravelAuth', 'LaravelAuth');
		\Laravel\Autoloader::alias('\Cloudmanic\Libraries\Me', 'Me');
		
		// Set Api auth filter.
		\Laravel\Routing\Route::filter('api_auth', function() 
		{
			// Check the authenication of the request.
			if(! CloudAuth::sessioninit())
			{
				// Redirect or display error?	
				$rt = CloudAuth::get_redirect();
				if(! empty($rt))
				{
					return \Laravel\Redirect::to($rt);
				} else
				{
					$data = array('status' => 0, 'errors' => array());
					$data['errors'][] = CloudAuth::get_error();
					return \Laravel\Response::json($data);			
				}
			}
		});
	}
}

/* End File */