<?php
require_once $BasePath . '/Models/APIModel.php';
require $BasePath . '/Schemas/Schema_Sync.php';

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
?>