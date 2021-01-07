<?php

namespace Degami\SqlSchema\Abstracts;

abstract class DBComponent
{
    /** @var boolean */
    protected $existing_on_db = false;

    /** @var boolean */
    protected $modified = false;

    /** @var boolean */
    protected $deleted = false;


    /**
     * get/sets is existing on db flag
     *
     * @param  boolean|null
     * @return boolean
     */
    public function isExistingOnDb($existing_on_db = null): bool
    {
        if ($existing_on_db !== null) {
            $this->existing_on_db = $existing_on_db;
        }

        return $this->existing_on_db;
    }

    /**
     * gets/sets is modified flag
     *
     * @param  boolean|null
     * @return boolean
     */
    public function isModified($modified = null): bool
    {
        if ($modified !== null) {
            $this->modified = $modified;
        }

        return $this->modified;
    }

    /**
     * gets/sets is deleted flag
     *
     * @param  boolean|null
     * @return boolean
     */
    public function isDeleted($deleted = null): bool
    {
        if ($deleted !== null) {
            $this->deleted = $deleted;
        }

        return $this->deleted;
    }
}
