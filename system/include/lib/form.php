<?php
MSConfig::RequireFile('formfields', 'msfieldset');

class EForm extends Exception {}
	class EFormNoInsert extends EForm {}
	class EFormNoUpdate extends EForm {}
	class EFormInvalidID extends EForm {}
	class EFormNoDefaultFType extends EForm {}
	class EFormRowIsSet extends EForm {}

interface IForm extends IFieldSet
{
	public function Make($title = null);
}

abstract class MSFormFieldSet extends MSFieldSet implements IForm
{
	final public function SetAction($val)
	 {
		$this->action = $val;
		return $this;
	 }

	final public function SetMultipart()
	 {
		$this->multipart = true;
		return $this;
	 }

	final public function GetAction() { return $this->action; }
	final public function IsMultipart() { return $this->multipart; }

	protected function OnCreate()
	 {
		$this->AddOptionsMeta(['autocomplete' => [], 'btn_insert_caption' => [], 'class' => []]);
		$this->RegisterEvents('before_make');
		parent::OnCreate();
	 }

	final protected function SplitGroups() { return false; }

	final protected function GetContent()
	 {
		ResourceManager::AddCSS('lib.ui');
		ResourceManager::AddJS('lib.form');
		$prev_group = null;
		$content = '';
		foreach($this as $item)
		 {
			if($opt = $item->object->GetOption('__no_label'))
			 {
				$label = false;
				$rc = 'no_class' === $opt ? '' : ' _no_label';
			 }
			elseif($item->title)
			 {
				$label = "<label class='form__label".(($c = $item->object->GetOption('__label_class')) ? " $c" : '')."'>$item->title</label>";
				$rc = '';
			 }
			else
			 {
				$label = false;
				$rc = ' _no_label';
			 }
			$has_error = (null !== $item->msg || null !== $item->state);
			if($label || $item->input)
			 {
				if($prev_group !== $item->group)
				 {
					if(null === $item->group) $content .= '</fieldset><fieldset>';
					else
					 {
						if($content) $content .= '</fieldset>';
						$gr = $this->GetGroup($item->group);
						$content .= '<fieldset class="form__group _'.$item->group.'">';
						$has_state = null !== $gr['state'];
						if($gr['title'] || $has_state) $content .= '<div class="form__group_title'.($has_state && !$gr['state'] ? ' _closed' : '').'">'.($has_state ? '<span class="form__group_title__button">'.$gr['title'].'</span>' : $gr['title']).'</div>';
					 }
				 }
				elseif(!$content) $content .= '<fieldset>';
				if($has_error)
				 {
					$err_msg = "<div class='form__err_msg' data-state='$item->state'>$item->msg</div>";
					$rc .= ' _field_error';
				 }
				else $err_msg = '';
				if(false === ($c = $item->object->GetOption('__row_class'))) $content .= $item->input.$err_msg;
				else
				 {
					if($c) $rc .= " $c";
					$content .= "<div class='form__row$rc' data-name='$item->name' data-type='$item->type' data-class='$item->class'>$label$item->input$err_msg</div>";
				 }
				$prev_group = $item->group;
			 }
		 }
		if($content) $content .= '</fieldset>';
		return $content;
	 }

	protected $ns = ['MSForm', 'MSFieldSet'];

	private $action = 'core.php';
	private $multipart = false;
}

class Form extends MSFormFieldSet
{
	final public function SetBack($val)
	 {
		$this->back_link = $val;
		return $this;
	 }

	final public function SetRedirectAndBack($val) { return $this->SetRedirect($val)->SetBack($val); }
	final public function GetTblName() { return $this->GetOption('tbl_name') ?: $this->GetId(); }
	final public function GetIdInputName() { return "__{$this->GetID()}__item_id"; }

