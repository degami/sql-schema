
# Degami\SqlSchema

Library for describe of the database schema.

forked from : https://github.com/czproject/sql-schema

## Installation

```
composer require degami/sql-schema
```

Degami\SqlSchema requires PHP 5.3.0 or later.

## Usage

``` php
use Degami\SqlSchema\Index;
$schema = new Degami\SqlSchema\Schema;

$table = $schema->addTable('book');
$table->addColumn('id', 'INT', NULL, array('UNSIGNED'));
      ->addColumn('name', 'VARCHAR', array(200));
      ->addColumn('author_id', 'INT', NULL, array('UNSIGNED'));
      ->addIndex(NULL, 'id', Index::TYPE_PRIMARY);
      ->addIndex('name_author_id', array('name', 'author_id'), Index::TYPE_UNIQUE);

foreach( $schema->getTables() as $table ) {
    echo $table->showCreate()."\n";
}
```

------------------------------

License: [New BSD License](license.md)
