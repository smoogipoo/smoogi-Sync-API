<?php

//Please run the following command to make git ignore changes to this file
//git update-index --assume-unchanged S3Config.php

if (!defined('AWS_KEY')) define('AWS_KEY', '');
if (!defined('AWS_SECRET')) define('AWS_SECRET', '');

static $Buckets = array('');