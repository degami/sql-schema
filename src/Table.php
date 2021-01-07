<?php

namespace Degami\SqlSchema;

use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\EmptyException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use Degami\SqlSchema\Abstracts\DBComponent;
use PDO;

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
     * Table constructor.
     *
     * @param string $name
     * @param bool $existing_on_db
     */
    public function __construct(string $name, $existing_on_db = false)
    {
        $this->name = $name;
        $this->isExistingOnDb(boolval($existing_on_db));
    }

    /**
     * get table name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * sets table comment
     *
     * @param string $comment
     * @return  self
     */
    public function setComment(string $comment): Table
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * gets table comment
     *
     * @return string
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * sets table option
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function setOption(string $name, string $value): Table
    {
        $this->options[$name] = $value;
        $this->isModified(true);
        return $this;
    }

    /**
     * gets table options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * gets table storage engine
     *
     * @return string storage engine
     */
    public function getStorageEngine(): ?string
    {
        return $this->storageEngine;
    }

    /**
     * sets table storage engine
     *
     * @param string storage engine $storageEngine
     * @return self
     */
    public function setStorageEngine($storageEngine): Table
    {
        $this->storageEngine = $storageEngine;
        $this->isModified(true);
        return $this;
    }

    /**
     * add a column
     *
     * @param string $name
     * @param string|null $type
     * @param mixed $parameters
     * @param array $options
     * @param boolean $nullable
     * @param mixed $default
     * @param bool $existing_on_db
     * @return self
     * @throws DuplicateException
     * @throws OutOfRangeException
     */
    public function addColumn(string $name, $type = null, $parameters = null, array $options = [], $nullable = true, $default = null, $existing_on_db = false): Table
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
     * moves a column after another
     *
     * @param string $name
     * @param string $after
     * @return self
     * @throws OutOfRangeException
     */
    public function moveColumn(string $name, string $after): Table
    {
        if ($this->getColumn($name) && $this->getColumn($after)) {
            $this->getColumn($name)->setColumnPosition($after)->isModified(true);
        }

        return $this;
    }

    /**
     * deletes a column
     *
     * @param string $name
     * @return self
     * @throws OutOfRangeException
     */
    public function deleteColumn(string $name): Table
    {
        $this->getColumn($name)->isDeleted(true);
        return $this;
    }

    /**
     * gets a column
     *
     * @param string $name
     * @return Column|null
     * @throws OutOfRangeException
     */
    public function getColumn(string $name): ?Column
    {
        if (isset($this->columns[$name])) {
            return $this->columns[$name];
        }

        throw new OutOfRangeException("Column `{$name}` not found in table `{$this->name}`");
    }

    /**
     * gets table columns
     *
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * add an index
     *
     * @param string|Index $name
     * @param array $columns
     * @param string $type
     * @param bool $existing_on_db
     * @return self
     * @throws DuplicateException
     */
    public function addIndex($name, $columns = [], $type = Index::TYPE_INDEX, $existing_on_db = false): Table
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
     * gets an index
     *
     * @param string $name
     * @return Index|null
     * @throws OutOfRangeException
     */
    public function getIndex(string $name): ?Index
    {
        if (isset($this->indexes[$name])) {
            return $this->indexes[$name];
        }

        throw new OutOfRangeException("Index not found");
    }

    /**
     * deletes an index
     *
     * @param string $name
     * @return self
     * @throws OutOfRangeException
     */
    public function deleteIndex(string $name): Table
    {
        $this->getIndex($name)->isDeleted(true);
        return $this;
    }

   /**
    * get indexes
    *
    * @return Index[]
    */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * set autoIncrement on a column
     *
     * @param string $column_name
     * @param bool $set_modified
     * @return self
     * @throws EmptyException
     * @throws OutOfRangeException
     */
    public function setAutoIncrementColumn(string $column_name, $set_modified = true): Table
    {
        if ($this->getColumn($column_name) == null) {
            throw new EmptyException("Column not found", 1);
        }
        foreach ($this->getColumns() as $key => &$column) {
            $column->setAutoIncrement(false, $set_modified);
        }
        $this->getColumn($column_name)->setAutoIncrement(true, $set_modified);
        $this->isModified($set_modified);

        return $this;
    }

    /**
     * adds a foreign key
     *
     * @param string|ForeignKey $name
     * @param array $columns
     * @param null $targetTable
     * @param array $targetColumns
     * @param string $onUpdateAction
     * @param string $onDeleteAction
     * @param bool $existing_on_db
     * @return self
     * @throws DuplicateException
     */
    public function addForeignKey($name, $columns = [], $targetTable = null, $targetColumns = [], $onUpdateAction = ForeignKey::ACTION_RESTRICT, $onDeleteAction = ForeignKey::ACTION_RESTRICT, $existing_on_db = false): Table
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
     * gets a foreign key
     *
     * @param string $name
     * @return ForeignKey|NULL
     */
    public function getForeignKey(string $name): ?ForeignKey
    {
        if (isset($this->foreignKeys[$name])) {
            return $this->foreignKeys[$name];
        }
        return null;
    }

    /**
     * deletes a foreign key
     *
     * @param string $name
     * @return self
     */
    public function deleteForeignKey(string $name): Table
    {
        $this->getForeignKey($name)->isDeleted(true);
        return $this;
    }

   /**
    * gets foreign keys
    *
    * @return ForeignKey[]
    */
    public function getForeignKeys(): array
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
    * shows create table sql query
    *
    * @return string
    */
    public function showCreate(): string
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
    public function showDrop(): string
    {
        return "DROP TABLE ".$this->getName().";";
    }

    /**
     * shows migration query
     *
     * @return string
     */
    public function migrate(): string
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
    public function showAlter(): string
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
     * @param string $db_name
     * @param string $table_name
     * @param PDO $pdo
     * @return self
     * @throws DuplicateException
     * @throws EmptyException
     * @throws OutOfRangeException
     */
    public static function readFromExisting(string $db_name, string $table_name, PDO $pdo): Table
    {
        $sql_queries = [
            'create' => "SHOW CREATE TABLE {$db_name}.{$table_name}",
            'fields' => "DESCRIBE {$db_name}.{$table_name}",
            //'index' => "SHOW INDEX FROM {$dbname}.{$table_name}",

            'index' => "SELECT
                tc.CONSTRAINT_NAME,
                tc.CONSTRAINT_TYPE,
                GROUP_CONCAT(kcu.COLUMN_NAME) AS COLUMN_NAME
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ON (
                tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME AND
                kcu.TABLE_SCHEMA = tc.TABLE_SCHEMA AND kcu.TABLE_NAME = tc.TABLE_NAME
                )
                WHERE tc.TABLE_SCHEMA = '{$db_name}' AND tc.`TABLE_NAME` LIKE '{$table_name}'
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
                WHERE kcu.REFERENCED_TABLE_SCHEMA = '{$db_name}' AND kcu.TABLE_NAME = '{$table_name}'
                GROUP BY kcu.CONSTRAINT_NAME, kcu.REFERENCED_TABLE_NAME, rc.UPDATE_RULE, rc.DELETE_RULE",
        ];

        $info = [];

        foreach ($sql_queries as $index => $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $info[$index] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $table = new static($table_name, true);

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
     * @return self
     * @throws DuplicateException
     * @throws EmptyException
     * @throws OutOfRangeException
     */
    public function addPrimaryKey($name): Table
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
     * @throws OutOfRangeException
     */
    public function addVarcharCol($name, $length): Table
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
     * @throws OutOfRangeException
     */
    public function addIntCol($name, $unsigned = true): Table
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
     * @throws OutOfRangeException
     */
    public function addTextCol($name): Table
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
     * @throws OutOfRangeException
     */
    public function addTimestampCol($name): Table
    {
        $this->addColumn($name, 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()');

        return $this;
    }
}
