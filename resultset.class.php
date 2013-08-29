<?php
/*!
 * \file	resultset.class.php
 * \brief	This file provides ResultSet class that simply iterates database result, 
 * 			and allow user to do some modicications to given data.
 *
 * \version	0.1
 * \author	Mohamad H. ARAB
 * \date	22 Jan, 2011
 *
 * Change log:
 */

//! Generates result set
/*!
 * This class supposed to generate simple result set.
 */

namespace SQLMonster;

use \ArrayAccess, \countable, \Iterator;

class ResultSet implements ArrayAccess, countable, Iterator {
	//! constructor
	/*! This is a constructor of class.
	 */
	public function __construct (\PDOStatement $sth) {
		trigger_error ("ResultSet::__constrct: Instantiating new ResultSet object.", E_USER_NOTICE);
		
		$this->_statement = $sth;
	}
	
	//! generates report
	/*! runs prepared query and generates new reprort
	 *
	 * @return CList generated report container
	 */
	public function generate ($query = null, $class = null) {
		return $this->fetchAll (is_null ($query) ? $this->getQuery() : $query, $class);
	}

	public function read ($query = null, $class = null) {
		$this->_fetch_class = $class;
		return $this->runQuery (is_null ($query) ? $this->getQuery() : $query);
	}

	private function fetchAll ($query, $class) {
		try {
			trigger_error ("Report::runQuery: preparing query: ({$query})", E_USER_NOTICE);
			$db = new Database ();

			$db->SetQuery (is_null ($query) ? $this->_query : $query);
		
			if (is_null($class)) {
				$data = $db->ReadAll(PDO::FETCH_OBJ);
			} else {
				$data = $db->ReadAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
			}

			return $data;
		} catch (Exception $e) {
				echo $e;
		}
	}

	private function runQuery ($query = null, $class = null) {
		try {
			trigger_error ("Report::runQuery: preparing query: ({$query})", E_USER_NOTICE);
			$db = new Database ();

			$db->SetQuery (is_null ($query) ? $this->_query : $query);

			if (is_null($class)) {
				$data = $db->Read(); // PDO::FETCH_OBJ);
			} else {
				$data = $db->Read(); //PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
			}

			$this->_db = $db;

			return $data;
		} catch (Exception $e) {
			echo $e;
		}
	}

	public function next (/* $type = PDO::FETCH_ASSOC, $class = null*/) {
		# return $this->_db->Next (PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
		if (is_null ($this->_current)) {
			$this->rewind ();
		}

		$this->_current = $this->_statement->fetch ();

		$this->_index ++;
	}

	public function apply ($query = null) {
		return $query == null ? $this->getQuery() : $query;
	}

	public function getCount ($query = null, $cache = true) {
		$sig = md5 ("{$this->_index}:$query");

		// cache count query to reduce database queries
		if ($cache && isset ($this->_count)) {
			if (isset ($this->_count [$sig])) {
				trigger_error ("Report::getCount: Get Helpers count from cache with signature $sig.", E_USER_NOTICE);
				return $this->_count [$sig];
			}
		}

		trigger_error ("Report::getCount: Get Helpers count with query: ($query)", E_USER_NOTICE);
		$query = $query == null ? $this->getQuery () : $query;

		static $last_query = '';
		static $rows = 0;

		$query = preg_replace (ReportDecorator::QueryRegex, 
			"SELECT COUNT(DISTINCT {$this->_index}) \\3\\5\\11" , $query);

		$db = new Database ();
		$db->SetQuery ($query);

		$res = $db->ReadOneRow ();
		$rows = intval ($res[0]);

		$db = null;

		// cache count 
		$this->_count [$sig] = $rows;

		return $rows;
	}

	public function setPrimaryIndex ($index) {
		$this->_index = $index;
	}

	public function getQuery () {
		return $this->_query;
	}

	public function setQuery ($query) {
		$this->_query = $query;
	}

	protected function load () {
		if (is_null ($this->_query))
			throw new Exception ("Query is empty");
	}

	# ArrayAccess interface methods ...
	# --------------------------------------
	public function offsetExists ($offset) {
		return (count ($this) >= $offset);
	}

	public function offsetGet ($offset) {
		if ($this->_index == $offset) {
			return $this->current ();
		}

		if (count ($this) > $offset) {
			if ($offset < $this->_index)
				$this->rewind();

			for ($index = 0; $index <= $offset; ++$index) {
				$this->next ();
			}

			return $this->current ();
		}

		return null;
	}

	public function offsetSet ($offset, $mixed) {
		print __FUNCTION__;	

	}

	public function offsetUnset ($offset) {
		print __FUNCTION__;	

	}

	public function count () {
		return $this->_statement->rowCount ();
	}

	public function current () {
		if (is_null ($this->_current)) {
			$this->rewind ();
			$this->_current = $this->_statement->fetch();
		}

		return $this->_current;
	}

	public function key () {
		print __FUNCTION__;
	}

	public function valid () {
		return !is_null ($this->_statement) and $this->_index < count ($this);
	}

	public function rewind () {
		$this->_statement->execute ();
		
		$this->_index = 0;
		$this->_current = null;
	}
	# --------------------------------------
	
	protected $_smarty;
	protected $_query;
	protected $_statement;
	protected $_db = null;
	protected $_fetch_class = null;
	protected $_current = null;
	protected $_index = 0;
}
?>
