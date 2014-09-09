<?php
require_once $BasePath . '/Schemas/Schema_Base.php';

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
        'lastlogin' => 'DATE',
    );

    private $loggedInUsersTable = array
    (
        'username' => 'VARCHAR(100)',
        'token' => 'VARCHAR(32)',
        'tokenissued' => 'INT UNSIGNED'
    );

    private $fileListTable = array
    (
        'filename' => 'VARCHAR(255)',
        'lastmodified' => 'DATE',
        'hash' => 'VARCHAR(256)',
    );

    public function CreateSchema($database)
    {
        mysql_query("CREATE DATABASE IF NOT EXISTS $this->DB_NAME;", $database);
        mysql_select_db($this->DB_NAME);

        //Create the main table
        mysql_query($this->generateCreateTableQuery('Qewbe', $this->qewbeTable), $database);

        //Create the users tables
        mysql_query($this->generateCreateTableQuery('Users', $this->usersTable), $database);
        mysql_query($this->generateCreateTableQuery('Users_LoggedIn', $this->loggedInUsersTable), $database);

        //Create the FileList table
        mysql_query($this->generateCreateTableQuery('FileList', $this->fileListTable), $database);
        $fkey = "ALTER TABLE `qewbe_filelist` ADD CONSTRAINT `user_id` FOREIGN KEY (`id`) REFERENCES `qewbe_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;";
        mysql_query($fkey, $database);
    }
}

?>