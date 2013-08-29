<?php
/*!
 * \file	class.report.php
 * \brief	This file provides Report class that simply shows a report to
 * 			the user, and allow user to do some modicications to given data.
 *
 * \version	0.9
 * \author	Mohamad H. ARAB
 * \date	28, APR 2008
 *
 * Change log:
 * 		29, APR 2008:
 * 			+ All data manipulations is now based on queries
 * 			+ show function replaced by generate
 * 			+ smarty is not require now. instead the generate function
 * 			  returns CList object containing all generated emplyee objects
 * 			  and we must manually pass it into engine and display the report.
 * 			  it make report engine more customizable.
 * 		9 Sep, 2009:
 * 			+ getCount cache added.
 * 		20 Jan, 2011:
 * 			+ Database cursors used
 */

//! Generates report and displays it to user
/*!
 * This class supposed to generate simple report containing the given
 * fields, and also provides some options for user to do some modifications
 * and/or view details of given information.
 */

namespace SQLMonster;

class Report implements IReport {
	//! constructor
	/*! This is a constructor of class.
	 * \param	Smarty $smarty
	 */
	public function __construct ($query = null, $index = 'id') {
		trigger_error ("Report::__constrct: Instantiating new report object.", E_USER_NOTICE);

		$this->_query = $query;
		$this->_index = $index;
	}

	//! generates report
	/*! runs prepared query and generates new reprort
	 *
	 * @return generated report data 
	 */
	public function generate ($query = null, $class = null) {
		return $this->fetchAll (is_null ($query) ? $this->getQuery() : $query, $class);
	}

	public function read ($query = null, $klass = null) {
		$this->_fetch_class = $klass;

		if (is_null ($klass)) {
			debug_print_backtrace();
		}

		return $this->runQuery (is_null ($query) ? $this->getQuery() : $query);
	}

	private function fetchAll ($query, $class) {
		try {
			trigger_error ("Report::runQuery: preparing query: ({$query})", E_USER_NOTICE);
			$db = new Database (array (
				'persistent' => true, 
				'type' => SQLMonster\FetchClass | SQLMonster\FetchPropsLate,
				'class' => get_called_class ()
			));

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
			$db = new Database (array (
				'persistent' => true, 
				'type' => SQLMonster\FetchClass | SQLMonster\FetchPropsLate,
				'class' => $this->_fetch_class
			));

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

	public function next ($type = PDO::FETCH_ASSOC, $class = null) {
		return $this->_db->Next (PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class);
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

		$db = new Database (array (
			'persistent' => true, 
			'type' => SQLMonster\FetchClass | SQLMonster\FetchPropsLate,
			'class' => get_called_class ()
		));

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

	protected $_smarty;
	protected $_query;
	protected $_db = null;
	protected $_result_set = null;
	protected $_fetch_class = null;
}
?>
