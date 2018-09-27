<?php
class SystemUpdatesManager
{
	use TOptions, TSystemMessages;

	const REGEX_VERSION = '/^([0-9]+)\.([0-9]+)\.([0-9]+)( beta)?$/';
	const REGEX_PID = '/^[a-z0-9]+[a-z0-9_-]+[a-z0-9]+$/';

	public function __construct(array $o)
	 {
		$this->AddOptionsMeta(['precheck' => ['type' => 'callback,null'], 'products' => ['type' => 'array', 'value' => []], 'replacements' => ['type' => 'array', 'value' => []], 'root' => ['type' => 'string']]);
		$this->SetOptionsData($o);
	 }

	final public static function CheckVersion($name, &$m)
	 {
		return preg_match('/^([a-z0-9_-]{4,64})\.(([0-9]+)\.([0-9]+)\.([0-9]+))$/', $name, $m);// - is instead of \
	 }

	final public static function VerCmp($ver1, $ver2)
	 {
		$func = function($v){
			if(preg_match(self::REGEX_VERSION, $v, $m))
			 {
				$m[4] = empty($m[4]) ? 1 : 0;
				return $m;
			 }
		};
		$m1 = $func($ver1);
		$m2 = $func($ver2);
		if(null === $m1 || null === $m2) return null;
		for($i = 1; $i <= 4; ++$i)
		 {
			if($m1[$i] < $m2[$i]) return -1;
			if($m1[$i] > $m2[$i]) return 1;
		 }
		return 0;
	 }

	final public function Run()
	 {
		$status = MSConfig::IsSecured() && ($product_id = $this->GetPID()) ? $this->HandleRequest($product_id) : false;
		HTTP::Status($status ?: 400);
	 }

	final private function GetPID(stdClass &$row = null)
	 {
		if(empty($_GET['product_id']) || !preg_match(self::REGEX_PID, $_GET['product_id'])) return;
		if(($opt = $this->GetOption('products')) && empty($opt[$_GET['product_id']])) return;
		if($row = \DB()->GetRowByKey('product', 'code', $_GET['product_id'])) return $_GET['product_id'];
	 }

	final private function HandleRequest($product_id)
	 {
		if('1' === @$_GET['step'])
		 {
			$v = $_GET['version'];
			if(preg_match(self::REGEX_VERSION, $v))
			 {
				if(true !== ($st = $this->PreCheck(1, $product_id, null, $v))) return $st;
				list($major, $minor, $build) = explode('.', $v);
				$cnd_0 = '`v`.`channel_id` = :channel_id';
				$cnd = ' AND `p`.`code` = :pid AND `v`.`release_date` IS NOT NULL AND `min_v`.`release_date` IS NOT NULL AND (100 * 100 * :major + 100 * :minor + :build BETWEEN 100 * 100 * `min_v`.`major` + 100 * `min_v`.`minor` + `min_v`.`build` AND 100 * 100 * `v`.`major` + 100 * `v`.`minor` + `v`.`build`)';
				$prm = ['channel_id' => $this->GetChannelId(), 'pid' => $product_id, 'major' => $major, 'minor' => $minor, 'build' => $build];
				if('default' === $prm['channel_id']) $cnd = $cnd_0.$cnd;
				else
				 {
					$cnd = "($cnd_0 OR `v`.`channel_id` = :channel_id_0) $cnd";
					$prm['channel_id_0'] = 'default';
				 }
				$res = DB::SelectLJ([
							'v' => ['product_version', 'min_ver,compatible'],
							'min_v' => ['product_version', 'major,minor,build', '`min_v`.`id` = `v`.`min_ver`'],
							'p' => ['product', 'title,url', '`p`.`id` = `v`.`product_id`'],
						],
						'CONCAT_WS(".", `v`.`major`, `v`.`minor`, `v`.`build`) AS `number`', $cnd, $prm, '`v`.`major` DESC, `v`.`minor` DESC, `v`.`build` DESC', ['limit' => 1]);
				if(count($res))
				 {
					$curr_v = $res->Fetch();
					$cmp = self::VerCmp($v, $curr_v->number);
					if(null === $cmp) self::SendJSON([], 'Не получен список обновлений.', 'error');
					elseif($cmp < 0)
					 {
						$data['product_id'] = $product_id;
						$data['items'] = [];
						$item = ['name' => 'core', 'version' => $curr_v->number, 'compatible' => $curr_v->compatible, 'title' => $curr_v->p__title.', ядро системы', 'url' => $curr_v->p__url];//!!! откуда берётся core???
						if($curr_v->compatible < 0) $item['info'] = "Новая версия $curr_v->number не обладает обратной совместимостью. Возможно полное или частичное прекращение работы этого сайта.";
						$data['items'][] = $item;
						self::SendJSON($data);
					 }
				 }
				self::SendJSON([], 'Нет доступных обновлений.', 'warning');
			 }
		 }
		elseif('2' === @$_GET['step'])
		 {
			if(!empty($_GET['item']) && self::CheckVersion($_GET['item'], $m))
			 {
				if('core' === $m[1])//!!! откуда это должно браться? в БД пока что нет такой информации
				 {
					if(true !== ($st = $this->PreCheck(2, $product_id, $m[1], $m[2]))) return $st;
					$cnd_0 = '`v`.`channel_id` = ?';
					$cnd = ' AND `v`.`release_date` IS NOT NULL AND `p`.`code` = ? AND `v`.`major` = ? AND `v`.`minor` = ? AND `v`.`build` = ?';
					$prm = [$this->GetChannelId(), $product_id, $m[3], $m[4], $m[5]];
					if('default' === $prm[0]) $cnd = $cnd_0.$cnd;
					else
					 {
						$cnd = "($cnd_0 OR `v`.`channel_id` = ?) $cnd";
						array_unshift($prm, 'default');
					 }
					$res = DB::SelectLJ([
								'v' => ['product_version', 'id,dynamic'],
								'p' => ['product', 'code', '`p`.`id` = `v`.`product_id`'],
							],
							false, $cnd, $prm);
					if(count($res))
					 {
						$version = $res->Fetch();
						$fname = $this->GetOption('root')."/$version->p__code/$_GET[item]";
						if($version->dynamic)
						 {
							MSConfig::RequireFile('datacontainer', 'msarchive');
							$a = new MSArchive($this->GetOption('root')."/$version->p__code", ['attachment' => $m[0], 'replacements' => $this->GetOption('replacements')]);
							$a->AddFromDir($fname);
							$a->Compress();
						 }
						else
						 {
							$fname .= '.tar.gz';
							if(file_exists($fname))
							 {
								header('Content-Type: application/gzip');
								header('Content-Disposition: attachment; filename="'.$m[0].'.tar.gz"');
								header('Content-Length: '.filesize($fname));
								readfile($fname);
								die();
							 }
						 }
					 }
					return 404;
				 }
			 }
		 }
	 }

	final private function PreCheck($step, $product_id, $item_id, $version)
	 {
		if($c = $this->GetOption('precheck'))
		 {
			$data = new stdClass;
			$st = call_user_func($c, $step, $product_id, $item_id, $version, $data);
			if(is_string($st)) self::SendJSON($data, $st, 'error');
			return $st;
		 }
		return true;
	 }

	final private function GetChannelId() { return !empty($_GET['channel_id']) && preg_match('/^[a-z0-9]{1,31}$/', $_GET['channel_id']) ? $_GET['channel_id'] : 'default'; }
}
?>