<?php 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
require_once(DOKU_INC.'inc/form.php');

class action_plugin_securelogin extends DokuWiki_Action_Plugin {
	var $slhlp;

	function action_plugin_securelogin () {
		$this->slhlp =& plugin_load('helper', 'securelogin');
	}
	
	/**
	 * return some info
	 */
	function getInfo(){
		return array(
	 'author' => 'Mikhail I. Izmestev',
	 'email'  => 'izmmishao5@gmail.com',
	 'date'   => '2009-04-03',
	 'name'   => 'securelogin events handler',
	 'desc'   => '',
	 'url'    => '',
		);
	}
	
	/**
	 * Register its handlers with the DokuWiki's event controller
	 */
	function register(&$controller) {
		if(!$this->slhlp || !$this->slhlp->canWork() || !$this->slhlp->haveKey(true)) return;
		$controller->register_hook('HTML_LOGINFORM_OUTPUT', 'BEFORE',  $this, '_login_form');
		$controller->register_hook('HTML_UPDATEPROFILEFORM_OUTPUT', 'BEFORE',  $this, '_profile_update_form');
		$controller->register_hook('AUTH_LOGIN_CHECK', 'BEFORE',  $this, '_auth');
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE',  $this, '_addHeaders');
	}

	function _addHeaders (&$event, $param) {
		global $ACT;
		if(!in_array($ACT, array('login', 'profile'))) return;
		
		$event->data["script"][] = array (
		  "type" => "text/javascript",
		  "src" => "lib/plugins/securelogin/jsbn.js",
		  "_data" => "",
		);

		$event->data["script"][] = array (
		  "type" => "text/javascript",
		  "src" => "lib/plugins/securelogin/prng4.js",
		  "_data" => "",
		);
		
		$event->data["script"][] = array (
		  "type" => "text/javascript",
		  "src" => "lib/plugins/securelogin/rng.js",
		  "_data" => "",
		);
		
		$event->data["script"][] = array (
		  "type" => "text/javascript",
		  "src" => "lib/plugins/securelogin/rsa.js",
		  "_data" => "",
		);
		
		$event->data["script"][] = array (
		  "type" => "text/javascript",
		  "src" => "lib/plugins/securelogin/base64.js",
		  "_data" => "",
		);
		
		$event->data["script"][] = array (
		  "type" => "text/javascript",
		  "src" => "lib/plugins/securelogin/securelogin.js",
		  "_data" => "",
		);
		
		$event->data["script"][] = array (
		  "type" => "text/javascript",
		  "charset" => "utf-8",
		  "_data" => 'function encrypt(text) {
	var rsa = new RSAKey();
	rsa.setPublic("'.$this->slhlp->getModulus().'", "'.$this->slhlp->getExponent().'");
	var res = rsa.encrypt(text);
	if(res) {
		return hex2b64(res);
	}
}
function attachHandlers() {
	var elform = $("'.(($ACT=='login')?'dw__login':'dw__register').'");
	if(elform)
		addEvent(elform, "submit", secure_'.$ACT.');
}
addEvent(window, "load", attachHandlers);',
		);
	}
	
	function _auth(&$event, $param) {
		$this->slhlp->workCorrect(true);
		if(isset($_REQUEST['use_securelogin']) && $_REQUEST['use_securelogin'] && isset($_REQUEST['securelogin'])) {
			list($request,) = split('@', $this->slhlp->decrypt($_REQUEST['securelogin']));
			if($request) {
				foreach(split(";", $request) as $var) {
					list($key, $value) = split(":",$var);
					$_REQUEST[$key] = $value;
					$_POST[$key] = $value;
				}
			}
			unset($_REQUEST['securelogin']);
			unset($_REQUEST['use_securelogin']);
		}
		if($_REQUEST['do'] == "login") {
			auth_login($_REQUEST['u'], $_REQUEST['p'], $_REQUEST['r'], $_REQUEST['http_credentials']);
			$event->preventDefault();
		}
	}
	
	function _profile_update_form(&$event, $param) {
		if(!$this->slhlp->workCorrect()) return;
		global $lang;
		$event->data->addHidden('securelogin', '');
		$submit = $event->data->findElementByType('button');
		if($submit) {
			$event->data->insertElement($submit, form_makeCheckboxField('use_securelogin', 'checked', $this->getLang('use_secureupdate'), '', 'simple', array('checked' => 'checked')));
		}
	}
	
	function _login_form(&$event, $param) {
		if(!$this->slhlp->workCorrect()) return;
		global $lang;
		/*
		 * add hidden field to store encrypted data
		 */
		$event->data->addHidden('securelogin', '');
		$submit = $event->data->findElementByType('button');
		if($submit) {	
			/*
			 * replace login button on new button associated with onClick event
			 * add checkbox make possible select security login function
			 */
			$event->data->insertElement($submit, form_makeCheckboxField('use_securelogin', 'checked', $this->getLang('use_securelogin'), '', 'simple', array('checked' => 'checked')));
		}
	}
}
?>