<?php

require 'APIModel.php';
require 'Helpers/MysqlHelper.php';
require 'Schemas/Schema_Sync.php';

class SyncAPI extends API
{
	public function __construct($request)
	{
		$instance = new MYSQLInstance(new SyncSchema());
		$this->connection = $instance->Connection;
		parent::__construct($request, $instance);
	}
}

if (!array_key_exists('HTTP_ORIGIN', $_SERVER))
	$_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];

try
{
	$API = new SyncAPI($_REQUEST['req']);
	echo $API->ProcessRequest();
}
catch (Exception $e)
{
	echo json_encode(array('error' => $e->getMessage()));
}

?>