	final public function GetRestriction()
	 {
		$no_insert = $this->GetOption('restriction') & self::NO_INSERT;
		$no_update = $this->GetOption('restriction') & self::NO_UPDATE;
		if($no_insert && $no_update) throw new EForm('You must specify only one restriction! Both '.__CLASS__.'::NO_INSERT and '.__CLASS__.'::NO_UPDATE specified.');
		if($no_insert) return 'no_insert';
		if($no_update) return 'no_update';
	 }

	final public function Make($title = null)
	 {
		if(null === $this->db_row)
		 {
			if(null === $this->db_row_id) $this->db_row_id = call_user_func($this->GetOption('filter_id'), 'id', false);
			if(null !== $this->db_row_id && !($this->db_row = DB::GetRowById($this->GetTblName(), $this->db_row_id))) throw new EFormInvalidID();
		 }
		$this->DispatchEvent('before_make', false, ['target' => $this]);
		if($this->db_row)
		 {
			if('no_update' === $this->GetRestriction()) throw new EFormNoUpdate('Update denied!');
			foreach($this->AsIFields() as $item)
			 {
				if(property_exists($this->db_row, $item->GetName())) $f = $item->GetName();
				elseif($item->OptionExists('__field', $f) && $f) ;
				else continue;
				$item->SetOption($item->HasErrMsg() ? 'default' : 'value', $this->db_row->$f);
			 }
		 }
		elseif('no_insert' === $this->GetRestriction()) throw new EFormNoInsert('Insertion denied!');
		$content = $this->GetContent();
		$fs_data = $this->GetData();// не удалять!
		return $this->MakeSkeleton($content, $title);
	 }

	final public function AddBtn($type, $caption, $name, $before = false)
	 {
		$this->buttons[$name] = ['type' => Filter::InEnum("$type", 'submit', 'button', 'href'), 'caption' => $caption, 'name' => $name];
		return $this;
	 }

	final public function GetDBRow(&$row_id = null)
	 {
		$row_id = $this->db_row_id;
		return $this->db_row;
	 }

	final public function SetDBRow(stdClass $row)
	 {
		if(null === $this->db_row)
		 {
			$this->db_row = $row;
			$key = \DB::GetPrimaryKey($this->GetTblName());
			$this->db_row_id = $this->db_row->{$key->name};
		 }
		else throw new EFormRowIsSet('Can not set DB row!');
		return $this;
	 }

	final public function SetDBRowId($id)
	 {
		if(null === $this->db_row && null === $this->db_row_id) $this->db_row_id = $id;
		else throw new EFormRowIsSet('Can not set DB row!');
		return $this;
	 }

	final public function GetFormAction() { return isset($_POST['__fsform_action']) ? ('update' === $_POST['__fsform_action'] || 'insert' === $_POST['__fsform_action'] ? $_POST['__fsform_action'] : false) : ''; }

	protected function OnError(Exception $e) { if($this->HasStatusMsg($msg)) MSDocument::AddErrorMsg($msg ?: 'Произошла ошибка!'); }

	protected function OnAddField($name, array &$o)
	 {
		if(DB::ColExists($this->GetTblName(), $name, $f))
		 {
			$o['default'] = $f->default;
			$o['null'] = $f->null;
		 }
	 }

	final protected function OnCreate()
	 {
		$this->AddOptionsMeta(['redirect' => [], 'restriction' => [], 'status_msgs' => [], 'tbl_name' => ['type' => 'string', 'value' => ''], 'use_transaction' => [], 'filter_id' => ['type' => 'callback', 'value' => ['Filter', 'GetValidPageId']]]);
		$this->RegisterEvents('before_update', 'after_update', 'before_insert', 'after_insert');
		parent::OnCreate();
		if(isset($_POST['_apply']) && !empty($_POST['_uri'])) $r = $_POST['_uri'];
		elseif(!empty($_POST['__redirect'])) $r = $_POST['__redirect'];
		else $r = $this->back_link = ($this->GetOption('redirect') ?: MSLoader::GetUrl(false));
		$this->SetRedirect($r);
		$this->SetRedirectBase(MSConfig::GetMSSMDir());
		$this->events_allowed = ['before_insert' => true, 'after_insert' => true, 'before_update' => true, 'after_update' => true];
		$this->status_msg_tpls = ['inserted' => 'Информация добавлена.', 'updated' => 'Информация обновлена.'];
		if($opts = $this->GetOption('status_msgs')) $this->status_msg_tpls = array_merge($this->status_msg_tpls, $opts);
	 }

