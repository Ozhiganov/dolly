$(function(){
var form = $('.search_form'), i_text = form.find('.search_form__text'), i_submit = form.find('[type="submit"]'), types = form.find('input[name="types[]"]'), l_alert = form.find('.search_form__alert'), l_found = form.find('.search_form__found'),
	filters = form.find('[name^="filters["][name$="]"]'), results = $('.search_results'),
	get_results = function(){
		l_found.text('');
		var v = $.trim(i_text.filter(':enabled').val()), chf = filters.filter(':checked, select');
		results.text('');
		g_del.Change();
		if(!v.length && !chf.length) return false;
		var data = form.serialize();
		location.hash = '#!' + data;
		ms.get(data, function(r, type){
			switch(type)
			 {
				case 'text':
					if('empty' === r) l_found.text('Ничего не найдено.');
					else ms.AddErrorMsg(r || 'Произошла ошибка.');
					break;
				case 'html':
					results.html(r);
					var n = results.find('.result').length, s = ms.GetAmountStr(n, ['', ''], ['ы', 'а'], ['ы', 'ов']);
					l_found.text('Найден' + s[0] + ' ' + n + ' результат' + s[1]);
					break;
				default: ms.AddErrorMsg('Неправильный ответ сервера!');
			 }
		}, 'search', {'auto_status':false, 'progbar':i_submit});
		return false;
	},
	g_del = new InitGroupDeleting(function(){return form.attr('data-url') + location.hash;});
filters.change(get_results);
types.change(function(){
	var d = !types.filter(':checked').length;
	i_text.prop('disabled', d);
	i_submit.prop('disabled', d);
	if(!d || types.length) get_results();
});
form.submit(get_results);
if(location.hash && location.hash.indexOf('#!') === 0)
 {
	var parts = location.hash.slice(2).split('&'), found = false;
	for(var i = 0; i < parts.length; ++i)
	 {
		var p = parts[i].split('='), input = form.find('[name="' + decodeURIComponent(p[0]) + '"]');
		if(input.length)
		 {
			var v = decodeURIComponent(p[1]);
			switch(input.prop('tagName').toLowerCase())
			 {
				case 'select': input.val(v); break;
				case 'input':
					switch(input.prop('type'))
					 {
						case 'checkbox': input.filter('[value="' + v + '"]').prop('checked', true); break;
						case 'text':
						case 'search': input.val(v); break;
					 }
					break;
			 }
			found = true;
		 }
	 }
	if(found) i_submit.click();
 }
else i_text.focus();
});