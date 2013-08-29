<?php
/*!
 * \file	class.reportsort.php
 * \brief	provides a decorator for Report class that sorts out the output of report
 *
 * \version	0.5.1
 * \author	Mohamad H. ARAB
 * \date 	28, APR 2008
 *
 * Change log:
 * 		29, APR 2008:
 * 			+ All data manipulations now based on queries.
 */

namespace SQLMonster;
//! Report decorator class
/*! this decorator class sorts the contents of reprt and then print them out 
 *
 * \sa class Report
 */
class ReportSort extends ReportDecorator {
	const OrderDescending	= 1;
	const OrderAscending	= 2;

	//! constructor
	/*! constructor for deoctator class, a valid Report object must be passed to it.
	 * \param Report $report
	 * \param array $fields fields that sorting must be based on
	 * \param enum $order the order that sort must be based on
	 */
	public function __construct (&$report, $fields = array (), $order = self::OrderAscending) {
		parent::__construct ($report);

		$this->_order 	= $order;
		$this->_fields 	= $fields;
	}

	//! sort function.
	/*! the sorting happens here ...
	 */
	protected function apply ($query) {
		// usort ($this->_report->getList()->iterator(), array ($this, 'compare'));
		// we must add this part to query but carefuly!
		
		$order = array ();
		foreach ($this->_fields as $field) {
			try {
				$field = $this->map ($field);

				$order[] = "$field " . ($this->_order == self::OrderDescending ? 'DESC' : 'ASC');
			} catch (Exception $e) {
				// Ignore
			}
		}

		if (count ($order) > 0) {
			$order = implode (",", $order);

			// $query = preg_replace ('/^(SELECT\s.*?)(\bFROM\b.*?)(\bWHERE\b.*?)?(\bGROUP\sBY\b.*?)?((?:\bORDER\sBY\b)(.*?))?(\bLIMIT\b.*?)?$/e', "'\\1\\2\\3\\4 ORDER BY ' . (strlen('\\6')>0?'\\6,':'') . '$order ' .'\\7'", $query);
			$query = preg_replace (self::QueryRegex . 'e', "'\\1\\3\\5\\7 ORDER BY ' . (strlen('\\10')>0?'\\10,':'') . '$order ' .'\\11'", $query);
		}

		return $query;
	}

	//! compare helper function
	/*! the function works in join with sort method and actually 
	 *  sorts the list with given fields and order that is passed
	 *  to constructor
	 *  \sa __construct, sort
	 *  \param object $a first object to compare
	 *  \param object $b second object to comapre
	 *  \return int 0, <0, >0 depending on comparsion result.
	 */
	protected function compare ($a , $b) {
		foreach ($this->_fields as $field) {
			try {
				if (is_int ($a->$field)) {
					$result = $a->$field - $b->$field;
				} else {
					$result = strcmp ($a->$field, $b->$field);
				}

				if ($result != 0) {
					return $result * $this->_order;
				}
			} catch (ADTException $e) {
				echo $e;
				return 0;
			}
		}
	}

	protected $_order;
	protected $_fields;
}

class SortFields {
	static public function isValid ($fields = null) {
		if ($fields == null) {
			return false;
		}

		$fields = (array)$fields;
		foreach ($fields as $field) {
			if (array_search ($field, SortFields::$Fields) === false) {
				return false;
			}
		}

		return true;
	}

	static private $Fields = array (
		"LastName",
		"BranchCode",
		"OrganizationID",
		"Birthdate",
		"FirstName",
		"JoinDate",
		"IDNumber",
		"Sex",
		"SubscriptionCode",
		"SpecialProfession"

	);

}
?>
