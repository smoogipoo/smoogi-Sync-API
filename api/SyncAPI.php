<?php
require 'APIModel.php';
require 'Schemas/Schema_Sync.php';
require 'Helpers/RNG.php';

class SyncAPI extends API
{
    public function __construct($request)
    {
        $instance = new MYSQLInstance(new SyncSchema());
        $this->connection = $instance->Connection;
        parent::__construct($request, $instance);
    }

    public static function Test(API $instance)
    {
        return ResponseFactory::GenerateError(Response::E_NORETURN, 'WE DID IT REDDIT');
    }
}

if (!array_key_exists('HTTP_ORIGIN', $_SERVER))
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];

try
{
    $API = null;

    if (!isset($_REQUEST['service']))
        return;

    switch (strtolower($_REQUEST['service']))
    {
        case "sync":
            $API = new SyncAPI($_REQUEST['req']);
            break;
    }

    if ($API != null)
        echo $API->ProcessRequest();
}
catch (Exception $e)
{
    echo json_encode(array( 'error' => $e->getMessage() ));
}

?>