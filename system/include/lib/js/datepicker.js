$(function(){
var uncheck = function(){$(this).nextAll('.set_date_null').children('input[type="checkbox"]').prop('checked', false);};
$('.msui_input._date._autoinit').each(function(){
	var o = {changeMonth:true, changeYear:true, onSelect:function(t_date, dp){
		this.previousSibling.value = [dp.selectedYear, dp.selectedMonth + 1, dp.selectedDay].join('-');
		uncheck.call(this);
	}}, n = $(this), tmp, m, p = {'data-min':'minDate', 'data-max':'maxDate'};
	for(var i in p) if(tmp = n.attr(i)) o[p[i]] = (m = (/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/).exec(tmp)) ? new Date(m[1], parseInt(m[2]) - 1, m[3]) : tmp;
	n.datepicker(o);
}).nextAll('.msui_input._time').change(uncheck);
});