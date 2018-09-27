$(function(){
var fs_config = $('input[name="fs_config"]').val(),
	$_INPUTS = 'input,select,textarea',
	get_fields_json = function(form){
		var f = [];
		form.find($_INPUTS).each(function(i){
			var n = {'node':this.nodeName.toLowerCase(), 'name':this.name};
			if('INPUT' === this.nodeName)
			 {
				if('button' === this.type ||
				   'submit' === this.type ||
				   'hidden' === this.type) return;
				n.type = this.type;
			 }
			if('' === this.name)
			 {
				this.name += i;
				n.name = this.name;
			 }
			f.push(n);
		});
		return {o:f, s:JSON.stringify(f)};
	};
if(fs_config)
 {
	try
	 {
		fs_config = JSON.parse(fs_config);
		$.each(fs_config, function(j, v){
			v.id = j;
			v.fsid = 'fs_' + j;
			v.valid = false;
			if(v.form_fields)
			 {
				try
				 {
					var k = JSON.parse(v.form_fields);
					v.form_fields = {o:k, s:v.form_fields};
					v.valid = true;
				 }
				catch(e) {}
			 }
			if(!v.valid)
			 {
				console.log("Invalid 'form_fields'!");
			 }
		});
	 }
	catch(e)
	 {
		fs_config = false;
	 }
 }
var HandlerEditor = new(function(){
	var data = false, editor = this,
	fs_save_form = $('.form'), fs_hdr = fs_save_form.find('.form__top'), fs_fieldset = fs_save_form.find('fieldset'),
	i_title = fs_save_form.find('input[name="save_form_title"]'),
	i_email = fs_save_form.find('input[name="save_form_email"]'),
	i_subject = fs_save_form.find('input[name="save_form_subject"]'),
	i_from = fs_save_form.find('input[name="save_form_from"]'),
	i_tpl = fs_save_form.find('textarea[name="save_form_template"]'),
	i_n_fields = fs_save_form.find('.fields_types[data-name="fields"]'),
	i_f_fields = fs_save_form.find('input[data-name="form_fields"]'),
	i_selector = fs_save_form.find('input[data-name="selector"]'),
	i_msg_success = fs_save_form.find('input[name="save_form_msg_success"]'),
	i_id = fs_save_form.find('input[data-name="id"]'),
	b_select_submit = fs_save_form.find('.btn_select_submit'),
	b_deselect_submit = fs_save_form.find('.btn_deselect_submit'),
	f_types = {'none':{'title':'—'}, 'Email':{'title':'Email'}, 'Tel':{'title':'Телефон'}},
	make_fields_input = function(input, fields){
		input.text('');
		for(var k = 0, i_n0 = input.attr('data-input_name'); k < fields.length; ++k)
		 {
			var i_n = i_n0 + '[' + encodeURIComponent(fields[k][0]) + ']', type = false, i_r = $('<div class="fields_types__item" />'), i_req = $('<input type="checkbox" />').prop({'name':i_n + '[required]', 'title':'Обязательное поле'}), i_type = $('<select class="form__select" />').prop({'name':i_n + '[type]'});
			if('undefined' !== typeof(fields[k][2]))
			 {
				if(fields[k][2].type) type = fields[k][2].type;
				if(fields[k][2].required) i_req.prop('checked', true);
			 }
			$('<input type="text" class="form__input_text" />').prop({'name':i_n + '[title]', 'placeholder':fields[k][0]}).val(fields[k][1]).appendTo(i_r);
			if('File' === type)
			 {
				i_req.prop({'disabled':true});
				i_type.append('<option>Файл</option>').prop({'disabled':true});
				$('<input type="hidden" />').prop({'name':i_type.prop('name')}).val('File').appendTo(i_r);
			 }
			else
			 {
				$.each(f_types, function(i, v){i_type.append('<option value="' + i + '">' + v.title + '</option>');});
				if(type) i_type.val(type);
			 }
			i_type.appendTo(i_r);
			i_req.appendTo(i_r);
			input.append(i_r);
		 }
	},
	get_label = (new(function(){
		var l = {
			'name':'Имя',
			'email':'Email',
			'phone':'Телефон',
			'phone_num':'Телефон',
			'city':'Город',
			'country':'Страна',
			'firstname':'Имя',
			'lastname':'Фамилия',
			'surname':'Фамилия',
			'address':'Адрес'
		};
		this.Run = function(name){
			return 'undefined' === typeof(l[name]) ? name : l[name];
		};
	})()).Run,
	create_tpl = function(form, on_fetch_field){
		var tpl = 'Информация о заказе:\n';
		form.find($_INPUTS).each(function(i){
			var name = this.name, label = '', tmp = {length:0}, type = false;
			if('INPUT' === this.nodeName)
			 {
				if('button' === this.type) return;
				else if('submit' === this.type)
				 {
					return;
				 }
				else if('hidden' === this.type)
				 {
					this.parentNode.removeChild(this);
					return;
				 }
				else if('file' === this.type) type = 'File';
				else if('email' === this.type) type = 'Email';
				else if('tel' === this.type) type = 'Tel';
			 }
			if(this.id) tmp = form.find('label[for="' + this.id + '"]');
			if(!tmp.length) tmp = $(this).prev('label');
			if(tmp.length) label = tmp.text();
			else label = this.hasAttribute('placeholder') ? this.getAttribute('placeholder') : '';
			if(label)
			 {
				label = $.trim(label);
				label = $.trim(label.replace(/^\*/, '').replace(/\*$/, ''));
			 }
			if(!label) label = get_label(name);
			if('File' !== type) tpl += label + ': {' + name + '}\n';
			if(on_fetch_field) on_fetch_field(name, label, type, this.required);
		});
		return tpl + '\nВремя отправления: {__dolly_timestamp}\nIP-адрес отправителя: {__dolly_ip}';
	},
	clear_form = function(){if(data) data.form.find('[' + ATTR_HIGHLIGHT + ']').removeAttr(ATTR_HIGHLIGHT);},
	open_editor = function(form, fs, p){
		var opened = false;
		if(data)
		 {
			if(data.form === form) return;
			opened = true;
		 }
		data = {'form':form, 'fs':fs, 'p':p};
		var flds;
		if(fs)
		 {
			i_title.val(fs.title);
			fs_hdr.text('Редактировать существующий обработчик');
			i_email.val(fs.email);
			i_subject.val(fs.subject);
			i_from.val(fs['from']);
			i_selector.val(fs['selector']);
			i_msg_success.val(fs['msg_success']);
			flds = fs.fields;
			i_tpl.val(fs.template);
			i_f_fields.val(fs.form_fields.s);
			i_id.val(fs.id);
		 }
		else
		 {
			i_title.val('Новый обработчик');
			fs_hdr.text('Создать новый обработчик');
			i_selector.val('');
			i_msg_success.val('Ваше сообщение отправлено.');
			flds = [];
			i_tpl.val(create_tpl(form, function(name, label, type, required){
				var f = [name, label, {}];
				if(type) f[2].type = type;
				if(required) f[2].required = required;
				flds.push(f);
			}));
			i_f_fields.val(get_fields_json(form).s);
			i_id.val('');
		 }
		make_fields_input(i_n_fields, flds);
		b_select_submit.parent()[0 === data.form.find("[type='submit']").length ? 'removeClass' : 'addClass']('_hidden');
		b_deselect_submit[i_selector.val() ? 'removeClass' : 'addClass']('_hidden');
		fs_save_form.trigger('form:open', [data.form, editor, opened]);
		fs_save_form.find('.form__st_msg').remove();
		fs_save_form.removeClass('_hidden');
	},
	close_editor = function(){
		if(data) fs_save_form.trigger('form:close', [data.form, editor]);
		fs_save_form.addClass('_hidden');
		clear_form();
		$('.toolbar__action._select_form._selected').removeClass('_selected');
		data = false;
	},
	get_selector = function(item, root){
		var a = ['id', 'name', 'class'],
			make_s = function(n){
				var s = '';
				for(var i = 0, v; i < a.length; ++i) if(v = n.attr(a[i])) s += '[' + a[i] + '="' + v + '"]';
				return s;
			},
			make_selector = function(n, s0, sc){
				var s = make_s(n);
				if(s)
				 {
					s = s0 + s;
					if(sc) s += ' ' + sc;
					if(/* 1 === */ root.find(s).length) return s;
					else
					 {
						var p = n.parent();
						if(p.length) s = make_selector(p, '', s);
						else s = '';
					 }
				 }
				else if(sc || s0)
				 {
					var p = n.parent();
					if(p.length) s = make_selector(p, '', sc ? sc : s0);
				 }
				return s;
			};
		return make_selector(item, item.get(0).nodeName.toLowerCase(), '');
	},
	check_submit_handler = function(n, event){
		if(event.target === n || 'SELECT' === event.target.nodeName || 'TEXTAREA' === event.target.nodeName) return false;
		else if('INPUT' === event.target.nodeName)
		 {
			if('button' !== event.target.type) return false;
			var t = $(event.target);
		 }
		else
		 {
			var t = $(event.target);
			if(t.has('select,textarea').length) return false;
			else if(t.has('input').length && !t.has('input[type="button"]').length) return false;
		 }
		return t;
	},
	add_status_msg = function(type, text){
		var n = $('<div>').appendTo(fs_save_form).attr({'class':'form__st_msg', 'data-status':type}).text(text);
		$('<input type="button" value="×" class="form__delete_st_msg" />').click(function(){$(this.parentNode).remove();}).appendTo(n);
		IFrameUI.ScrollTo(n.get(0));
	},
	disable_inputs = function(){
		var n = fs_fieldset.find('input,select,textarea');
		for(var i = 0; i < arguments.length; ++i) n = n.not(arguments[i]);
		n.prop('disabled', true);
		fs_save_form.find('[type="submit"]').prop('disabled', true);
	},
	enable_inputs = function(){
		var n = fs_fieldset.find('input,select,textarea');
		for(var i = 0; i < arguments.length; ++i) n = n.not(arguments[i]);
		n.prop('disabled', false);
		fs_save_form.find('[type="submit"]').prop('disabled', false);
	},
	select_submit_handler = {
		'click': function(event){
			var t = check_submit_handler(this, event);
			if(!t) return;
			i_selector.val(get_selector(t, data.form));
			b_deselect_submit[i_selector.val() ? 'removeClass' : 'addClass']('_hidden');
			data.form.find('[' + ATTR_HIGHLIGHT + ']').removeAttr(ATTR_HIGHLIGHT);
			t.attr(ATTR_HIGHLIGHT, '1');
		},
		'submit': function(event){
			event.stopPropagation();
			return false;
		},
		'mouseover': function(event){
			var t = check_submit_handler(this, event), h = data.form.find('[' + ATTR_HIGHLIGHT + ']'), s = i_selector.val();
			if(s) h = h.not(s);
			if(t) t.attr(ATTR_HIGHLIGHT, '1');
			h.removeAttr(ATTR_HIGHLIGHT);
		},
		'turn_off': function(event, form, editor){
			editor.EnableInputs();
			form.off('click', select_submit_handler.click);
			form.off('submit', select_submit_handler.submit);
			form.off('mouseover', select_submit_handler.mouseover);
			form.find('[' + ATTR_HIGHLIGHT + ']').removeAttr(ATTR_HIGHLIGHT);
			b_select_submit.attr('data-enabled', 'false').val(b_select_submit.attr('data-value-start'));
			IFrameUI.Dim(false);
		}
	},
	ATTR_HIGHLIGHT = 'data-dollysites__i_highlight';
	fs_save_form.find('.form__toggle_state').click(function(){fs_save_form.toggleClass('_collapsed');});
	b_select_submit.click(function(event){
		if(!data) return;
		if('true' === this.getAttribute('data-enabled')) select_submit_handler.turn_off(event, data.form, editor);
		else
		 {
			var rect = IFrameUI.ScrollTo(data.form.get(0));
			if(!rect)
			 {
				add_status_msg('warning', 'Форма сейчас скрыта. Чтобы выбрать кнопку, нужно открыть форму, чтобы она была видна на странице.');
				return;
			 }
			disable_inputs(this, b_deselect_submit);
			data.form.click(select_submit_handler.click);
			data.form.submit(select_submit_handler.submit);
			data.form.mouseover(select_submit_handler.mouseover);
			this.setAttribute('data-enabled', 'true');
			this.value = this.getAttribute('data-value-finish');
			if(rect.height) IFrameUI.Dim(true, data.form.get(0));
			var s = i_selector.val();
			try
			 {
				if(s) data.form.find(s).attr(ATTR_HIGHLIGHT, '1');
			 }
			catch(e) {}
		 }
	});
	b_deselect_submit.click(function(){
		if(!data) return;
		i_selector.val('');
		data.form.find('[' + ATTR_HIGHLIGHT + ']').removeAttr(ATTR_HIGHLIGHT);
		b_deselect_submit.addClass('_hidden');
	});
	fs_save_form.on('form:open', function(event, form, editor, opened){
		if(opened) select_submit_handler.turn_off(event, form, editor);
	});
	fs_save_form.on('form:close', select_submit_handler.turn_off);
	this.Open = open_editor;
	this.Close = close_editor;
	this.AddWarningMsg = function(text){return add_status_msg('warning', text);};
	this.EnableInputs = enable_inputs;
	fs_save_form.find('.form__close').click(close_editor);
	i_tpl.on('input click keyup', function(event){
		if(!data) return;
		if('keyup' === event.type)
		 {
			switch(event.which)
			 {
				case 37:
				case 38:
				case 39:
				case 40: break;
				default: return;
			 }
		 }
		clear_form();
		var s_start = this.selectionStart, s_end = this.selectionEnd, regex = /\{([a-z0-9_\-\[\].]+)\}/gi, v;
		if('backward' === this.selectionDirection)
		 {
			s_start = this.selectionEnd;
			s_end = this.selectionStart;
		 }
		while((v = regex.exec(this.value)) !== null)
		 {
			if(v.index <= s_end && s_start <= (v.index + v[0].length))
			 {
				data.form.find('[name="' + data.p + v[1] + '"]').attr(ATTR_HIGHLIGHT, '1');
				break;
			 }
		 }
	});
	$('<input type="button" class="msui_small_button _create_template" />').val('создать заново').click(function(){
		if(!data) return;
		if('' === $.trim(i_tpl.val()) || confirm('Перезаписать шаблон письма?')) i_tpl.val(create_tpl(data.form));
	}).insertAfter(i_tpl);
})(),
FormsList = new(function(fsc){
	var wr = $('.forms_list'), items = [],
	get_fs_by_fields = function(form){
		var form_fields = get_fields_json(form), fs = false;
		$.each(fsc, function(j, v){
			if(v.valid)
			 {
				if(form_fields.s === v.form_fields.s)
				 {
					fs = v;
					return false;
				 }
			 }
		});
		return fs;
	},
	item = function(form, button){
		var fs = false, i_fs_id = form.find('input[type="hidden"][name="__fs_id"]'), p = '';
		if(fsc)
		 {
			var fs_id = i_fs_id.val();
			if(i_fs_id.length && '' !== fs_id)
			 {
				$.each(fsc, function(j, v){
				if(v.valid && fs_id === v.fsid)
				 {
					fs = v;
					return false;
				 }
				});
				if(fs)
				 {
					var form_fields = get_fields_json(form);
					if(form_fields.o.length === fs.form_fields.o.length)
					 {
						for(var i = 0; i < form_fields.o.length; ++i)
						 if(form_fields.o[i].node !== fs.form_fields.o[i].node || form_fields.o[i].name !== fs_id + '_' + i)
						  {
							fs = false;
							break;
						  }
					 }
					else fs = false;
					if(fs) p = fs.fsid + '_';
				 }
				else fs = get_fs_by_fields(form);
			 }
			else fs = get_fs_by_fields(form);
		 }
		if(fs) button.attr({'data-handler':'1', 'title':'Редактировать обработчик формы'});
		items.push({'form':form, 'button':button, 'fs':fs});
		var conf_form = function(){
			for(var i = 0; i < items.length; ++i) items[i].button.toggleClass('_selected', items[i].button === button);
			HandlerEditor.Open(form, fs, p);
			IFrameUI.ScrollTo(form.get(0));
		};
		form.click(conf_form);
		button.click(conf_form);
	};
	this.Clear = function(prog){
		wr.text('').removeClass('_empty _loaded');
		if(true === prog) wr.addClass('_loading');
		items = [];
	};
	this.Load = function(doc){
		wr.removeClass('_loading');
		var forms = doc.find('form');
		if(forms.length)
		 {
			wr.addClass('_loaded');
			forms.each(function(i){
				new item($(this), $('<input type="button" class="toolbar__action _select_form" />').attr('title', 'Добавить обработчик формы').val('#' + (1 + i)).appendTo(wr));
			});
		 }
		else wr.addClass('_empty');
	};
})(fs_config);
FormsList.Clear(true);
IFrameUI.OnLoad(function(){FormsList.Load($(this.document));}, true);
});