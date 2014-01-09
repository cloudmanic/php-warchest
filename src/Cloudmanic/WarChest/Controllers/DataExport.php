<?php

//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 4/22/2013

namespace Cloudmanic\WarChest\Controllers;

use \DB;
use \Input;
use \Config;
use Cloudmanic\WarChest\Models\DeleteLog;
use Cloudmanic\WarChest\Libraries\Me;
use Cloudmanic\WarChest\Libraries\Events;

class DataExport extends ApiController 
{
	public $db = null;
	
	//
	// Since API call. 
	//
	public function since()
	{
	  $data = [];
	
	  $accounts = $this->_get_account_list();	  
	  $timestamp = (Input::get('since')) ? Input::get('since') : '2008-01-01 00:00:00';
		$tables = $this->_get_export_list(\Config::get('site.data_export.data'));
		$version = Config::get('site.data_export.version');
		$name = Config::get('site.data_export.name');
  
	  // Include the DBInfo database first to avoid race conditions.
	  $data['DbInfo'] = array('DbInfoVersion' => $version, 'DbInfoLastSynced' => date('Y-m-d H:i:s'));
  
	  // Loop through the different accounts.
	  $acct = Me::get_account();
	  foreach($accounts AS $account)
	  {	  	  
		  // Set the account for this loop.
		  Me::set_account($account);

			// Loop through the tables and just get the data that has changed.  
		  foreach($tables AS $key => $row)
		  {
		  	if($row['model'])
				{	
					// Make things faster because we are not doing all the sub queries.
					if($row['noextra'])
					{
						$row['table']::set_no_extra();
					}	
									
					// Get data, build sql table.
					$row['table']::set_since($timestamp);
					$d = $row['table']::get();

					// Add Data
					$data[$row['table']] = $this->_set_since_data($d, $row['table'], $row['keys']);
				} else if((! $row['model']) && ($row['table'] != 'DbInfo') && ($row['table'] != 'PostQueue'))
		    {
					$data[$row['table']] = [];
/*
		    	echo $row['table'];
			    // Get data the old fashion way.
			    $data = DB::table($row['table'])->where('', '', '<=')->get();
			    foreach($data AS $key2 => $row2)
			    {
				    $data[$key2] = (array) $row2;
			    }
		    	$data[$row['table']] = $this->_set_since_data($d, $row['table'], $row['keys']);
*/
		    }
		  }
		  
		  // Now include the delete log so we know what has been deleted.
		  DeleteLog::set_since($timestamp);
		  if(! isset($data['DeleteLog']))
		  {
		  	$data['DeleteLog'] = DeleteLog::get();
		  } else
		  {
			  $data['DeleteLog'] = array_merge($data['DeleteLog'], DeleteLog::get());
		  }
		}

		// Set account back.
		Me::set_account($acct);
	  
		// Record the action.
		Events::send('data-since');
	  
	  return $this->api_response($data, 1, NULL, NULL, false);
	}
	
	// --------------------- Sqlite Stuff ----------------------------------- //
	
