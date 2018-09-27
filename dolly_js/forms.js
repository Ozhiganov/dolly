function _DollySites_Forms(data){
var get_type = function(a){var i = {}, j = i.toString; return null == a ? a + '' : 'object' == typeof a || 'function' == typeof a ? i[j.call(a)] || 'object' : typeof a;},
	is_array = Array.isArray || function(a){return 'array' === get_type(a);},
	DollyForm = function(form, data){
		form.setAttribute('action', '/index.php?__dolly_action=handle_form');
		form.setAttribute('method', 'post');
		var input;
		if(data.selector)
		 {
			input = form.querySelectorAll(data.selector);
			for(var i = 0; i < input.length; ++i)
			 if(i) input[i].onclick = input[i - 1].onclick;
			 else input[i].onclick = function(){form.submit();};
		 }
		for(var i = 0; i < data.fields.length; ++i)
		 {
			if(input = form.querySelector('[name="' + data.fields[i][0] + '"]'))
			 {
				input.name = data.id + '_' + i;//data.fields[i][0];
			 }
		 }
		input = form.querySelector('input[type="hidden"][name="__fs_id"]');
		if(!input)
		 {
			input = document.createElement('input');
			input.type = 'hidden';
			input.name = '__fs_id';
			form.appendChild(input);
		 }
		input.value = data.id;
		input = form.querySelector('input[type="hidden"][name="__redirect"]');
		if(!input)
		 {
			input = document.createElement('input');
			input.type = 'hidden';
			input.name = '__redirect';
			form.appendChild(input);
		 }
		input.value = location.pathname + location.search + location.hash;
		if('undefined' !== typeof(jQuery))
		 {
			jQuery(window).load(function(){jQuery(form).off('submit');});
		 }
		else// if('undefined' !== typeof(form.removeEventListener)) form.removeEventListener('submit');
		 {
			// form.parentNode.replaceChild(form.cloneNode(false), form);
		 }
	},
	get_fields_json = function(form){
		var f = [], inputs = form.querySelectorAll('input,select,textarea'), n;
		for(var i = 0; i < inputs.length; ++i)
		 {
			n = {'node':inputs[i].nodeName.toLowerCase(), 'name':inputs[i].name};
			if('INPUT' === inputs[i].nodeName)
			 {
				if('button' === inputs[i].type ||
				   'submit' === inputs[i].type ||
				   'hidden' === inputs[i].type) continue;
				n.type = inputs[i].type;
			 }
			else if('SELECT' === inputs[i].nodeName)
			 {
				var opts = inputs[i].getElementsByTagName('option');
				for(var k = 0; k < opts.length; ++k) opts[k].setAttribute('value', opts[k].textContent);
			 }
			if('' === inputs[i].name)
			 {
				inputs[i].name += i;
				n.name = inputs[i].name;
			 }
			f.push(n);
		 }
		return JSON.stringify(f);
	};
var forms = document.getElementsByTagName('form'), d = [];
if(!forms.length) return;
if(is_array(data))
 {
	for(var j = 0; j < data.length; ++j)
	 {
		try
		 {
			if(data[j].form_fields && JSON.parse(data[j].form_fields)) d.push(data[j]);
		 }
		catch(e) {}
	 }
 }
else if('object' === typeof(data))
 {
	for(var j in data)
	 {
		try
		 {
			if(data[j].form_fields && JSON.parse(data[j].form_fields)) d.push(data[j]);
		 }
		catch(e) {}
	 }
 }
else return;
for(var i = 0; i < forms.length; ++i)
 {
	var s = get_fields_json(forms[i]);
	for(var j = 0; j < d.length; ++j)
	 if(s === d[j].form_fields)
	  {
		new DollyForm(forms[i], d[j]);
		break;
	  }
 }
}