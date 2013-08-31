<?php
/*
 * \file	passwordfield.class.php
 * \brief	Encrypted password field
 *
 * \version	0.1
 * \author	Mohamad H. ARAB
 * \date 	24, Nov 2011
 */

namespace SQLMonster;
require_once "field.class.php";


class PasswordField extends Field {
	public function __construct () {
		parent::__construct ();
	}

	public function get () {
		return $this;
	}

	protected function hash ($value)
	{
		$salt = md5 ($value);
		$hash = \crypt ($value, "\$1\$$salt\$");

		return $hash;
	}

	public function set ($value) 
	{
		if (strpos ($value, '$1$') !== 0) {
			$this->_value = $this->hash ($value);
		} else {
			$this->_value = $value;
		}
	}

	public function compare ($value) 
	{
		return $this->_value === $this->hash ($value);
	}

	protected function _check_value () 
	{
		return true;
	}

	public function clear () {
		return $this->_value;
	}
}
?>
