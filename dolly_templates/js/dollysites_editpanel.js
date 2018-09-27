(function($){
	$.fn.removeClassFull = function(c){
	    this.removeClass(c);
		if(this.attr('class') === '') this.removeAttr('class');
		return this;
	};
})(jQuery);
$(function(){
var page_cnt = $("iframe[name='page_container']"), body = $('body');
$('<div id="nicEditor"></div><div class="dolly_editor_elements"></div><button type="button" class="de_save msui_button toolbar__button _save">' + SAVE + '</button>').appendTo('.toolbar__row._2');
$('[type="button"].de_save').click(function(){
	var doc = page_cnt.get(0).contentWindow.document, q = doc.location.pathname + doc.location.search, b = $(this).addClass('_loading');
	doc.body.removeAttribute('spellcheck');
	doc.body.removeAttribute('contenteditable');
	$.post('/admin.php?action=save_page', {page:doc.documentElement.outerHTML, path:q}, function(){
		doc.body.setAttribute('spellcheck', 'false');
		doc.body.setAttribute('contenteditable', 'true');
	}).always(function(){b.removeClass('_loading');});
});
document.getCaret = function(el){
	if(el.selectionStart) { return el.selectionStart; }
	else if(document.selection)
	 {
		el.focus();
		var r = document.selection.createRange();
		if(r == null) {return 0;}
		var re = el.createTextRange(), rc = re.duplicate();
		re.moveToBookmark(r.getBookmark());
		rc.setEndPoint('EndToStart', re);
		return rc.text.length;
	 }
	return 0;
};
document.insertTextAtCursor = function(el, text, offset){
	var val = el.value, endIndex, range, doc = el.ownerDocument;
	if(typeof el.selectionStart == "number" && typeof el.selectionEnd == "number")
	 {
		endIndex = el.selectionEnd;
		el.value = val.slice(0, endIndex) + text + val.slice(endIndex);
		el.selectionStart = el.selectionEnd = endIndex + text.length + (offset ? offset : 0);
	 }
	else if(doc.selection != "undefined" && doc.selection.createRange)
	 {
		el.focus();
		range = doc.selection.createRange();
		range.collapse(false);
		range.text = text;
		range.select();
	 }
};
page_cnt.on('load', function(){
	var dom_doc = page_cnt.get(0).contentWindow.document, doc = $(dom_doc), page_body = $(dom_doc.body).attr({spellcheck: 'false'}), nic = new nicEditor({fullPanel : true});
	nic.setPanel('nicEditor');
	nic.addInstance(dom_doc.body);
	document.currentElement = false;

	function printBottomInfo(element){
		var el = $('.dolly_editor_elements').html('<span class="de_navtag" id="currentEl" style="font-weight:bold">' + $(element).prop('tagName') + '</span>');
		$(element).parents().each(function(i){
			if(this.tagName == 'HTML' || this.tagName == 'BODY') return;
			var eClass = (this.className === '') ? '' : '.' + this.className, eId = (this.id === '') ? '' : '#' + this.id, string = eId + (eId !== '' && eClass !== '' ? ', ' : '') + eClass;
			if('' === string) string = '&nbsp;';
			el.html('<span class="de_navtag" id="' + i + '" title="' + string + '">' + this.tagName + '</span> </div> <span class="de_navgt">&gt;</span> ' + el.html());
		});
	}

	// ПАНЕЛЬ: навигация по тегам, навели курсор на тег
	/* body.on('mouseenter', '.de_navtag', function(event) {
		var element = $($(document.currentElement).parents()[event.target.id]);
		
		$(element).addClass('dollyeditor_select');
	}); */
	
	// ПАНЕЛЬ: навигация по тегам, убрали курсор с тега
	// $('body').on('mouseout', '.de_navtag', function(event) {
		// var element = $($(document.currentElement).parents()[event.target.id]);

		// $(element).removeClassFull('dollyeditor_select');
	// });

	doc.click(function(event){
		var target = $(event.target);
/* 		// очищаем навигацию в панели, если кликнули по элементу, который относится к панели
		if(target.is('#dollyeditor_top') || target.is('#dollyeditor_bottom') || target.is('#dollyeditor_codeblock') || target.is('#dollyeditor_replaceblock') || target.parents('#dollyeditor_top').length || target.parents('#dollyeditor_bottom').length || target.parents('#dollyeditor_codeblock').length || target.parents('#dollyeditor_replaceblock').length) {
			$('#dollyeditor_bottom .de_element').html('');
			return;
		} */
		if(event.target.tagName == 'A') event.preventDefault();
		printBottomInfo(event.target);// выводим панель навигации по тегам
		document.currentElement = event.target;
	});
	$(window).triggerHandler('resize');
});
body.on('click', '.de_navtag', function(event){
	document.currentNavElement = (event.target.id === 'currentEl') ? document.currentElement : $(document.currentElement).parents()[event.target.id];
	document.currentNavElementOriginal = document.currentNavElement.outerHTML;
	// $(document.currentNavElement).removeClassFull('dollyeditor_select');
	$('#dollyeditor_codeblock').remove();
	body.append('<div id="dollyeditor_codeblock" class="dollyeditor_codeblock" contenteditable="false" unselectable="on">' +
'<textarea></textarea>' +
'<div id="de_codeblock_manage1">' +
'<span id="de_codeblock_toreplace">' + APPEND + '</span>' +
'</div>' +
'<div id="de_codeblock_manage2">' +
'<span id="de_codeblock_cancel">' + CANCEL + '</span>&nbsp;&nbsp;' +
'<span id="de_codeblock_save">' + SAVE + '</span>' +
'</div>' +
'</div>');
	$('#dollyeditor_codeblock textarea').text(document.currentNavElement.outerHTML);
	event.stopPropagation();
})
.on('click', '#de_replaceblock_cancel', function(event){
	$('#dollyeditor_replaceblock').remove();
	event.stopPropagation();
})
.on('click', '#de_codeblock_cancel', function(event){
	document.currentNavElement.outerHTML = document.currentNavElementOriginal;
	$('#dollyeditor_codeblock').remove();
	event.stopPropagation();
})
.on('click', '#de_codeblock_save', function(event){
	$('#dollyeditor_codeblock').remove();
	event.stopPropagation();
})
.on('keyup', '#dollyeditor_codeblock textarea', function(event){
	var textarea = $('#dollyeditor_codeblock textarea')[0];
	if(event.which == 187)// если в textarea ввели "="
	 {
		document.insertTextAtCursor(textarea, '""');
		textarea.selectionStart = textarea.selectionEnd = document.getCaret(textarea) - 1;
	 }
	event.stopPropagation();
})
.on('input', '#dollyeditor_codeblock textarea', function(event){
	var tag = document.currentNavElement.tagName, newElement = $('<sometag>' + $('#dollyeditor_codeblock textarea').val() + '</sometag>').find(tag).first().addClass('dollyeditor_newelement'), doc = $(document.currentElement.ownerDocument);
	$(document.currentNavElement).replaceWith(newElement);
	document.currentNavElement = doc.find('.dollyeditor_newelement').removeClassFull('dollyeditor_newelement')[0];
	event.stopPropagation();
})
.on('click', '#de_codeblock_toreplace', function(event){
	body.append('<div id="dollyeditor_replaceblock" class="dollyeditor_codeblock" contenteditable="false" unselectable="on" style="text-align: center;">' +
		'<textarea class="de_replaceblock_textarealeft"></textarea>' +
		'<span style="position:relative;margin:5px;bottom:75px;">'+ON+'</span>' +
		'<textarea class="de_replaceblock_textarearight"></textarea>' +
		'<span id="de_replaceblock_add1" style="float:right; cursor:pointer; padding-right:7px; padding-top:3px; color:#5a77d1;">' +
		ADD_TO_REPLACES + '</span><br>' +
		'<div style="margin-top:40px;"></div>' +
		'<br><span id="de_replaceblock_add2" style="cursor:pointer;color:#5a77d1;">'+ CUT +'</span>' +
		'<br><br><br>' +
		'<span id="de_replaceblock_cancel" style="cursor:pointer;float:left;color:#5a77d1;">'+BACK+'</span></div>');
	$('.de_replaceblock_textarealeft').html(document.currentNavElementOriginal);
	$('.de_replaceblock_textarearight').html(document.currentNavElement.outerHTML);
	event.stopPropagation();
})
.on('click', '#de_replaceblock_add1', function(event){
	$.post('/admin.php?action=add_replacement&sub=editor',
		{
			'out[1][l_input]': $('.de_replaceblock_textarealeft').val(),
			'out[1][l_textarea]': $('.de_replaceblock_textarealeft').val(),
			'out[1][r_input]': $('.de_replaceblock_textarearight').val(),
			'out[1][r_textarea]': $('.de_replaceblock_textarearight').val(),
		}, function(){
			$('#de_replaceblock_add1').html(SUCCESS).css({color: 'green'});
			setTimeout(function() {$('#dollyeditor_codeblock, #dollyeditor_replaceblock').remove();}, 1000);
		});
	// Добавить в замены
	// передать аяксом $('.de_replaceblock_textarealeft').val() - это "что заменить", и $('.de_replaceblock_textarearight').val() - это "на что заменить" 
	// после этого выполнить:
	event.stopPropagation();
})
.on('click', '#de_replaceblock_add2', function(event){ // Вырезать на всех страницах (заменить на " ")
	// передать аяксом document.currentNavElementOriginal
	// после этого выполнить:
	$.post('/admin.php?action=add_replacement&sub=editor',
		{
			'out[1][l_input]'   : document.currentNavElementOriginal,
			'out[1][l_textarea]': document.currentNavElementOriginal,
			'out[1][r_input]'   : '',
			'out[1][r_textarea]': '',
		}, function(){
			$('#de_replaceblock_add2').html(SUCCESS).css({color: 'green'});
			setTimeout(function() {$('#dollyeditor_codeblock, #dollyeditor_replaceblock').remove();}, 1000);
		});
	event.stopPropagation();
});
});