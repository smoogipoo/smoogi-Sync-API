<?php
require_once $BasePath . '/Helpers/Responses/ResponseFactory.php';
require_once $BasePath . '/Helpers/MYSQL/MysqlHelper.php';
require_once $BasePath . '/Helpers/RNG.php';

abstract class API
{
    public $Method = '';
    public $Database;
    private $endpt = '';
    private $verb = '';
    private $args = array();

    public function __construct($request, MYSQLInstance $instance)
    {
        $this->Database = $instance;

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Content-Type: application/json');

        $this->args = explode('/', rtrim($request, '/'));
        $this->endpt = array_shift($this->args);
        if (count($this->args) > 0 && !is_numeric($this->args[0]))
            $this->verb = array_shift($this->args);

        $this->Method = $_SERVER['REQUEST_METHOD'];
    }

    public function ProcessRequest()
    {
        if ((int)method_exists($this, $this->endpt) > 0)
        {
            if ($this->endpt == 'CreateAccount'
                || $this->endpt == 'Login' || $this->endpt == 'Logout')
            {
                return ResponseFactory::SendResponse($this->{$this->endpt}());
            }
            $resp = $this->isLoggedIn($this->endpt);
            if ($resp == null)
                return ResponseFactory::SendResponse("Endpoint does not exist: $this->endpt", 404);
            else
            {
                return ResponseFactory::SendResponse($resp);
            }
        }
        return null;
    }

    private function CreateAccount()
    {
        if ($this->Method !== 'POST')
            return ResponseFactory::GenerateError(Response::E_INVALIDREQUESTTYPE
                , 'Expected POST request type, received ' . $this->Method . '.');

        if (!isset($_POST['username'])
            || !isset($_POST['password'])
            || !isset($_POST['email']))
        {
            return ResponseFactory::GenerateError(Response::E_NOCREDENTIALS, 'Incomplete credentials given.');
        }

        if ($_POST['username'] === '' || $_POST['password'] === '')
            return ResponseFactory::GenerateError(Response::E_EMPTYCREDENTIALS, 'Empty credentials entered.');

        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
            return ResponseFactory::GenerateError(Response::E_INVALIDEMAIL, 'Invalid email address provided.');

        $found = $this->Database->SelectRows('users', array( 'username' => $_POST['username'] ));

        if (mysql_num_rows($found) == 0)
        {
            $result = $this->Database->InsertRows('users', array
            (
                'username' => $_POST['username'],
                'password' => $_POST['password'],
                'email' => $_POST['email']
            ));

            if (!$result)
                return ResponseFactory::GenerateError(Response::E_NORETURN, 'Unable to create account.');

            return ResponseFactory::GenerateResponse(1, Response::R_USERACCOUNTCREATED
                , array( 'username' => $_POST['username'] ));
        }
        else
            return ResponseFactory::GenerateError(Response::E_USEREXISTS, 'User already exists.');
    }

    /*
     * Performs the requested callback only if the user is authenticated
     * (token is valid).
     */
    private function isLoggedIn($callback)
    {
        if (!isset($_GET['token']))
            return ResponseFactory::GenerateError(Response::E_NOCREDENTIALS, 'No token issued.');

        $found = $this->Database->SelectRows('users_loggedin', array
        (
            'token' => $_GET['token']
        ));

        $arr = mysql_fetch_array($found);
        $rowCount = mysql_num_rows($found);
        if (mysql_num_rows($found) == 0
            || ($rowCount != 0 && (time() - $arr['tokenissued'] > 86400)))
        {
            return ResponseFactory::GenerateError(Response::E_NOTLOGGEDIN, 'Not logged in.');
        }
        if (!is_callable(array ( get_called_class(), $callback)))
            return null;
        return call_user_func(array( get_called_class(), $callback ), $this);
    }

    private function getLoggedInUsers($username)
    {
        return $this->Database->SelectRows('users_loggedin', array
        (
            'username' => $username
        ));
    }

    private function Login()
    {
        if ($this->Method !== 'GET')
            return ResponseFactory::GenerateError(Response::E_INVALIDREQUESTTYPE, 'Expected GET request type, received ' . $this->Method . '.');

        if (!isset($_GET['username'])
            || !isset($_GET['password']))
        {
            return ResponseFactory::GenerateError(Response::E_NOCREDENTIALS, 'Incomplete credentials given.');
        }

        $found = $this->Database->SelectRows('users', array
        (
            'username' => $_GET['username'],
            'password' => $_GET['password']
        ));

        if (mysql_num_rows($found) == 0)
            return ResponseFactory::GenerateError(Response::E_INVALIDCRETENDTIALS, 'Wrong username/password.');

        $currentExisting = $this->getLoggedInUsers($_GET['username']);

        $arr = mysql_fetch_array($currentExisting);
        $rowCount = mysql_num_rows($currentExisting);
        if ($rowCount != 0 && (time() - $arr['tokenissued']) > 86400)
        {
            //Expire token after 24 hours
            $this->Database->DeleteRows('users_loggedin', array( 'token' => $arr['token']));
            $rowCount = 0;
        }

        if ($rowCount == 0)
        {
            $tok = RNG::FixedString(32, RNG::ALPHANUMERICAL);
            $this->Database->InsertRows('users_loggedin', array
            (
                'username' => $_GET['username'],
                'token' => $tok,
                'tokenissued' => time()
            ));
        }

        $currentExisting = $this->getLoggedInUsers($_GET['username']);
        $arr = mysql_fetch_array($currentExisting);
        return ResponseFactory::GenerateResponse(1, Response::R_TOKENCALLBACK, array( 'token' => $arr['token'] ));
    }

    private function Logout()
    {
        if ($this->Method !== 'POST')
            return ResponseFactory::GenerateError(Response::E_INVALIDREQUESTTYPE, 'Expected POST request type, received ' . $this->Method . '.');

        if (!isset($_POST['token']))
            return ResponseFactory::GenerateError(Response::E_INVALIDTOKEN, 'No token provided.');

        $found = $this->Database->SelectRows('users_loggedin', array
        (
            'token' => $_POST['token']
        ));

        if (mysql_num_rows($found) != 0)
        {
            $this->Database->DeleteRows('users_loggedin', array
            (
                'token' => $_POST['token']
            ));

            return ResponseFactory::GenerateResponse(1, Response::R_LOGOUTSUCCESS);
        }
        else
            return ResponseFactory::GenerateError(Response::E_INVALIDTOKEN, $_POST['token']);
    }
}

?>