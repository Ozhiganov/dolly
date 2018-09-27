$(function(){
var row = $('[name="alt_image"]').parents('.form__row');
$('.msbanners_type input[type="radio"]').change(function(){if(this.checked) row["flash" == this.value ? 'removeClass' : 'addClass']('_hidden');}).last().change();
});