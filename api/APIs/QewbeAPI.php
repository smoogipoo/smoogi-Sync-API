<?php
require_once $BasePath . '/Models/APIModel.php';
require $BasePath . '/Schemas/Schema_Qewbe.php';
require_once $BasePath . '/Helpers/RNG.php';

class QewbeAPI extends API
{
    const DOMAIN = 'http://qew.be';

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
    	global $UploadPath;

        if ($instance->Method != 'POST')
            return ResponseFactory::GenerateError(Response::E_INVALIDREQUESTTYPE, 'Expected POST request type, received ' . $instance->Method . '.');

        if (!isset($_FILES['file']))
        	return ResponseFactory::GenerateError(Response::R_INVALIDDATA, 'File is missing.');

        if (!isset($_FILES['file']['name']) || strrpos($_FILES['file']['name'], '.') == FALSE)
        	return ResponseFactory::GenerateError(Response::R_INVALIDDATA, 'File name or extension is missing.');

        if (!isset($_FILES['file']['type']))
        	return ResponseFactory::GenerateError(Response::R_INVALIDDATA, 'Mime type is missing');

        $user = QewbeAPI::getUserFromToken($instance, $_GET['token']);

        $ext = substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.'));
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
            $current = RNG::IncrementalString($tableArr['nextfile'], RNG::ALPHANUMERICAL);
            $currentFileCount = $tableArr['filecount'];
            $instance->Database->UpdateRows('qewbe', array
            (
                'filecount' => ++$currentFileCount,
                'nextfile' => $current
            ));
        }

        $targetFilename = $current . $ext;

        $hash = hash_file('sha256', $_FILES['file']['tmp_name']);
        if (!move_uploaded_file($_FILES['file']['tmp_name'], sprintf($UploadPath, $targetFilename)))
        	return ResponseFactory::GenerateError(Response::E_INTERNALERROR, 'Moving file failed.');

        //Add file for the user
        $instance->Database->InsertRows('filelist', array
        (
            'uid' => $user['id'],
            'filename' => $targetFilename,
            'type' => $_FILES['file']['type'],
            'uploaded' => $ftime,
            'hash' => $hash
        ));

        $file = array
        (
            'Name' => $targetFilename,
            'Domain' => QewbeAPI::DOMAIN,
            'Type' => $_FILES['file']['type'],
            'Hash' => $hash,
            'Uploaded' => $ftime
        );

        return ResponseFactory::GenerateResponse(1, Response::R_DATACALLBACK, array( 'File' => $file));
    }

    /*
     * Deletes a user's file.
     * method: GET
     * params: name
     */
    public static function RemoveFile(API $instance)
    {
        if ($instance->Method != 'GET')
            return ResponseFactory::GenerateError(Resposne::E_INVALIDREQUESTTYPE, 'Expected GET request type, received' . $instance->Method . '.');

        $user = QewbeAPI::getUserFromToken($instance, $_GET['token']);
        if (!$instance->Database->DeleteRows('filelist', array( 'filename' => $_GET['name'], 'uid' => $user['id'])))
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