<?php

namespace Degami\SqlSchema;

use Degami\SqlSchema\Abstracts\DBTableComponent;
use Degami\SqlSchema\Exceptions\OutOfRangeException;

class Column extends DBTableComponent
{
    const OPTION_UNSIGNED = 'UNSIGNED';
    const OPTION_ZEROFILL = 'ZEROFILL';
    const POSITION_LAST = '_LAST';
    const POSITION_FIRST = '_FIRST';
    const TYPE_CHAR = 'CHAR'; // CHAR(size)  A FIXED length string (can contain letters, numbers, and special characters). The size parameter specifies the column length in characters - can be from 0 to 255. Default is 1
    const TYPE_VARCHAR = 'VARCHAR'; // VARCHAR(size)   A VARIABLE length string (can contain letters, numbers, and special characters). The size parameter specifies the maximum column length in characters - can be from 0 to 65535
    const TYPE_BINARY = 'BINARY'; // BINARY(size)    Equal to CHAR(), but stores binary byte strings. The size parameter specifies the column length in bytes. Default is 1
    const TYPE_VARBINARY = 'VARBINARY'; // VARBINARY(size)     Equal to VARCHAR(), but stores binary byte strings. The size parameter specifies the maximum column length in bytes.
    const TYPE_TINYBLOB = 'TINYBLOB'; // TINYBLOB    For BLOBs (Binary Large OBjects). Max length: 255 bytes
    const TYPE_TINYTEXT = 'TINYTEXT'; // TINYTEXT    Holds a string with a maximum length of 255 characters
    const TYPE_TEXT = 'TEXT'; // TEXT(size)  Holds a string with a maximum length of 65,535 bytes
    const TYPE_BLOB = 'BLOB'; // BLOB(size)  For BLOBs (Binary Large OBjects). Holds up to 65,535 bytes of data
    const TYPE_MEDIUMTEXT = 'MEDIUMTEXT'; // MEDIUMTEXT  Holds a string with a maximum length of 16,777,215 characters
    const TYPE_MEDIUMBLOB = 'MEDIUMBLOB'; // MEDIUMBLOB  For BLOBs (Binary Large OBjects). Holds up to 16,777,215 bytes of data
    const TYPE_LONGTEXT = 'LONGTEXT'; // LONGTEXT    Holds a string with a maximum length of 4,294,967,295 characters
    const TYPE_LONGBLOB = 'LONGBLOB'; // LONGBLOB    For BLOBs (Binary Large OBjects). Holds up to 4,294,967,295 bytes of data
    const TYPE_ENUM = 'ENUM'; // ENUM(val1, val2, val3, ...)     A string object that can have only one value, chosen from a list of possible values. You can list up to 65535 values in an ENUM list. If a value is inserted that is not in the list, a blank value will be inserted. The values are sorted in the order you enter them
    const TYPE_SET = 'SET'; // SET(val1, val2, val3, ...)
    const TYPE_BIT = 'BIT'; // BIT(size)   A bit-value type. The number of bits per value is specified in size. The size parameter can hold a value from 1 to 64. The default value for size is 1.
    const TYPE_TINYINT = 'TINYINT'; // TINYINT(size)   A very small integer. Signed range is from -128 to 127. Unsigned range is from 0 to 255. The size parameter specifies the maximum display width (which is 255)
    const TYPE_BOOL = 'BOOL'; // BOOL    Zero is considered as false, nonzero values are considered as true.
    const TYPE_BOOLEAN = 'BOOLEAN'; // BOOLEAN     Equal to BOOL
    const TYPE_SMALLINT = 'SMALLINT'; // SMALLINT(size)  A small integer. Signed range is from -32768 to 32767. Unsigned range is from 0 to 65535. The size parameter specifies the maximum display width (which is 255)
    const TYPE_MEDIUMINT = 'MEDIUMINT'; // MEDIUMINT(size)     A medium integer. Signed range is from -8388608 to 8388607. Unsigned range is from 0 to 16777215. The size parameter specifies the maximum display width (which is 255)
    const TYPE_INT = 'INT'; // INT(size)   A medium integer. Signed range is from -2147483648 to 2147483647. Unsigned range is from 0 to 4294967295. The size parameter specifies the maximum display width (which is 255)
    const TYPE_INTEGER = 'INTEGER'; // INTEGER(size)   Equal to INT(size)
    const TYPE_BIGINT = 'BIGINT'; // BIGINT(size)    A large integer. Signed range is from -9223372036854775808 to 9223372036854775807. Unsigned range is from 0 to 18446744073709551615. The size parameter specifies the maximum display width (which is 255)
    const TYPE_FLOAT = 'FLOAT'; // FLOAT(size, d)  A floating point number. The total number of digits is specified in size. The number of digits after the decimal point is specified in the d parameter. This syntax is deprecated in MySQL 8.0.17, and it will be removed in future MySQL versions
    const TYPE_DOUBLE = 'DOUBLE'; // DOUBLE(size, d)     A normal-size floating point number. The total number of digits is specified in size. The number of digits after the decimal point is specified in the d parameter
    const TYPE_DECIMAL = 'DECIMAL'; // DECIMAL(size, d)    An exact fixed-point number. The total number of digits is specified in size. The number of digits after the decimal point is specified in the d parameter. The maximum number for size is 65. The maximum number for d is 30. The default value for size is 10. The default value for d is 0.
    const TYPE_DEC = 'DEC'; // DEC(size, d)    Equal to DECIMAL(size,d)
    const TYPE_DATE = 'DATE'; // DATE  A date. Format: YYYY-MM-DD. The supported range is from '1000-01-01' to '9999-12-31'
    const TYPE_DATETIME = 'DATETIME'; // DATETIME(fsp)     A date and time combination. Format: YYYY-MM-DD hh:mm:ss. The supported range is from '1000-01-01 00:00:00' to '9999-12-31 23:59:59'. Adding DEFAULT and ON UPDATE in the column definition to get automatic initialization and updating to the current date and time
    const TYPE_TIMESTAMP = 'TIMESTAMP'; // TIMESTAMP(fsp)    A timestamp. TIMESTAMP values are stored as the number of seconds since the Unix epoch ('1970-01-01 00:00:00' UTC). Format: YYYY-MM-DD hh:mm:ss. The supported range is from '1970-01-01 00:00:01' UTC to '2038-01-09 03:14:07' UTC. Automatic initialization and updating to the current date and time can be specified using DEFAULT CURRENT_TIMESTAMP and ON UPDATE CURRENT_TIMESTAMP in the column definition
    const TYPE_TIME = 'TIME'; // TIME(fsp)   A time. Format: hh:mm:ss. The supported range is from '-838:59:59' to '838:59:59'
    const TYPE_YEAR = 'YEAR'; // YEAR  A year in four-digit format. Values allowed in four-digit format: 1901 to 2155, and 0000.

