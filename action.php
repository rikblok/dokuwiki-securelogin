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
	 'date'   => '2009-03-26',
	 'name'   => 'securelogin events handler',
	 'desc'   => '',
	 'url'    => '',
		);
	}
	
	/**
	 * Register its handlers with the DokuWiki's event controller
	 */
	function register(&$controller) {
		$controller->register_hook('HTML_LOGINFORM_OUTPUT', 'BEFORE',  $this, '_login_form');
		$controller->register_hook('HTML_UPDATEPROFILEFORM_OUTPUT', 'BEFORE',  $this, '_profile_update_form');
		$controller->register_hook('AUTH_LOGIN_CHECK', 'BEFORE',  $this, '_auth');
	}

	function _auth(&$event, $param) {
		$this->_decrypt();
		if($_REQUEST['do'] == "login") {
			auth_login($_REQUEST['u'], $_REQUEST['p'], $_REQUEST['r'], $_REQUEST['http_credentials']);
			$event->preventDefault();
		}
	}

	function _decrypt() {
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
		return;
	}
	
	function _profile_update_form(&$event, $param) {
		$event->data->addHidden('securelogin', 'test');
		$submit = $event->data->findElementByType('button');
		if($submit) {
			ptln($this->slhlp->encrypt_script());
			ptln('
<script>
	function secure_login() {
		var form = document.getElementById("dw__register");
		if(!form.use_securelogin.checked) return true;
		var newpass = form.newpass;
		var passchk = form.passchk;
		var oldpass = form.oldpass;
		var sectok = form.sectok;
		
		form.securelogin.value = encrypt("newpass:"+newpass.value+";passchk:"+passchk.value+";oldpass:"+oldpass.value+"@"+sectok.value);
		oldpass.value = "******";
		newpass.value = "******";
		passchk.value = "******";
		return true;
	}
</script>
			');
			$event->data->replaceElement($submit, form_makeButton('submit', '', $lang['btn_save'], array('onClick' => 'return secure_login();')));
			$event->data->insertElement($submit, form_makeCheckboxField('use_securelogin', 'checked', $this->getLang('use_securelogin'), '', 'simple', array('checked' => 'checked')));
		}
		return;
	}
	
	function _login_form(&$event, $param) {
		global $lang;
		/*
		 * add hidden field to store encrypted data
		 */
		$event->data->addHidden('securelogin', 'test');
		$submit = $event->data->findElementByType('button');
		if($submit) {
/*
 * this is a hack, i don't know how to place here script 
 */
			ptln($this->slhlp->encrypt_script());
			ptln('
<script>
	function secure_login() {
		var form = document.getElementById("dw__login");
		if(!form.use_securelogin.checked) return true;
		var user = form.u;
		var pass = form.p;
		var sectok = form.sectok;
		
		form.securelogin.value = encrypt("p:"+pass.value+"@"+sectok.value);
		pass.value = "******";
		return true;
	}
</script>
			');
			
			/*
			 * replace login button on new button associated with onClick event
			 * add checkbox make possible select security login function
			 */
			$event->data->replaceElement($submit, form_makeButton('submit', '', $lang['btn_login'], array('onClick' => 'return secure_login();')));
			$event->data->insertElement($submit, form_makeCheckboxField('use_securelogin', 'checked', $this->getLang('use_securelogin'), '', 'simple', array('checked' => 'checked')));
		}
	}

}
?>