function URLFilterDefault(val)
{
	return Transliterate_RU_EN(val, function(v){return v.replace(/_+/, '');});
}
function URLFilterRU(val)
{
	return val.replace(/[^a-z0-9а-яё\-]+/ig, '-').replace(/-{2,}/g, '-').replace(/^-/, '').replace(/-$/, '').toLowerCase();
}
$(function(){
	$('.msui_input._set_page_url').each(function(){
		var input = $(this),
			form = input.parents('.form'),
			mlsel = form.find('.form__input_wr._multilevel[data-name="parent_id"]'),
			i_url_part = form.find('.form__row[data-name="url_part"] > input[type="text"]'),
			i_title = form.find('.form__row[data-name="title"] > input[type="text"]'),
			i_page_id = form.find('input[name="__page__item_id"]'),
			set_homepage = form.find('input[type="checkbox"][data-name="set_homepage"]'),
			filter = i_url_part.attr('data-url_filter'),
			dropdown = $('<div class="dropdown_page_exists"></div>'),
			get_parent,
			b_auto = false,
			show_url = function(){
				var p = get_parent(), url_part = i_url_part.prop('disabled') ? '' : i_url_part.val();
				dropdown.detach();
				input.removeClass('_error');
				i_url_part.removeClass('_error');
				if(url_part || set_homepage.prop('checked'))
				 {
					input.val(location.protocol + '//' + location.hostname + i_url_part.attr('data-base') + (typeof p.sid === 'undefined' || !p.sid ? '' : p.sid + '/') + (url_part ? url_part + '/' : ''));
					if(url_part) ms.jget({'__fs_id':i_url_part.attr('data-fs_id'), '__get_field_data':i_url_part.attr('data-name'), 'url_part':url_part, 'parent_id':p.id}, function(r){
						if(r.page && (!i_page_id.length || r.page.id != i_page_id.val()))
						 {
							input.addClass('_error');
							i_url_part.addClass('_error');
							var h = '<div>Страница с такой ссылкой уже существует:</div><div><a href="' + r.page.href + '" target="_blank">' + (r.page.title || r.page.href) + '</a> ';
							if(r.page.hidden) h += '<span class="dropdown_page_exists__state" data-hidden="' + r.page.hidden + '"></span>';
							h += '</div><div>Укажите другой фрагмент URL или выберите другую родительскую страницу.</div>';
							dropdown.html(h).insertAfter(input);
						 }
					}, 'check_url');
				 }
				else input.val('');
			};
		if(mlsel.length)
		 {
			get_parent = function(){
				var opt = mlsel.find('select[name]:enabled option:selected');
				if(!opt.length) opt = mlsel.find('select').first().find('option').first();
				return {sid:opt.attr('data-sid'), id:opt.attr('value')};
			};
			mlsel.on('mlselect:change', show_url);
		 }
		else get_parent = function(){return {sid:i_url_part.attr('data-parent_sid'), id:form.find('input[data-name="parent_id"]').val()};};
		i_url_part.on('input', show_url);
		if(filter && ('undefined' !== typeof(window[filter])))
		 {
			b_auto = $('<input type="button" class="msui_small_button title__url_part" value="авто" title="Вставить фрагмент URL из названия" />').click(function(){
				if(!$.trim(i_url_part.val()) || confirm('Заменить фрагмент URL?'))
				 {
					i_url_part.val(window[filter](i_title.val()));
					show_url();
				 }
			}).appendTo(i_url_part.prev());
			b_auto._set_state = set_homepage.length ? function(){b_auto.prop('disabled', !$.trim(i_title.val()) || set_homepage.prop('checked'));} : function(){b_auto.prop('disabled', !$.trim(i_title.val()));};
			i_title.on('input', b_auto._set_state);
			b_auto._set_state();
		 }
		if(set_homepage.length)
		 {
			set_homepage.change(function(){
				mlsel.find('select').prop('disabled', this.checked);
				i_url_part.prop('disabled', this.checked);
				if(b_auto) b_auto._set_state();
				show_url();
			});
			if(set_homepage.prop('checked')) set_homepage.change();
		 }
	});
});