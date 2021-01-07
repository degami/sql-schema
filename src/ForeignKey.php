<?php

namespace Degami\SqlSchema;

use Degami\SqlSchema\Exceptions\OutOfRangeException;
use Degami\SqlSchema\Abstracts\DBTableComponent;

class ForeignKey extends DBTableComponent
{
    const ACTION_RESTRICT = 'RESTRICT';
    const ACTION_NO_ACTION = 'NO ACTION';
    const ACTION_CASCADE = 'CASCADE';
    const ACTION_SET_NULL = 'SET NULL';

    /** @var string */
    private $name;

    /** @var string[] */
    private $columns = [];

    /** @var string */
    private $target_table;

    /** @var string[] */
    private $target_columns;

    /** @var string */
    private $on_update_action = self::ACTION_RESTRICT;

    /** @var string */
    private $on_delete_action = self::ACTION_RESTRICT;

    /**
     * ForeignKey constructor.
     * @param string $name
     * @param Table $table
     * @param mixed $columns
     * @param string $targetTable
     * @param mixed $targetColumns
     * @param string $onUpdateAction
     * @param string $onDeleteAction
     * @param bool $existing_on_db
     */
    public function __construct(string $name, Table $table, $columns, string $targetTable, $targetColumns, $onUpdateAction = self::ACTION_RESTRICT, $onDeleteAction = self::ACTION_RESTRICT, $existing_on_db = false)
    {
        $this->name = $name;
        $this->table = $table;
        $this->target_table = $targetTable;

        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $this->columns = $columns;

        if (!is_array($targetColumns)) {
            $targetColumns = [$targetColumns];
        }

        $this->target_columns = $targetColumns;
        $this->on_update_action = $onUpdateAction;
        $this->on_delete_action = $onDeleteAction;

        $this->isExistingOnDb(boolval($existing_on_db));
    }

    /**
     * gets foreign key name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * adds a column
     *
     * @param  string
     * @return self
     */
    public function addColumn($column): ForeignKey
    {
        $this->columns[] = $column;
        $this->isModified(true);
        return $this;
    }

    /**
     * gets columns
     *
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * sets target table
     *
     * @param  string
     * @return self
     */
    public function setTargetTable($target_table): ForeignKey
    {
        $this->target_table = $target_table;
        $this->isModified(true);
        return $this;
    }

    /**
     * gets target table
     *
     * @return string
     */
    public function getTargetTable(): string
    {
        return $this->target_table;
    }

    /**
     * adds target column
     *
     * @param string $target_column
     * @return self
     */
    public function addTargetColumn(string $target_column): ForeignKey
    {
        $this->target_columns[] = $target_column;
        $this->isModified(true);
        return $this;
    }

    /**
     * gets target columns
     *
     * @return string[]
     */
    public function getTargetColumns(): array
    {
        return $this->target_columns;
    }

    /**
     * sets on update action
     *
     * @param string $on_update_action
     * @return self
     * @throws OutOfRangeException
     */
    public function setOnUpdateAction(string $on_update_action): ForeignKey
    {
        if (!$this->validateAction($on_update_action)) {
            throw new OutOfRangeException("Action '$on_update_action' is invalid.");
        }

        $this->on_update_action = $on_update_action;
        $this->isModified(true);
        return $this;
    }

    /**
     * gets on update action
     *
     * @return string
     */
    public function getOnUpdateAction(): string
    {
        return $this->on_update_action;
    }

    /**
     * sets on delete action
     *
     * @param string $on_delete_action
     * @return self
     * @throws OutOfRangeException
     */
    public function setOnDeleteAction(string $on_delete_action): ForeignKey
    {
        if (!$this->validateAction($on_delete_action)) {
            throw new OutOfRangeException("Action '$on_delete_action' is invalid.");
        }

        $this->on_delete_action = $on_delete_action;
        $this->isModified(true);
        return $this;
    }

    /**
     * gets on delete action
     *
     * @return string
     */
    public function getOnDeleteAction(): string
    {
        return $this->on_delete_action;
    }

    /**
     * validate action
     *
     * @param string $action
     * @return bool
     */
    private function validateAction(string $action): bool
    {
        return $action === self::ACTION_RESTRICT
        || $action === self::ACTION_NO_ACTION
        || $action === self::ACTION_CASCADE
        || $action === self::ACTION_SET_NULL;
    }

    /**
     * gets sql query part
     *
     * @return string
     */
    public function render(): string
    {
        $output = 'CONSTRAINT ' . $this->getName() . ' FOREIGN KEY';
        $output .= ' ('. implode(', ', $this->getColumns()) .')';
        $output .= ' REFERENCES ' . $this->getTargetTable() . ' ';
        $output .= '('. implode(', ', $this->getTargetColumns()) .')';

        if ($this->getOnUpdateAction()) {
            $output .= ' ON UPDATE ' . $this->getOnUpdateAction() . ' ';
        }
        if ($this->getOnDeleteAction()) {
            $output .= ' ON DELETE ' . $this->getOnUpdateAction() . ' ';
        }
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
            return 'ALTER TABLE '.$this->getTable()->getName() . ' DROP FOREIGN KEY ' . $this->getName() . ';';
        } else if (!$this->isExistingOnDb()) {
            return 'ALTER TABLE '.$this->getTable()->getName() . ' ADD '. $this->render();
        } else if ($this->isModified()) {
            return
                "ALTER TABLE ".$this->getTable()->getName() . " DROP FOREIGN KEY " . $this->getName() . ";\n".
                "ALTER TABLE ".$this->getTable()->getName() . " ADD ". $this->render();
        }

        return "";
    }
}
