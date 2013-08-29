<?php
/*!
 * \file	class.tabledecorator.php
 * \brief	provides a decorator for Report class that adds new tables to current query
 *
 * \version	0.1
 * \author	Mohamad H. ARAB
 * \date 	17, Feb 2009
 *
 * Change log:
 *
 */

namespace SQLMonster;

//! Report decorator class
/*! this decorator class adds new table to query of report and then print them out 
 *
 * \sa class Report
 */
class TableDecorator extends ReportDecorator {
	//! constructor
	/*! constructor for deoctator class, a valid Report object must be passed to it.
	 * \param Report $report
	 * \param string $table table that must be added to current query
	 * \param string/array $using field in new table or two fields in new table and old table in 
	 * 					   query that must be used to connect current table to query.
	 * \param array fields that must be added to query. default is all fields. 
	 * \param string join defines type of join must be LEFT, RIGHT or INNER
	 */
	public function __construct (&$report, $table, $using, $fields = "*", $join = "LEFT") {
		parent::__construct ($report);

		$this->_join 	= $join;
		$this->_table 	= $table;
		$this->_using 	= $using;
		$this->_fields 	= $fields;
	}

	//! apply function.
	/*! the patching happens here ...
	 */
	protected function apply ($query) {
		// we must add this part to query but carefuly!


		// first remove any wihte space and/or quotes...
		$table = preg_replace ('/[\W\.\'\"\n`;]/', '', $this->_table);

		// now create join rule ...
		$using = preg_replace ('/[\.\W\'\";]/', '', is_array ($this->_using) ? $this->_using [0]: $this->_using);
		
		if (is_string ($this->_using)) {
			// here we have a single id so, we use using.
			$join = "USING ('" . $using . "')";
		} else if (is_array ($this->_using)) {
			// here we have array so we use table direct connection
			$join = "ON $table.{$using} = " . preg_replace ('/\W\'\"`;/', '', $this->_using [1]);
		}

		// now we try to findout which fields required to extract
		$fields = array();
		if (is_array ($this->_fields)) {
			// we have an array of fields so we join the, to gether using comma
			foreach ($this->_fields as $key=>$field) {
				if (!empty ($fields)) $fields .= ', ';

				// $temp = "$table." . preg_replace ('/\W\.\'\" `;/', '', $field);
				$field = preg_replace ('/\W\.\'\" `;/', '', $field);
				
				// here we check if there is any function in selected field 
				// we just attach table inside function prantesis ...
				if (preg_match ('/^(\w+)\((\w+[,\d\w]*)\)$/', $field, $matches)) {
					$field = "$matches[1]($table.$matches[2])";
				} else {
					$field = "$table.$field";
				}
				if (is_string ($key)) {
					$field .= " AS " . preg_replace ('/\W\.\'\"`;/', '', $key);
				}

				$fields[] = $field;
			}
		} else {
			$fields[] = $table . '.' . preg_replace ('/\W\.\'\"`;/', '', $this->_fields);
		}


		if (isset ($table) && isset ($using)) {
			// break apart query!
			preg_match (self::QueryRegex, $query, $matches);

			// here we check to see if table is already joined to query we just omit it.
			if (stristr ($matches [4], $table)) {
				// we have the table here so we omit it!
				unset ($table);
			}

			// check to see if we have field here .
			for ($index = 0; $index < count ($fields); ++$index) {
				if (stristr ($matches [2], $fields[$index])) {
					$fields[$index] = '';
				}
			}


			// remove duplicates
			array_unique ($fields);
			// remove empty fields...
			array_filter ($fields, 'not_empty');

			// join fields to gether ...
			$fields = implode (', ', $fields);

			// rejoin qurey parts.
			$query = "SELECT $matches[2]" . (empty($fields) ? " " : ", $fields ") . 
				"FROM $matches[4]" . (isset($table) ? " $this->_join JOIN $table $join " : " ");

			for ($index = 5; $index < count ($matches); $index+=2) 
					 $query .= $matches[$index];
			// $query = preg_replace (self::QueryRegex, 
			//	"\\1, $fields \\3 {$this->_join} JOIN $table $join \\5\\7\\9\\11", $query);
		}

		return $query;
	}

	protected $_table;
	protected $_join;
	protected $_using;
	protected $_fields;
}
?>
