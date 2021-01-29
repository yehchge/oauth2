<?php

class session {

	function __construct() {
	}

	public static function init($PHPSESSID='') {
		if ($PHPSESSID) {
			session_id($PHPSESSID);
			session_start();
		} else if (isset($_COOKIE['PHPSESSID']) and $_COOKIE['PHPSESSID'] != NULL) {
            session_id($_COOKIE['PHPSESSID']);
			session_start();
		} else if (isset($_GET['PHPSESSID'])) {
			session_id($_GET['PHPSESSID']);
			session_start();
        } else {
			session_start();
            $set_sid = session_id();
            if(!isset($_COOKIE_LIFETIME)) $_COOKIE_LIFETIME=3600; // 一小時 default: 3600
            setcookie("PHPSESSID", $set_sid, time()+$_COOKIE_LIFETIME,"/","."._SESSION_DOMAIN);
        }
	}

	/**
	* @param $sName 使用session的變數名稱 $sVars 值
	* @desc 設定session變數
	*/
	public static function set($sName, $sVars) {
        if(isset($_SESSION[$sName])) unset($_SESSION[$sName]);
		$_SESSION[$sName] = $sVars;
    }

	public static function get($sName) {
		$sReturnStr = FALSE;
		if(isset($_SESSION[$sName])) $sReturnStr = $_SESSION[$sName];
		return $sReturnStr;
	}

	public static function sess_unset() {
		session_unset();
	}

}
