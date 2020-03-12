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


    public function __construct($pdo = null, $preload = false)
    {
        $this->pdo = $pdo;

        $this->init($preload);
    }

    private function init($preload)
    {
        $this->tables = [];

        if ($this->pdo instanceof \PDO) {
            $this->name = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
            if ($preload) {
                $this->preload();
            }
        }

        return $this;
    }

    public function preload()
    {
        if ($this->pdo instanceof \PDO) {
            foreach ($this->pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN) as $key => $tablename) {
                $this->addTable(Table::readFromExisting($this->name, $tablename, $this->pdo));
            }
        }

        return $this;
    }

    public function reset($preload = false)
    {
        return $this->init($preload);
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
    public function getTable($tablename)
    {
        if (isset($this->tables[$tablename])) {
            return $this->tables[$tablename];
        }

        if ($this->pdo instanceof \PDO) {
            $this->addTable(Table::readFromExisting($this->name, $tablename, $this->pdo));

            return $this->tables[$tablename];
        }

        throw new OutOfRangeException("Table not found");
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
