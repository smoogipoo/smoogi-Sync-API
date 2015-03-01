<?php
require 'APIConfig.php';
require $BasePath . '/APIs/SyncAPI.php';
require $BasePath . '/APIs/QewbeAPI.php';

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