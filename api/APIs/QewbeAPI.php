<?php
include($BasePath . '/AWSConfig.php');
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
        //Find the user ID
        $loggedInUser = mysql_fetch_array($instance->Database->SelectRows('users_loggedin', array( 'token' => $token )));
        return mysql_fetch_array($instance->Database->SelectRows('users', array( 'username' => $loggedInUser['username'] )));
    }

    /*
     * Uploads a file to S3 and returns the filename.
     * Adds the filename to the database and increment
     * the in-database tracking variables.
     */
    public static function UploadFile(API $instance)
    {
        if ($instance->Method != 'POST')
            return ResponseFactory::GenerateError(Response::E_INVALIDREQUESTTYPE, 'Expected POST request type, received ' . $instance->Method . '.');

        if ($_FILES['file']['error'] > 0)
            return ResponseFactory::GenerateError(Response::R_NODATA, "No file uploaded.");

        $tmp = explode('.', $_FILES['file']['name']);
        $fext = end($tmp);
        $fhash = hash('sha256', $_FILES['file']['tmp_name']);
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
            //Increment the file
            $current = mysql_fetch_array($instance->Database->SelectTable('qewbe'))['nextfile'];
            //Update the filename in the DB
            //This must be done ASAP to prevent conflicts
            $instance->Database->UpdateRows('qewbe', array( 'nextfile' => ++$current ));
            //Update the file counts in the DB
            $currentFileCount = mysql_fetch_array($instance->Database->SelectTable('qewbe'))['filecount'];
            $instance->Database->UpdateRows('qewbe', array( 'filecount' => ++$currentFileCount ));
        }

        global $s3Client;
        $targetFilename = $current . '.' . $fext;
        $fileData = $_FILES['file']['tmp_name'];
        $contentType = $_FILES['file']['type'];

        if ($s3Client->putObject($fileData, QewbeAPI::DOMAIN, $targetFilename, S3::ACL_PUBLIC_READ, array(), $contentType))
        {
            //Add file for the user
            $user = QewbeAPI::getUserFromToken($instance, $_GET['token']);
            $instance->Database->InsertRows('filelist', array
            (
                'uid' => $user['id'],
                'filename' => $targetFilename,
                'type' => $contentType,
                'uploaded' => $ftime,
                'hash' => $fhash
            ));
            $file = array
            (
                'Name' => $targetFilename,
                'Domain' => QewbeAPI::DOMAIN,
                'Type' => $contentType,
                'Hash' => $fhash,
                'Uploaded' => $ftime
            );
            return ResponseFactory::GenerateResponse(1, Response::R_DATACALLBACK, array( 'File' => $file));
        }
        return ResponseFactory::GenerateError(Response::R_NODATA, 'Upload failed.');
    }

    /*
     * Returns all the files for the user that exist in AWS.
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
            array_push($ret, array( 'File' => $file ));
        }
        return ResponseFactory::GenerateResponse(1, Response::R_DATACALLBACK, array( 'Files' => $ret ));
    }
}
?>