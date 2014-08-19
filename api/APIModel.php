<?php

abstract class APIErrors extends API
{
	const E_NOSERVICE 			= 0;
	const E_NOCREDENTIALS 		= 1;
	const E_INVALIDCRETENDTIALS = 2;
	const E_INVALIDTOKEN		= 3;
	const E_USEREXISTS			= 4;
	const E_INVALIDEMAIL		= 5;
	const E_EMPTYCREDENTIALS	= 6;

	const E_INVALIDREQUESTTYPE	= -2;
	const E_NORETURN			= -1;
}

abstract class API
{
	private $method = '';

	private $endpt = '';

	private $verb = '';

	private $args = array();

	private $parameters = array();

	private $file = null;

	private $connection;

	private $instance;

	public function __construct($request, $instance)
	{
		$this->instance = $instance;

		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: *");
		header("Content-Type: application/json");

		$this->args = explode('/', rtrim($request, '/'));
		$this->endpt = array_shift($this->args);
		if (count($this->args) > 0 && !is_numeric($this->args[0]))
			$this->verb = array_shift($this->args);

		$this->method = $_SERVER['REQUEST_METHOD'];
		if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))
		{
			if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE')
				$this->method = 'DELETE';
			else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT')
				$this->method = 'PUT';
			else
				$this->method = 'ERR';
		}

		switch ($this->method)
		{
			case 'POST':
			case 'DELETE':
				$this->request = $this->cleanRequest($_POST);
				break;
			case 'GET':
				$this->request = $this->cleanRequest($_GET);
				break;
			case 'PUT':
				$this->request = $this->cleanRequest($_GET);
				$this->file = file_get_contents("php://input");
				break;
			default:
				$this->sendResponse('Invalid Method', 405);
				break;
		}
	}

	public function ProcessRequest()
	{		
		if ((int)method_exists($this, $this->endpt) > 0)
			return $this->sendResponse($this->{$this->endpt}($this->args));
		return $this->sendResponse("Endpoint does not exist: $this->endpt", 404);
	}

	private function cleanRequest($data)
	{

		$clean_data = Array();
		if (is_array($data))
		{
			foreach ($data as $k => $v)
				$clean_data[$k] = $this->cleanRequest($v);
		}
		else
			$clean_data = trim(strip_tags($data));
		return $clean_data;
	}

	private function sendResponse($response, $status = 200)
	{
		header("HTTP/1.1 $status " . $this->requestStatus($status));
		return json_encode($response);
	}

	private function generateError($error,$description = "")
	{
		$ret = array
		(
			"Description" => $description,
			"ErrorCode"   => $error
		);
		return $ret;
	}

	private function requestStatus($code)
	{
		$status = array 
		(
			200 => 'OK',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			500 => 'Internal Server Error'
		);
		return $status[$code]
			? $status[$code]
			: $status[500];
	}

	private function CreateAccount($args)
	{
		if ($this->method !== 'POST')
			return $this->generateError(APIErrors::E_INVALIDREQUESTTYPE, "Expected POST request type, received " . $this->method . '.');

		if (!isset($_POST['username'])
			|| !isset($_POST['password'])
			|| !isset($_POST['email']))
		{
			return $this->generateError(APIErrors::E_NOCREDENTIALS, "Incomplete credentials given.");
		}

		if (!isset($_POST['service']))
			return $this->generateError(APIErrors::E_NOSERVICE, "No service requested.");

		if ($_POST['username'] === "" || $_POST['password'] === "")
			return $this->generateError(APIErrors::E_EMPTYCREDENTIALS, "Empty credentials entered.");

		if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
			return $this->generateError(APIErrors::E_INVALIDEMAIL, "Invalid email address provided.");

		$found = $this->instance->SelectRows("users", array("username" => $_POST['username']));

		if (mysql_num_rows($found) == 0)
		{
			$result = $this->instance->InsertRows("users", array
				(
					"username" => $_POST['username'],
					"password" => $_POST['password'],
					"email"    => $_POST['email']
				));

			if (!$result)
				return $this->generateError(APIErrors::E_NORETURN, "Unable to create account.");
			
			return "User account created: " . $_POST['username'];
		}
		else
			return $this->generateError(APIErrors::E_USEREXISTS, "User already exists.");
	}

	private function Login()
	{
		return $this->generateError(APIErrors::E_NORETURN);
	}
}

?>