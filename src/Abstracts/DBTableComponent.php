<?php

namespace Degami\SqlSchema\Abstracts;

use Degami\SqlSchema\Table;

abstract class DBTableComponent extends DBComponent
{
    /** @var Table */
    protected Table $table;


    public function getTable(): Table
    {
        return $this->table;
    }
}
