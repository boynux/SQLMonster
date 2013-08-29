<?php
/*
 *\file		class.field.php
 *\brief	Base class for data base fields
 *
 *
 *\version	0.1
 *\author	Mohamad H. ARAB
 *\date		8 Dec, 2010
 *
*/

namespace SQLMonster; 

class Field {
	public function __construct ($name = null, $params = array ()) {
		$this->_value = 0;

		foreach ($params as $key => $value) {
			switch ($key) {
			case 'value':
				$this->_value = $value;
				break;
			case 'isnull':
				$this->_null = true;
			default:
				break;
			}
		}

		$this->_name = $name;
	}

	public function clear () {
		if ($this->_check_value ()) {
			// return $this->_value;
			return $this->get ();
		} else {
			throw new InvalidData ("Field data is not valid ($this->_name).");
		}
	}

	public function set ($value) {
		$this->_value = $value;
	}

	public function get () {
		return $this->_value;
	}

	protected function _check_value () {
		return true;
	}

	public function __toString () {
		return (string)$this->_value;
	}

	protected $_name;
	protected $_value = null;
}

?>
