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
		$this->slhlp =& plugin_load('helper', $this->getPluginName());
		if(!$this->slhlp) msg('Loading the '.$this->getPluginName().' helper failed. Make sure that the '.$this->getPluginName().' plugin is installed.', -1);
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
        'desc'   => 'Secure login via http',
        'url'    => '',
		);
	}
	
	/**
	 * return sort order for position in admin menu
	 */
	function getMenuSort() {
		return 999;
	}

	function getMenuText($lang) {
		switch($lang) {
			case 'ru': return "Настройки безопасного входа";
			default: return "Secure login configuration";
		}
	}
	
	/**
	 * handle user request
	 */
	function handle() {
		if(!$this->slhlp->canWork())
			msg("You need openssl php module for this plugin work!", -1);
		elseif($this->slhlp->haveKey() && !$this->slhlp->workCorrect())
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
		elseif($this->slhlp->haveKey() && !$this->slhlp->workCorrect())
			print $this->locale_xhtml('needpatch');
		ptln('<div id="secure__login">');
		$this->_html_generateKey();
		
		if(!$this->slhlp->haveKey()) return;
		
		$this->_html_test();

		print $this->render("===== ".$this->getLang('public_key')." ===== \n".
				"<code>\n".
				$this->slhlp->getPublicKey().
				"</code>",
				$format='xhtml');
	}
	
	function _html_generateKey() {
		global $ID;
		$form = new Doku_Form('generate__key', wl($ID)."?do=admin&page=".$this->getPluginName());
		$form->startFieldset($this->getLang('generate_key'));
		$form->addElement(form_makeMenuField('fn[newkey]', $this->slhlp->getKeyLengths(), $this->slhlp->getKeyLength(), $this->getLang('key_length'), 'key__length', 'block', array('class' => 'edit')));
		$form->addElement(form_makeButton('submit', '', $this->getLang('generate')));
		$form->endFieldset();
		ptln('<div class="half">');
		html_form('generate', $form);
		ptln('</div>');
	}
	
	function _html_test() {
		global $ID;
		$form = new Doku_Form('test__publicKey', wl($ID)."?do=admin&page=".$this->getPluginName());
		$form->startFieldset($this->getLang('test_key'));
		$form->addElement(form_makeTextField('fn[test][message]', 'test message', $this->getLang('test_message'), 'test__message', 'block'));
		$form->addElement(form_makeButton('submit', '', $this->getLang('test')));
		$form->endFieldset();
		html_form('test__publicKey', $form);
	}
}
?>