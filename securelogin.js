function esc( x ) {
	return encodeURIComponent(x);
}

function secure_profile() {
	var form = jQuery("#dw__register")[0];
	if(!form || !form.use_securelogin.checked) return true;
	var newpass = form.newpass;
	var passchk = form.passchk;
	var oldpass = form.oldpass;
	var sectok = form.sectok;
	
	form.securelogin.value = encrypt("newpass="+esc(newpass.value)+"&passchk="+esc(passchk.value)+"&oldpass="+esc(oldpass.value)+";"+sectok.value);
	oldpass.value = "******";
	newpass.value = "******";
	passchk.value = "******";
	return true;
}

function secure_login() {
	var form = jQuery("#dw__login")[0];
	if(!form || !form.use_securelogin.checked) return true;
	var user = form.u;
	var pass = form.p;
	var sectok = form.sectok;
	
	form.securelogin.value = encrypt("p="+esc(pass.value)+";"+sectok.value);
	pass.value = "******";
	return true;
}

function secure_admin() {
	var el = jQuery("#test__message")[0];
	if(el) 
		el.value = encrypt(esc(el.value)); 
	return true;					
}

function secure_add_user() {
	var form = securelogin_get_form(jQuery('#add_userid')[0]);
	if(!form || !form.use_securelogin.checked) return true;
	var pass = form.add_userpass;
	var sectok = form.sectok;
	
	form.securelogin.value = encrypt("userpass="+esc(pass.value)+";"+sectok.value);
	pass.value = "******";
	return true;
}

function secure_modify_user() {
	var form = securelogin_get_form(jQuery('#modify_userid')[0]);
	if(!form || !form.use_securelogin.checked) return true;
	var pass = form.modify_userpass;
	var sectok = form.sectok;
	
	form.securelogin.value = encrypt("userpass="+esc(pass.value)+";"+sectok.value);
	pass.value = "******";
	return true;
}

function ajaxSuccess(data) {
		if(data === ''){ return; }
		var jsNode = document.createElement('script');
		jsNode.setAttribute('type', 'text/javascript');
		jsNode.text = data;
		document.getElementsByTagName('head')[0].appendChild(jsNode);

		for(var i = 0; i < securelogin_forms.length; ++i) {
			var form = securelogin_forms[i][1];
			switch(securelogin_forms[i][0]) {
			case 'dw__login':
			case 'dw__register':
				var uslNode = document.createElement('label');
				var button = jQuery("input.button", form)[0];
				button.parentNode.insertBefore(uslNode, button);
				uslNode.setAttribute('class', 'simple');
				uslNode.setAttribute('for', 'use_securelogin');
				var label;
				if('dw__login' == securelogin_forms[i][0]) {
					jQuery(form).submit(secure_login);
					label = securelogin_login_label;
				}
				else {
					jQuery(form).submit(secure_profile);
					label = securelogin_update_label;
				}
				uslNode.innerHTML = '<input type="checkbox" id="use_securelogin" name="use_securelogin" value="1" checked="checked"/> <span>'+label+'</span>';
				break;
			case 'test__publicKey':
				jQuery(form).submit(secure_admin);
				break;
			case 'add_userid':
			case 'modify_userid':
				var uslNode = document.createElement('tbody');
				var button = jQuery("input.button", form)[0].parentNode.parentNode.parentNode;
				button.parentNode.insertBefore(uslNode, button);
				var tr = document.createElement('tr');
				uslNode.appendChild(tr);
				var td = document.createElement('td');
				tr.appendChild(td);
				td.innerHTML = '<label class="simple" for="use_securelogin">'+securelogin_update_label+'</label>';
				td = document.createElement('td');
				tr.appendChild(td);
				td.innerHTML = '<input type="checkbox" id="use_securelogin" name="use_securelogin" value="1" checked="checked"/>';
				if('add_userid' == securelogin_forms[i][0])
					jQuery(form).submit(secure_add_user);
				else
					jQuery(form).submit(secure_modify_user);
				break;
			}
		}
}

if(securelogin_forms) {
	jQuery.post(
		DOKU_BASE + 'lib/exe/ajax.php',
		{ call: 'securelogin_public_key' },
		ajaxSuccess
	);
}
