<?php
//Please run the following command to make git ignore changes to this file
//git update-index --assume-unchanged AWSConfig.php

if (!class_exists('S3'))require_once('Helpers/AWS/S3.php');

$s3Client = new S3('', '');

?>