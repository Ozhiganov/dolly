function DropDownList(list, o)
{
	o = $.extend({}, {onchange:function(){}}, o);
	list = $(list);
	var value = list.find('.dropdown_list__value'),
	input = list.find('[type="hidden"]'),
	items = list.find('.dropdown_list__list'),
	item_click = function()
	 {
		var item = $(this), v = item.attr('data-value'), selected = list.find('._selected');
		selected.removeClass('_selected');
		item.addClass('_selected');
		value.text(item.text());
		input.val(v);
		if(selected.index(item) == -1)
		 {
			if($.isArray(o.onchange)) for(var i = 0; i < o.onchange.length; ++i) o.onchange[i].call(list);
			else o.onchange.call(list);
		 }
	 };
	list.find('.dropdown_list__item').click(item_click);
	this.AddOption = function(value, title, selected) { $('<li/>').addClass("dropdown_list__item" + (selected ? ' _selected' : '')).attr('data-value', value).text(title).click(item_click).appendTo(items); };
}
DropDownList.CreateMarkup = function(o)
{
	o = $.extend({}, {name:'', id:'', 'class':'', default_value:'', default_title:'—'}, o);
	return "<div class='dropdown_list " + o['class'] + "'><span class='dropdown_list__value _image_dropdown'><span class='dropdown_list__value_text'>" + o.default_title + "</span></span><ul class='dropdown_list__list'></ul><input type='hidden' value='" + o.default_value + "'" + (o.id ? " id='" + o.id + "'" : '') + (o.name ? " name='" + o.name + "'" : '') + ' /></div>';
};