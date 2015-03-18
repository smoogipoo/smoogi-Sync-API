<?php
require_once './api/APIConfig.php';
require_once BASE_PATH . '/Helpers/MYSQL/MysqlHelper.php';
require_once BASE_PATH . '/Helpers/RNG.php'; 
require_once BASE_PATH . '/Schemas/Schema_Qewbe.php';
require_once BASE_PATH . '/Helpers/S3/S3.php';
require_once BASE_PATH . '/Helpers/S3/S3Config.php';

function returnFile($file)
{
	header('Content-Length: ' . filesize($file));
	readfile($file);
}

//Todo: MYSQL blob-cache for files

$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
$file = ltrim($_GET['res'], '/');

$s3Client = new S3(AWS_KEY, AWS_SECRET);
$db = new MYSQLInstance(new QewbeSchema());

$dbRes = $db->SelectRowsLimit('filelist', array('filename' => $file), 1);

//If we have no DB results, assume file doesn't exist and exit
if (mysql_num_rows($dbRes) < 1)
{
	header($protocol . ' 404 Not Found');
	$GLOBALS['http_response_code'] = 404;
	exit();
}

$locs = explode(',', mysql_fetch_array($dbRes)['locations']);

header($protocol . ' 200 OK');
header('Content-Type: ' . mysql_fetch_array($dbRes)['type']);

//If no locations, file hasn't been distributed, check locally
if (count($locs) == 0)
	returnFile(sprintf(UPLOAD_PATH_FILE, $file));
else
{
	//Todo: We could probably regionize this for best performance.
	//Having a regions enum in the DB might be helpful will clean this all up.
	$bucket = 0;
	foreach ($locs as $bucket)
	{
		if (empty($bucket))
			continue;
		try
		{
			//Download the object from the first bucket
			$f = sprintf(TEMP_PATH_FILE, RNG::FixedString(24, RNG::ALPHANUMERICAL));
			if ($s3Client->getObject($bucket, $file, $f))
			{
				returnFile($f);
				break;
			}
		}
		catch (Exception $e) { }
	}
}