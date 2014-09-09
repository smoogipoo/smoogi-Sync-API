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

    public static function UploadFile(API $instance)
    {

        if ($_FILES['file']['error'] > 0)
            return ResponseFactory::GenerateError(Response::R_NODATA, "No file uploaded.");

        $fext = end(explode('.', $_FILES['file']['name']));
        $fhash = hash('sha256', $_FILES['file']['tmp_name']);
        $ftime = time();

        //Increment the file
        $current = $instance->Database->SelectRows('qewbe', 'nextfile');
        $current++;
        //Update the filename in the DB ASAP
        $instance->Database->UpdateRows('qewbe', array( 'nextfile' => $current ));

        //Upload the file to S3
        $aws = \Aws\Common\Aws::factory('AWSConfig.php');
        $s3Client = $aws->get('s3');
        $result = $s3Client->putObject(array
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
            'user_id' => $user['id'],
            'lastmodified' => $ftime,
            'hash' => $fhash
        ));
        return ResponseFactory::GenerateResponse(1, Response::R_DATACALLBACK, array( 'file' => $current));
    }

    public static function GetFiles(API $instance)
    {
        $user = QewbeAPI::getUserFromToken($instance, $_GET['token']);
        $files = mysql_fetch_array($instance->Database->SelectRows('filelist', array( 'user_id' => $user['id'] )));

        $aws = \Aws\Common\Aws::factory('AWSConfig.php');
        $s3Client = $aws->get('s3');
        $iterator = $s3Client->getIterator('ListObjects', array( 'Bucket' => 'u.qew.be' ));
        $ret = array();
        foreach ($iterator as $object)
        {
            //Don't add files which the user doesn't own
            foreach ($files as $k => $v)
            {
                if ($k == 'filename' && $v != $object['Key'])
                    continue;
            }
            $file = array
            (
                'Name' => $object['Key'],
                'Hash' => $object['Metadata']['Hash'],
                'Uploaded' => $object['Metadata']['Uploaded']
            );
            array_push($ret, array( 'file' => $file ));
        }
        return ResponseFactory::GenerateResponse(1, Response::R_DATACALLBACK, array( 'files' => $ret ));
    }
}
?>