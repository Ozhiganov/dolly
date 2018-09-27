<?php
namespace MSOptionsForm;

\MSConfig::RequireFile('traits', 'fsfield', 'tsystemmessages');

class Info extends \MSForm\Info implements \MSFieldSet\IIgnoreValue
{
	protected function GetRow(&$id) { return ($id = null); }
}

class Image extends \MSForm\FileField implements \MSFieldSet\IFieldAsync
{
	use \TSystemMessages;

	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['max_size' => [], 'set_size' => ['type' => 'array,false', 'value' => false]]);
		$this->ChangeOptionsMeta('iname', ['value' => 'image']);
		parent::__construct($owner, $name, $title, $options);
		$owner->BindToEvent('before_update', function(\EventData $d, $evt_name){
			$upl = new \ImageUploader($this->GetName(), $this->GetOption('dir'), $this->GetOption('root'));
			// if($this->GetOption('required') && 'after_insert' === $evt_name) $upl->Required();// нужно продумывать дополнительно, поскольку сейчас required не имеет смысла - запись можно всё равно внести
			if($max_size = $this->GetOption('max_size'))
			 {
				if(is_array($max_size)) $upl->SetMaxSize(...$max_size);
				else $upl->SetMaxSize($max_size, $max_size);
			 }
			if($file = $upl->LoadFile($this->GetBaseName('')))
			 {
				$d->data[$this->GetName()][2] = \ms::GetFileExt($file);
				if(list($w, $h) = $this->GetOption('set_size'))
				 {
					$rgr = $d->data[$this->GetName()][0];
					$info = GetImageSize($file);
					$d->data[] = [$rgr, $w, $info[0]];
					$d->data[] = [$rgr, $h, $info[1]];
				 }
			 }
			else unset($d->data[$this->GetName()]);
		});
	 }

	public function GetData()
	 {
		if(isset($_POST['delete_image']))
		 {
			if($n = \MSOptionsForm::CheckField($this))
			 {
				$path = null;
				\MSIcons::DeleteImage($this->GetFName($path, '', '*'), $path['root']);
				$data = [[$n[0], $n[1], null]];
				if($opt = $this->GetOption('set_size')) foreach($opt as $n1) $data[] = [$n[0], $n1, null];
				foreach($data as $args) \Registry::SetValue(...$args);
				self::SendJSON(['id' => '']);
			 }
		 }
	 }

	public function MakeInput()
	 {
		$fs = $this->GetFieldSet()->SetMultipart();
		$html = \ui::FileInput($this->GetName(), $this->GetId());
		if($ext = $this->GetValue())
		 {
			$path = null;
			$src = $this->GetFName($path, '', $ext);
			$html .= '<div><img src="'.$path['host'].'/f/w130/h250'.$src.'" alt="Фото" class="image_with_icon__preview" />';
			$html .= "<input type='button' title='Удалить изображение' value='Удалить' class='msui_small_button _icon _delete' data-id='' data-name='{$this->GetName()}' data-fs_id='{$fs->GetId()}' onclick='DelImg(this);' /></div>";
		 }
		$this->SetOption('__label_class', '_multirow');
		return "<div class='form__input_wr image_with_icon'>$html</div>";
	 }

	public function GetValue() { return $this->GetOption('value'); }
}

class MultiSelect3 extends \MSFieldSet\RenderableInput
{
	final public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['callback' => ['type' => 'callback,null'], 'collapse' => ['type' => 'bool,null'], 'data' => ['value' => []], 'title_fld' => ['type' => 'string', 'value' => 'title'], 'value_fld' => [], 'data_attrs' => [], 'tbl_2_name' => ['type' => 'string', 'value' => ''], 'order_by' => ['type' => 'string', 'value' => '`title` ASC'], 'reverse_selected' => ['type' => 'bool', 'value' => false], 'where' => ['type' => 'array,callback', 'value' => [false, null]]]);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		\MSConfig::RequireFile('multiselect3');
		\ResourceManager::AddJS('lib.multiselect3');
		\ResourceManager::AddCSS('lib.multiselect3');
		$this->SetOption('__label_class', '_multirow');
		$o = $this->GetOptions('reverse_selected', 'init', 'collapse');
		$o['class'] = 'form__input_wr _multilevel';
		if(null === $o['collapse']) $o['collapse'] = 'uncheck';
		if($opt = $this->GetOption('class')) $o['class'] .= " $opt";
		$t_fld = $this->GetOption('title_fld');
		$d = $this->GetOption('data');
		if($tbl_name = $this->GetOption('tbl_2_name'))
		 {
			$v_fld = $this->GetOption('value_fld') ?: 'id';
			list($cnd, $prm) = $this->GetOption('where');
			$d = \DB::Select($tbl_name, $d ?: "`$v_fld` AS `id`, `$t_fld` AS `title`", $cnd, $prm, $this->GetOption('order_by'));
			if($opt = $this->GetOption('callback')) $d->SetCallback($opt);
		 }
		else
		 {
			$v_fld = $this->GetOption('value_fld') ?: (\MSFieldSet\Select::IsDataCallable($d) ? 'id' : null);
		 }
		$obj = new \MultiSelect3($d, $v_fld, $t_fld, $this->GetOption('data_attrs'), $o);
		if(($opt = $this->GetInputValue()) && ($opt = unserialize($opt))) $obj->SetSelected($opt);
		return $obj->SetId($this->GetId())->SetName($this->GetInputName())->Make();
	 }

	protected function PostProcess($value)
	 {
		$r = $this->GetOption('reverse_selected');
		$ids = [];
		if($value && is_array($value)) foreach($value as $k => $v) if($r === empty($v)) $ids[$k] = $k;
		return serialize(parent::PreProcess($ids));
	 }
}
?>