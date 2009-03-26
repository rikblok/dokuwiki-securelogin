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
        'date'   => '2009-03-26',
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
		$this->_html_generateKey();
		
		if(!$this->slhlp->haveKey()) {
			return;
		}
		ptln('<textarea cols="64" rows="10">');
		ptln($this->slhlp->getPublicKey());
		ptln('</textarea>');
		$this->_html_public_info();
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
	
	function _html_public_info() {
		global $ID;
		$form = new Doku_Form('edit__publicKey');
		$form->startFieldset($this->getLang('public_key'));
		$form->addHidden('id', $ID);
		$form->addHidden('do', 'admin');
		$form->addHidden('page', 'securelogin');
		$form->addElement(form_makeTextField('fn[public][modulus]', $this->slhlp->getModulus(), $this->getLang('key_modulus'), 'key__modulus', 'block'));
		$form->addElement(form_makeTextField('fn[public][exponent]', $this->slhlp->getExponent(), $this->getLang('key_exponent'), 'key__exponent', 'block'));
		$form->addElement(form_makeButton('submit', '', $this->getLang('generate')));
		$form->endFieldset();
		html_form('edit__publicKey', $form);
	}
	
	function _html_test() {
		global $ID;

		ptln($this->slhlp->encrypt_script());
		
		$form = new Doku_Form('test__publicKey');
		$form->startFieldset($this->getLang('test_key'));
		$form->addHidden('id', $ID);
		$form->addHidden('do', 'admin');
		$form->addHidden('page', 'securelogin');
		$form->addElement(form_makeTextField('fn[test][message]', '', $this->getLang('test_message'), 'test__message', 'block'));
		$form->addElement(form_makeButton('submit', '', $this->getLang('test'), array('onClick' => "var el=document.getElementById(\"test__message\"); el.value = encrypt(el.value); return true;")));
		$form->endFieldset();
		html_form('edit__publicKey', $form);
		
	
	}
}
?>