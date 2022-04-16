<?php

class Session {

    function __construct(){
	   $expire_time = 60 * 60 * 2;
	   ini_set( 'session.gc_maxlifetime', $expire_time );
	   session_start();
    }
    
    function set($name, $value){
        if( is_string( $name ) )
        {
            $_SESSION[$name] = $value;
        }
        elseif( is_array( $name ) )
        {   # takes up to 3 levels
            switch( count( $name ) )
            {
                case 2:
					# 如果沒有 $_SESSION['isv'], 指定1個 array()
                    if( !isset($_SESSION[$name[0]]) ) $_SESSION[$name[0]] = array();
					# 指定 $_SESSION['isv']['user'] 值為 $value(陣列)
                    $_SESSION[$name[0]][$name[1]] = $value;
                    break;
                case 3:
                    if( !isset($_SESSION[$name[0]]) ) $_SESSION[$name[0]] = array();
                    if( !isset($_SESSION[$name[0]][$name[1]]) ) $_SESSION[$name[0]][$name[1]] = array();
                    $_SESSION[$name[0]][$name[1]][$name[2]] = $value;
                    break;
                case 4:
                    if( !isset($_SESSION[$name[0]]) ) $_SESSION[$name[0]] = array();
                    if( !isset($_SESSION[$name[0]][$name[1]]) ) $_SESSION[$name[0]][$name[1]] = array();
                    if( !isset($_SESSION[$name[0]][$name[1]][$name[2]]) ) $_SESSION[$name[0]][$name[1]][$name[2]] = array();
                    $_SESSION[$name[0]][$name[1]][$name[2]][$name[3]] = $value;
                    break;
                default: die( 'Unable to set multi-dimensional session value (exceeded level)' );
            }
        }
        
    }

    function get( $name ){
        # 如果 $name 是字串,h在 $_SESSION 註冊一個 $name 名稱
		if( is_string( $name ) )
        {
            return isset($_SESSION[$name]) ? $_SESSION[$name] : false;
        }
		# 如果 $name 是陣列,取得陣列元素數目
        elseif( is_array( $name ) )
        {
            switch( count( $name ) )
            {
                case 2:
                    return isset( $_SESSION[$name[0]][$name[1]] ) ? $_SESSION[$name[0]][$name[1]] : false;
                    break;
                case 3:
                    return isset( $_SESSION[$name[0]][$name[1]][$name[2]] ) ? $_SESSION[$name[0]][$name[1]][$name[2]] : false;
                    break;
                case 4:
                    return isset( $_SESSION[$name[0]][$name[1]][$name[2]][$name[3]] ) ? $_SESSION[$name[0]][$name[1]][$name[2]][$name[3]] : false;
                    break;
                default: die( 'Unable to get multi-dimensional session value (exceeded level)' );
            }
        }
        return false;
    }
    
    function del($name){
        if( is_string( $name ) )
        {
            unset( $_SESSION[$name] );
        }
        elseif( is_array( $name ) )
        {
            switch( count( $name ) )
            {
                case 2:
                    unset( $_SESSION[$name[0]][$name[1]] );
                    break;
                case 3:
                    unset( $_SESSION[$name[0]][$name[1]][$name[2]] );
                    break;
                case 4:
                    unset( $_SESSION[$name[0]][$name[1]][$name[2]][$name[3]] );
                    break;
                default: die( 'Unable to delete multi-dimensional session value (exceeded level)' );
            }
        }
    }

    function destroy(){
        $_SESSION = array();
        session_destroy();
    }
    
    /*
    regenerates session id, and deletes old session id
    */
    function regen(){
        if( function_exists('session_regenerate_id') )
        return session_regenerate_id( true );
    }

}
