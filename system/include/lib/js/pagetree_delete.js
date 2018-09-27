function InitGroupDeleting(redirect)
{
	var selector = '.pagetree__action._check input[name="item[]"]', n_sel = $('.pagetree_actions__n_sel'),
	delete_selected = $('.global_action._delete_selected').click(function(){
		var selected = $(selector + ':checked');
		if(!selected.length)
		 {
			ms.AddWarningMsg('Не отмечены страницы для удаления!');
			delete_selected.addClass('_hidden');
			return;
		 }
		if(!confirm(delete_selected.attr('data-msg_confirm') || 'Удалить выбранные страницы со всеми фотографиями, файлами и подразделами?')) return;
		var form = $('<form method="post" action="core.php"></form>').css('display', 'none');
		selected.each(function(){form.append('<input type="hidden" name="ids[]" value="' + this.value + '" />');});
		if(redirect) $('<input type="hidden" name="__redirect" />').val($.isFunction(redirect) ? redirect() : redirect).appendTo(form);
		form.append('<input type="hidden" name="__mssm_action" value="delete_page" />');
		form.appendTo(document.body).submit();
	}),
	on_change = function(){
		var l = $(selector + ':checked').length, t = '';
		if(l)
		 {
			var s = ms.GetAmountStr(l, ['', ''], ['ы', 'а'], ['ы', 'ов']);
			t = 'Выбран' + s[0] + ' ' + l + ' элемент' + s[1];
		 }
		delete_selected[l ? 'removeClass' : 'addClass']('_hidden');
		n_sel[l ? 'removeClass' : 'addClass']('_hidden').text(t);
	};
	this.Change = on_change;
	$(document).on('change', selector, on_change);
};