    protected $dataTypes = [
        'string' => [
            self::TYPE_CHAR,
            self::TYPE_VARCHAR,
            self::TYPE_BINARY,
            self::TYPE_VARBINARY,
            self::TYPE_TINYBLOB,
            self::TYPE_TINYTEXT,
            self::TYPE_TEXT,
            self::TYPE_BLOB,
            self::TYPE_MEDIUMTEXT,
            self::TYPE_MEDIUMBLOB,
            self::TYPE_LONGTEXT,
            self::TYPE_LONGBLOB,
            self::TYPE_ENUM,
            self::TYPE_SET,
        ],
        'numeric' => [
            self::TYPE_BIT,
            self::TYPE_TINYINT,
            self::TYPE_BOOL,
            self::TYPE_BOOLEAN,
            self::TYPE_SMALLINT,
            self::TYPE_MEDIUMINT,
            self::TYPE_INT,
            self::TYPE_INTEGER,
            self::TYPE_BIGINT,
            self::TYPE_FLOAT,
            self::TYPE_DOUBLE,
            self::TYPE_DECIMAL,
            self::TYPE_DEC,
        ],
        'datetime' => [
            self::TYPE_DATE,
            self::TYPE_DATETIME,
            self::TYPE_TIMESTAMP,
            self::TYPE_TIME,
            self::TYPE_YEAR,
        ],
    ];

    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var array */
    private $parameters = [];

    /** @var array */
    private $options = [];

    /** @var bool */
    private $nullable = true;

    /** @var bool */
    private $autoIncrement = false;

    /** @var mixed|NULL */
    private $defaultValue;

    /** @var string|NULL */
    private $comment;

    /** @var null  */
    private $columnPosition = self::POSITION_LAST;

