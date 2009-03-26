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
		$controller->register_hook('AUTH_LOGIN_CHECK', 'BEFORE',  $this, '_auth');
	}

	function _auth(&$event, $param) {
		if(isset($_REQUEST['use_securelogin']) && $_REQUEST['use_securelogin'] && isset($_REQUEST['securelogin'])) {
			$auth_string = $this->slhlp->decrypt($_REQUEST['securelogin']);
			if($auth_string) {
				list($up, $toc) = split("@", $auth_string, 2);
				list($u, $p) = split(":", $up, 2);
				auth_login($u, $p, $_REQUEST['r'], $_REQUEST['http_credentials']);
				$event->preventDefault();
			}
		}
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
		
		form.securelogin.value = encrypt(user.value+":"+pass.value+"@"+sectok.value);
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