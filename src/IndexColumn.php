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
     * IndexColumn constructor.
     *
     * @param string $name
     * @param string $order
     * @param null $length
     * @param bool $existing_on_db
     */
    public function __construct(string $name, $order = self::ASC, $length = null, $existing_on_db = false)
    {
        $this->name = $name;
        $this->order = $order;
        $this->length = $length;
        $this->isExistingOnDb(boolval($existing_on_db));
    }

    /**
     * sets index name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): IndexColumn
    {
        $this->name = $name;
        $this->isModified(true);
        return $this;
    }

    /**
     * gets index name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * sets order
     *
     * @param string $order
     * @return self
     * @throws OutOfRangeException
     */
    public function setOrder(string $order): IndexColumn
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
     * gets order
     *
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * sets length
     *
     * @param int|NULL $length
     * @return self
     */
    public function setLength(?int $length): IndexColumn
    {
        $this->length = $length;
        $this->isModified(true);
        return $this;
    }

    /**
     * gets length
     *
     * @return int|NULL
     */
    public function getLength(): ?int
    {
        return $this->length;
    }

    /**
     * gets sql query part
     *
     * @return string
     */
    public function render(): string
    {
        $output = '`'.$this->getName().'` ';
        $length = $this->getLength();
        $output .= isset($length) ? '(' . $length . ')' : '';
        $output .= ' ' . $this->getOrder();
        return $output;
    }
}
