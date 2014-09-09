<?php

abstract class Schema
{
    public $DB_PREFIX;
    protected $DB_NAME;

    public function __construct($dbname, $dbprefix)
    {
        $this->DB_NAME = $dbname;
        $this->DB_PREFIX = $dbprefix;
    }


    public function CreateSchema($database) { }

    /*
      * Generate a MYSQL create table string with the requested $table name containing $rows rows.
      * $rows is an array type of name => type.
      * Ex: $rows = array("Name" => "VARCHAR(50)");
      */
    protected function generateCreateTableQuery($table, $rows)
    {
        $tableQuery = "CREATE TABLE IF NOT EXISTS $this->DB_PREFIX" . "$table (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)";

        foreach ($rows as $k => $v)
            $tableQuery .= ', ' . $k . ' ' . $v;
        $tableQuery .= ');';

        return $tableQuery;
    }
}

?>