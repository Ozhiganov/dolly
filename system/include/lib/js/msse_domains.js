$(function(){
var proto = $('.form__row._prototype'), delete_row = function()
 {
	var row = $(this.parentNode);
	if(!IsEmpty(row.find(':text').val()) && !confirm('Удалить?')) return;
	row.remove();
 };
$('.msui_small_button._delete').click(delete_row);
$('#add_domain').click(function()
 {
	var clone = proto.clone().insertBefore(proto).removeClass('_prototype');
	clone.find('.msui_small_button._delete').click(delete_row);
	clone.find('input').attr('name', "domains[]").focus();
 });
$('[name="main_domain"]').bind('keyup change', function(){$('[name="adddom"]').prop('disabled', IsEmpty(this.value));}).change();
$('#no_replacement').change(function(){$('input[name="static_host"]').prop('disabled', this.checked);});
});