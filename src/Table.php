<?php

namespace Degami\SqlSchema;

use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\EmptyException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use Degami\SqlSchema\Abstracts\DBComponent;

class Table extends DBComponent
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
    public function __construct($name, $existing_on_db = false)
    {
        $this->name = $name;
        $this->isExistingOnDb(boolval($existing_on_db));
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
        $this->isModified(true);
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
        $this->isModified(true);
        return $this;
    }

    /**
     * add Column
     * @param string $name
     * @param string $type
     * @param mixed $parameters
     * @param array $options
     * @param boolean $nullable
     * @param mixed $default
     * @return self
     */
    public function addColumn($name, $type = null, $parameters = null, array $options = [], $nullable = true, $default = null, $existing_on_db = false)
    {
        $column = null;

        if ($name instanceof Column) {
            $column = $name;
            $name = $column->getName();
        } else {
            $column = new Column($name, $this, $type, $parameters, $options, $nullable, $default, $existing_on_db);
        }

        if (isset($this->columns[$name])) {
            throw new DuplicateException("Column '$name' in table '{$this->getName()}' already exists.");
        }

        $this->columns[$name] = $column;
        if (!$existing_on_db) {
            $this->isModified(true);
        }
        return $this;
    }

    /**
     * moves a Column after another
     *
     * @param $name
     * @param $after
     * @return $this
     * @throws OutOfRangeException
     */
    public function moveColumn($name, $after)
    {
        if ($this->getColumn($name) && $this->getColumn($after)) {
            $this->getColumn($name)->setColumnPosition($after)->isModified(true);
        }

        return $this;
    }

   /**
    * deletes Column
    * @param  string $name
    * @return self
    */
    public function deleteColumn($name)
    {
        $this->getColumn($name)->isDeleted(true);
        return $this;
    }

    /**
     * get Column
     *
     * @param $name
     * @return Column|null
     * @throws OutOfRangeException
     */
    public function getColumn($name)
    {
        if (isset($this->columns[$name])) {
            return $this->columns[$name];
        }

        throw new OutOfRangeException("Column `{$name}` not found in table `{$this->name}`");
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
    public function addIndex($name, $columns = [], $type = Index::TYPE_INDEX, $existing_on_db = false)
    {
        $index = null;

        if ($name instanceof Index) {
            $index = $name;
            $name = $index->getName();
        } else {
            $index = new Index($name, $this, $columns, $type, $existing_on_db);
            $name = $index->getName();
        }

        if (isset($this->indexes[$name])) {
            throw new DuplicateException("Index '$name' in table '{$this->getName()}' already exists.");
        }

        $this->indexes[$name] = $index;
        if (!$existing_on_db) {
            $this->isModified(true);
        }
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

        throw new OutOfRangeException("Index not found");
    }

   /**
    * deletes Index
    * @param  string $name
    * @return self
    */
    public function deleteIndex($name)
    {
        $this->getIndex($name)->isDeleted(true);
        return $this;
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
    public function setAutoIncrementColumn($columnName, $set_modified = true)
    {
        if ($this->getColumn($columnName) == null) {
            throw new EmptyException("Column not found", 1);
        }
        foreach ($this->getColumns() as $key => &$colum) {
            $colum->setAutoIncrement(false, $set_modified);
        }
        $this->getColumn($columnName)->setAutoIncrement(true, $set_modified);
        $this->isModified($set_modified);

        return $this;
    }

   /**
    * add Foreign Key
    * @param string|ForeignKey $name
    * @param array  $columns
    * @param string $targetTable
    * @param array  $targetColumns
    * @return self
    */
    public function addForeignKey($name, $columns = [], $targetTable = null, $targetColumns = [], $onUpdateAction = ForeignKey::ACTION_RESTRICT, $onDeleteAction = ForeignKey::ACTION_RESTRICT, $existing_on_db = false)
    {
        $foreignKey = null;

        if ($name instanceof ForeignKey) {
            $foreignKey = $name;
            $name = $foreignKey->getName();
        } else {
            $foreignKey = new ForeignKey($name, $this, $columns, $targetTable, $targetColumns, $onUpdateAction, $onDeleteAction, $existing_on_db);
            $name = $foreignKey->getName();
        }

        if (isset($this->foreignKeys[$name])) {
            throw new DuplicateException("Foreign key '$name' in table '{$this->getName()}' already exists.");
        }

        $this->foreignKeys[$name] = $foreignKey;
        $this->isModified(true);
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
    * deletes Foreign Key
    * @param  string $name
    * @return self
    */
    public function deleteForeignKey($name)
    {
        $this->getForeignKey($name)->isDeleted(true);
        return $this;
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
     *
     * @throws DuplicateException
     * @throws EmptyException
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
    *
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

    /**
     * shows drop sql query
     *
     * @return string
     */
    public function showDrop()
    {
        return "DROP TABLE ".$this->getName().";";
    }

    /**
     * shows migration query
     *
     * @return string
     */
    public function migrate()
    {
        if ($this->isExistingOnDb() && $this->isDeleted()) {
            return $this->showDrop();
        } else if (!$this->isExistingOnDb()) {
            return $this->showCreate();
        } else {
            return $this->showAlter();
        }
    }

    /**
     * shows alter query
     *
     * @return string
     */
    public function showAlter()
    {
        $columns = [];
        foreach ($this->getColumns() as $key => $column) {
            $columns[] = $column->showAlter();
        }

        $out = '';

        $columns = array_filter($columns);
        if (count($columns)) {
            $out = "ALTER TABLE ".$this->getName()." ";
            $out .= implode(",\n", $columns);
            $out .= ';';
            $out .= "\n";
        }

        $indexes = [];
        foreach ($this->getIndexes() as $key => $index) {
            $indexes[] = $index->showAlter();
        }
        $indexes = array_filter($indexes);
        if (count($indexes)) {
            $out .= implode("\n", $indexes);
            $out .= ';';
            $out .= "\n";
        }

        $foreigns = [];
        foreach ($this->getForeignKeys() as $key => $foreign) {
            $foreigns[] = $foreign->showAlter();
        }
        $foreigns = array_filter($foreigns);
        if (count($foreigns)) {
            $out .= implode("\n", $foreigns);
            $out .= ';';
            $out .= "\n";
        }

        return $out;
    }

    /**
     * read table structure from db
     *
     * @param $dbname
     * @param $tablename
     * @param $pdo
     * @return static
     * @throws DuplicateException
     * @throws EmptyException
     */
    public static function readFromExisting($dbname, $tablename, $pdo)
    {
        $sql_queries = [
            'create' => "SHOW CREATE TABLE {$dbname}.{$tablename}",
            'fields' => "DESCRIBE {$dbname}.{$tablename}",
            //'index' => "SHOW INDEX FROM {$dbname}.{$tablename}",

            'index' => "SELECT
                tc.CONSTRAINT_NAME,
                tc.CONSTRAINT_TYPE,
                GROUP_CONCAT(kcu.COLUMN_NAME) AS COLUMN_NAME
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ON (
                tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME AND
                kcu.TABLE_SCHEMA = tc.TABLE_SCHEMA AND kcu.TABLE_NAME = tc.TABLE_NAME
                )
                WHERE tc.TABLE_SCHEMA = '{$dbname}' AND tc.`TABLE_NAME` LIKE '{$tablename}'
                GROUP BY tc.CONSTRAINT_NAME, tc.CONSTRAINT_TYPE",
            'refs' => "SELECT
                kcu.TABLE_NAME,
                GROUP_CONCAT(kcu.COLUMN_NAME) AS COLUMN_NAME,
                kcu.CONSTRAINT_NAME,
                kcu.REFERENCED_TABLE_NAME,
                GROUP_CONCAT(kcu.REFERENCED_COLUMN_NAME) AS REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc ON (
                rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME AND
                kcu.TABLE_SCHEMA = rc.UNIQUE_CONSTRAINT_SCHEMA AND kcu.TABLE_NAME = rc.TABLE_NAME
                )
                WHERE kcu.REFERENCED_TABLE_SCHEMA = '{$dbname}' AND kcu.TABLE_NAME = '{$tablename}'
                GROUP BY kcu.CONSTRAINT_NAME, kcu.REFERENCED_TABLE_NAME, rc.UPDATE_RULE, rc.DELETE_RULE",
        ];

        $info = [];

        foreach ($sql_queries as $index => $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $info[$index] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $table = new static($tablename, true);

        foreach ($info['fields'] as $field) {
            $name = $field['Field'];

            $type = $field['Type'];
            $parameters = null;
            $options = [];

            if (preg_match("/(.*?)\((.*?)\)(.*?)/i", $field['Type'], $matches)) {
                $type = $matches[1];
                $parameters = [$matches[2]];

                if (!empty($matches[3])) {
                    $options = explode(" ", trim($matches[3]));
                }
            } elseif (count(explode(" ", $field['Type'])) > 1) {
                $tmp = explode(" ", $field['Type']);

                $type = reset($tmp);
                $options = array_slice($tmp, 1);
            }


            $nullable = ($field['Type'] == 'YES');
            $default = $field['Default'];

            $table->addColumn($name, $type, $parameters, $options, $nullable, $default, true);

            if ($field['Extra'] == 'auto_increment') {
                $table->setAutoIncrementColumn($name, false);
            }
        }

        foreach ($info['index'] as $index) {
            if ($index['CONSTRAINT_TYPE'] == 'FOREIGN KEY') {
                continue;
            }
            $name = $index['CONSTRAINT_NAME'];

            $type = Index::TYPE_INDEX;
            if ($name == 'PRIMARY') {
                $name = null;
                $type = Index::TYPE_PRIMARY;
            } else if ($index['CONSTRAINT_TYPE'] == 'UNIQUE') {
                $type = Index::TYPE_UNIQUE;
            }
            // $type = Index::TYPE_FULLTEXT;
            $columns = explode(",", $index['COLUMN_NAME']);

            $table->addIndex($name, $columns, $type, true);
        }

        foreach ($info['refs'] as $relation) {
            $name = $relation['CONSTRAINT_NAME'];
            $columns = explode(",", $relation['COLUMN_NAME']);
            $targetTable = $relation['REFERENCED_TABLE_NAME'];
            $targetColumns = explode(",", $relation['REFERENCED_COLUMN_NAME']);

            $table->addForeignKey($name, $columns, $targetTable, $targetColumns, ForeignKey::ACTION_RESTRICT, ForeignKey::ACTION_RESTRICT, true);
        }

        return $table;
    }

    /**
     * add primary key helper function
     *
     * @param $name
     * @return $this
     * @throws DuplicateException
     * @throws EmptyException
     */
    public function addPrimaryKey($name)
    {
        $this
            ->addColumn($name, Column::TYPE_INT, null, [Column::OPTION_UNSIGNED], false)
            ->addIndex(null, $name, Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn($name);

        return $this;
    }

    /**
     * add varchar column helper function
     *
     * @param $name
     * @param $length
     * @return $this
     * @throws DuplicateException
     */
    public function addVarcharCol($name, $length)
    {
        $this->addColumn($name, Column::TYPE_VARCHAR, [$length]);

        return $this;
    }

    /**
     * add int column helper function
     *
     * @param $name
     * @param bool $unsigned
     * @return $this
     * @throws DuplicateException
     */
    public function addIntCol($name, $unsigned = true)
    {
        $options = null;
        if ($unsigned == true) {
            $options = [Column::OPTION_UNSIGNED];
        }

        $this->addColumn($name, Column::TYPE_INT, null, $options);

        return $this;
    }

    /**
     * add text column helper function
     *
     * @param $name
     * @return $this
     * @throws DuplicateException
     */
    public function addTextCol($name)
    {
        $this->addColumn($name, Column::TYPE_TEXT, null);

        return $this;
    }

    /**
     * add timestamp column function
     *
     * @param $name
     * @return $this
     * @throws DuplicateException
     */
    public function addTimestampCol($name)
    {
        $this->addColumn($name, 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()');

        return $this;
    }
}
