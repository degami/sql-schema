<?php

namespace Degami\SqlSchema;

use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use PDO;

class Schema
{
    /** @var string */
    private $name;

    /** @var array  [name => Table] */
    private $tables = [];

    /** @var PDO|null */
    private $pdo;

    /**
     * Schema constructor.
     *
     * @param PDO|null $pdo
     * @param bool $preload
     * @throws DuplicateException
     * @throws Exceptions\EmptyException
     * @throws OutOfRangeException
     */
    public function __construct($pdo = null, $preload = false)
    {
        $this->pdo = $pdo;

        $this->init($preload);
    }

    /**
     * @param bool $preload
     * @return self
     * @throws DuplicateException
     * @throws Exceptions\EmptyException
     * @throws OutOfRangeException
     */
    private function init(bool $preload): Schema
    {
        $this->tables = [];

        if ($this->pdo instanceof PDO) {
            $this->name = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
            if ($preload) {
                $this->preload();
            }
        }

        return $this;
    }

    /**
     * preloads database state
     *
     * @return self
     * @throws DuplicateException
     * @throws Exceptions\EmptyException
     * @throws OutOfRangeException
     */
    public function preload(): Schema
    {
        if ($this->pdo instanceof PDO) {
            foreach ($this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $key => $table_name) {
                $this->addTable(Table::readFromExisting($this->name, $table_name, $this->pdo));
            }
        }

        return $this;
    }

    /**
     * resets data
     *
     * @param false $preload
     * @return self
     * @throws DuplicateException
     * @throws Exceptions\EmptyException
     * @throws OutOfRangeException
     */
    public function reset($preload = false): Schema
    {
        return $this->init($preload);
    }

    /**
     * gets schema name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * adds a table
     *
     * @param string|Table $name
     * @return Table
     * @throws DuplicateException
     */
    public function addTable($name): Table
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
     * gets a table
     *
     * @param string $table_name
     * @return Table|NULL
     * @throws DuplicateException
     * @throws Exceptions\EmptyException
     * @throws OutOfRangeException
     */
    public function getTable(string $table_name): ?Table
    {
        if (isset($this->tables[$table_name])) {
            return $this->tables[$table_name];
        }

        if ($this->pdo instanceof PDO) {
            $existing = count($this->pdo->query("SHOW TABLES LIKE '$table_name'")->fetchAll(PDO::FETCH_COLUMN)) > 0;
            if ($existing) {
                $this->addTable(Table::readFromExisting($this->name, $table_name, $this->pdo));
            } else {
                $this->addTable($table_name);
            }

            return $this->tables[$table_name];
        }

        throw new OutOfRangeException("Table '$table_name' not found");
    }

    /**
     * deletes a table
     *
     * @param string $name
     * @return self
     * @throws DuplicateException
     * @throws Exceptions\EmptyException
     * @throws OutOfRangeException
     */
    public function deleteTable(string $name): Schema
    {
        $this->getTable($name)->isDeleted(true);
        return $this;
    }

    /**
     * gets defined tables
     *
    * @return Table[]
    */
    public function getTables(): array
    {
        return $this->tables;
    }
}
