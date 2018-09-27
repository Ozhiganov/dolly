function SetDaysCount(sender)
{
 var days = sender.parentNode.childNodes[0];
 var months = sender.parentNode.childNodes[1];
 var years = sender.parentNode.childNodes[2];
 var days_in_month;
 var month = parseInt(months.value);
 if(!month)
  {
	days.disabled = true;
	return;
  }
 switch(month)
  {
   case 1:
   case 3:
   case 5:
   case 7:
   case 8:
   case 10:
   case 12: days_in_month = 31; break;
   case 4:
   case 6:
   case 9:
   case 11: days_in_month = 30; break;
   case 2: days_in_month = (years.options[years.selectedIndex].value % 4 == 0) ? 29 : 28; break;
  }
 var max_day = parseInt(days.lastChild.value);
 if(max_day < days_in_month)
  for(var i = max_day + 1; i <= days_in_month; ++i)
   {
	var opt = document.createElement('option');
	opt.appendChild(document.createTextNode(i));
	days.appendChild(opt);
   }
 else if(max_day > days_in_month) while(parseInt(days.lastChild.value) > days_in_month) days.removeChild(days.lastChild);
 days.disabled = false;
}

function SetYears(sender)
{
 var curr_val = parseInt(sender.value);
 if(!curr_val) return;
 var max_val = parseInt(sender.lastChild.value);
 var min_val = parseInt(sender.firstChild.value);
 if(isNaN(min_val) || !min_val) min_val = parseInt(sender.childNodes[1].value);
 var diff = max_val - curr_val;
 if(diff < 2) for(var i = 0; i < 2 - diff; ++i)
  {
	var opt = document.createElement('option');
	opt.appendChild(document.createTextNode(max_val + i + 1));
	sender.appendChild(opt);
	sender.removeChild(parseInt(sender.firstChild.value) ? sender.firstChild : sender.childNodes[1]);
  }
 diff = curr_val - min_val;
 if(diff < 2) for(var i = 0; i < 2 - diff; ++i)
  {
	var opt = document.createElement('option');
	opt.appendChild(document.createTextNode(min_val - i - 1));
	sender.insertBefore(opt, parseInt(sender.firstChild.value) ? sender.firstChild : sender.childNodes[1]);
	sender.removeChild(sender.lastChild);
  }
}

function InitDateSelects()
{
 var spans = document.getElementsByTagName('span');
 for(i = 0; i < spans.length; ++i) if(spans[i].className == 'd_sel') spans[i].childNodes[2].onchange();
}

$(InitDateSelects);