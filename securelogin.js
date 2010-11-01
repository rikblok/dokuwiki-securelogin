function esc( x ) {
	return x.replace( /%/g, '%26').replace( /;/g, '%3B' ).replace( /:/g, '%3A' ).replace( /@/g, '%40' );
}

function secure_profile() {
	var form = $("dw__register");
	if(!form || !form.use_securelogin.checked) return true;
	var newpass = form.newpass;
	var passchk = form.passchk;
	var oldpass = form.oldpass;
	var sectok = form.sectok;
	
	form.securelogin.value = encrypt("newpass:"+esc(newpass.value)+";passchk:"+esc(passchk.value)+";oldpass:"+esc(oldpass.value)+"@"+sectok.value);
	oldpass.value = "******";
	newpass.value = "******";
	passchk.value = "******";
	return true;
}

function secure_login() {
	var form = $("dw__login");
	if(!form || !form.use_securelogin.checked) return true;
	var user = form.u;
	var pass = form.p;
	var sectok = form.sectok;
	
	form.securelogin.value = encrypt("p:"+esc(pass.value)+"@"+sectok.value);
	pass.value = "******";
	return true;
}

function secure_admin() {
	var el=$("test__message"); 
	if(el) 
		el.value = encrypt(el.value); 
	return true;					
}

function secure_add_user() {
	var form = securelogin_get_form($('add_userid'));
	if(!form || !form.use_securelogin.checked) return true;
	var pass = form.add_userpass;
	var sectok = form.sectok;
	
	form.securelogin.value = encrypt("userpass:"+esc(pass.value)+"@"+sectok.value);
	pass.value = "******";
	return true;
}

function secure_modify_user() {
	var form = securelogin_get_form($('modify_userid'));
	if(!form || !form.use_securelogin.checked) return true;
	var pass = form.modify_userpass;
	var sectok = form.sectok;
	
	form.securelogin.value = encrypt("userpass:"+esc(pass.value)+"@"+sectok.value);
	pass.value = "******";
	return true;
}

if(securelogin_forms) {
	var ajax = new sack(DOKU_BASE+'lib/exe/ajax.php');
	ajax.AjaxFailedAlert = '';
	ajax.encodeURIString = true;
	ajax.onCompletion = function(){
		var data = this.response;
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
				var button = getElementsByClass('button', form, 'input')[0];
				button.parentNode.insertBefore(uslNode, button);
				uslNode.setAttribute('class', 'simple');
				uslNode.setAttribute('for', 'use_securelogin');
				var label;
				if('dw__login' == securelogin_forms[i][0]) {
					addEvent(form, "submit", secure_login);
					label = securelogin_login_label;
				}
				else {
					addEvent(form, "submit", secure_profile);
					label = securelogin_update_label;
				}
				uslNode.innerHTML = '<input type="checkbox" id="use_securelogin" name="use_securelogin" value="1" checked="checked"/> <span>'+label+'</span>';
				break;
			case 'test__publicKey':
				addEvent(form, "submit", secure_admin);
				break;
			case 'add_userid':
			case 'modify_userid':
				var uslNode = document.createElement('tbody');
				var button = getElementsByClass('button', form, 'input')[0].parentNode.parentNode.parentNode;
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
					addEvent(form, "submit", secure_add_user);
				else
					addEvent(form, "submit", secure_modify_user);
				break;
			}
		}
	}
	ajax.runAJAX('call=securelogin_public_key');
}
