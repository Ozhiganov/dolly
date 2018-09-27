function Transliterate_RU_EN(str, r)
{
	var match = {'а':'a', 'б':'b', 'в':'v', 'г':'g', 'д':'d', 'е':'e', 'ж':'g', 'з':'z', 'и':'i', 'й':'y', 'к':'k', 'л':'l', 'м':'m', 'н':'n', 'о':'o', 'п':'p', 'р':'r', 'с':'s', 'т':'t', 'у':'u', 'ф':'f', 'ы':'i', 'э':'e', 'ё':'yo', 'х':'h', 'ц':'ts', 'ч':'ch', 'ш':'sh', 'щ':'shch', 'ъ':'', 'ь':'', 'ю':'yu', 'я':'ya'},
		tmp = str.toLowerCase().replace(/\W/g, function(a){return ((match[a] != undefined) ? match[a] : '-')});
	if(r) tmp = r(tmp);
	return tmp.replace(/-{2,}/g, '-').replace(/^-/, '').replace(/-$/, '');
}