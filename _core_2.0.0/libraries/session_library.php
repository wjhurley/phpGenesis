<?php if(!defined("CORE_FOLDER")) die("Hacking attempt logged from IP " . $_SERVER['REMOTE_ADDR']);
//	phpGenesis
//	Copyright (c) 2009. All Rights Reserved. This is commercial software.
//	No part of it may be distributed, sold, or otherwise used without the 
//  express written consent of the copyright owners.
//
//  Copyright Owners:
//		Jamon Holmgren - jamon@jamonholmgren.com
//		Tim Santeford - tsantef@gmail.com

// session_library last edited 12/11/2009 by Jamon Holmgren
// TO-DO
//	add function_exists()

/*
    Reasons Why the session libray may not work: 
		The BASE_URL must be set to an actual URL prefix and not left blank. BASE_URL cannot end in a slash.
*/


	function session_ini() {
		if(settings('session', 'name') === NULL) die("Session can't start. The config doesn't contain session settings.");
		// Establish Secure Session Settings
		ini_set('session.gc_maxlifetime', settings('session', 'timeout'));
		if (setting_isset('session', 'save_path')) { 
			// Make sure session path exists
			if (!is_dir(settings('session', 'save_path'))) { mkdir(settings('session', 'save_path'), 0777); }
			ini_set('session.save_path', settings('session', 'save_path')); 
		}
		ini_set('session.use_cookies', 1);
		ini_set('session.use_only_cookies', 1); // Dissallow session ids in the POST or GET
		ini_set('session.cookie_httponly', 1); 
		$uri = (isset($_SERVER['SCRIPT_URI'])) ? $_SERVER['SCRIPT_URI'] : '';
		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (strpos($uri, 'https://') !== false)) {
			ini_set('session.cookie_secure', 1);	$secure_cookie = true;
		}
		session_set_cookie_params(time() +settings('session', 'timeout'), settings('cookie', 'path'), settings('cookie', 'domain'));
		session_name(settings('session', 'name'));
		
		// Start Session
		session_safe_start();
		
		//mnsetcookie(session_name(), session_id(), time() + settings('session', 'timeout'), settings('cookie', 'path'), settings('cookie', 'domain'), t, true);	
		
		/** Session Security **/
		// The session was not generated by this application
		if (!session_isset('SESSION_KEY') || session('SESSION_KEY') != session_key()) { 
			session_regenerate();
		}	
		
		// Check for session timeout
		if (time() - session('SESSION_LAST_ACCESS') > settings('session', 'timeout')) { 
			session_regenerate();
		} 		
		
		// Referer was not from the site
		if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], BASE_URL) !== 0) { 
			session_regenerate();
		}		
			
		session('SESSION_LAST_ACCESS', time());
	
	} session_ini();
	
	function session_safe_start() {
		$ok = @session_start();
		if(!$ok){
			session_regenerate_id(true); // replace the Session ID
			session_start(); // restart the session (since previous start failed)
		}
	}
	
	function session_regenerate($keep_values = false) { 
		if($keep_values === false) {
			session_delete();
			session_name(settings('session', 'name'));
			session_safe_start();
		}		
		session_regenerate_id(true);
		session('SESSION_KEY', session_key());
		session('SESSION_LAST_ACCESS', time()); 
	}	
	
	function session_key() {
		return md5(APP_ID . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
	}
	
	function session_end() {
		setcookie(session_name(), '', time() - 3600, settings('cookie', 'path'), settings('cookie', 'domain'));	
		session_delete();
	}
	
	function session_delete() {
		$_SESSION = array(); 
		session_destroy();
	}
	
	function session($setting, $value = NULL) {
		if($value === NULL) {
			if(session_isset($setting)) return $_SESSION[$setting];                          
		} else {
			$_SESSION[$setting] = $value;
		}
		return NULL; // not set
	}
	
	function session_isset($setting) {
		if(isset($_SESSION[$setting])) return true;
		return false;
	} // end global_isset
	
	function unset_session($setting) {
		unset($_SESSION[$setting]);
	}
	
	/*
	function session_cleanup() {
		session_write_close();
	}
	
	register_hook("before_shutdown", "session_cleanup");
	*/
?>