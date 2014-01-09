<?php
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 3/25/2013
//

namespace Cloudmanic\WarChest\Models\Accounts;

class Accounts extends \Cloudmanic\WarChest\Models\BasicModel
{		
	public static $joins = [
		[ 'table' => 'Users', 'right' => 'UsersId', 'left' => 'AccountsOwnerId' ]
	];
}

/* End File */