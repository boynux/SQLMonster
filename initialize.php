<?php
/**
 * Initialization file for SQLMonster
 */

error_reporting (E_ALL & ~E_USER_NOTICE & ~E_USER_WARNING & ~E_NOTICE);
ini_set ("display_errors", true);

// find main path of the program
$main_path = realpath(__DIR__);

// switch to real main path ...
$current_path = getcwd ();
chdir ($main_path);

require_once "config.class.php";

$config = Config::instance ();

$config->root_path = $main_path .'/';
$config->libs_dir  = '/';

spl_autoload_register ('autoload');

function autoload ($classname) {
	$ns = preg_split ('/\\\/', $classname);

	$config = Config::instance();

	# we got namespace definitions here ...
	if (count ($ns) > 1 && $ns[0] == 'SQLMonster') {
		$path = strtolower ($ns[1]);

		if (file_exists ($config->root_path . $config->libs_dir . "$path.class.php")) {
			require_once $config->root_path . $config->libs_dir . "$path.class.php";
			return true;
		} else {
			return false;
		}
	}
}

chdir ($current_path);
