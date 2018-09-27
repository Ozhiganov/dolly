$(function(){
$('.edit_pairs__row .delete_block').MSUIDeleteBlock({ondelete:function(){this.find('input[type="text"]').prop('disabled', true);}, onundo:function(){this.find('input[type="text"]').prop('disabled', false);}});
var row = $('.edit_pairs__row:nth-child(2)').clone(true), b_add = $('.smlinks__add'), p_row = b_add.parent();
b_add.click(function(){
	var r = row.clone(true), i = r.find('input[type="text"]').val('');
	p_row.before(r);
	i.filter('[name^="title"]').attr('name', 'title_new[]');
	i.filter('[name^="id"]').attr('name', 'id_new[]').focus();
});
if($('.edit_pairs__layout_name').length) $(document).on('input focus', '.edit_pairs__row [name^="id"]', function(){
	var i = $(this), n = i.nextAll('.edit_pairs__layout_name');
	n.text(n.attr('data-prefix') + i.val());
});
});