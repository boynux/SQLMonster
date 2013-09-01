<?php
require_once __DIR__ . "/../config.class.php";

// because we are using config.ini in examples path we need to tel Config class
// how to find correct config.ini file. If you put secrets/config.ini in 
// initialize path you can skip this.
Config::instance (__DIR__);

require_once __DIR__ . "/../initialize.php";

require_once 'test.class.php';

$test = new Test;
$test->name = 'test 1';

$test->save ();

$test2 = Test::instance()->filter ( array ('name__like' => 'test 1'));
echo "Record for test is : " . $test2[0] . "\n";

$test2->delete ();
?>
