<?php
require_once $BasePath . '/Models/APIModel.php';
require $BasePath . '/Schemas/Schema_Qewbe.php';

class QewbeAPI extends API
{
    public function __construct($request)
    {
        $instance = new MYSQLInstance(new QewbeSchema());
        $this->connection = $instance->Connection;
        parent::__construct($request, $instance);
    }

    public static function Test(API $instance)
    {
        return ResponseFactory::GenerateError(Response::E_NORETURN, 'WE DID IT REDDIT');
    }
}
?>