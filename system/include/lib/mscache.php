<?php
class MSCache extends MSDocument
{
	final public function Show()
	 {
		$size = $num_of_files = 0;
		foreach($this->dirs as $dir) $size += ms::GetDirSize(Page::GetStaticRoot().$dir, $num_of_files);
		$num_of_files_db = DB::COUNT('image_cache');
?><form action="core.php" method="post"><div>Размер кэша: <?=Format::RoundFileSize($size)?>, физически <?=$num_of_files.' файл'.Format::GetAmountStr($num_of_files, '', 'а', 'ов')?>, в базе данных <?=$num_of_files_db.' запис'.Format::GetAmountStr($num_of_files_db, 'ь', 'и', 'ей')?> о файлах <input class="msui_button _cancel" type="submit" value="Очистить" name="clear" /></div></form><?php
	 }

	final public function Handle()
	 {
		if(isset($_POST['clear']))
		 {
			set_time_limit(600);
			foreach($this->dirs as $dir) ms::rmdir(Page::GetStaticRoot().$dir);
			DB::Delete('image_cache', false);
		 }
	 }

	private $dirs = array('/f', '/fc', '/fctop', '/crop');
}
?>