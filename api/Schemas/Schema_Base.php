<?php

abstract class Schema
{
    public $DB_PREFIX;

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

    /*
     * Generate a MYSQL query to add a foreign key to $table.
     * $keyReference must be passed as TName(TKey)
     */
    protected function generateCreateForeignKeyQuery($table, $keyName, $keyReference)
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