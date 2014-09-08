<?php
require 'Schema_Base.php';

class SyncSchema implements Schema
{
	public $DB_NAME = 'sync';
	public $DB_PREFIX = 'sync_';

	private $usersTable = array
	(
		"username"		=> "VARCHAR(100)",
		"password"		=> "VARCHAR(256)",
		"email"			=> "VARCHAR(254)",
		"lastlogin"		=> "DATE",
	);

	private $loggedInUsersTable = array
	(
		"username"		=> "VARCHAR(100)",
		"token"			=> "VARCHAR(32)",
        "tokenissued"   => "INT UNSIGNED"
	);

	private $fileListTable = array
	(
		"filename"		=> "VARCHAR(255)",
		"lastmodified"	=> "DATE",
		"hash"			=> "VARCHAR(256)",
	);

	public function CreateSchema($database)
	{
		mysql_query("CREATE DATABASE IF NOT EXISTS $this->DB_NAME;", $database);
		mysql_select_db($this->DB_NAME);

		//Create the users tables
		mysql_query($this->GenerateCreateTableQuery('Users', $this->usersTable), $database);
		mysql_query($this->GenerateCreateTableQuery('Users_LoggedIn', $this->loggedInUsersTable), $database);

		//Create the FileList table
		mysql_query($this->GenerateCreateTableQuery('FileList', $this->fileListTable), $database);
		mysql_query($this->GenerateCreateForeignKeyQuery('FileList', 'user_id', '`Users`(`id`)'), $database);
	}

	/*
	 * Generate a MYSQL create table string with the requested $table name containing $rows rows.
	 * $rows is an array type of name => type.
	 * Ex: $rows = array("Name" => "VARCHAR(50)");
	 */
    private function GenerateCreateTableQuery($table, $rows)
	{
		$tableQuery = "CREATE TABLE IF NOT EXISTS $this->DB_PREFIX" . "$table (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)";

		foreach ($rows as $k => $v)
			$tableQuery .= ', ' . $k . ' ' . $v;
		$tableQuery .= ');';

		return $tableQuery;
	}

    /*
     * Generate a MYSQL query to add a foreign key to $table.
     * $keyReference must be passed as TName(TKey)
     */
    private function GenerateCreateForeignKeyQuery($table, $keyName, $keyReference)
	{
		$tableQuery = "IF NOT EXISTS (SELECT NULL FROM information_schema.TABLE_CONSTRAINTS WHERE " .
			"CONSTRAINT_SCHEMA = DATABASE() AND " .
			"CONSTRAINT_NAME   = $keyName AND " .
			"CONSTRAINT_TYPE   = 'FOREIGN KEY') THEN " .
		    "ALTER TABLE $this->DB_PREFIX" . "$table ADD CONSTRAINT $keyName FOREIGN KEY ($keyName) REFERENCES $keyReference;";

	    return $tableQuery;
	}
}

?>