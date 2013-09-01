<?php
/**
 * Database schema for this test:
 * +---------+--------------+------+-----+---------+----------------+
 * | Field   | Type         | Null | Key | Default | Extra          |
 * +---------+--------------+------+-----+---------+----------------+
 * | id      | int(11)      | NO   | PRI | NULL    | auto_increment |
 * | name    | varchar(256) | YES  |     | NULL    |                |
 * | enabled | bit(1)       | YES  |     | NULL    |                |
 * +---------+--------------+------+-----+---------+----------------+
 */

class Test extends SQLMonster\Model
{
    // Required, database fields
    protected $_fields = array (
		'id' => array ('IntegerField', null),
		'name' => array ('Textfield', null),
		'enabled' => array ('BooleanField', null)
	);

    // Required, table name
    protected $_table = 'test';

    // Required, Primary Key
	protected $_pk = 'id';

    // Optional
	public function __toString () {
		return (string)$this->name;
	}

}
