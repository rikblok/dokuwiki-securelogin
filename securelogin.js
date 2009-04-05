function secure_profile() {
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

function secure_admin() {
	var el=$("test__message"); 
	if(el) 
		el.value = encrypt(el.value); 
	return true;					
}
