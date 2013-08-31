<?php
/*
 * \file	class.booleanfield.php
 * \brief	Boolean field for Base class 
 *
 * \version	0.1
 * \author	Mohamad H. ARAB
 * \date 	21, Dec 2011
 */

namespace SQLMonster;
require_once "field.class.php";

class BooleanField extends Field {
	protected function _check_value () {
		return true;
	}

	public function set ($value) {
		if (is_bool ($value)) {
			$this->_value = $value;
		} else {
			$this->_value = ord ($value) == 1 ? true : false;
		}
	}

	public function get () {
		return (bool)$this->_value;
	}
}
?>
