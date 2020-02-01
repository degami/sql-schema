<?php

namespace Degami\SqlSchema;

use Degami\SqlSchema\Exceptions\OutOfRangeException;
use Degami\SqlSchema\Abstracts\DBComponent;

class Index extends DBComponent
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
     * @param  string
     * @param  string[]|string
     * @param  string
     */
    public function __construct($name, $columns = [], $type = self::TYPE_INDEX, $existing_on_db = false)
    {
        $this->name = $name;
        $this->setType($type);

        if (!is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $column) {
            $this->addColumn($column, boolval($existing_on_db));
        }

        $this->isExistingOnDb(boolval($existing_on_db));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  string
     * @return self
     */
    public function setType($type)
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  IndexColumn|string
     * @return IndexColumn
     */
    public function addColumn($column, $existing_on_db = null)
    {
        if (!($column instanceof IndexColumn)) {
            $column = new IndexColumn($column, IndexColumn::ASC, null, $existing_on_db);
        }

        $this->columns[] = $column;
        $this->isModified(true);
        return $this;
    }

    /**
     * @return IndexColumn[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return string
     */
    public function render()
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
}
