<?php 
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 11/11/2012
//

namespace Cloudmanic\WarChest\Libraries;

class CacheDir
{
	private static $_dir = null;

	//
	// Set the cache directory.
	//
	public static function set_directory($dir)
	{
		// If the directory does not exist create it.
		if(! is_dir($dir))
		{
			if(! mkdir($dir, 0770, true))
			{
				die("Unable to create directory - $dir - (CacheDir)");
			}
		}
		
		// Set the directory.
		self::$_dir = $dir;
	}
	
	//
	// Download from a url and write to the cached directory.
	// We pass in an optional name.
	//
	public static function download_write($url, $name = null)
	{
		// Download the file.
		$data = file_get_contents($url);

		// Figure out the name.
		if(is_null($name))
		{
			$name = md5($url);
		}
		
		// Write the file to cache.
		file_put_contents(self::$_dir . '/' . $name, $data);
		
		// Figure out the file type to add an extention.
		// Then move the downloaded file to the correct location with ext.
		$info = self::get_file_extension(self::$_dir . '/' . $name);
		rename(self::$_dir . '/' . $name, self::$_dir . '/' . $name . '.' . $info['ext']);
		$name = $name . '.' . $info['ext'];
		$full_path = self::$_dir . '/' . $name;
		
		// Return an array with full path to the file and the file name.
		return array('file' => $name, 'full_path' => $full_path, 'mime' => $info['mime']); 
	}
	
	//
	// Returns true of the file is in the cache.
	// Returns the file info if found.
	//
	public static function is_cached($file)
	{
		if(! is_file(self::$_dir . '/' . $file))
		{
			return false;
		}
		
		$info = self::get_file_extension(self::$_dir . '/' . $file);
		
		return array('file' => $file, 'full_path' => self::$_dir . '/' . $file, 'mime' => $info['mime']);
	}
	
	//
	// Get file extention from a full file path. Returns
	// the mime and the file ext.
	//
	public static function get_file_extension($path)
	{
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$type = finfo_file($finfo, $path);
		finfo_close($finfo);
		list($base, $ext) = explode('/', $type);
		return array('ext' => $ext, 'mime' => $type);
	}
}

/* End File */