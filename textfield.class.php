<?php
/*
 * \file	class.base.php
 * \brief	provides abastact class for database related calsses
 *
 * \version	0.1
 * \author	Mohamad H. ARAB
 * \date 	28, Nov 2010
 */

namespace SQLMonster;
require_once "field.class.php";

class TextField extends Field {
	protected function _check_value () {
		return true;
	}
}
?>
