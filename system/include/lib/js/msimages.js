function ButtonGroup()
{
	var buttons = [], handlers = [],
	enable_buttons = function(){for(var i = 0; i < buttons.length; ++i) buttons[i].disabled = false;},
	disable_buttons = function(){for(var i = 0; i < buttons.length; ++i) buttons[i].disabled = true;},
	handler = function(hdl)
	 {
		var disabled = true, __this_obj = this;
		this.Interface = new function()
		 {
			this.OnSuccess = function(){__this_obj.Disable();};
			this.Run = function()
			 {
				if(disabled) return;
				disable_buttons();
				hdl.call(this);
			 };
		 };
		this.ReturningInterface = new function()
		 {
			this.Enable = function()
			 {
				disabled = false;
				enable_buttons();
			 };
		 };
		this.Disable = function(){disabled = true;};
		this.Enable = function(){disabled = false;};
	 },
	run = function(){for(var i = 0; i < handlers.length; ++i) handlers[i].Interface.Run();};
	this.AttachButton = function(btn)
	 {
		btn.onclick = run;
		buttons.push(btn);
	 };
	this.AttachHandler = function(hdl)
	 {
		var obj = new handler(hdl);
		handlers.push(obj);
		return obj.ReturningInterface;
	 };
	this.Enable = function()
	 {
		for(var i = 0; i < handlers.length; ++i) handlers[i].Enable();
		enable_buttons();
	 };
	this.Disable = function()
	 {
		for(var i = 0; i < handlers.length; ++i) handlers[i].Disable();
		disable_buttons();
	 };
}

