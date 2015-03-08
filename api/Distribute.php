<?php

require 'APIConfig.php';
require $BasePath . '/Helpers/S3/S3.php';
require $BasePath . '/Helpers/S3/S3Config.php';

$s3Client = new S3(AWS_KEY, AWS_SECRET);

function distributeFolder($dir)
{
	$files = array_diff(scandir($dir), array('..', '.'));
	foreach ($files as $k => $v)
	{
		$loc = $dir . DIRECTORY_SEPARATOR . $v;
		if (is_dir($loc))
			distributeFolder($loc);
		else
			distributeFile($loc);
	}
}

function distributeFile($file)
{
	global $UploadPath;
	global $Buckets;
	global $s3Client;

	foreach ($Buckets as $bucket)
	{
		$fName = ltrim(str_replace($UploadPath, '', $file), '/');

		if (!$s3Client->putObject($s3Client->inputFile($file, false), $bucket, $fName))
			error_log("Failed to move $file into $bucket.");
	}
}

distributeFolder($UploadPath);

?>