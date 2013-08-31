<?php
/*
 * \file	class.model.php
 * \brief	provides base class for database related calsses
 *
 * \version	0.9.1
 * \author	Mohamad H. ARAB
 * \date 	09, Jan 2011
 *
 * 		* 20 Jan, 2011:
 * 			+ Iterator integeration added
 * 			+ Many-to-Many relations support added
 * 		* 22 Jan, 2011:
 * 			+ ArrayAccess and counable interfaces added
 *		* 4 Feb, 2011:
 *			+ filter and get_object functions converted to static functions.
 *			+ optional order parameter added to filter function.
 *              * 25 Jun 2012:
 *                      + the function update() returned to class Model
 * 				
 */

namespace SQLMonster;

require_once "exception.class.php";

use \ReflectionClass as ReflectionClass;
use \Exception as Exception;
use \SimpleXMLElement as SimpleXMLElement;
 
abstract class Model 
	implements 
		\Iterator,
		\ArrayAccess, 
		\countable 
{

	private $_operators = array ('lt', 'gt', 'le', 'ge', 'in', 'like', 'between');

	/**
	 * __construct 
	 * 
	 * default constructor.
	 *
	 * @args array
	 * 	we can pass field initialization values into constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct ($args = array ()) {
		# initializations
		$this->_initialize ();

		# if there is any arguments, 
		# try to initialize object with values.
		foreach ($args as $arg) {
			$this->{key ($arg)} = current ($arg);
		}
	}

	public function relation ($name, $reference, $table, $backref) {
		$_relation [$name] = array (
			'reference' => $reference, 
			'table' => $table,
			'backref' => $backref
		);
	}

	/**
	 * _initialize 
	 * 
	 * object initialization
	 *
	 * @access protected
	 * @return void
	 */
	protected function _initialize () {
		# first we instantiate new Query object that is used to query database.
		$this->_report = new Query (NULL, $this->_table.".".$this->_pk);

		# creating initial query ...
		$table = $this->_table;
		$fields = array_map (function ($field) use ($table) {return $table .".". $field; }, array_keys($this->_fields));

		# set initial query in Query object.
		$this->_report->setQuery ("SELECT " . implode (',', $fields) . " FROM {$this->_table}");

		# now instantiate every user defined fields
		foreach ($this->_fields as $key => $value) {
			$klass = new ReflectionClass ('SQLMonster\\' . current ($value));

			$args = array_slice ($value, 1, -1);
			$args = array_merge (array ($key), $args);

			$this->_fields[$key] = $klass->newInstanceArgs ($args);
		}
	}

	protected function _initializeReport ()
	{
	}

	public function __call ($method, $params = array ()) {
		if (preg_match ("/(\w+)_set$/", $method, $matches)) {

			$klass = new ReflectionClass ($matches[1]);
			$object = $klass->newInstanceArgs ();

			foreach ($object->_fields as $field => $value) {
				if (@$value->_reference == get_called_class ()) {
					try {
						# 18 Mar, 2011
						# temporary send called parameters to filter method.
						$result = $object->filter (array ($field => $this));
						if (count ($params) > 0)
							$result->filter ($params[0]);
					} catch (SQLMonster\DoesNotExist $e) {
						return false;
					}
	
					return $result;
				}
			}

			return $object;
		}
	}

	protected function _check_fields ($fields) {
		foreach ($fields as $field => $value) {
			if (!array_key_exists ($field, $this->_fields)) {
				throw new KeyError ("Database field (" . $field . ") is not valid");
			}
		}

		return true;
	}

	/**
	 * get_object
	 * retrives single object from database.
	 *
	 * @param mixed @filter
	 *  criteria to extract given object.
	 *  for possible values see @filter function.
	 *
	 * @access public
	 * @static
	 *
	 * @return Model
	 *  return Model object cotaining fetched column and throws 
	 *  approperiate Exception either no object or more than one
	 *  object exist.
	 */
	public static function get_object ($filter = array ()) {
		$criteria = array ();

		# creating new model object.
		$object = static::instance()->filter ($filter);
	
		# as we expect only one result in database for this function to reutrn,
		# check for ONLY ONE table row has been returned and raise excpetion otherwise.
		if (count ($object) > 1) {
			throw new ResultIsNotUnique ("More than one result object returned!");
		}

		# and if the result set is empty !
		if (count ($object) == 0) {
			throw new DoesNotExist ("No object found.");
		}

		# return fetched object 
		return $object->current ();
	}

	/**
	 * order_by 
	 * 
	 * @param array $fields 
	 * 	fields to set order for them ...
	 *
	 * @param string $order 
	 * 	possible values are ASC or DESC
	 *
	 * @access public
	 * @return $this
	 */
	public function order_by ($fields = array (), $order = 'DESC') {
		$this->_report = new ReportSort ($this->_report, (array)$fields, strcasecmp ($order, 'DESC') == 0 ? 1 : 2);

		return $this;
	}

	private function _parse_keys ($filter) {
		$cond = array ();

		foreach ($filter as $key => $value) {
			$val = $this->_parse_value ($value);

			$keys = preg_split ("/__/", $key);
			$op = '';

			if (count ($keys) > 1) {
				if (in_array ($keys [count ($keys) - 1], $this->_operators)) 
					$op = array_pop ($keys);

				$self = $this;
				for ($index = 0; $index < count ($keys) - 1; ++$index) {
					if ($self->{$keys[$index]} instanceof Model) {
						$object = $self->{$keys[$index]};
					
						$self->_tables [$object->_table] = array (
							"TABLE" => $object->_table,
							"USING" => array ($object->_pk, $self->_table.".".$keys[$index]),
							"FIELD" => array ($keys[$index + 1])
						);
					} else if (array_key_exists ($keys[$index], $self->_relation)) {
						print "$keys[$index]";
					} else {
						throw new InvalidData ("Invalid reference table ($keys[$index])");
					}

					// $self = $object;
				}
				
				$key = $keys[$index] ;

				switch ($op) {
				case 'lt':
					$op = ReportFilter::OPERATOR_LESSTHAN;
					break;
				case 'gt':
					$op = ReportFilter::OPERATOR_GREATERTHAN;
					break;
				case 'le':
					$op = ReportFilter::OPERATOR_LESSOREQUAL;
					break;
				case 'ge':
					$op = ReportFilter::OPERATOR_GREATEROREQUAL;
					break;
				case 'in':
					$op = ReportFilter::OPERATOR_IN;
					break;
				case 'like':
					$op = ReportFilter::OPERATOR_LIKE;
					break;
				case 'between':
					$op = ReportFilter::OPERATOR_BETWEEN;
					break;
				case 'not':
					$op = ReportFilter::OPERATOR_NOTEQUAL;
					break;
				default:
					$op = null;
					// throw new InvalidData ("Invalid operator ($op)");
				}

				$cond[$key] = array (ReportFilter::VALUE => $val, ReportFilter::OPERATOR => $op);
			} else {
				$cond[$key] = array(ReportFilter::VALUE => $val);
			}
		}
	
		return $cond;
	}

	private function _parse_value ($value) {
		$result = array ();

		// if ($value instanceof Model and count ($value) == 0) {
		if ($value instanceof Model and $value->{$value->_pk} != 0) {
			$result = $value->{$value->_pk};
		} else if ($value instanceof Model and count ($value) == 1)  {
			$result = $value->current()->{$value->_pk};
		} else if ($value instanceof Model) {
			foreach ($value as $instance) {
				$result[] = $instance->{$instance->_pk};
			}
		} else if (is_array ($value)) {
			do {
				$result[] = $this->_parse_value (current ($value));
			} while (next ($value));

			reset ($value);
		} else if ($value instanceof \DateTime) {
			# return $value->format (DATE_ISO8601);
			return $value->format ("Y-m-d H:i:s");
		} else {
			return "$value";
		}	

		return $result;
	}

	public function pagination($offset, $limit)
	{
		$this->_report = new ReportPager($this->_report, $offset, $limit);

		# we are ready to query!
		$this->_can_query = true;
		$this->_result_set = null;

		# and if every thing goes well here is the final result.
		return $this;
	}

	/**
	 * filter
	 * filter specific table and returns traversable Model object.
	 *
	 * @param: mixed @filter
	 * 	could be array containing assiciated field + optional operators and respected values.
	 * 	ex. array ('name__like' => 'test') # creates a filter for `name` field + like operator 
	 * 	with value of 'test'.
	 * 	also could be a single value that is considered as primary index value.
	 * @param: array @order
	 * 	associative array containing column names and order type to set ordering.
	 * 	ex. array ('name' => 'DESC')
	 *
	 * @acces public 
	 *
	 * @return Model 
	 * 	return a Model object that contains filtered data from table.
	 */
	public function filter ($filters = array (), $order = array ()) {
		# contains filter criteria after parsing
		$criteria = array ();

		# check to see what type of filter criteria in given and parse given values.
		if (!is_array ($filters)) {
			$criteria[$this->_table . '.' .$this->_pk] = array (ReportFilter::VALUE => $this->_parse_value ($filters));
		} else {
			$criteria = $this->_parse_keys ($filters);
		}

		# instantiating new ReportFilter to apply given criteria.
		$this->_report = new ReportFilter ($this->_report, $criteria);

		# appending other tables if necessary. 
		foreach ($this->_tables as $table) {
			$this->_report = new TableDecorator ($this->_report, $table['TABLE'],
				$table ['USING'],
				$table ['FIELD']
			);
		}

		# check to see if there is any order items given use default ordering 
		# in model object instead.
		if (count ($order) == 0 and count ($this->_order_by) > 0) {
			$order = $this->_order_by;
		}

		# revers orders (ReportSort applies order in revers order)
		$order = array_reverse  ($order);

		# new apply report orders one by one.
		foreach ($order as $field => $type) {
			$this->_report= new ReportSort ($this->_report, (array)$field, strcasecmp ($type, 'DESC') == 0 ? 1 : 2);
		}

		# we are ready to query!
		$this->_can_query = true;

		# and if every thing goes well here is the final result.
		return $this;
	}

	/*
	 * create_adt
	 *
	 * create abstract data type object from model information.
	 *
	 * @access public
	 * @return ADT object
	 *
	 */
	public function create_adt ($rec = true) {
		$objects = array ();

		if (!$this->{$this->_pk} and count ($this)) {
			foreach ($this as $model) {
				$objects[] = $model->create_adt_helper ($rec);
			}
		} else if ($this->{$this->_pk}){
			$objects = $this->create_adt_helper ($rec);
		}

		return $objects;
	}

	private function create_adt_helper ($rec = true) {
		#  create an empty raw object 
		$object = (object)null;

		#  iterating through model and populate object
		foreach ($this->_fields as $field => $value) {
			if ($this->{$field} instanceof Model)
				try {
					if ($rec)
						$object->{$field} = $this->{$field}->create_adt ();
				} catch (Exception $e) {
					$object->{$field} = array ();
				}
			else 
				$object->{$field} = $this->{$field};
		}

		return $object;
	}

	/*
	 * create_xml
	 *
	 * create xml from model information.
	 *
	 * @access public
	 * @return XML Document
	 *
	 */
	public function create_xml ($doc, $echo = false, $not_embed = array (), $relations = array ()) {
		return $this->create_xml_element ($doc->firstChild, $not_embed, $relations);
	}

	private function create_xml_element ($parent, $not_embed, $relations) {
		$root = $parent -> appendChild (new \DOMElement (strtolower (get_called_class ()) . "s"));

		try {
			foreach ($this as $model) {
				$child = $root->appendChild ($model->create_xml_element_rec ($root, $not_embed));

				if (count ($relations) > 0) {
					foreach ($relations as $relation) {
						$model->$relation->create_xml_element ($child, $not_embed /*array ('helper', 'type')*/, array ());
					}
				}
			}
		} catch (SQLMonster\ModelNotReady $e) {
		 	$root->appendChild ($this->create_xml_element_rec ($root, $not_embed));
		}

		return $parent;

	}

	private function create_xml_element_rec ($parent, $not_embed) {

		$node = $parent -> appendChild (new \DOMElement (strtolower (get_called_class ())));

		foreach ($this->_fields as $field => $value) {
			try {
				$value = $this->{$field};

				if ($value instanceof Model && $field != $this->_pk and !in_array ($field, $not_embed)) {
					if ($value->{$value->_pk}) {
						$node->appendChild ($value->create_xml_element_rec ($parent, $not_embed));
					} else if ($this->{$field} instanceof Model and $field != $this->_pk) {
						; // TODO: Recursively add this node to element ...
					} 
				} else {
					if ($value instanceof Model)
						$node->setAttribute ($field, $value->{$value->_pk});
					else
						$node->setAttribute ($field, $value);
				}
			} catch (Exception $ex) {
				// echo $ex;
			}
		}

		return $node;
 	}

	/* ----- Begining of ArrayAccess interface implementation ----- */

	/**
	 * offsetExists 
	 * 
	 * check wether current offset is valid or not. this is part of ArrayAccess implementation.
	 *
	 * @param mixed $offset 
	 *
	 * @access public
	 * @return boolean
	 */
	public function offsetExists ($offset) {
		# if our Query is not executed yet, then go and call it.
		if (!isset ($this->_result_set) or is_null ($this->_result_set)) {
			$this->rewind ();
		}

		return $this->_result_set->offsetExists ($offset);
	}

	/**
	 * offsetGet 
	 * 
	 * return object for given offset in array, this is part of ArrayAccess interface implementation.
	 *
	 * @param mixed $offset 
	 * @access public
	 *
	 * @return Model object
	 */
	public function offsetGet ($offset) {
		# check to see if read function is already called on Query object,
		# otherwise call it first.
		if (!isset ($this->_result_set) or is_null ($this->_result_set)) {
			$this->rewind();
		}
		
		return $this->_result_set->offsetGet($offset);
	}

	/**
	 * offsetSet 
	 * 	
	 * 	Not impelemented yet! this is part of ArrayAccess interface.
	 *
	 * @param mixed $offset 
	 * @param mixed $value 
	 *
	 * @access public
	 * @return void
	 */
	public function offsetSet ($offset, $value) {
		throw new Exception ("Not supported yet!");
	}

	/**
	 * offsetUnset 
	 * 
	 * 	Not impelemented yet! this is part of ArrayAccess interface.
	 *
	 * @param mixed $offset 
	 * @access public
	 * @return void
	 */
	public function offsetUnset ($offset) {
		throw new Exception ("Not supported yet!");
	}

	/* ----- End of ArrayAccess interface implementation ----- */

	/* ----- Begining of iterator interface implementation ----- */
	/**
	 * @Iterator interface implementation 
	 *
	 * @current
	 * @next
	 * @key
	 * @rewind
	 * @valid
	 */

	/**
	 * current 
	 * return current object. this is part of Iterator interface.
	 *
	 * @access public
	 * @return Model instance.
	 */
	public function current () {
		if (!$this->_can_query ) {
			throw new ModelNotReady ();
		}
		# first check to see if we already called read function on 
		# out Query object, if not call it.
		if (!isset ($this->_result_set) or is_null ($this->_result_set)) {
			$this->_result_set = $this->_report->read (null, get_called_class());
		}

		return $this->_result_set->current ();
	}

	/**
	 * next 
	 * advances iterator to next object, this is part of Iterator interface 
	 *
	 * @access public
	 * @return void
	 */
	public function next () {
		return $this->_result_set->next ();
	}

	/**
	 * key 
	 * 
	 *	returns current object key, this is part of Iterator interface.
	 *
	 * @access public
	 * @return mixed
	 */
	public function key () {
		return key ($this->_result_set);
	}

	/**
	 * valid 
	 * 
	 * return where this iterator position is valid or not, this is part of iterator interface.
	 *
	 * @access public
	 * @return boolean
	 */
	public function valid () {
		return $this->_result_set->valid();
	}

	/**
	 * rewind 
	 * 
	 * rewind iterator to the first item. this is part of iterator interface.
	 * 
	 * @access public
	 * @return void
	 */
	public function rewind () {
		if (!$this->_can_query ) {
			throw new ModelNotReady ();
		}

		# first check to see if we already called read function on 
		# out Query object, if not call it.
		if (!isset ($this->_result_set) or is_null ($this->_result_set)) {
			$this->_result_set = $this->_report->read (null, get_called_class());
			return;
		}

		# call ResultSet rewind otherwise.
		$this->_result_set->rewind ();
	}

	/* ----- End of iterator interface implementation ------ */

	/* ----- Begining of coutable interface implementation ------ */
	/**
	 * count 
	 *
	 * returns count of avaiable objects. this is part of Coutable iterator.
	 *
	 * @access public
	 * @return int
	 */
	public function count () {
		# check to see if read function is already called on Query object,
		# otherwise call it first.
		if (!isset ($this->_result_set) or is_null ($this->_result_set)) {
			try {
				$this->rewind();
			} catch (ModelNotReady $e) {
				return 0;
			}
		}

		# and here we return actual count.
		return count ($this->_result_set);
	}

	/* ----- End of coutable interface implementation ------ */

	public function __get ($item) {
		if (array_key_exists ($item, $this->_relation)) {
			if (array_key_exists ($item, $this->_references)) 
				return $this->_references[$item];

			$klass = new ReflectionClass ($this->_relation[$item]['model']);
			$object = $klass->newInstanceArgs ();

			$ref_class = new ReflectionClass ($this->_relation[$item]['reference']);
			$reference = $ref_class->newInstanceArgs ();

			$reference->_tables [$object->_table] = array (
				'TABLE' => $object->_table,
				'USING' => array ($item, $reference->_table .".".$reference->_pk),
				'FIELD' => array ($this->_relation[$item]['backref'] => $this->_relation[$item]['backref'])
			);

			try {
				$this->_references[$item] = $reference->filter (array ($object->_table . '.' . $this->_relation[$item]['backref'] => $this));
			} catch (DoesNotExist $e) {
				return  $reference;
			}

			return $this->_references[$item];
		}
	
		if (!array_key_exists ($item, $this->_fields)) {
			throw new KeyError ("key {$item} not found in class " . get_called_class (). "!");
		}
		
		return $this->_fields [$item]->get ();
	}

	public function __set ($item, $value) {
		if (!array_key_exists ($item, $this->_fields)) {
			# throw new KeyError ("key {$item} not found!");
			return;
		}
		
		$this->_fields[$item]->set ($value);

		return $value;
	}

	public function clear () {
		$result = array ();

		foreach ($this->_fields as $field => $value) {
			$result[$field] = $value->clear ();
		}

		return $result;
	}

	protected function _add_to_database ($data) {
		$fields = array ();
		$values = array ();

		foreach ($data as $key => $field) {
			$fields[] = "`$key`";
			$values[] = "'" . $this->_parse_value ($field) . "'";
		}

		if ($this->{$this->_pk}) {
			$pk = $data[$this->_pk];
			unset ($data[$this->_pk]);
			
			$set= array ();
			foreach ($data as $key => $value) {
				$value = $this->_parse_value ($value);
				$set[] = "`$key`='$value'";
			}
			$set = implode (",", $set);

			if ( $pk instanceof Model )
				$pk = $pk->{$pk->_pk};

			try {
				$this->get_object ($pk);
				$Query = "UPDATE {$this->_table} SET $set WHERE `{$this->_pk}`='$pk';";
			} catch (DoesNotExist $e) {
				$Query = "INSERT INTO {$this->_table} (" . implode (',', $fields) . ") VALUES (" . implode (',', $values) . ")";
			}
		} else {
			$Query = "INSERT INTO {$this->_table} (" . implode (',', $fields) . ") VALUES (" . implode (',', $values) . ")";
		}

		$dbh = new Database (array (
			'persistent' => true, 
		));

		$dbh->setQuery ($Query);

		try {
			$last_id = $dbh->execute ();
			if ($this->{$this->_pk}) 
				return $this->{$this->_pk};
			else 
				return $last_id;
		} catch (Exception $e) {
			throw new DuplicateEntry ($e);
		}
	}

	protected function _delete_from_database ($pk) {
		$Query = "DELETE FROM {$this->_table} WHERE {$this->_pk} = '$pk'";

		$dbh = new Database (array (
			'persistent' => true, 
		));
		$dbh->setQuery ($Query);

		try {
			return $dbh->execute ();
		} catch (Exception $e) {
			throw new DoesNotExist ($e);
		}
	}

	public static function create_new ($data) {
		$klass = new ReflectionClass (get_called_class ());
		$object = $klass->newInstanceArgs ();

		foreach ($object->_fields as $field => $value) {
			if ($field !== $object->_pk and isset ($data[$field])) {
				$object->{$field} = $data[$field];
			}
		}
		
		return $object;
	}

	public function update ($data) {
		foreach ($this->_fields as $field => $value) {
			if ($field !== $this->_pk and isset ($data[$field])) {
				$this->{$field} = $data[$field];
			}
		}
		
		return $this;
	}

	public function save () {
		$result = $this->clear ();
		$this->{$this->_pk} =  $this->_add_to_database ($result);

		return $this;
	}

	public function delete () {
		return $this->_delete_from_database ($this->{$this->_pk});
	}

	public static function instance () {
		$klass = new ReflectionClass (get_called_class ());
		return $klass->newInstanceArgs ();
	}

	protected $_report;
	protected $_fields = array ();
	protected $_relation = array ();
	protected $_table = '';
	protected $_tables = array ();
	protected $_pk = 'id';
	protected $_order_by = array ();
	protected $_references = array ();
	protected $_result_set = null;
	protected $_can_query = false;
}
?>
