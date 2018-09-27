MSContacts = new(function(){
var row_proto, header_proto, DragableItem = new(function(){
	var OnStartDrag = function(x, y){$(this.parentNode).addClass('_captured');}, OnEndDrag = function(destiny){$(this.parentNode).removeClass('_captured');};
	this.Create = function(item)
	 {
		item.OnStartDrag = OnStartDrag;
		item.OnEndDrag = OnEndDrag;
		item.OnDragOver = function(dest)
		 {
			var row = this.parentNode;
			$(dest[0].parentNode).children().each(function(i){if(i = (this == dest[0]) << 1 | this == row) return $(dest[0])[i > 1 ? 'before' : 'after'](row) && false;});
			UpdateNames();
		 };
		return item;
	 };
}),
UpdateNames = function(){$('.mscontacts .form__row').each(function(i){$(this).find('input').each(function(){this.name = this.name.replace(/^([a-z_-]+\[)[0-9]+(\](\[[a-z0-9_-]*\])?)$/, '$1' + i + '$2');});});};
this.Init = function()
 {
	var rows = $('.mscontacts__list .form__row'), protos = $('.mscontacts__prototype .form__row');
	row_proto = protos.filter('._value');
	header_proto = protos.filter('._header');
	MSDragNDropManager.Init();
	$('.mscontacts__add').click(function(){
		var clone = (this.className.indexOf(' _header') == -1 ? row_proto : header_proto).clone(true), row = clone.get(0), drag = MSDragNDropManager.InitItem(DragableItem.Create(row.lastChild));
		row.IgnoreX = TrueFunc;
		$('.mscontacts__list .form__row').each(function(){
			drag.AttachTarget(this);
			this.lastChild.AttachTarget(row);
		});
		clone.appendTo('.mscontacts__list').find('input[type="text"]').first().focus();
		UpdateNames();
	});
	rows.each(function(i){
		var drag = MSDragNDropManager.InitItem(DragableItem.Create(this.lastChild));
		rows.each(function(k){if(k != i) drag.AttachTarget(this);});
		this.IgnoreX = TrueFunc;
	});
	$('.delete_block._mscontacts').MSUIDeleteBlock({ondelete:function(){this.find('input').prop('disabled', true);this.find('.multiselect3').attr('data-enabled', 'false');}, onundo:function(){this.find('input').prop('disabled', false);this.find('.multiselect3').attr('data-enabled', 'true');}});
	$('.mscontacts__value').bind('input', function(){
		var s = $.trim(this.value), tests = [{'regex':/^(mailto:)?([a-z0-9_-]+\.)*[a-z0-9_-]+@([a-z0-9][a-z0-9-]*[a-z0-9]\.)+[a-z]{2,4}$/i, 'type':'email'}, {'regex':/^icq:[0-9]+[0-9 -]*[0-9]+$/, 'type':'icq'}, {'regex':/^skype:.+$/, 'type':'skype'}, {'regex':/^(\+[1-9]|8)[0-9]{10}$/, 'type':'phone_num'}, {'regex':/^(https?:)?\/\/.+$/, 'type':'url'}, {'regex':/^[а-яё0-9-]+\.рф$/i, 'type':'url'}, {'regex':/^([\da-z.-]+)\.(com|net|org|info|biz|ru|su)$/i, 'type':'url'}];
		for(var i = 0; i < tests.length; ++i)
		 if(tests[i].regex.test(s))
		  {
			this.setAttribute('data-type_id', tests[i].type);
			this.nextSibling.value = tests[i].type;
			return;
		  }
		this.removeAttribute('data-type_id');
		this.nextSibling.value = '';
	});
	$('.multiselect3.mscontacts__groups').MSMultiSelect3();
	$('.mscontacts_show_group').change(function(){
		var rows = $('.mscontacts__list > .form__row'), v = this.value;
		if('' === v) rows.removeClass('_hidden');
		else
		 {
			// rows.filter(function(){
				// return $(this).find('.multiselect3__option input[type="checkbox"][name$="[' + v + ']"]:checked').length > 0;
			// }).addClass('_hidden');
			rows.each(function(){
				var r = $(this);
				r.toggleClass('_hidden', !r.find('.multiselect3__option input[type="checkbox"][name$="[' + v + ']"]:checked').length);
			});//.addClass('_hidden');
		 }
	});
 };
});
$(MSContacts.Init);