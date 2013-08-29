<?php
namespace SQLMonster;

use \Exception as Exception;

class ModelNotReady extends Exception {}

class KeyError extends Exception {}
class DoesNotExist extends Exception {}
class MissingInformation extends Exception {}
class ResultIsNotUnique extends Exception {}
class InvalidData extends Exception {}
class DuplicateEntry extends Exception {}
?>
