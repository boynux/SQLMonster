<?php
/*
 * \file	class.adslnumber.php
 * \brief	Integer field that provides restriction on ADSL 
 * 			allowd phone numbers for Base class 
 *
 * \version	0.2
 * \author	Mohamad H. ARAB
 * \date 	1 Jan 2011
 */

namespace SQLMonster;

require_once "libs/class.integerfield.php";
require_once "libs/class.telco.php";
require_once "libs/class.exception.php";


class ADSLNumberField extends TextField {
	/*
	public function set ($value) {
		try {
			$telco = TelcoManager::check_prefix ($value);
			parent::set ($value);
		} catch (TelcoCenterNotFound $e) { 
			if (get_class ($e) == "TelcoCenterNotFound")
				throw new TelcoCenterNotFound ($e->getMessage());
			else
				parent::set ($value);
		}
	}
	 */

	public function _check_value () {
		try {
			$telco = \TelcoManager::check_prefix ($this->_value);
			return true;
		} catch (\TelcoCenterNotFound $e) { 
			if (get_class ($e) == "TelcoCenterNotFound")
				return false;
			else
				return true;
		}
	}

	public function telco_center () {
		return \TelcoManager::instance()->check_prefix ($this->_value);	
	}
}
?>
