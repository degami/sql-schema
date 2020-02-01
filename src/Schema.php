<?php

namespace Degami\SqlSchema;

use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;

class Schema
{
    /** @var string */
    private $name;

    /** @var array  [name => Table] */
    private $tables = [];

    /** @var \PDO|null */
    private $pdo;


    public function __construct($pdo = null)
    {
        $this->pdo = $pdo;

        if ($this->pdo instanceof \PDO) {
            $dbname = $pdo->query('SELECT DATABASE()')->fetchColumn();
            foreach ($pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN) as $key => $tablename) {
                $this->addTable(Table::readFromExisting($dbname, $tablename, $pdo));
            }
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
    * @param  string|Table
    * @return Table
    */
    public function addTable($name)
    {
        $table = null;

        if ($name instanceof Table) {
            $table = $name;
            $name = $table->getName();
        } else {
            $table = new Table($name);
        }

        if (isset($this->tables[$name])) {
            throw new DuplicateException("Table '$name' already exists.");
        }

        return $this->tables[$name] = $table;
    }

    /**
    * @param  string
    * @return Table|NULL
    */
    public function getTable($name)
    {
        if (isset($this->tables[$name])) {
            return $this->tables[$name];
        }

        throw new OutOfRangeException("Column not found");
    }

   /**
    * deletes Table
    * @param  string $name
    * @return self
    */
    public function deleteTable($name)
    {
        $this->getTable($name)->isDeleted(true);
        return $this;
    }

    /**
    * @return Table[]
    */
    public function getTables()
    {
        return $this->tables;
    }
}
