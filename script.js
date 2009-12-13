var securelogin_forms = new Array();

function securelogin_add_js(source) {
	var jsNode = document.createElement('script');
	jsNode.setAttribute('type', 'text/javascript');
	jsNode.setAttribute('src', source);
	document.getElementsByTagName('head')[0].appendChild(jsNode);
}

function securelogin_get_form(el) {
	while(el && el.nodeName != 'FORM')
		el = el.parentNode;
	return el;
}

addInitEvent(function () {
	var forms = new Array('dw__login', 'dw__register', 'test__publicKey', 'add_userid', 'modify_userid');
	
	var jsNeeded = false;
	for (var i = 0; i < forms.length; ++i) {
		var form = securelogin_get_form($(forms[i]));
		if(!form) continue;
		if(!jsNeeded)
			jsNeeded = true;
		var slNode = document.createElement('input');
		slNode.setAttribute('type', 'hidden');
		slNode.setAttribute('name', 'securelogin');
		slNode.setAttribute('id', 'securelogin');
		form.appendChild(slNode);
		securelogin_forms.push(new Array(forms[i], form));
	}
	
	if(jsNeeded) {
		securelogin_add_js(DOKU_BASE+'lib/plugins/securelogin/rsalib.js');
		securelogin_add_js(DOKU_BASE+'lib/plugins/securelogin/securelogin.js');
	}
});