function MSImages(id)
{
	var images_cntr = $('#' + id);
	if(!images_cntr.length) return;
	var cropper = new MSCrop({onsave:function(data){
		data.id = page_id;
		ms.jpost(data, false, 'save_crop');
	}}),
	lang = MSImages.Lang, parent_id = null, cover_id = null, save_group = new ButtonGroup(), delete_group = new ButtonGroup(), page_id,
	save_order = save_group.AttachHandler(function()
	 {
		var arr = [];
		images_cntr.children().each(function(){arr.push(this.id.substr(5));});
		ms.jpost({order:arr.join('|')}, this.OnSuccess, 'set_order');
	 }),
	box_delete_click = function(boxes)
	 {
		this.exec = function()
		 {
			var shade = $('#thumb' + this.id.substr(3) + ' em'), title = 'Снять отметку для удаления', has_checked = false, has_unchecked = false;
			if(this.checked) shade.addClass('del');
			else
			 {
				shade.removeAttr('class');
				title = 'Отметить для удаления';
			 }
			this.previousSibling.title = title;
			for(var i = 0; i < boxes.length; ++i)
			 {
				if(has_checked && has_unchecked) break;
				if(boxes[i].checked) has_checked = true;
				else has_unchecked = true;
			 }
			if(has_checked) delete_group.Enable();
			else delete_group.Disable();
			var btns = $('#work_area form div.buttons input[name="select_all"]');
			if(!has_unchecked) btns.addClass('all').removeClass('partly').val(lang.deselect());
			else if(has_checked) btns.addClass('all partly').val(lang.deselect());
			else btns.removeClass('all partly').val(lang.select_all());
		 };
	 },
	box_check_click = function(btns){this.exec = function(){set_btns_disabled(btns, false);};};
	images_cntr.find('i').click(function(){
		page_id = this.parentNode.getElementsByTagName('input')[0].id.substr(3);
		ms.jpost({id:page_id, pid:0}, cropper.Show, 'show_crop');
	});
	$('#work_area form div.buttons input').each(function(){
		switch(this.type)
		 {
			case 'button':
			case 'submit':
				var names = this.name.split(' ');
				for(var k = 0; k < names.length; ++k)
				 switch(names[k])
				  {
					case 'order': save_group.AttachButton(this); break;
					case 'delete': delete_group.AttachButton(this); break;
				  }
				break;
			case 'hidden': if('parent_id' == this.name) parent_id = this.value; break;
		 }
	 });
	images_cntr.find('a').attr('title', lang.edit());
	var boxes_delete = [], boxes_check = [], has_cover = false,
	save_checked_storage = new function()
	 {
		var obj;
		this.Call = function(){return obj.Enable();};
		this.Set = function(val){obj = val;};
	 },
	save_cover_storage = new function()
	 {
		var obj;
		this.Call = function()
		 {
			cover_id = this.value;
			return obj.Enable();
		 };
		this.Set = function(val){obj = val;};
	 };
	images_cntr.find('input').each(function(){
		switch(this.name)
		 {
			case 'checked':
				boxes_check.push(this);
				this.onchange = save_checked_storage.Call;
				break;
			case 'cover':
				this.onchange = save_cover_storage.Call;
				has_cover = true;
				break;
			default:
				if('checkbox' == this.type && 'edit' == this.parentNode.className)
				 {
					boxes_delete.push(this);
					this.title = lang.ch_for_del();
					this.onclick = (new box_delete_click(boxes_delete)).exec;
				 }
		 }
	 });
	if(boxes_check.length) save_checked_storage.Set(save_group.AttachHandler(function(){
		var str = '';
		for(var i = 0; i < boxes_check.length; ++i) if(boxes_check[i].checked) str += (str ? '|' : '') + boxes_check[i].value;
		ms.post(str ? {'checklist':str} : {'parent_id':parent_id}, this.OnSuccess, 'check_items', lang.saved_checked());
	 }));
	if(has_cover) save_cover_storage.Set(save_group.AttachHandler(function()
	 {
		if(cover_id)
		 {
			var data = {id:cover_id};
			if(parent_id) data.parent_id = parent_id;
			ms.post(data, this.OnSuccess, 'set_cover_id', lang.saved_cover());
		 }
		else ms.AddWarningMsg(lang.choose_cover());
	 }));

	images_cntr.get(0).onSortBegin = function(elem)
	 {
		this.sortHelper.className = "outer helper";
		this.sortHelper.innerHTML = "<div class='plug'></div>";
		$(elem).css('opacity', 0.8);
	 };
	images_cntr.get(0).onSortEnd = function(elem){$(elem).css('opacity', 1);};
	sortable.makeSortable(images_cntr.get(0), {ondragover:function(elem, target)
	 {
		$(elem).css('opacity', 0.6);
		target.style.borderColor = '#45688e';
	 }, ondragout:function(elem, target)
	 {
		$(elem).css('opacity', 0.8);
		target.style.borderColor = '#ccc';
	 }, ondragend:function(elem, target)
	 {
		$(elem).css('opacity', 1);
		target.style.borderColor = '#ccc';
	 }});
	sortable.on_move_item = save_order.Enable;
	$('.form._load_image').submit(function()
	 {
		var input = $(this).find('input[type="file"][name="ext"]:not(:disabled)'), fname = input.val();
		if(!input.length) return true;
		if(IsEmpty(fname) || !MSUploader.CheckExt(fname, new Array('gif', 'jpg', 'jpeg', 'png'))) { ms.AddErrorMsg('Выберите jpeg-, gif- или png-файл!'); return false; }
		return true;
	 });
	$('#work_area form div.buttons input[name="select_all"]').click(function(){
		var boxes = images_cntr.find('.outer .frame div .edit input[type="checkbox"]');
		if(boxes.filter(':not(:checked)').length == boxes.length)
		 {
			boxes.prop('checked', true);
			$(this).addClass('all').val(lang.deselect());
		 }
		else
		 {
			boxes.prop('checked', false);
			$(this).removeClass('all partly').val(lang.select_all());
		 }
		boxes.each(function(){this.onclick();});
	 });
}

MSImages.Lang = {
edit:function(){return 'Редактировать';},
ch_for_del:function(){return 'Отметить для удаления';},
saved_order:function(){return 'Порядок сохранён.';},
saved_checked:function(){return 'Избранные фото отмечены.';},
saved_cover:function(){return 'Обложка выбрана.';},
choose_cover:function(){return 'Выберите обложку!';},
select_all:function(){return 'Отметить все';},
deselect:function(){return 'Убрать отметки';}
};

$(function(){
	new MSImages('sortalbum');
	switch($('.form._load_image input[name="__fsform_action"]').val())
	 {
		case 'insert':
			var fast_load_form = $('#fast_load_form'), parent_id = fast_load_form.find('input[name="parent_id"]'), upl = (new MSUploader('core.php', 'mfiles', '#fast_load_btn', function(){$('#load_many_wrapper').remove();})).SetTypes('image/jpeg,image/gif,image/png').SetExts('jpg', 'jpeg', 'gif', 'png').AddData('__mssm_action', 'fast_load').SetOnLoad(function(){location.reload();});
			if(parent_id.length) upl.AddData("parent_id", parent_id.val());
			$('#load_one_btn').click(function(){fast_load_form.attr('data-hidden', 1);});
			$('#load_many_btn').click(function(){fast_load_form.attr('data-hidden', 0);});
			break;
		case 'update': break;
	 }
});