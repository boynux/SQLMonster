<?php
/*
 * \file	class.integerfield.php
 * \brief	Integer field for Base class 
 *
 * \version	0.1
 * \author	Mohamad H. ARAB
 * \date 	28, Nov 2010
 */

namespace SQLMonster;
require_once "field.class.php";

class IntegerField extends Field {
	protected function _check_value () {
		return is_int ($this->_value);
	}

	public function set ($value) {
		$this->_value = intval($value);
	}
}
?>
