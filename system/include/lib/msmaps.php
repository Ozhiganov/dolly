<?php
class MSMaps extends MSDocument
{
	final public static function ConfigOptions(MSOptions $o)
	 {
		$o->AddHeader('Настройки карт');
		$o->AddOption('msmaps', 'default_point', 'string', 'Центр карты по умолчанию', 'select', Relation::Get('map_default_point')->Select(array('title', 'CONCAT_WS("|", `lng`, `lat`) AS `value`'), null, '`title` ASC'), 'FetchAssoc', 'value', 'title', '', '&mdash;');
		return $o;
	 }

	final public function __construct($prefix = '')
	 {
		$this->prefix = $prefix;
	 }

	final public function Show()
	 {
		MSConfig::RequireFile('multiselect3');
		$this->AddJSLink('ymaps')
			 ->AddJS('lib.msmaps', 'lib.multiselect3', 'lib.base64', 'lib.msdndmanager')
			 ->AddCSS('lib.msmaps', 'lib.multiselect3');
		$this->groups = DB::Select($this->GetGroupTblName(), '*', false, null, '`title` ASC');
		$res = DB::Select($this->GetTblName(), '*', false, null, $this->GetOrder());
?><div class="nav"><input type="button" class="msui_small_button _icon _add add_msmap" value="Добавить карту" /><input type="button" class="b_state _collapse" title="Свернуть все" /><input type="button" class="b_state _expand" title="Развернуть все" /></div><div id="maps"><?php
		if(count($res)) foreach($res as $row) print($this->MkBlock($row));
?></div><?php
		print($this->MkBlock());
	 }

	final public function Handle()
	 {
		ms::UpdatePos($this->GetTblName());
		switch($this->ActionPOST())
		 {
			case 'add':
				$p = Registry::GetValue('msmaps', 'default_point');
				if(!($p && ($p = array_filter(explode('|', $p), 'is_numeric')) && 2 === count($p))) $p = [0, 0];
				$col = DB::GetColMeta($this->GetTblName(), 'zoom');
				$id = DB::Insert($this->GetTblName(), ['lng' => $p[0], 'lat' => $p[1]]);
				self::SendJSON(['id' => $id, 'lng' => $p[0], 'lat' => $p[1], 'zoom' => $col->default], 'Карта добавлена.');
			case 'save':
				if($id = Filter::NumFromPOST('id'))
				 {
					$attrs = ['~id' => $id, 'title' => trim($_POST['title']), 'type' => $_POST['type']];
					if(!is_null($lat = Filter::GetFloatOrNull(@$_POST['lat']))) $attrs['lat'] = $lat;
					if(!is_null($lng = Filter::GetFloatOrNull(@$_POST['lng']))) $attrs['lng'] = $lng;
					if(!is_null($zoom = Filter::GetIntOrNull(@$_POST['zoom']))) $attrs['zoom'] = $zoom;
					DB::Update($this->GetTblName(), $attrs, '`id` = :id');
					$cnd = '(`map_id` = :map_id)';
					$prm = ['map_id' => $id];
					if(empty($_POST['points'])) DB::Delete($this->GetPointTblName(), $cnd, $prm);
					else
					 {
						$pt_ids = [];
						foreach($_POST['points'] as $pt) if(isset($pt['id']) && is_numeric($pt['id'])) $pt_ids[$pt['id']] = $pt['id'];
						if($pt_ids)
						 {
							MSConfig::RequireFile('msdb.sql');
							$cnd .= new \MSDB\SQL\IN($pt_ids, ['indexes' => 'to_string', 'expr' => " AND `id` NOT"], $prm);
						 }
						DB::Delete($this->GetPointTblName(), $cnd, $prm);
						foreach($_POST['points'] as $pt)
						 {
							$attrs = ['map_id' => $id, 'lat' => (float)$pt['lat'], 'lng' => (float)$pt['lng']];
							if(!empty($pt['preset'])) $attrs['preset'] = $pt['preset'];
							if(!empty($pt['ballooncontent'])) $attrs['ballooncontent'] = $pt['ballooncontent'];
							if(isset($pt['id']) && is_numeric($pt['id'])) DB::UpdateById($this->GetPointTblName(), $attrs, $pt['id']);
							else DB::Insert($this->GetPointTblName(), $attrs);
						 }
					 }
					$p = ['map_id' => $id];
							if(empty($_POST['group_id']))
							 {
								DB::Delete($this->GetJunctionTblName(), '`map_id` = :map_id', $p);
							 }
							else
							 {
								foreach($_POST['group_id'] as $g_key => $g_id) DB::Replace($this->GetJunctionTblName(), ['map_id' => $id, 'group_id' => $g_id]);
								DB::Delete($this->GetJunctionTblName(), new \MSDB\SQL\IN($_POST['group_id'], ['indexes' => 'to_string', 'expr' => '`map_id` = :map_id AND `group_id` NOT'], $p), $p);
							 }
					self::SendJSON(null, 'Изменения сохранены.');
				 }
				else throw new EDocumentHandle('Неправильный идентификатор карты.');
			case 'delete':
				if($id = Filter::NumFromPOST('id'))
				 {
					DB::Delete($this->GetTblName(), '`id` = ?', [$id]);
					self::SendJSON(null, 'Карта удалена.');
				 }
				else throw new EDocumentHandle('Неправильный идентификатор карты.');
			default:
		 }
	 }

