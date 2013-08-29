<?php
/*
 * \file 	class.reportdecorator.php
 * \brief 	Provides an abstract class to be a common subclass of 
 * 			all report decorator classes.
 *
 * \version	0.2
 * \author	Mohamad H. ARAB
 * \date 	28, APR 2008
 *
 * 	Change log:
 * 		+ 11 Sep, 2009:
 * 			* getCount new implementation 
 */

namespace SQLMonster;

//! class ReportDecorator provides common super class for all Decorator classes
/*!
 * \class ReportDecorator : IReport
 * 
 * This class contains some function definistions those are common in 
 * all decorator classes.
 */

abstract class ReportDecorator implements IReport {
	const QueryRegex = '/^(SELECT\s(.*?))(\bFROM\b(.*?))(\bWHERE\b(.*?))?(\bGROUP\sBY\b(.*?))?(\bORDER\sBY\b(.*?))?(\bLIMIT\b(.*?))?$/';

	protected $_report; /*!< IReport $report member variable, stores pass report object in constructor */

	//! constructor
	/*!\param IReport $report */
	public function __construct (&$report) {
		$this->_report = $report;
	}

	//! generates report
	/*! do approperiate fiters and modicications on query and 
	 * pass the function to its report object passed to constructor
	 *
	 * \sa __construct
	 * \return integer the count of records. ...
	 */
	public function generate ($query = null, $get_list = true, $class = null) {
		if (is_null ($query))
			$query = $this->apply ($this->getQuery());
		else
			$query = $this->apply ($query);

		return $this->_report->generate($query, $get_list, $class);
	}

	public function read ($query = null, $class = null) {
		if (is_null ($query))
			$query = $this->apply ($this->getQuery());
		else
			$query = $this->apply ($query);

		return $this->_report->read ($query, $class);
	}

	public function next ($type = PDO::FETCH_ASSOC, $class = null) {
		return $this->_report->next (null, $class);
	}

	//! get total rows of report
	/*! do approperiate fiters and modicications on query and 
	 * pass the function to its report object passed to constructor
	 *
	 * \sa __construct
	 * \return integer the count of records. ...
	 */
	public function getCount ($query = null, $cache = true) {
		if (is_null ($query))
		 	$query = $this->apply ($this->getQuery());
		else
			$query = $this->apply ($query);

		return $this->_report->getCount ($query, $cache);
	}

	public function setPrimaryIndex ($index) {
		$this->_report->setPrimaryIndex($index);
	}

	abstract protected function apply ($query);

	public function getQuery () {
		return $this->_report->getQuery();
	}

	//! retrive list of report records from super class.
	/*!\return CList */
	public function &getList () {
		return $this->_report->getList();
	}

	public function setList (&$list) {
		$this->_report->setList ($list);
	}

	public function setQuery ($query) {
		$this->_report->setQuery($query);
	}

	public static function map ($key, $reverse = false) {
		static $map = array (
			'id' 				=> 'id',
			'from'				=> '`from`',
			'to'				=> '`to`'
		);

		$result = $reverse ? array_search ($key, $map) : @$map[$key];

		if (!is_null ($result)) {
			return $result;
		} else 
			return $key;
	}
}
?>
