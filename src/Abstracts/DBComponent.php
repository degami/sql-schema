<?php

namespace Degami\SqlSchema\Abstracts;

abstract class DBComponent
{
    /** @var boolean */
    protected $existing_on_db;

    /** @var boolean */
    protected $modified;

    /** @var boolean */
    protected $deleted;


    /**
     * @param  boolean|null
     * @return boolean
     */
    public function isExistingOnDb($existing_on_db = null)
    {
        if ($existing_on_db !== null) {
            $this->existing_on_db = $existing_on_db;
        }

        return $this->existing_on_db;
    }

    /**
     * @param  boolean|null
     * @return boolean
     */
    public function isModified($modified = null)
    {
        if ($modified !== null) {
            $this->modified = $modified;
        }

        return $this->modified;
    }

    /**
     * @param  boolean|null
     * @return boolean
     */
    public function isDeleted($deleted = null)
    {
        if ($deleted !== null) {
            $this->deleted = $deleted;
        }

        return $this->deleted;
    }
}
