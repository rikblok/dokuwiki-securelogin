<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');
require_once(DOKU_INC.'inc/form.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_securelogin extends DokuWiki_Admin_Plugin {
	var $slhlp = null;
	
	function admin_plugin_securelogin () {
		$this->slhlp =& plugin_load('helper', 'securelogin');
		if(!$this->slhlp) msg('Loading the securelogin helper failed. Make sure that the securelogin plugin is installed.', -1);
	}
	
	/**
	 * return some info
	 */
	function getInfo(){
		return array(
        'author' => 'Mikhail I. Izmestev',
        'email'  => 'izmmishao5@gmail.com',
        'date'   => '2009-04-03',
        'name'   => 'securelogin dokuwiki plugin',
        'desc'   => 'Security login via http',
        'url'    => '',
		);
	}
	
	/**
	 * return sort order for position in admin menu
	 */
	function getMenuSort() {
		return 999;
	}

	/**
	 * handle user request
	 */
	function handle() {
		if(!$this->slhlp->canWork())
			msg("You need openssl php module for this plugin work!", -1);
		elseif(!$this->slhlp->workCorrect())
			msg("Your version of dokuwiki not generate AUTH_LOGIN_CHECK event, plugin not work!");
			
		$fn = $_REQUEST['fn'];
		
		if (is_array($fn)) {
			$cmd = key($fn);
			$param = $fn[$cmd];
		} else {
			$cmd = $fn;
			$param = null;
		}
		
		switch($cmd) {
			case "newkey":	$this->slhlp->generateKey($param); break;
			case "public":	$this->slhlp->savePublicInfo($param); break;
			case "test":	msg($this->slhlp->decrypt($param['message'])); break;
		}
	}
	
	/**
	 * output appropriate html
	 */
	function html() {
		if(!$this->slhlp->canWork()) {
			print $this->locale_xhtml('needopenssl');
			return;
		}
		elseif(!$this->slhlp->workCorrect())
			print $this->locale_xhtml('needpatch');
		$this->_html_generateKey();
		
		if(!$this->slhlp->haveKey()) {
			return;
		}
		$this->_html_test();
	}
	
	function _html_generateKey() {
		global $ID;
		$form = new Doku_Form('generate__key');
		$form->startFieldset($this->getLang('generate_key'));
		$form->addHidden('id', $ID);
		$form->addHidden('do', 'admin');
		$form->addHidden('page', 'securelogin');
		$form->addElement(form_makeMenuField('fn[newkey]', $this->slhlp->getKeyLengths(), '', $this->getLang('key_length'), 'key__length', 'block', array('class' => 'edit')));
		$form->addElement(form_makeButton('submit', '', $this->getLang('generate')));
		$form->endFieldset();
		html_form('generate', $form);
	}
	
	function _html_test() {
		global $ID;

		ptln('<script language="JavaScript" type="text/javascript" src="lib/plugins/securelogin/jsbn.js"></script>
		   <script language="JavaScript" type="text/javascript" src="lib/plugins/securelogin/prng4.js"></script>
		   <script language="JavaScript" type="text/javascript" src="lib/plugins/securelogin/rng.js"></script>
		   <script language="JavaScript" type="text/javascript" src="lib/plugins/securelogin/rsa.js"></script>
		   <script language="JavaScript" type="text/javascript" src="lib/plugins/securelogin/base64.js"></script>
		   <script>
		   function encrypt(text) {
				  var rsa = new RSAKey();
				  rsa.setPublic("'.$this->slhlp->getModulus().'", "'.$this->slhlp->getExponent().'");
				  var res = rsa.encrypt(text);
				  if(res) {
				    return hex2b64(res);
				  }
				}
			</script>');
		
		$form = new Doku_Form('test__publicKey');
		$form->startFieldset($this->getLang('test_key'));
		$form->addHidden('id', $ID);
		$form->addHidden('do', 'admin');
		$form->addHidden('page', 'securelogin');
		$form->addElement(form_makeTextField('fn[test][message]', 'test message', $this->getLang('test_message'), 'test__message', 'block'));
		$form->addElement(form_makeButton('submit', '', $this->getLang('test'), array('onClick' => "var el=document.getElementById(\"test__message\"); el.value = encrypt(el.value); return true;")));
		$form->endFieldset();
		html_form('test__publicKey', $form);	
	}
}
?>