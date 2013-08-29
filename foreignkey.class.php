<?php
/*
 * \file	class.foreignkey.php
 * \brief	provides field class for Foreign Keys
 *
 * \version	0.3
 * \author	Mohamad H. ARAB
 * \date 	14 Dec, 2010
 *
 * 		29 Jan, 2011:
 * 			+ null is allowed.
 */
namespace SQLMonster;

use \ReflectionClass  as ReflectionClass;

class ForeignKey extends Field {
	public function __construct ($name, $reference, $params = array ()) {
		parent::__construct ($name, $params);
		
		$this->_unique = true;

		if (isset ($params['field'])) {
			$this->target_field = $params['field'];
		}

		if (isset ($params['unique'])) {
			$this->_unique = $params['unique'];
		}

		if (isset ($params['null'])) {
			$this->_null = true;
		}	

		$this->_reference = $reference;
/*
		$klass = new ReflectionClass ($this->_reference);
		if ($this->_unique) {
			$this->_value = $klass->newInstanceArgs ();
		} else {
			$this->_value = array ();
		}
	*/
	}


	protected function _check_value () {
		if ($this->_null and !$this->_value_id)
			return true; 
		#
		try {
			# $this->_value->get_object ($this->_value_id);
			$this->get ();
			return true;
		} catch (DoesNotExist $e) {
			return false;
		}
	}


	public function get () {
		if (!$this->_value_id)
			return new $this->_reference;
	
		if ($this->_dirty) {
			$klass = new ReflectionClass ($this->_reference);
			if ($this->_unique) {
				$this->_value = $klass->newInstanceArgs ();
			} else {
				$this->_value = array ();
			}

			if (isset  ($this->target_field)) {
				$this->_value = $this->_value->get_object (array ($this->target_field => $this->_value_id));
			} else {
				$this->_value = $this->_value->get_object ($this->_value_id);
			}
		}

		$this->_dirty = false;
		return $this->_value;
	}

	public function set ($value) {
		/*
		if ($this->_unique)
			$this->_value = $this->_value->get_object ($value);
		else 
			$this->_value = $this->_value->filter ($value);
		 */

		$this->_value_id = $value;
		$this->_dirty = true;
	}

	protected $_value_id = null;
	protected $_dirty = true;
	protected $_null = false;
}
?>
