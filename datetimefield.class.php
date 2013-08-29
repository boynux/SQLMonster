<?php
/*
 * \file	class.datetimefield.php
 * \brief	provides date time field class
 * 
 * \version	0.1
 * \author	Mohamad H. ARAB
 * \date 	21 Dec, 2010
 */

namespace SQLMonster;

class DateTimeField extends Field {
	protected function _check_value () {
		if ($this->_value instanceof \DateTime) {
			return true;
		} else {
			return false;
		}
	}

	public function set ($value) {
		try {
			if (!$value  instanceof \DateTime) {
				$this->_value = new \DateTime ($value);
			} else {
				$this->_value = $value;
			}
		} catch (Exception $e) {
			throw new InvalidData ("Invalid date format ($value)");
		}
	}

	public function get ()
	{		
		if ($this->_value  instanceof \DateTime) 
			return $this->_value->format (DATE_ATOM);
		else 
			return $this->_value;
	}

	public function __toString () {
		// return $this->_value->format ("d-m-o H:i");
		return $this->_value->format (DATE_ATOM);	
	}
}
?>
