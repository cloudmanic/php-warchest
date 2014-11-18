<?php 
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 12/12/2012
//

namespace Cloudmanic\WarChest\Libraries;

class Date
{
	//
	// Return a date by a month and a year. Is either "Today" 
	// or some date.
	//
	static public function recent_day($date, $style = 'n/j/Y')
	{
		$time = strtotime($date);
		
		if(($time > strtotime('-1 day')) && ($time < strtotime('+1 day')))
		{
			return 'Today';
		}
		
		return date($style, $time);
	}
	
	//
	// Return a user friendly string based on the time 
	// php time object we pass in.
	//
	static public function fancy_time_since(
		$start, $end = 'now', $mo_text = "Mo. Ago", $mo_one_text = "Month Ago",
		$d_text = 'Days Ago', $d_one_text = "Day Ago", $m_text = 'Min. Ago',
		$m_one_text = '1 Min. Ago', $h_one_text = "1 Hour Ago",
		$h_text = 'Hours Ago', $format = 'M j, Y')
	{
		// Calculate days / mins.
		$start_ts = strtotime($start);
		$end_ts = strtotime($end);
		$diff = $end_ts - $start_ts;
		if ($diff < 0)
		{
			$diff = 0;
		}
		
		$days = round($diff / 86400);
		$mins = round($diff / 60);
		
		if($mins < 0)
		{
			$mins = 0;
		}
		
		if($days > 330) 
		{
			$data = date($format, strtotime($start));
		} else if($days > 35)
		{
			$data = ceil($days / 30) . " $mo_text";
		} else if($days >= 30)
		{
			$data = "$mo_one_text";
		} else if($days == 1)
		{
		  $data = $days . " $d_one_text";
		} else if ($days > 1)
		{
		  $data = $days . " $d_text";
		} else if($mins <= 1)
		{
			$data = $m_one_text;	
		} else if($mins < 60)
		{
		  $data = $mins . " $m_text";
		} else if($mins > 120)
		{
		  $data = floor($mins / 60) . " $h_text";
		} else
		{
			$data = $h_one_text;
		}
		  
		return $data;
	}
}

/* End File */
