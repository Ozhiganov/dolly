<?php
class MSPHPInfo extends MSDocument
{
	final public function Show()
	 {
		$url = MSLoader::GetUrl();
?><div class="secondary_menu"><?php
		foreach(array(['selected' => empty($_GET['view']), 'title' => 'Основное', 'href' => ''],
					  ['selected' => INFO_CONFIGURATION == @$_GET['view'], 'title' => 'Конфигурация', 'href' => '?view='.INFO_CONFIGURATION],
					  ['selected' => INFO_MODULES == @$_GET['view'], 'title' => 'Модули', 'href' => '?view='.INFO_MODULES],
					  ['selected' => INFO_ENVIRONMENT == @$_GET['view'], 'title' => 'Переменные окружения', 'href' => '?view='.INFO_ENVIRONMENT],
					  ['selected' => INFO_VARIABLES == @$_GET['view'], 'title' => 'Предопределённые переменные EGPCS', 'href' => '?view='.INFO_VARIABLES],
					  ['selected' => 'all' == @$_GET['view'], 'title' => 'Всё', 'href' => '?view=all']) as $item)
		 print($item['selected'] ? "<span class='secondary_menu__item _selected'>$item[title]</span>" : "<a href='$url$item[href]' class='secondary_menu__item'>$item[title]</a>");
?></div><?php
		$this->AddCSS('lib.phpinfo');
		switch(@$_GET['view'])
		 {
			case 'all': $what = INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES | INFO_ENVIRONMENT | INFO_VARIABLES; break;
			case INFO_CONFIGURATION:
			case INFO_MODULES:
			case INFO_ENVIRONMENT:
			case INFO_VARIABLES:
			case INFO_GENERAL: $what = $_GET['view']; break;
			default: $what = INFO_GENERAL;
		 }
		ob_start();
		phpinfo($what);
		$content = ob_get_contents();
		ob_end_clean();
		$pos_1 = mb_strpos($content, '<body>') + 6;
		$pos_2 = mb_strrpos($content, '</body>');
		print(mb_substr($content, $pos_1, $pos_2 - $pos_1, 'utf-8'));
	 }

	final public function Handle()
	 {
		
	 }
}
?>