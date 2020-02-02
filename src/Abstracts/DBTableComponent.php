<?php

namespace Degami\SqlSchema\Abstracts;

abstract class DBTableComponent extends DBComponent
{
    /** @var Table */
    protected $table;


    public function getTable()
    {
        return $this->table;
    }
}