	final protected function GetDefaultFType($name, array &$o)
	 {
		$fields = \DB::GetColMeta($this->GetTblName());
		if(empty($fields[$name])) throw new EFormNoDefaultFType("Unable to get default type for the field `$name`!");
		if(\DB::IsForeignKey($this->GetTblName(), $name, $fk))
		 {
			$o['type'] = $fields[$name]->type;
			$o['tbl_2_name'] = $fk['table'];
			return 'ForeignKey';
		 }
		switch($type = $fields[$name]->type)
		 {
			case 'date': return 'DatePicker';
			case 'datetime': return 'DateTimePicker';
			case 'time': return 'TimePicker';
			case 'longtext': return 'TextEditor';
			case 'varchar':
					$o['maxlength'] = $fields[$name]->size;
					return 'TextInput';
			case 'int':
					if(1 == $fields[$name]->size) return 'Checkbox';
					if($fields[$name]->unsigned) $o['min'] = 0;
					return 'Number';
			default:
				if(stripos($type, 'text') !== false) return 'Textarea';
				if(stripos($type, 'enum') !== false)
				 {
					$vals = explode("','", substr($type, 6, -2));
					$o['data'] = array_combine($vals, empty($o['data']) ? $vals : $o['data']);
					if(empty($o['class'])) $o['class'] = 'msui_select';
					return 'Select';
				 }
				if(stripos($type, 'year') !== false)
				 {
					if(!isset($o['start'])) $o['start'] = 'now';
					if(!isset($o['end'])) $o['end'] = 'now-20';
					return 'Year';
				 }
				if(stripos($type, 'float') !== false || stripos($type, 'decimal') !== false) return 'Decimal';
				return 'TextInput';
		 }
	 }

	final protected function MakeSkeleton($content, $title)
	 {
		$mssm_dir = MSConfig::GetMSSMDir();
		if($row = $this->GetDBRow($row_id))
		 {
			$action = 'update';
			$btn_caption = 'Сохранить';
			$index = 1;
			$html = html::Hidden('name', $this->GetIdInputName(), 'value', $row_id)."<a href='$mssm_dir{$this->back_link}' class='msui_back'>Назад</a>".ui::Submit('value', 'Применить', 'name', '_apply');
		 }
		else
		 {
			$action = 'insert';
			$btn_caption = $this->GetOption('btn_insert_caption') ?: 'Добавить';
			$index = 0;
			$html = '';
			foreach($this->buttons as $b) $html .= 'href' == $b['type'] ? "<a class='msui_back' href='$mssm_dir$b[name]'>$b[caption]</a>" : ui::$b['type']('value', $b['caption'], 'name', $b['name']);
		 }
		$form = ui::Form('class', 'form'.(($c = $this->GetOption('class')) ? " $c" : ''), 'id', $this->GetId().'_form', 'action', $this->GetAction(), 'autocomplete', $this->GetOption('autocomplete'));
		if(false !== $title)
		 {
			if(null === $title) $title = ['Добавление', 'Редактирование'];
			elseif(is_object($title)) $title = is_callable($title) ? call_user_func($title, $row) : "$title";
			$form->SetCaption(is_array($title) ? $title[$index] : $title);
		 }
		$form->SetMiddle($content)->SetBottom(html::Hidden('name', '__redirect', 'value', $this->GetRedirect()).html::Hidden('name', '__fsform_action', 'value', $action).ui::FAction("fs.form:$action").ui::Submit('value', $btn_caption).$html.$this->GetHiddenField().html::Hidden('name', '_uri', 'value', str_replace($mssm_dir, '', $_SERVER['REQUEST_URI'])));
		if($this->IsMultipart()) $form->SetAttr('enctype', 'multipart/form-data');
		return $form;
	 }

