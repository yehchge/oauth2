<?php

session_start();

class session {

	function __construct() {
	}	

	function set($sName, $sVars) {
		if(isset($_SESSION[$sName])) unset($_SESSION[$sName]);
		$_SESSION[$sName] = $sVars;
   	}
	
	function get($sName) {
		$sReturnStr = FALSE;
		if(isset($_SESSION[$sName])) $sReturnStr = $_SESSION[$sName];
		return $sReturnStr;
	}

}