	//
	// Get sqlite database.
	//
	public function sqlite()
	{			
		$accounts = $this->_get_account_list();
		$tables = $this->_get_export_list(\Config::get('site.data_export.data'));
		$version = \Config::get('site.data_export.version');
		$name = \Config::get('site.data_export.name');
				
		$now = date('Y-m-d G:i:s');
		$dir = '/tmp/' . $name . '_' .  md5(time().uniqid());
		mkdir($dir); 
		$dbfile = $dir . '/' . $name . '.sqlite';
		$this->db = new \PDO('sqlite:' . $dbfile);
		$this->db->query('BEGIN');
		
		
	  // Loop through the different accounts.
	  $acct = Me::get_account();
	  foreach($accounts AS $account)
	  {	  
		  // Set the account for this loop.
		  Me::set_account($account);
		  
		  // Loop through the tables and build them.
		  foreach($tables AS $key => $row)
		  {
		    // Build the table schema
		    $this->_sqlite_build_table($row['table'], $row['keys'], $row['indexes']);
		  
		    if($row['model'])
		    {		    	
		    	// Make things faster because we are not doing all the sub queries.
		    	if($row['noextra'])
		    	{
		    		$row['table']::set_no_extra();
		    	}
		  
		    	// Get data, build sql table.
		    	$data = $row['table']::get();		    	
		    	$this->_sqlite_insert_table($data, $row['table'], $row['keys']);
		    } else if((! $row['model']) && ($row['table'] != 'DbInfo') && ($row['table'] != 'PostQueue'))
		    {
			    // Get data the old fashion way.
			    $data = DB::table($row['table'])->get();
			    foreach($data AS $key2 => $row2)
			    {
				    $data[$key2] = (array) $row2;
			    }
		    	$this->_sqlite_insert_table($data, $row['table'], $row['keys']);
		    }
		    
		    // If this is the db info table we do something special by hand.
		    if($row['table'] == 'DbInfo')
		    {
		    	$data = array();
		    	$data[] = array('DbInfoVersion' => $version, 'DbInfoLastSynced' => $now);
		    	$this->_sqlite_insert_table($data, $row['table'], $row['keys']);
		    }
		  }
		}

	  $this->db->query('COMMIT');
	
		// Record the action.
		Events::send('data-sqlite');
	
	  // Return the file.
	  if(Input::get('zip'))
	  {
	  	exec("zip -j $dbfile.zip $dbfile");
			return \Response::download("$dbfile.zip");
		} else
		{
			return \Response::download($dbfile);
		}
	}
	
	//
	// Create a table.
	//
	private function _sqlite_build_table($table, $keys, $indexes)
	{
  	// Build sql query string
  	$tmp = array();
  	
  	foreach($keys AS $key => $row)
  	{
  	  $tmp[] = $key . ' ' . $row;
  	}
  	
  	// Create Table.
  	$this->db->query("CREATE TABLE IF NOT EXISTS $table (" . implode($tmp, ', ') . ")");
  	
  	// Build indexes.
  	foreach($indexes AS $key => $row)
  	{
    	$this->db->query("CREATE INDEX IF NOT EXISTS $row ON $table ($row)");
  	}
	}

	//
	// Helper function to insert data into a table.
	//
	private function _sqlite_insert_table($data, $table, $keys)
	{	
		// Setup the keys
		$k = array_keys($keys);
	
		// Loop through and insert the data.
		foreach($data AS $key => $row)
		{
			// Match the keys to the data.
			$v = array();
			$m = array();
			foreach($k AS $key2 => $row2)
			{
			  $v[] = ':' . $row2;
			  $m[':' . $row2] = $row[$row2];
			}
		
			$sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $k) . ') VALUES (' . implode(', ', $v) . ')';
			$stmt = $this->db->prepare($sql);
			$stmt->execute($m);
		}
	}
	
	// --------------------- Helper Functions ----------------------------------- //
	
	//
	// Get list of accounts for this user.
	//
	private function _get_account_list()
	{
/*
		$name = \Config::get('site.data_export.name');
		$accounts = array();
		
		foreach(AcctUsersLu::get_accounts_by_user(Me::get('UsersId')) AS $key => $row)
		{
		  if(strtolower($row['ApplicationsName']) == $name)
		  {				
		  	$accounts[] = $row;
		  }
		}
*/
		
		// Return a list of accounts.
		return [ Me::get_account() ];
	}
	
  //
  // Set filter out data and just return what we asked for in the config.
  //
  private function _set_since_data($data, $table, $keys)
  {
	  $r_data = [];
  
	  // Loop through data and set the data we want to return.
		foreach($data AS $key => $row)
		{	
			foreach($keys AS $key2 => $row2)
			{	
				$tmp[$key2] = $row[$key2]; 
			}
			
			$r_data[] = $tmp; 
		}

		return $r_data;
  }
	
  //
  // Build arrays of data that we export via this library.
  // We export in all sorts of formats but tables and columns 
  // are always the same.
  //
  private function _get_export_list($data)
  {    
	  // DbInfo
		$text = [ 'DbInfoVersion' => 'TEXT', 'DbInfoLastSynced' => 'TEXT' ];
		$data[] = [ 'table' => 'DbInfo', 'numbers' => array(), 'keys' => $text, 'indexes' => array(), 'model' => FALSE, 'noextra' => FALSE ];
		
		return $data;
	}
}

/* End File */
