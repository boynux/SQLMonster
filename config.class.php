<?php
/*
 * file: class.config.php
 * Description: Main initialization for directories and paths
 *
 * Version: 0.1
 * Author:  Mohamad H. ARAB <mamad876@gmail.com>
 * Date:    11. MAR 2008
 */

class Config {
	const config = 'secrets/config.ini'; 

	// This is a singleton class.
	// we use instance method to retrive it's
	// instance instead
	private function __construct () {
		$this->readConfigs ();
	}

	static public function &instance () {
		static $_instance;
		if (!isset ($_instance)) {
			$_instance = new Config();
		}

		return $_instance;
	}

	public function __set ($key, $value) {
		if (isset ($key) && isset ($value) && !empty ($key) && !empty ($value)) {
			if (preg_match ("/::+/", $value)) {
				$value = preg_split ("/::/", $value);
			}

			$this->_config[$key] = $value;
		}
	}

	public function __get ($key) {
		if (isset ($key) && !empty ($key)) {
			if (is_array ($this->_config) && array_key_exists ($key, $this->_config)) {
				return $this->_config [$key];
			} else {
				throw new Exception ("Invalid key ($key) for config provided!");
			}
		}
	}

	private function readConfigs () {
		$fh = fopen (self::config, "r");

		if (!$fh) {
			throw new Exception ("Could not open config file.");
		}

		while (!feof ($fh)) {
			$buffer = fgets ($fh, 4096);
			$this->parseLine ($buffer);
		}

		fclose ($fh);
	}

	private function parseLine ($line) {
		if (preg_match ("/^[#\$\n]/", $line) || empty ($line))
			return;

		trim ($line);
		if (preg_match ("/^(\w*)[ \t]*=[ \t]*(.*)$/", $line, $matches)) {
			$this->$matches[1] = $matches[2];
		} else {
			trigger_error ("Inavlid property syntax. Line ($line) skipped.", E_USER_WARNING);
		}
	}

	private $_config = array();
}
?>
