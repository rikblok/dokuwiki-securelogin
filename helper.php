<?php
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_INC.'inc/infoutils.php');


/**
 * This is the base class for all syntax classes, providing some general stuff
 */
class helper_plugin_securelogin extends DokuWiki_Plugin {
	var $_keyFile;
	var $_keyIFile;
	var $_key = null;
	var $_keyInfo = null;
	var $_workCorrect = false;
	var $_canWork = false;
	
	/**
	 * constructor
	 */
	function helper_plugin_securelogin(){
		global $conf;

		$this->_keyIFile = $conf['cachedir'].'/securelogin.ini';
		$this->_keyFile = $conf['cachedir'].'/securelogin.key';

		if( true 
			&& function_exists("openssl_pkey_export_to_file")
			&& function_exists("openssl_pkey_get_details")
			&& function_exists("openssl_pkey_get_private")
			&& function_exists("openssl_pkey_new")
			&& function_exists("openssl_private_decrypt")
			)
			$this->_canWork = true;
	}
	
	function canWork() {
		return $this->_canWork;
	}

	/**
	 * return some info
	 */
	function getInfo(){
		return array(
            'author' => 'Mikhail I. Izmestev',
            'email'  => 'izmmishao5@gmail.com',
            'date'   => '2009-04-03',
            'name'   => 'securelogin helper',
            'desc'   => '',
            'url'    => '',
		);
	}
	
	function haveKey($onlyPublic = false) {
		if($onlyPublic) {
			if($this->_keyInfo)	return true;

			if(file_exists($this->_keyIFile)) {
				$this->_keyInfo = parse_ini_file($this->_keyIFile);
				return true;
			}
		}
		
		if(!$this->_key && file_exists($this->_keyFile)) {
			$this->_key = openssl_pkey_get_private(file_get_contents($this->_keyFile));
			if($this->_key) {
				if(file_exists($this->_keyIFile))
					$this->_keyInfo = parse_ini_file($this->_keyIFile);
				else
					$this->savePublicInfo($this->getPublicKeyInfo($this->getPublicKey()));
			}
		}
		return null != $this->_key;
	}
	
	function getKeyLengths() {
		return array('512' => '512', '1024' => '1024', '2048' => '2048');
	}
	
	function generateKey($length) {
		if(!array_key_exists($length, $this->getKeyLengths())) {
			msg("Error key length $length not supported", -1);
			return;
		}
//		this not work :(
//		$newkey = @openssl_pkey_new(array('private_key_bits' => $length));
//
		$newkey = @openssl_pkey_new();
		if(!$newkey) {
			msg('Error generating new key', -1);
			return; 
		}
		if(!openssl_pkey_export_to_file($newkey, $this->_keyFile))
			msg('Error export new key', -1);
		else {
			$this->_key = openssl_pkey_get_private(file_get_contents($this->_keyFile));
			$this->savePublicInfo($this->getPublicKeyInfo($this->getPublicKey()));
		}
	}
	
	function getPublicKey() {
		if(!$this->haveKey()) {
			return null;
		}
		
		$key = openssl_pkey_get_details($this->_key);
		return $key['key'];
	}
	
	function getModulus() {
		return ($this->haveKey(true))?$this->_keyInfo['modulus']:null;
	}
	
	function getExponent() {
		return ($this->haveKey(true))?$this->_keyInfo['exponent']:null;
	}
	
	function savePublicInfo($info) {
		$fpinfo = fopen($this->_keyIFile, "w");
		foreach($info as $key => $val) {
			fprintf($fpinfo, "%s=\"%s\"\n", $key, $val);
		}
		fclose($fpinfo);
		$this->_keyInfo = parse_ini_file($this->_keyIFile);
	}
	
	function decrypt($text) {
		if($this->haveKey())
			openssl_private_decrypt(base64_decode($text), $decoded, $this->_key);
		return $decoded;
	}
	
	function my_unpack($format, &$bin, $length) {
		$res = unpack($format, $bin);
		$bin = substr($bin, $length);
		return $res;
	}
	
	function readBER(&$bin) {
		if(!strlen($bin)) return FALSE;
		
		
		$data = $this->my_unpack("C1type/c1length", $bin, 2);
		
		if($data[length] < 0) {
			$count = $data[length] & 0x7F;
			$data[length] = 0;
			while($count) {
				 $data[length] <<= 8;
				 $tmp = $this->my_unpack("C1length", $bin, 1);
				 $data[length] += $tmp[length];
				 $count--;
			}
		}
		
		switch($data[type]) {
			case 0x30:	
				$data[value] = array();
				do {
					$tmp = $this->readBER($bin);
					if($tmp)
						$data[value][] = $tmp; 
				} while($tmp);
				break;
			case 0x03:
				$tmp = $this->my_unpack("C1", $bin, 1);
				$data[value] = $this->readBER($bin);
				break;
			default: 
				$count = $data[length];
				while($count) {
					$tmp = $this->my_unpack("C1data", $bin, 1);
					$data[value] .= sprintf("%02X", $tmp[data]);
					$count--;
				}
		}
		
		return $data;
	}
	
	function getPublicKeyInfo($pubkey) {
		$pubkey = split("(-\n|\n-)", $pubkey);
		$binary = base64_decode($pubkey[1]);
		
		$data = $this->readBER($binary);
		
		$pubkeyinfo = array(
			"modulus" => $data[value][0][value][2][value][value][0][value],
			"exponent" => $data[value][0][value][2][value][value][1][value]);
		
		return $pubkeyinfo;	
	}
	
	function workCorrect($yes = false) {
		if($yes)
			$this->_workCorrect = true;
		return $this->_workCorrect;
	}
}
?>