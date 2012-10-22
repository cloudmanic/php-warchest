<?php 
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Date: 10/13/2012
//

namespace Cloudmanic\WarChest\Libraries;

class Start
{
	//
	// Laravel Init. Stuff to setup that is useful 
	// for the Laravel framework. 
	// 
	public static function laravel_init()
	{
		// Load AutoLoad Aliases
		\Laravel\Autoloader::alias('\Cloudmanic\WarChest\Libraries\LaravelAuth', 'LaravelAuth');
		\Laravel\Autoloader::alias('\Cloudmanic\WarChest\Libraries\Me', 'Me');
		
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
		
		// Build a micro for activating a class or not. We use this in a main navigation
		// to set the html class to active or not.
		\Laravel\HTML::macro('is_active', function($name, $home = false, $class = 'active')
		{
			$base = \Laravel\URI::segment(1);
			
			// Is the the default route?
			if($home && (empty($base)))
			{
				return $class;
			}
			
			// Compare the segment.
			if($base == $name)
			{
				return $class;
			}
			
			return '';
		});
	}
}

/* End File */