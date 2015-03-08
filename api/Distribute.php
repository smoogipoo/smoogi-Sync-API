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

/*
 * Synchronizes the files between all buckets.
 */
function reSync()
{
	global $s3Client;
	global $Buckets;

	//Get the initial list of files from all buckets as array(bucketname => array(files))
	$bucketFiles = array();
	foreach ($Buckets as $b)
	{
		$files = array();
		foreach ($s3Client->getBucket($b) as $name => $value)
			$files[$value['name']] = $value;
		$bucketFiles[$b] = $files;
	}

	//Compare buckets, for all missing files,
	//create an array(fromBucket => array(toBucket => file))
	$diff = array();
	for ($b = 0; $b < count($Buckets); $b++)
	{
		for ($bNext = $b + 1; $bNext < count($Buckets); $bNext++)
		{
			//Files missing in Buckets[b]
			$missingFiles = getDiff($bucketFiles[$Buckets[$b]], $bucketFiles[$Buckets[$bNext]]);
			if (count($missingFiles) != 0)
			{
				$diff[$Buckets[$b]] = array($Buckets[$bNext] => $missingFiles);
				$bucketFiles[$Buckets[$b]] = array_merge($bucketFiles[$Buckets[$b]], $missingFiles);
			}

			//Files missing in Buckets[bNext]
			$missingFiles = getDiff($bucketFiles[$Buckets[$bNext]], $bucketFiles[$Buckets[$b]]);
			if (count($missingFiles) != 0)
			{
				$diff[$Buckets[$bNext]] = array($Buckets[$b] => $missingFiles);
				$bucketFiles[$Buckets[$bNext]] = array_merge($bucketFiles[$Buckets[$bNext]], $missingFiles);
			}
		}
	}

	//Copy missing files over between buckets
	foreach ($diff as $fromBucket => $toArray)
		foreach ($toArray as $toBucket => $files)
			foreach ($files as $file)
				$s3Client->copyObject($fromBucket, $file['name'], $toBucket, $file['name']);
}

function getDiff($list1, $list2)
{
	return array_diff(array_merge($list1, $list2), $list2);
}

//Distribute initial files
distributeFolder($UploadPath);

//Resync all buckets
reSync();
?>