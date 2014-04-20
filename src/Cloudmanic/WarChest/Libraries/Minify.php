<?php 
//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
// Date: 6/30/2013
//

namespace Cloudmanic\WarChest\Libraries;

class Minify
{
	private $_config = [];
	private $_prod_file_lines = [];
	private $_prod_files = [];
	
	//
	// Construct.
	//
	public function __construct($config)
	{
		$this->_config = $config;
	}
	
	//
	// Minify.
	//
	public function minify()
	{
		$this->_delete_old_files();
		$this->_combine_css();
		$this->_combine_js();
		$this->_build_prod_css_js();
		$this->_build_config_file();
	}
	
	//
	// Delete old file.
	//
	public function _delete_old_files()
	{
		if(! isset($this->_config['config_file']))
		{
			return false;
		}
		
		$files = file_get_contents($this->_config['config_file']);
		foreach(json_decode($files) AS $key => $row)
		{
			unlink($row);
		}
	}
	
	//
	// Build config file.
	//
	public function _build_config_file()
	{
		file_put_contents($this->_config['config_file'], json_encode($this->_prod_files));
	}
	
	//
	// Build the production css / js file.
	//
	public function _build_prod_css_js()
	{
		$str = '';

		foreach($this->_prod_file_lines AS $key => $row)
		{
			$str .= $row . "\n";
		}
		
		file_put_contents($this->_config['prod_file'], $str);
	}
	
	//
	// Combine css
	//
	private function _combine_css()
	{
		$master_css = '';
		$css_js = file_get_contents($this->_config['dev_file']);
		$lines = explode("\n", $css_js);
		
		foreach($lines AS $key => $row)
		{
			preg_match('<link.+href=\"(.+.css)\".+\/>', $row, $matches);
			
			if(isset($matches[1]) && (! empty($matches[1])))
			{
				$master_css .= file_get_contents($this->_config['base_dir'] . $matches[1]); 
			}
		}
		
		// If we have any new CSS we build a new hash file for the CSS.
		$hash = md5($master_css);							
		echo "\n###### Compressing CSS File ######\n";
		file_put_contents($this->_config['css_dir'] . "$hash.css", \CssMin::minify($master_css));
		
		// Add CSS to the prod file. 
		$this->_prod_files[] = $this->_config['css_dir'] . "$hash.css";
		$this->_prod_file_lines[] = '<link type="text/css" rel="stylesheet" href="' . $this->_config['css_url'] . "$hash.css" . '" media="screen" />';
	
		echo "\n";		
	}
	
	//
	// Combine the javascript
	//
	private function _combine_js()
	{
		$master_js = '';
		$css_js = file_get_contents($this->_config['dev_file']);
		$lines = explode("\n", $css_js);
		
		foreach($lines AS $key => $row)
		{
			preg_match('<script.+src=\"(.+.js)\".+>', $row, $matches);
			if(isset($matches[1]) && (! empty($matches[1])))
			{
				$master_js .= file_get_contents($this->_config['base_dir'] . $matches[1]) . "\n\n"; 
			}
		}
		
		// Build hash.
		$hash = md5($master_js);
		
		// Combine.
		echo "\n###### Combining JS Files ######\n";
		file_put_contents($this->_config['js_dir'] . "$hash.js", $master_js);
		unset($master_js);
		
		// Shrink.
		echo "\n###### Compressing JS File ######\n";
		exec('uglifyjs ' .  $this->_config['js_dir'] . "$hash.js -m -o " . $this->_config['js_dir'] . "$hash.min.js");
		unlink($this->_config['js_dir'] . "$hash.js");
		
		// Add javascript to the prod file. 
		$this->_prod_files[] = $this->_config['js_dir'] . "$hash.min.js";
		$this->_prod_file_lines[] = '<script type="text/javascript" src="' . $this->_config['js_url'] . $hash . '.min.js"></script>';
		
		echo "\n";
	}
}

/* End File */