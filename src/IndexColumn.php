<?php

namespace Degami\SqlSchema;

use Degami\SqlSchema\Exceptions\OutOfRangeException;
use Degami\SqlSchema\Abstracts\DBComponent;

class IndexColumn extends DBComponent
{
    const ASC = 'ASC';
    const DESC = 'DESC';

    /** @var string */
    private $name;

    /** @var string */
    private $order;

    /** @var int|NULL */
    private $length;

    /**
     * @param  string
     * @param  string
     * @param  int|NULL
     */
    public function __construct($name, $order = self::ASC, $length = null, $existing_on_db = false)
    {
        $this->name = $name;
        $this->order = $order;
        $this->length = $length;
        $this->isExistingOnDb(boolval($existing_on_db));
    }

    /**
     * @param  string
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->isModified(true);
        return $this;
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
    public function setOrder($order)
    {
        $order = (string) $order;

        if ($order !== self::ASC && $order !== self::DESC) {
            throw new OutOfRangeException("Order type '$order' not found.");
        }

        $this->order = $order;
        $this->isModified(true);
        return $this;
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param  int|NULL
     * @return self
     */
    public function setLength($length)
    {
        $this->length = $length;
        $this->isModified(true);
        return $this;
    }

    /**
     * @return int|NULL
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return string
     */
    public function render()
    {
        $output = '`'.$this->getName().'` ';
        $length = $this->getLength();
        $output .= isset($length) ? '(' . $length . ')' : '';
        $output .= ' ' . $this->getOrder();
        return $output;
    }
}
