<?php
require 'APIConfig.php';
require 'APIs/SyncAPI.php';
require 'APIs/QewbeAPI.php';

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
        case "qewbe":
            $API = new QewbeAPI($_REQUEST['req']);
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