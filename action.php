<?php 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
require_once(DOKU_INC.'inc/form.php');

class action_plugin_securelogin extends DokuWiki_Action_Plugin {
	var $slhlp;

	function action_plugin_securelogin () {
		$this->slhlp =& plugin_load('helper', $this->getPluginName());
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
		$controller->register_hook('AUTH_LOGIN_CHECK', 'BEFORE',  $this, '_auth');
		$controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE',  $this, '_ajax_handler');
	}
	
	function _auth(&$event, $param) {
		$this->slhlp->workCorrect(true);
		if(!$this->slhlp || !$this->slhlp->canWork() || !$this->slhlp->haveKey(true)) return;
		
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
	
	function _ajax_handler(&$event, $param) {
		if($event->data != 'securelogin_public_key') return;
		if(!$this->slhlp || !$this->slhlp->canWork() || !$this->slhlp->haveKey(true)) return;
		
		header('Content-Type: text/javascript; charset=utf-8');
		print 'function encrypt(text) {
        var rsa = new RSAKey();
        rsa.setPublic("'.$this->slhlp->getModulus().'", "'.$this->slhlp->getExponent().'");
        var res = rsa.encrypt(text);
        if(res) {
                return hex2b64(res);
        }
		}
		var securelogin_login_label = "'.$this->getLang('use_securelogin').'";
		var securelogin_update_label = "'.$this->getLang('use_secureupdate').'";';
		
		$event->preventDefault();
		return;
	}
}
