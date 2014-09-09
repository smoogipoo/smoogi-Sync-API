<?php
require $BasePath . '/Helpers/Responses/Response.php';

class ResponseFactory
{
    public static function SendResponse($response, $status = 200)
    {
        header("HTTP/1.1 $status " . ResponseFactory::requestStatus($status));
        return json_encode($response);
    }

    private static function requestStatus($code)
    {
        $status = array
        (
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error'
        );
        return $status[$code]
            ? $status[$code]
            : $status[500];
    }

    public static function GenerateError($error, $description = '')
    {
        if ($description != '')
            return ResponseFactory::GenerateResponse(0, $error, $description);
        else
            return ResponseFactory::GenerateResponse(0, $error);
    }

    public static function GenerateResponse($ok, $response, $data = array())
    {
        $okstatus = $ok == 1 ? true : false;
        $ret = array
        (
            'OK' => $okstatus,
            'Response' => $response,
        );
        if (!empty($data))
            $ret['Data'] = $data;
        return $ret;
    }
}
?>