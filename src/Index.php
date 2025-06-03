<?php

namespace Degami\SqlSchema;

use Degami\SqlSchema\Exceptions\OutOfRangeException;
use Degami\SqlSchema\Abstracts\DBTableComponent;

class Index extends DBTableComponent
{
    const TYPE_INDEX = 'INDEX';
    const TYPE_PRIMARY = 'PRIMARY';
    const TYPE_UNIQUE = 'UNIQUE';
    const TYPE_FULLTEXT = 'FULLTEXT';

    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var IndexColumn[] */
    private $columns = [];


    /**
     * Index constructor.
     *
     * @param string|null $name
     * @param Table $table
     * @param array $columns
     * @param string $type
     * @param false $existing_on_db
     */
    public function __construct(?string $name, Table $table, $columns = [], $type = self::TYPE_INDEX, $existing_on_db = false)
    {
        $this->name = $name;
        $this->table = $table;
        $this->type = $type;

        if (!is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as &$column) {
            if (!($column instanceof IndexColumn)) {
                $column = new IndexColumn($column, IndexColumn::ASC, null, boolval($existing_on_db));
            }
        }

        $this->columns = $columns;

        $this->isExistingOnDb(boolval($existing_on_db));
    }

    /**
     * gets index name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * sets type
     *
     * @param string $type
     * @return self
     * @throws OutOfRangeException
     */
    public function setType(string $type): Index
    {
        $type = (string) $type;
        $exists = $type === self::TYPE_INDEX
        || $type === self::TYPE_PRIMARY
        || $type === self::TYPE_UNIQUE
        || $type === self::TYPE_FULLTEXT;

        if (!$exists) {
            throw new OutOfRangeException("Index type '$type' not found.");
        }

        $this->type = $type;
        $this->isModified(true);
        return $this;
    }

    /**
     * gets type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * adds a column
     *
     * @param IndexColumn|string
     * @param null $existing_on_db
     * @return self
     */
    public function addColumn($column, $existing_on_db = null): Index
    {
        if (!($column instanceof IndexColumn)) {
            $column = new IndexColumn($column, IndexColumn::ASC, null, $existing_on_db);
        }

        $this->columns[] = $column;
        $this->isModified(true);
        return $this;
    }

    /**
     * gets columns
     *
     * @return IndexColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * gets sql query part
     *
     * @return string
     */
    public function render(): string
    {
        $output = $this->getType() ;
        $output .= ' ' . ($this->getType() == 'PRIMARY' ? ' KEY' : '') ;
        $output .= ' ' . ($this->getName() != null ? '`'.$this->getName().'`' : '');
        $indexcols = [];
        foreach ($this->getColumns() as $key => $col) {
            $indexcols[] = $col->render();
        }
        $output .= '('. implode(', ', $indexcols) .')';
        return $output;
    }

    /**
     * gets alter query part
     *
     * @return string
     */
    public function showAlter(): string
    {
        if ($this->isExistingOnDb() && $this->isDeleted()) {
            return 'DROP INDEX `'.$this->getName() . '` ON '.$this->getTable()->getName().';';
        } else if (!$this->isExistingOnDb()) {
            if ($this->getType() != 'PRIMARY') {
                return "CREATE " . $this->render();
            }
        } else if ($this->isModified() && $this->getType() != 'PRIMARY') {
            return
                "DROP INDEX `".$this->getName() . "` ON ".$this->getTable()->getName().";\n".
                "CREATE " . $this->render();
        }

        return "";
    }
}
