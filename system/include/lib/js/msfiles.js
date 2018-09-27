$(function(){
var file = $('.form._msfiles input[type="file"][name="ext"]'), title = $('.form._msfiles [data-name="title"] input[type="text"]');
file.change(function(){if(IsEmpty(title.val())) title.val(ms.GetFileName(this.value).name);});
});