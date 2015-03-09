<?php
require_once BASE_PATH . '/Schemas/Schema_Base.php';

class QewbeSchema extends Schema
{
    public function __construct()
    {
        parent::__construct('qewbe', 'qewbe_');
    }

    private $qewbeTable = array
    (
        'filecount' => 'INT UNSIGNED',
        'nextfile' => 'VARCHAR(10)'
    );

    private $usersTable = array
    (
        'username' => 'VARCHAR(100)',
        'password' => 'VARCHAR(256)',
        'email' => 'VARCHAR(254)',
        'lastlogin' => 'INT UNSIGNED',
    );

    private $loggedInUsersTable = array
    (
        'username' => 'VARCHAR(100)',
        'token' => 'VARCHAR(32)',
        'tokenissued' => 'INT UNSIGNED'
    );

    private $fileListTable = array
    (
        'uid' => 'INT UNSIGNED',
        'filename' => 'VARCHAR(255)',
        'type' => 'VARCHAR(50)',
        'uploaded' => 'INT UNSIGNED',
        'hash' => 'VARCHAR(256)',
        'locations' => 'VARCHAR(256)'
    );

    public function CreateSchema($database)
    {
        mysql_query("CREATE DATABASE IF NOT EXISTS $this->DB_NAME;", $database);
        mysql_select_db($this->DB_NAME);

        //Create the main table
        mysql_query($this->generateCreateTableQuery('qewbe', $this->qewbeTable), $database);

        //Create the users tables
        mysql_query($this->generateCreateTableQuery('users', $this->usersTable), $database);
        mysql_query($this->generateCreateTableQuery('users_loggedin', $this->loggedInUsersTable), $database);

        //Create the FileList table
        mysql_query($this->generateCreateTableQuery('filelist', $this->fileListTable), $database);
    }
}

?>