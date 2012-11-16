<?php 
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 11/11/2012
//

namespace Cloudmanic\WarChest\Libraries;

class Upload
{
	private static $_upload_dir = '/tmp';
	private static $_upload_var = 'file';
	private static $_error = '';
	private static $_info = array();
	private static $_file_count = 0;

	// ---------------- Setters ----------------- //

	//
	// Set upload directory.
	//
	public static function set_upload_directory($dir)
	{
		self::$_upload_dir = $dir;
	}

	//
	// Set the variable name of the file being uploaded.
	//
	public static function set_variable($var)
	{
		self::$_upload_var = $var;
	}

	// --------------- Getters ------------------ //
	
	//
	// Get the error message.
	//
	public static function get_error()
	{
		return self::$_error;
	}

	// --------------- Action ------------------- //
	
	//
	// Do the upload. Returns an array with the upload
	// or false if it was a failure.
	//
	public static function upload()
	{
		// Is $_FILES[$field] set? If not, no reason to continue.
		if(! isset($_FILES[self::$_upload_var]))
		{
			self::$_error = 'You did not select a file to upload.';
			return false;
		}

		// Is the upload path a directory.
		if(! is_dir(self::$_upload_dir))
		{
			self::$_error = 'The upload path does not appear to be valid.';
			return false;			
		}

		// Is the upload path writeable
		if(! is_writeable(self::$_upload_dir))
		{
			self::$_error = 'The upload destination folder does not appear to be writable.';
			return false;
		}	
		
		// Was the file able to be uploaded? If not, determine the reason why.
		if(! is_uploaded_file($_FILES[self::$_upload_var]['tmp_name']))
		{
			$error = (! isset($_FILES[self::$_upload_var]['error'])) ? 4 : $_FILES[self::$_upload_var]['error'];

			switch($error)
			{
				// UPLOAD_ERR_INI_SIZE
				case 1:	
					self::$_error = 'The uploaded file exceeds the maximum allowed size in your PHP configuration file.';
				break;
				
				// UPLOAD_ERR_FORM_SIZE
				case 2: 
					self::$_error = 'The uploaded file exceeds the maximum size allowed by the submission form.';
				break;
				
				// UPLOAD_ERR_PARTIAL
				case 3: 
					self::$_error = 'The file was only partially uploaded.';
				break;
				
				// UPLOAD_ERR_NO_FILE
				case 4: 
					self::$_error = 'You did not select a file to upload.';
				break;
				
				// UPLOAD_ERR_NO_TMP_DIR
				case 6: 
					self::$_error = 'The temporary folder is missing.';
				break;
					
				// UPLOAD_ERR_CANT_WRITE
				case 7: 
					self::$_error = 'The file could not be written to disk.';
				break;
				
				// UPLOAD_ERR_EXTENSION
				case 8: 
					self::$_error = 'The file upload was stopped by extension.';
				break;
				
				default:   
					self::$_error = 'You did not select a file to upload.';
				break;
			}

			return false;
		}
		
		// Set the uploaded data as class variables
		$type = preg_replace("/^(.+?);.*$/", "\\1", self::_file_mime_type($_FILES[self::$_upload_var]));
		self::$_info['file_temp'] = $_FILES[self::$_upload_var]['tmp_name'];
		self::$_info['file_size'] = $_FILES[self::$_upload_var]['size'];
		self::$_info['file_type'] = strtolower(trim(stripslashes($type), '"'));
		self::$_info['client_name'] = $_FILES[self::$_upload_var]['name'];
		self::$_info['file_name'] = self::_clean_file_name($_FILES[self::$_upload_var]['name']);
		self::$_info['file_ext']	= pathinfo(self::$_info['file_name'], PATHINFO_EXTENSION);
		self::$_info['file_base'] = pathinfo(self::$_info['file_name'], PATHINFO_FILENAME);
		self::$_info['full_path'] = self::$_upload_dir . '/' . self::$_info['file_name'];
		self::$_info['is_image'] = 0;
		self::$_info['image_width'] = 0;
		self::$_info['image_height'] = 0;

		// Upload the file.
		if(! @move_uploaded_file(self::$_info['file_temp'], self::$_info['full_path']))
		{
			self::$_error = 'A problem was encountered while attempting to move the uploaded file to the final destination.';
			return false;
		}
		
		// Set the image properties.
		self::_set_image_properties(self::$_info['full_path']);

		return self::$_info;
	}
	
	// ---------------- Private Helper Functions ------------------- //
	
	//
	// Set the image properties if this is an image.
	//
	private static function _set_image_properties($path)
	{
		// Nothing to do if this is not an image.
		if(! $img = getimagesize($path))
		{
			return false;
		}
		
		// Set the image information.
		self::$_info['is_image'] = 1;
		self::$_info['image_width'] = $img[0];
		self::$_info['image_height'] = $img[1];
	}
	
	//
	// Clean up a file name.
	//
	private static function _clean_file_name($filename)
	{
		$bad = array(
						"<!--",
						"-->",
						"'",
						"<",
						">",
						'"',
						'&',
						'$',
						'=',
						';',
						'?',
						'/',
						"%20",
						"%22",
						"%3c",		// <
						"%253c",	// <
						"%3e",		// >
						"%0e",		// >
						"%28",		// (
						"%29",		// )
						"%2528",	// (
						"%26",		// &
						"%24",		// $
						"%3f",		// ?
						"%3b",		// ;
						"%3d"		// =
					);

		$filename = str_replace($bad, '', $filename);
		$filename = preg_replace("/\s+/", "_", $filename);
		$filename = strtolower($filename);
		
		// Check to see if the file already exists. If it does tack on a number.
		if(is_file(self::$_upload_dir . '/' . $filename))
		{
			$base = pathinfo($filename, PATHINFO_FILENAME);
			$base = str_ireplace('_' . self::$_file_count, '', $base);
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			self::$_file_count++;
			$filename = self::_clean_file_name($base . '_' . self::$_file_count . '.' . $ext);
		}

		return stripslashes($filename);
	}
	
	//
	// Pass in an upload file variable. 
	//
	private static function _file_mime_type($file)
	{
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$file_type = $finfo->file($file['tmp_name']);

		if(strlen($file_type) > 1)
		{
			return $file_type;
		}
	}
}

/* End File */