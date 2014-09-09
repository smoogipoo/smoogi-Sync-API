<?php
require_once $BasePath . '/Helpers/AWS/aws-autoloader.php';
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

        if ($_FILES['file']['error'] > 0)
            return ResponseFactory::GenerateError(Response::R_NODATA, "No file uploaded.");

        $fext = end(explode('.', $_FILES['file']['name']));
        $fhash = hash('sha256', $_FILES['file']['tmp_name']);
        $ftime = time();

        //Increment the file
        $current = $instance->Database->SelectRows('qewbe', 'nextfile');
        //Update the filename in the DB
        //This must be done ASAP to prevent conflicts
        $instance->Database->UpdateRows('qewbe', array( 'nextfile' => ++$current ));
        //Update the file counts in the DB
        $currentFileCount = $instance->Database->SelectRows('qewbe', 'filecount');
        $instance->Database->UpdateRows('qewbe', array( 'filecount' => ++$currentFileCount ));

        //Upload the file to S3
        $aws = \Aws\Common\Aws::factory('AWSConfig.php');
        $s3Client = $aws->get('s3');
        $s3Client->putObject(array
        (
            'Bucket' => 'u.qew.be',
            'Key' => $current . '.' . $fext,
            'SourceFile' => $_FILES['file']['tmp_name'],
            'Metadata' => array
            (
                'Hash' => $fhash,
                'Uploaded' => $ftime
            )
        ));
        $s3Client->waitUntil('ObjectExists', array
        (
            'Bucket' => 'u.qew.be',
            'Key' => $current . '.' . $fext
        ));

        //Add file for the user
        $user = QewbeAPI::getUserFromToken($instance, $_POST['token']);
        $instance->Database->InsertRows('filelist', array
        (
            'filename' => $current . '.' . $fext,
            'type' => $_FILES['file']['type'],
            'user_id' => $user['id'],
            'lastmodified' => $ftime,
            'hash' => $fhash
        ));
        return ResponseFactory::GenerateResponse(1, Response::R_DATACALLBACK, array( 'File' => $current));
    }

    /*
     * Returns all the files for the user that exist in AWS.
     */
    public static function GetFiles(API $instance)
    {
        $user = QewbeAPI::getUserFromToken($instance, $_GET['token']);
        $files = mysql_fetch_array($instance->Database->SelectRows('filelist', array( 'user_id' => $user['id'] )));

        $ret = array();
        foreach ($files as $row)
        {
            $file = array
            (
                'Name' => $row['filename'],
                'Type' => $row['type'],
                'Hash' => $row['hash'],
                'Uploaded' => $row['lastmodified']
            );
            array_push($ret, array( 'File' => $file ));
        }
        return ResponseFactory::GenerateResponse(1, Response::R_DATACALLBACK, array( 'Files' => $ret ));
    }
}
?>