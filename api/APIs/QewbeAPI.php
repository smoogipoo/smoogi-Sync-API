<?php
require_once $BasePath . '/Models/APIModel.php';
require $BasePath . '/Schemas/Schema_Qewbe.php';

class QewbeAPI extends API
{
    const DOMAIN = 'u.qew.be';

    public function __construct($request)
    {
        $instance = new MYSQLInstance(new QewbeSchema());
        $this->connection = $instance->Connection;
        parent::__construct($request, $instance);
    }

    private static function getUserFromToken(API $instance, $token)
    {
        //Convert token to usename
        $loggedInUser = mysql_fetch_array($instance->Database->SelectRows('users_loggedin', array( 'token' => $token )));
        return mysql_fetch_array($instance->Database->SelectRows('users', array( 'username' => $loggedInUser['username'] )));
    }

    /*
     * Updates and returns the next file.
     * method: GET
     * params: ext, hash, type,
     */
    public static function UploadFile(API $instance)
    {
        if ($instance->Method != 'GET')
            return ResponseFactory::GenerateError(Response::E_INVALIDREQUESTTYPE, 'Expected GET request type, received ' . $instance->Method . '.');

        if (!isset($_GET['ext'])
            || !isset($_GET['hash'])
            || !isset($_GET['type']))
        {
            return ResponseFactory::GenerateError(Response::R_INVALIDDATA, 'File extension, hash or type is missing.');
        }

        $ext = $_GET['ext'];
        if (strlen($ext) > 0 && $ext[0] != '.')
            $ext = '.' . $ext;
        $ftime = time();

        $current = 'a';
        $currentFileCount = 1;

        if (mysql_num_rows($instance->Database->SelectTable('qewbe')) == 0)
        {
            //First file ever, woohoo!
            $instance->Database->InsertRows('qewbe', array
            (
                'nextfile' => $current,
                'filecount' => $currentFileCount
            ));
        }
        else
        {
            $tableArr = mysql_fetch_array($instance->Database->SelectTable('qewbe'));
            $current = $tableArr['nextfile'];
            $currentFileCount = $tableArr['filecount'];
            $instance->Database->UpdateRows('qewbe', array
            (
                'filecount' => ++$currentFileCount,
                'nextfile' => ++$current
            ));
        }

        $targetFilename = $current . $ext;
        //Add file for the user
        $user = QewbeAPI::getUserFromToken($instance, $_GET['token']);
        $instance->Database->InsertRows('filelist', array
        (
            'uid' => $user['id'],
            'filename' => $targetFilename,
            'type' => $_GET['type'],
            'uploaded' => $ftime,
            'hash' => $_GET['hash']
        ));
        $file = array
        (
            'Name' => $targetFilename,
            'Domain' => QewbeAPI::DOMAIN,
            'Type' => $_GET['type'],
            'Hash' => $_GET['hash'],
            'Uploaded' => $ftime
        );
        return ResponseFactory::GenerateResponse(1, Response::R_DATACALLBACK, array( 'File' => $file));
    }

    /*
     * Deletes a user's file.
     * method: GET
     * params: hash
     */
    public static function RemoveFile(API $instance)
    {
        if ($instance->Method != 'GET')
            return ResponseFactory::GenerateError(Resposne::E_INVALIDREQUESTTYPE, 'Expected GET request type, received' . $instance->Method . '.');

        $user = QewbeAPI::getUserFromToken($instance, $_GET['token']);
        if (!$instance->Database->DeleteRows('filelist', array( 'Hash' => $_GET['hash'] )))
            return ResponseFactory::GenerateError(Response::E_FILEDOESNTEXIST, 'The requested file hash doesn\'t exist.');
        return ResponseFactory::GenerateResponse(1, Response::R_DATACALLBACK, 'File deleted.');
    }

    /*
     * Returns all the files for the user.
     * method: GET
     */
    public static function GetFiles(API $instance)
    {
        if ($instance->Method != 'GET')
            return ResponseFactory::GenerateError(Response::E_INVALIDREQUESTTYPE, 'Expected GET request type, received ' . $instance->Method . '.');

        $user = QewbeAPI::getUserFromToken($instance, $_GET['token']);
        $files = mysql_fetch_array($instance->Database->SelectRows('filelist', array( 'uid' => $user['id'] )));

        $ret = array();
        foreach ($files as $row)
        {
            $file = array
            (
                'Name' => $row['filename'],
                'Domain' => QewbeAPI::DOMAIN,
                'Type' => $row['type'],
                'Hash' => $row['hash'],
                'Uploaded' => $row['uploaded']
            );
            array_push($ret, $file);
        }
        return ResponseFactory::GenerateResponse(1, Response::R_DATACALLBACK, array( 'Files' => $ret ));
    }
}
?>