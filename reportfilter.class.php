<?php
/*!
 * \file 	class.reportfilter.php
 * \brief	Provides decorator class for Report class.
 *
 * Filter the requested items depending on given criteria. 
 * 
 * \version	0.9.1
 * \author	Mohamad H. ARAB
 * \date 	28, APR 2008
 *
 * Change log:
 * 		29, APR 2008:
 * 			+ All data manipulationsis now based on queries.
 * 		6, APR 2008:
 * 			+ different oprator support for search (such as =, <, >, LIKE, ...) added.
 * 			+ different logic operator such as and, or, ... added.
 * 		25 Jan, 2011:
 * 			+ removing '%' from value causes LIKE operator not to work correctly.
 * 		21 Nov, 2011 (0.9)
 * 			+ code clean-up
 * 			+ first release condidate version
 * 		20 Jan, 2012 (0.9.1):
 *			+ backquote table fields to prevent prolem in SQL when key name 
 *			  is a SQL reserved name.
 */

namespace SQLMonster;

/**
 * class ReportFilter 
 *
 * This class is decorator for Report class that apllies filter 
 * according to given criteria.
 */
class ReportFilter extends ReportDecorator {
	const VALUE						= 'VALUE';
	const OPERATOR 				 	= 'OPERATOR';
	const OPERATOR_EQUAL		 	= '=';
	const OPERATOR_LESSTHAN 	 	= '<';
	const OPERATOR_GREATERTHAN		= '>';
	const OPERATOR_LESSOREQUAL 		= '<=';
	const OPERATOR_GREATEROREQUAL	= '>=';
	const OPERATOR_NOTEQUAL			= '<>';	
	const OPERATOR_BETWEEN			= 'BETWEEN';	
	const OPERATOR_LIKE				= 'LIKE';
	const OPERATOR_IN				= 'IN';
	const OPERATOR_IS				= 'IS';
	const OPERATOR_AND				= 'AND';
	const OPERATOR_OR				= 'OR';

	public function __construct (&$report, $criteria, $logic_operator = self::OPERATOR_AND) {
		parent::__construct ($report);

		$this->_criteria = $criteria;
		$this->_logic_operator = $logic_operator;
	}

	/**
	 * creates SQL valid where string array
	 *
	 * @return string[] 
	 */
	protected function createFilter ()
	{
		// check operators ...
		$this->checkOperators ();

		foreach ($this->_criteria as $key => $value) {
			$operator = @$value[self::OPERATOR];

			if (strchr ($key, '.') === false)
				$key = "`$key`";

			if (is_string ($value[self::VALUE]) or is_int ($value[self::VALUE])) 
			{
				$_value = preg_replace ('/([\'\"`\;])/', '\$1', $value[self::VALUE]);
				$where[] = "$key " . (isset ($operator) ? $operator : self::OPERATOR_EQUAL) . " \'$_value\'";
			}
			else if (is_array ($value[self::VALUE]) and $operator == self::OPERATOR_BETWEEN) 
			{
				if (!is_int ($value[0])) {
					$value[self::VALUE][0] = "\'{$value[self::VALUE][0]}\'";
					$value[self::VALUE][1] = "\'{$value[self::VALUE][1]}\'";
				}
				$_value = implode (' AND ', $value[self::VALUE]);
				$where[] = "$key " . self::OPERATOR_BETWEEN . " {$_value}";
			} 
			else if (is_array ($value[self::VALUE])) 
			{
				$_value = '(' . implode (', ', $value[self::VALUE]) . ')';
				$where[] = "$key " . (isset ($operator) ? $operator : self::OPERATOR_IN) . " {$_value}";
			} 
			else if (is_null ($value[self::VALUE])) 
			{
				$where[] = "$key " . self::OPERATOR_IS . " NULL";
			}
		}

		return $where;
	}

	/**
	 * checks provided operators and generates 
	 * error in case of any problem.
	 *
	 * @return bool
	 */
	protected function checkOperators ()
	{
		foreach ($this->_criteria as $value) {
			// check for valid data with select operator
			if (isset ($value[self::OPERATOR])) {
				switch ($value[self::OPERATOR]) {
				case self::OPERATOR_EQUAL:
				case self::OPERATOR_LESSTHAN:
				case self::OPERATOR_GREATERTHAN:
				case self::OPERATOR_LESSOREQUAL:
				case self::OPERATOR_GREATEROREQUAL:
				case self::OPERATOR_NOTEQUAL:
				case self::OPERATOR_LIKE:
					if (!is_numeric ($value[self::VALUE]) or !is_string ($value[self::VALUE])) {
						trigger_error ("ReportFilter::apply: the values must be numeric/string.", E_USER_WARNING);
					}
					break;
				case self::OPERATOR_IN:
					if (!is_array($value[self::VALUE]) or count ($value) < 2) {
						trigger_error ("ReportFilter::apply: the values with in operator must be array.", E_USER_WARNING);
					}
					break;
				case self::OPERATOR_BETWEEN:
					if (!is_array($value[self::VALUE]) or count ($value) != 2) {
						trigger_error ("ReportFilter::apply: the values with between operator must be array.", E_USER_WARNING);
					}
					break;
				default:
					trigger_error ("ReportFilter::filter: Could not understand given operator ({$value[self::OPERATOR]}), skipping.", E_USER_WARNING);
					unset ($value[self::OPERATOR]);
					break;
				}
			}
		}
	}

	protected function apply ($query) {
		if (count ($this->_criteria) == 0)
			return $query;

		// Well, there is no where clause.
		$where = array();

		try {
			$where = $this->createFilter ();
		} catch (Exception $e) {
			trigger_error ("ReportFilter::apply: Could not add field ($key) to filter query.", E_USER_WARNING);
		}

		if (count ($where) > 0) {
			$where = implode (" {$this->_logic_operator} ", $where);
			$query = preg_replace (parent::QueryRegex . 'e', 
			"'\\1\\3 WHERE ' . (strlen('\\6')>0?'(\\6) {$this->_logic_operator} ':'') . '$where ' . '\\7\\9\\11'", $query);
		}

		return $query;
	}

	protected $_cirtria;
	protected $_logic_operator;
	protected $_negate;
}
?>
