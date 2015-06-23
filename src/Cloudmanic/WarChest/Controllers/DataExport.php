<?php

//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 4/22/2013

namespace Cloudmanic\WarChest\Controllers;

use \DB;
use \App;
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
	  $data['DbInfo'] = [ 'DbInfoVersion' => $version, 'DbInfoLastSynced' => date('Y-m-d H:i:s') ];
	  
	  // Mapping
	  $data['ColMapping'] = [];
  
	  // Loop through the different accounts.
	  $acct = Me::get_account();
	  foreach($accounts AS $account)
	  {	  	  
		  // Set the account for this loop.
		  Me::set_account($account);

			// Loop through the tables and just get the data that has changed.  
		  foreach($tables AS $key => $row)
		  {
				// We short cut the data getting part by calling an export function in the model.
				if(isset($row['table']) && isset($row['model']) && isset($row['export']) && $row['export'])
				{
					$data[$row['table']] = App::make($row['model'])->set_since($timestamp)->export();
					continue;
				}
		  
				// Deal with models.
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
          $d = $row['table']::export($timestamp, $row['noextra']);
					$data[$row['table']] = $this->_set_since_data($d, $row['table'], $row['keys']);
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

    // Do the column mapping.
    if(Input::get('colmap') && (Input::get('colmap') == 'true'))
    {
      $this->_do_col_mapping($data);
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
	// Do a column mapping.
	//
	private function _do_col_mapping(&$data)
	{
    $count = 0;
    $skip = [ 'DbInfo', 'ColMapping' ];
    
    foreach($data AS $key => $row)
    {
      if(in_array($key, $skip))
      {
        continue;
      }
      
      if(! isset($row[0]))
      {
        continue;
      }
      
      // Do column mapping
      foreach(array_keys($row[0]) AS $key2 => $row2)
      {
        if(isset($data['ColMapping'][$row2]))
        {
          continue;
        }
        
        $data['ColMapping'][$row2] = 'c' . $count;
        $count++; 
      }
      
      // Rewrite the column names.
      foreach($row AS $key2 => $row2)
      {
        foreach($row2 AS $key3 => $row3)
        {
          $data[$key][$key2][$data['ColMapping'][$key3]] = $row3;
          unset($data[$key][$key2][$key3]);
        }
      }
    }
	
	}
	
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