	final protected function Action(...$args)
	 {
		switch($_POST['__fsform_action'])
		 {
			case 'update':
				if('no_update' === $this->GetRestriction()) throw new EFormNoUpdate('Update denied!');
				$all_data = [];
				$data = $this->FSData2DBRow($args, $all_data);
				$id = call_user_func($this->GetOption('filter_id'), $this->GetIdInputName(), true);
				if(null === $id) throw new EFormInvalidID();
				if($utrans = $this->UsingTransaction('update')) \DB::BeginTransaction();
				$this->DispatchEvent('before_update', false, ['id' => $id, 'data' => &$data, 'all_data' => $all_data, 'form' => $this], ['data' => ['set' => true, 'type' => 'array']]);
				$key = \DB::GetPrimaryKey($this->GetTblName());
				$this->AfterEvent($utrans, $this->DispatchEvent('after_update', false, $this->GetEvtData_After(isset($data[$key->name]) ? $data[$key->name] : $id, $data, $all_data, $this->status_msg_tpls['updated'], ['affected' => DB::UpdateById($this->GetTblName(), $data, $id)]), $this->GetEvtCfg_After()));
				break;
			case 'insert':
				if('no_insert' === $this->GetRestriction()) throw new EFormNoInsert('Insertion denied!');
				$all_data = [];
				$data = $this->FSData2DBRow($args, $all_data);
				if($utrans = $this->UsingTransaction('insert')) \DB::BeginTransaction();
				$this->DispatchEvent('before_insert', false, ['data' => &$data, 'all_data' => $all_data, 'form' => $this], ['data' => ['set' => true]]);
				$id = \DB::Insert($this->GetTblName(), $data);
				$this->AfterEvent($utrans, $this->DispatchEvent('after_insert', false, $this->GetEvtData_After($id, $data, $all_data, $this->status_msg_tpls['inserted']), $this->GetEvtCfg_After()));
				break;
		 }
	 }

	final private function UsingTransaction($action) { return ($opt = $this->GetOption('use_transaction')) ? (is_array($opt) ? !empty($opt[$action]) : true) : false; }

	final private function FSData2DBRow(array $args, array &$all_data)
	 {
		$data = [];
		$i = 0;
		foreach($this->AsIFields() as $fld)
		 {
			$all_data[$fld->GetName()] = $args[$i];
			if(!($fld instanceof \MSFieldSet\IIgnoreValue))
			 {
				if(!($args[$i] instanceof \FSVoidValue) && DB::ColExists($this->GetTblName(), $fld->GetName())) $data[$fld->GetName()] = $args[$i];
			 }
			++$i;
		 }
		return $data;
	 }

	final private function GetEvtCfg_After(array $v = [])
	 {
		$v['status_msg'] = ['set' => true, 'type' => 'string'];
		$v['redirect'] = ['set' => true, 'type' => 'string'];
		return $v;
	 }

	final private function GetEvtData_After($id, array $data, array $all_data, $status_msg, array $v = [])
	 {
		$v['id'] = $id;
		$v['data'] = $data;
		$v['all_data'] = $all_data;
		$v['form'] = $this;
		$v['status_msg'] = $status_msg;
		$v['redirect'] = '';
		return $v;
	 }

	final private function AfterEvent($utrans, \EventData $d)
	 {
		if($utrans) \DB::Commit();
		if($d->status_msg) MSDocument::AddSuccessMsg($d->status_msg);
		if($d->redirect) $this->Redirect($d->redirect);
	 }

	private $back_link;
	private $buttons = [];
	private $status_msg_tpls = [];
	private $db_row = null;
	private $db_row_id = null;

	const NO_INSERT = 0b01;
	const NO_UPDATE = 0b10;
}
?>