    /**
     * @param  string
     * @param  string|NULL
     * @param  array|string|NULL
     * @param  array  [OPTION => VALUE, OPTION2]
     */
    public function __construct($name, $table, $type, array $parameters = null, array $options = [], $nullable = true, $default = null, $existing_on_db = false)
    {
        $this->name = $name;
        $this->table = $table;

        if (!$this->validateType($type)) {
            throw new OutOfRangeException("Invalid type `{$type}` for column `{$name}`");
        }

        $this->type = $type;

        if ($parameters === null) {
            $parameters = [];
        } elseif (!is_array($parameters)) {
            $parameters = [$parameters];
        }

        $this->parameters = $parameters;

        if (isset($options['first']) && $options['first'] == true) {
            $this->setColumnPosition(self::POSITION_FIRST);
        }

        if (isset($options['after']) && is_string($options['after'])) {
            $this->setColumnPosition($options['after']);
        }

        if (isset($options['first'])) {
            unset($options['first']);
        }
        if (isset($options['after'])) {
            unset($options['after']);
        }
        if (isset($options['comment'])) {
            $this->setComment($options['comment']);
            unset($options['comment']);
        }

        foreach ($options as $k => $v) {
            if (is_int($k)) {
                $this->options[$v] = null;
            } else {
                $this->options[$k] = $v;
            }
        }

        $this->nullable = $nullable;
        $this->defaultValue = $default;

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
     * @param  string|array|NULL
     * @return self
     */
    public function setParameters($parameters)
    {
        if ($parameters === null) {
            $parameters = [];
        } elseif (!is_array($parameters)) {
            $parameters = [$parameters];
        }

        $this->parameters = $parameters;
        $this->isModified(true);
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param  string
     * @param  scalar|NULL
     * @return self
     */
    public function addOption($option, $value = null)
    {
        $this->options[$option] = $value;
        $this->isModified(true);
        return $this;
    }

    /**
     * @param  array
     * @return self
     */
    public function setOptions(array $options)
    {
        $this->options = [];

        foreach ($options as $k => $v) {
            if (is_int($k)) {
                $this->options[$v] = null;
            } else {
                $this->options[$k] = $v;
            }
        }
        $this->isModified(true);
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function hasOption($name)
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @param  bool
     * @return self
     */
    public function setNullable($nullable = true)
    {
        $this->nullable = $nullable;
        $this->isModified(true);
        return $this;
    }

    /**
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @param  bool
     * @return self
     */
    public function setAutoIncrement($autoIncrement = true, $set_modified = true)
    {
        $this->autoIncrement = $autoIncrement;
        if ($set_modified) {
            $this->isModified(true);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param  scalar|NULL
     * @return self
     */
    public function setDefaultValue($defaultValue = null)
    {
        if (strtoupper($defaultValue) == 'NULL') {
            $this->setNullable(true);
        } elseif ($defaultValue !== null) {
            $this->setNullable(false);
        }

        $this->defaultValue = $defaultValue;
        $this->isModified(true);
        return $this;
    }

    /**
     * @return scalar|NULL
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue != null) {
            if (strtoupper($this->defaultValue) == 'NULL') {
                return 'NULL';
            } else if (stripos($this->defaultValue, "()")) {
                return $this->defaultValue;
            }
            return "'{$this->defaultValue}'";
        }
        return null;
    }

    /**
     * @param  string|NULL
     * @return self
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        $this->isModified(true);
        return $this;
    }

    /**
     * @return string|NULL
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * sets column position
     *
     * @param string|null $position
     * @return $this
     */
    public function setColumnPosition($position = null)
    {
        $this->columnPosition = $position;

        return $this;
    }

    /**
     * gets column position
     *
     * @return string
     */
    public function getColumnPosition()
    {
        if ($this->columnPosition == self::POSITION_FIRST) {
            return ' FIRST';
        } elseif ($this->columnPosition == self::POSITION_LAST) {
            return '';
        } elseif (is_string($this->columnPosition) && trim($this->columnPosition) != '') {
            return " AFTER `{$this->columnPosition}`";
        }

        return '';
    }

    /**
     * @return string
     */
    public function render()
    {
        $output = '`'.$this->getName() . '` ';
        $output .= $this->getType().
                (count($this->getParameters()) ? '('.implode(' ', $this->getParameters()).')' : '');

        foreach ($this->getOptions() as $option => $value) {
            $output .= ' ' . $option;
            if (isset($value)) {
                $output .= ' = ' . $value;
            }
        }

        $output .= ($this->isNullable() === false ? ' NOT NULL' : ' NULL');
        $output .= ($this->getDefaultValue() != null ? ' DEFAULT '.$this->getDefaultValue() : '');
        $output .= ($this->isAutoIncrement() == true ? ' AUTO_INCREMENT' : '');
        $output .= (trim($this->getComment()) != '' ? ' COMMENT \''.$this->getComment().'\'' : '');

        return $output;
    }

    public function showAlter()
    {
        if ($this->isExistingOnDb() && $this->isDeleted()) {
            return "DROP COLUMN ".$this->getName();
        } else if (!$this->isExistingOnDb()) {
            return "ADD ".$this->render() . $this->getColumnPosition();
        } else if ($this->isModified()) {
            return "MODIFY ".$this->render() . $this->getColumnPosition();
        }

        return "";
    }

    /**
     * check if type is valid
     *
     * @param $type
     * @return bool
     */
    protected function validateType($type)
    {
        return in_array(trim(strtoupper($type)), array_merge($this->dataTypes['numeric'], $this->dataTypes['string'], $this->dataTypes['datetime']));
    }
}
