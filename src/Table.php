<?php

namespace Degami\SqlSchema;

use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\EmptyException;

class Table
{
    /**
    * @var string
    */
    private $name;

    /**
    * @var string|NULL
    */
    private $comment;

    /**
    * @var array  [name => Column]
    */
    private $columns = [];

    /**
    * @var array  [name => Index]
    */
    private $indexes = [];

    /**
    * @var array  [name => ForeignKey]
    */
    private $foreignKeys = [];

    /**
    * @var array  [name => value]
    */
    private $options = [];

    /**
    * @var string storage engine
    */
    private $storageEngine = null;

    /**
    * @param string
    */
    public function __construct($name)
    {
        $this->name = $name;
    }

   /**
    * get Name
    * @return string
    */
    public function getName()
    {
        return $this->name;
    }

   /**
    * set Comment
    * @param string $comment
    * @return  self
    */
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

   /**
    * get Comment
    * @return string
    */
    public function getComment()
    {
        return $this->comment;
    }

   /**
    * set Option
    * @param string $name
    * @param string $value
    * @return self
    */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

   /**
    * get Options
    * @return array
    */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string storage engine
     */
    public function getStorageEngine()
    {
        return $this->storageEngine;
    }

    /**
     * @param string storage engine $storageEngine
     *
     * @return self
     */
    public function setStorageEngine($storageEngine)
    {
        $this->storageEngine = $storageEngine;

        return $this;
    }

    /**
     * add Column
     * @param string  $name
     * @param string  $type
     * @param mixed   $parameters
     * @param array   $options
     * @param boolean $nullable
     * @param mixed   $default
     * @return self
     */
    */
    public function addColumn($name, $type = null, $parameters = null, array $options = [], $default = null)
    {
        $column = null;

        if ($name instanceof Column) {
            $column = $name;
            $name = $column->getName();
        } else {
            $column = new Column($name, $type, $parameters, $options, $default);
        }

        if (isset($this->columns[$name])) {
            throw new DuplicateException("Column '$name' in table '{$this->getName()}' already exists.");
        }

        $this->columns[$name] = $column;
        return $this;
    }

   /**
    * get Column
    * @param  string $name
    * @return Column|null
    */
    public function getColumn($name)
    {
        if (isset($this->columns[$name])) {
            return $this->columns[$name];
        }
        return null;
    }

    /**
     * get Columns
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

   /**
    * add Index
    * @param string|Index $name
    * @param array  $Columns
    * @param string $type
    */
    public function addIndex($name, $columns = [], $type = Index::TYPE_INDEX)
    {
        $index = null;

        if ($name instanceof Index) {
            $index = $name;
            $name = $index->getName();
        } else {
            $index = new Index($name, $columns, $type);
            $name = $index->getName();
        }

        if (isset($this->indexes[$name])) {
            throw new DuplicateException("Index '$name' in table '{$this->getName()}' already exists.");
        }

        $this->indexes[$name] = $index;
        return $this;
    }

   /**
    * get Index
    * @param  string $name
    * @return Index|null
    */
    public function getIndex($name)
    {
        if (isset($this->indexes[$name])) {
            return $this->indexes[$name];
        }
        return null;
    }

   /**
    * get Indexes
    * @return Index[]
    */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * set AutoIncrement Column
     * @param string $columnName
     * @return self
     */
    public function setAutoIncrementColumn($columnName)
    {
        if ($this->getColumn($columnName) == null) {
            throw new EmptyException("Column not found", 1);
        }
        foreach ($this->getColumns() as $key => &$colum) {
            $colum->setAutoIncrement(false);
        }
        $this->getColumn($columnName)->setAutoIncrement(true);

        return $this;
    }

   /**
    * add Foreign Key
    * @param string|ForeignKey $name
    * @param array  $columns
    * @param string $targetTable
    * @param array  $targetColumns
    * @return ForeignKey
    */
    public function addForeignKey($name, $columns = [], $targetTable = null, $targetColumns = [], $onUpdateAction = ForeignKey::ACTION_RESTRICT, $onDeleteAction = ForeignKey::ACTION_RESTRICT)
    {
        $foreignKey = null;

        if ($name instanceof ForeignKey) {
            $foreignKey = $name;
            $name = $foreignKey->getName();
        } else {
            $foreignKey = new ForeignKey($name, $columns, $targetTable, $targetColumns, $onUpdateAction, $onDeleteAction);
            $name = $foreignKey->getName();
        }

        if (isset($this->foreignKeys[$name])) {
            throw new DuplicateException("Foreign key '$name' in table '{$this->getName()}' already exists.");
        }

        $this->foreignKeys[$name] = $foreignKey;
        return $this;
    }

    /**
    * @param  string
    * @return ForeignKey|NULL
    */
    public function getForeignKey($name)
    {
        if (isset($this->foreignKeys[$name])) {
            return $this->foreignKeys[$name];
        }
        return null;
    }

   /**
    * get Foreign Keys
    * @return ForeignKey[]
    */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

   /**
    * validate
    * @throws Exception
    * @return void
    */
    public function validate()
    {
        $tableName = $this->getName();

        if (empty($this->columns)) {
            throw new EmptyException("Table '$tableName' hasn't columns.");
        }

        $hasPrimaryIndex = false;

        foreach ($this->getIndexes() as $index) {
            if ($index->getType() === Index::TYPE_PRIMARY) {
                if ($hasPrimaryIndex) {
                    throw new DuplicateException("Duplicated primary index in table '$tableName'.");
                }
                $hasPrimaryIndex = true;
            }
        }
    }

   /**
    * show Create
    * @return string
    */
    public function showCreate()
    {
        $out = "CREATE TABLE `" . $this->getName() . "` (\n";
        $columns = [];
        foreach ($this->getColumns() as $k => $column) {
            $columns[] = $column->render();
        }

        $indexes = [];
        foreach ($this->getIndexes() as $key => $index) {
            $indexes[] = $index->render();
        }

        $foreigns = [];
        foreach ($this->getForeignKeys() as $key => $foreign) {
            $foreigns[] = $foreign->render();
        }

        $out .= implode(",\n", $columns);
        $out .= ((!empty($indexes) || !empty($foreigns)) ? ',':'')."\n";

        if (!empty($indexes)) {
            $out .= implode(",\n", $indexes);
        }
        $out .= ((!empty($foreigns)) ? ',':'')."\n";

        if (!empty($foreigns)) {
            $out .= implode(",\n", $foreigns);
        }
        $out .= ((!empty($foreigns)) ? "\n":'');

        $out .= ")";
        if ($this->getStorageEngine()) {
            $out .= " ENGINE = ".$this->getStorageEngine();
        }
        $out .= ";";

        return $out;
    }
}