	final protected function MkBlock(stdClass $row = null)// зачем id у блоков? id нужен для инициализации карты.
	 {
		$i_groups = new MultiSelect3($this->groups, 'id', 'title', null, ['name' => 'group_id', 'init' => 'auto', 'title' => 'Расположение на сайте']);
		if($row)
		 {
			$res = DB::Select($this->GetJunctionTblName(), '`group_id`', '`map_id` = ?', [$row->id]);
			if($ids = $res->FetchAllFields()) $i_groups->SetSelected($ids);
		 }
		$ret_val = '<div class="map'.($row ? '' : ' _prototype').'">
	<div class="map__top">
		'.ui::Text('name', 'title', 'placeholder', 'Название карты', 'value', $row ? $row->title : null).$i_groups->Make().'
		<input type="button" value="" class="map__action _save" title="Сохранить изменения" /><input type="button" value="" class="map__action msui_toggle2 _expanded" title="Свернуть карту" /><input type="button" value="" class="map__action msui_drag_row _move" title="Изменить позицию" /><input type="button" value="" class="map__action _delete" title="Удалить карту" />
	</div>
	<div class="map__area"'.($row ? " id='map_area_$row->id'" : '').'></div>';
		foreach(['id', 'lng', 'lat', 'zoom', 'type'] as $key)
		 {
			$i = html::Hidden('name', $key);
			if($row) $i->SetAttr('value', $row->$key);
			elseif('type' === $key) $i->SetAttr('value', 'yandex#map');
			$ret_val .= $i;
		 }
		if($row) foreach(DB::Select($this->GetPointTblName(), '*', '`map_id` = ?', [$row->id]) as $p) $ret_val .= html::Hidden('name', 'points')->SetData('id', $p->id, 'lat', $p->lat, 'lng', $p->lng, 'preset', $p->preset, 'ballooncontent', base64_encode($p->ballooncontent));
		return $ret_val.'</div>';
	 }

	final protected function GetTblName() { return $this->prefix.'map'; }
	final protected function GetPointTblName() { return $this->prefix.'map_point'; }
	final protected function GetGroupTblName() { return $this->prefix.'map_group'; }
	final protected function GetJunctionTblName() { return $this->prefix.'map_place'; }
	final protected function GetOrder() { return SQLExpr::MSMapsOrderBy(); }

	private $prefix;
	private $groups;
}
?>