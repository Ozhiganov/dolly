<?php
class MSSEDomains extends MSDocument
{
	final public function Show()
	 {
		if(!file_exists($fname = $_SERVER['DOCUMENT_ROOT'].'/.htaccess')) return print(ui::WarningMsg('Файл .htaccess не существует.'));
		if(!file_exists($kfname = $_SERVER['DOCUMENT_ROOT'].self::KEY_FILE)) return print(ui::WarningMsg('Ключ не существует.'));
		$this->AddCSS('lib.msse_domains')->AddJS('lib.msse_domains');
		$content = file_get_contents($fname);
		if(preg_match_all(self::REGEX, $content, $matches))
		 {
			if(!empty($matches[3])) $_POST['main_domain'] = $matches[3][0];
			foreach($matches[1] as $m) if(false === strpos($m, 'www\.')) $_POST['domains'][] = str_replace('\.', '.', $m);
		 }
		if(!empty($_POST['main_domain'])) $this->PutInArrays($this->main_domain_raw, $this->domains, $this->rootfile, $this->systemfile);
		$html = '<div class="form__row _no_label"><div class="strict_warning">Всегда указывайте основной и дополнительные домены,<br />иначе сайт будет работать неправильно!</div></div>
<div class="form__row"><label class="form__label _textarea">Ключи:</label><textarea name="key" class="msui_textarea _keys">'.file_get_contents($kfname).'</textarea></div>';
		if(!$this->EqRoots()) $html .= '<div class="form__row"><label class="form__label">Ключи<br/> &laquo;статического&raquo; домена:</label>'.(false === ($skey = $this->GetSKey($err_msg)) ? "<div class='err_msg'>$err_msg</div>" : "<textarea name='static_key' class='msui_textarea _keys'>$skey</textarea>").'</div>';
		$html .= $this->MakeFormRow('main_domain', $this->GetMainDomain(), 'Основной домен', 'Добавить домен', 'add', 'add_domain');
		if($domains = $this->GetDomains()) foreach($domains as $domain) $html .= $this->MakeFormRow($domain['name'], $domain['domain']);
		$html .= $this->MakeFormRow('domains[]').'<div class="form__row"><label class="form__label">&laquo;Статический&raquo; домен:</label><input type="text" name="static_host" class="msui_input" value="'.Page::GetStaticHost('').'" /></div>
<div class="form__row _no_label"><input type="checkbox" name="no_replacement" value="1" id="no_replacement" />&nbsp;<label for="no_replacement">не заменять в основном шаблоне</label></div>';
		echo ui::Form()->SetCaption('Домены')->SetMiddle("<fieldset>$html</fieldset>")->SetBottom(ui::Submit('value', 'Сохранить').'<input type="hidden" name="adddom" value="1" />');
	 }

	final public function Handle()
	 {
		if($key = trim(@$_POST['key'])) file_put_contents($_SERVER['DOCUMENT_ROOT'].self::KEY_FILE, $key);
		if(($skey = trim(@$_POST['static_key'])) && ($this->GetSKey($tmp, $fname) !== false)) file_put_contents($fname, $skey);
		if($main_domain = trim(@$_POST['main_domain']))
		 {
			if(file_exists($fname = $_SERVER['DOCUMENT_ROOT'].'/.htaccess') && isset($_POST['adddom']))
			 {
				$this->PutInArrays($this->main_domain_raw, $this->domains, $this->rootfile, $this->systemfile);
				$this->Subst($_SERVER['DOCUMENT_ROOT'].'/.htaccess', $this->GetRootFile());
				$this->Subst($_SERVER['DOCUMENT_ROOT'].'/system/.htaccess', $this->GetSystemFile());
				if(empty($_POST['no_replacement']) && self::ReplaceStatic(trim($_POST['static_host']))) $this->AddSuccessMsg('Основной шаблон сайта изменён.');
				$this->AddSuccessMsg('Изменения сохранены.');
			 }
		 }
		else $this->AddErrorMsg('Укажите основной домен!');
	 }

	final public static function ReplaceStatic($host, $old_host = false, $fname = false)
	 {
		$idn = new idna_convert();
		$static_host = $idn->encode($host);
		if(!$fname) $fname = $_SERVER['DOCUMENT_ROOT'].'/html/html.html';
		$src = file_get_contents($fname);
		$dest = $old_host ? str_replace($old_host, $host, $src, $count) : preg_replace('/\/\/static\.(.+?)\//i', "//$static_host/", $src, -1, $count);
		if(sha1($src) != sha1($dest))
		 {
			file_put_contents($fname, $dest);
			return $count;
		 }
	 }

