<?php
/*
 *	File:	class.database.php
 *	Desc:	Provides Database class for accessing database
 			backends in more elegant way, using PDO
	
 *	Version: 0.2
 *	Author:	 Mohamad H. ARAB
 *	Date:	 04 Mar 2008 
 *
 *	Change log:
 *		12, MAR 2008:
 *			+ configuration moved to Config sigleton class
 *		28 Sep, 2009:
 *			+ persistent features added as default selection for database constructor		
 *		20 Dec 2010:
 *			+ Fetch class support added.
 *		22 jan, 2011:
 *			+ Name space definitions added.
 */

# SQLMonster namespace 
namespace SQLMonster;

use \PDO as PDO;
use \Config as Config;

const FetchClass 			= PDO::FETCH_CLASS;
const FetchPropsLate		= PDO::FETCH_PROPS_LATE;
const FetchObject			= PDO::FETCH_OBJ;
const FetchAssoc			= PDO::FETCH_ASSOC;
const FetchBoth				= PDO::FETCH_BOTH;

const IN_PARAM				= 0;
const OUT_PARAM 			= 1;

const TransactionBegin		= 1;
const TransactionEnd		= 2;
const TransactionRollback	= 4;

class Database {
	public function __construct ($params = array ()) {
		$this->_dbh = null;

		try {
			$config = Config::instance ();

			if (array_key_exists ('presistent', $params)) {
				$persistent = $params['presistent'];
			} else {
				$persistent = false;
			}

			$this->_dbh = new PDO(
				$config->connection_string, 
				$config->database_username, 
				$config->database_password, 
				array(
					PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8, collation_connection=utf8_persian_ci;",
					PDO::ATTR_PERSISTENT => $persistent
				)
			);

			foreach ($params as $param => $value) {
				if (in_array ($param, $this->_params)) {
					$this->$param = $value;
				}
			}

			trigger_error ("Database::__construct: Connection established.", E_USER_NOTICE);
		}
		catch (PDOException $e) {
			if ($persistent) {
				if ($this->_dbh)
					$this->_dbh->rollBack();
			}
			echo "Error while connecting database, ({$e->getCode()}): {$e->getMessage()}";
		}
	}

	public function __destruct () {
		if (isset($this->_stmt)) {
			$this->_stmt->closeCursor();
			unset ($this->_stmt);
		} 

		if (isset ($this->_dbh)) {
			$this->_dbh = null;
		}

		trigger_error ("Database::__destruct: Destructed", E_USER_NOTICE);
	}

	public function SetQuery ($query) {
		$this->_flush ();

		$this->_query = $query;
		// print $query;
		trigger_error ("Database::SetQuery: Query ($query) has been added.", E_USER_NOTICE);
	}

	public function SetParam ($name, $value, $type = PDO::PARAM_STR) {
		if (!isset ($this->_params) )
			$this->_params = array();

		$this->_params[$name] = array (self::IN_PARAM, $value, $type);
		trigger_error ("Database::SetParam: Parameter $name ($value, $type) has been added", E_USER_NOTICE);
	}

	public function SetOutParam ($name, &$value, $type = PDO::PARAM_STR, $len = 64) {
		if (!isset ($this->_params) )
			$this->_params = array();

		$this->_params[$name] = array (self::OUT_PARAM, &$value, $type, $len);
		trigger_error ("Database::SetOutParam: Parameter $name ($value, $type, $len) has been added", E_USER_NOTICE);
	}

	public function Execute () {
		$sth = $this->_prepare ();
		$this->_bind($sth);

		$this->_execute($sth);
		$this->_closeCursor ($sth);

		# return $this->_stmt->rowCount();
		return $this->_dbh->lastInsertId ();
	}

	public function Read () {
		$sth = $this->_prepare ();
		$this->_bind($sth);

		# print $this->_query . "<br />";
		$this->_execute($sth);

		$result_set = new ResultSet ($sth);

		return $result_set;
	}

	public function Next ($type = PDO::FetchBoth, $class = null) {
		if (!isset ($this->_stmt)) {
			throw new Exception ("Query is not prepared yet!");
		}

		if ($type & FetchClass) {
			$this->_stmt->setFetchMode ($type, $class);
			return $this->_stmt->fetch ();
		} else
			return $this->_stmt->fetch ();
	}

	public function ReadAll ($type = FetchBoth, $class = null) {
		$this->Read();
		if ($type & FetchClass)  {
			$this->_stmt->setFetchMode ($type, $class);
			$result =  $this->_stmt->fetchAll();
		}
		else 
			$result =  $this->_stmt->fetchAll($type);


		$this->_closeCursor ();
		return $result;
	}

	public function ReadOneRow ($type = FetchAssoc, $class = null) {
		$this->Read();

		if ($type & FetchClass)
			$row = $this->Next($type, $class);
		else
			$row = $this->Next($type);

		$this->_closeCursor();

		return $row;
	}

	public function Transaction ($job) {
		if ($job == TransactionBegin) {
 			$this->_dbh->setAttribute (PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
			$this->_dbh->beginTransaction ();
			trigger_error ("Database::Trasaction: Transaction started", E_USER_NOTICE);
		} else if ($job == TransactionEnd) {
			$this->_dbh->commit ();
			trigger_error ("Database::Trasaction: Transaction commited", E_USER_NOTICE);
		} else if ($job == TransactionRollback) {
			$this->_dbh->rollBack ();
			trigger_error ("Database::Trasaction: Transaction rolled back", E_USER_NOTICE);
		}
	}

	private function _closeCursor () {
		if (!isset ($this->_stmt)) {
			throw new Exception ("Query is not prepared yet!");
		}

		$this->_stmt->closeCursor();
	}

	private function _prepare () {
		if (!isset ($this->_query) )
			throw new Exception ("Query not specified!");

		if (!$statement = $this->_dbh->prepare ($this->_query)) {
			throw new Exception ("Could not prepare SQL query ($query)");
		}

		if (isset ($this->type)) {
			if ($this->type & FetchClass and isset ($this->class))
				$statement->setFetchMode ($this->type, $this->class);
			else 
				$statement->setFetchMode ($this->type);
		} 

		$this->_stmt = $statement;

		trigger_error ("Database::_prepare: query prepared for execution.", E_USER_NOTICE);
		return $statement;
	}

	private function _bind($sth) {
		if (!isset ($sth) ) {
			throw new Exception ("Query is not prepared yet!");
		}

		if (isset ($this->_params)) {
			foreach ($this->_params as $name => $param) {

				if ($param[0] == IN_PARAM) {
					$sth->bindParam ($name, $param[1], $param[2]);
				} else {
					$var =& $this->_params[$name][1];
					$sth->bindParam ($name, $var, $param[2], $param[3]);
				}
			}

			trigger_error ("Database::_bind: Parameters has been bind to query (". count ($this->_params) . ")", E_USER_NOTICE);
		}
	}

	private function _execute ($sth) {
		if (!isset ($sth) )
			throw new \Exception ("Query is not prepared yet!");

		if (!$sth->execute()) {
			$info = $sth->errorInfo();
			throw new \Exception ("Executing SQL statement failed (" . $sth->errorCode() . "): " . @$info[2]);
		}

		trigger_error ("Database::_execute: Query has been executed.", E_USER_NOTICE);
	}

	private function _flush () {
		// flush all parameters
		$this->_params = array ();
	}

	private $_params = array (
		'class',
		'type',
		'presistent'
	);
}
?>