	final protected function GetSKey(&$err_msg = null, &$fname = null)
	 {
		$fname = Page::GetStaticRoot().self::KEY_FILE;
		if(file_exists($fname)) return file_get_contents($fname);
		$err_msg = file_exists(Page::GetStaticRoot()) ? 'Не удалось открыть файл ключа &laquo;статического&raquo; домена!' : '&laquo;Статический&raquo; корень не существует!';
		return false;
	 }

	final protected function MakeFormRow($name, $value = false, $lbl = 'Дополнительный домен', $btn_caption = 'Удалить', $btn_class = 'delete', $btn_id = null)
	 {
		$idn = new idna_convert();
		return '<div class="form__row'.(false === $value ? ' _prototype' : '').'"><label class="form__label">'.$lbl.':</label><input type="text" maxlength="50" class="msui_input" value="'.$idn->decode($value).'" name="'.$name.'" />
<input type="button" class="msui_small_button _icon _'.$btn_class.'"'.($btn_id ? ' id="'.$btn_id.'"' : '').' value="'.$btn_caption.'" /></div>';
	 }

	final protected function MakeRule1($from, $whole = true) { return 'RewriteCond %{HTTP_HOST} '.($whole ? '^' : '').str_replace('.', '\.', $from).'$'; }
	final protected function MakeRule2($to) { return 'RewriteRule ^(.*)$ http://'.$to.'/$1 [R=permanent,L]'; }
	 
	final protected function PutInArrays(&$main_domain_raw, &$domains, &$rootfile, &$systemfile)
	 {
		$rootfile = $systemfile = array();
		$idn = new idna_convert();
		$main_domain_raw = trim($_POST['main_domain']);
		$main_domain = $idn->encode($main_domain_raw);
		$rootfile[] = $this->MakeRule1('www.'.$main_domain);
		$rootfile[] = $this->MakeRule2($main_domain);
		$systemfile[] = $this->MakeRule1('www.'.$main_domain);
		$systemfile[] = $this->MakeRule2($main_domain.'/system');
		if(isset($_POST['domains']))
		 {
			foreach($_POST['domains'] as $domain)
			 {
				$domain_raw = trim($domain);
				if($domain_raw && !isset($domains[$domain_raw]) && $domain_raw != $main_domain_raw)
				 {
					$domain = $idn->encode($domain_raw);
					$rootfile[] = '';
					$rootfile[] = $this->MakeRule1($domain);
					$rootfile[] = $this->MakeRule2($main_domain);
					$rootfile[] = '';
					$rootfile[] = $this->MakeRule1('www.'.$domain);
					$rootfile[] = $this->MakeRule2($main_domain);
					$systemfile[] = '';
					$systemfile[] = $this->MakeRule1($domain, false);
					$systemfile[] = $this->MakeRule2($main_domain.'/system');				
					$domains[$domain_raw] = array('domain' => $domain_raw, 'name' => 'domains[]');
				 }
			 }
		 }
	 }

	final protected function Subst($fname, $list)
	 {
		// $fname = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';
		$content = file_get_contents($fname);
		file_put_contents($fname, preg_replace(self::REGEX, '', $content));
		$first = 0;
		// $f = fopen($fname, 'rt');
		$lines = file($fname);
		$lines = array_map('trim', $lines);
		// print('<pre>');var_dump($lines);die;
		/* fclose($f);
		foreach($lines as $i=>$line)
		 {
			if(strpos($line, 'RewriteCond %{HTTP_HOST}') !== false) 
			 {
				if($p = strpos($line, '^www\.')) $first = $last = $i;
				else $last = $i;
			 }
		 }
		if(!$first)  */
		foreach($lines as $i => $line)
		 {
			if(strpos($line, 'Options +FollowSymlinks') !== false)
			 {
				$first = $i + 1;
				array_unshift($list, '');
				break;
			 }
		 }
		// else $last += 2;	
		$lines1 = array_slice($lines, 0, $first);
		$lines2 = array_slice($lines, $first);
		$lines = array_merge($lines1, $list, $lines2);
		file_put_contents($fname, implode(PHP_EOL, $lines));
	 }
	
	final protected function GetMainDomain() { return $this->main_domain_raw; }
	final protected function GetDomains() { return $this->domains; }
	final protected function GetRootFile() { return $this->rootfile; }
	final protected function GetSystemFile() { return $this->systemfile; }
	final protected function EqRoots() { return Page::GetStaticRoot() == $_SERVER['DOCUMENT_ROOT']; }
	
	private $main_domain_raw;
	private $domains = array();
	private $rootfile = array();
	private $systemfile = array();

	const KEY_FILE = '/system/include/key.php';
	const REGEX = '|\s*RewriteCond\s+%{HTTP_HOST}\s+\^?(([a-z0-9-]+\\\.)+[a-z0-9-]+)\$\s+RewriteRule\s+\^\(\.\*\)\$\s+http://(.*)/\$1\s+\[R=permanent,\s*L\]|im';
}
?>