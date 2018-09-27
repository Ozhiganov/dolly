<?php
abstract class MSUIMsg
{
	public function __construct($msg, $class, $img_name, $img_w = 0, $img_h = 0, $img_alt = '')
	 {
		$this->class = $class;
		$this->img_name = $img_name;
		$this->img_w = $img_w;
		$this->img_h = $img_h;
		$this->img_alt = $img_alt;
		$this->msg = $msg;
	 }

	final public function __tostring() { return "<div class='status_msg _{$this->class}'>".($this->img_name ? "<img src='/system/img/ui/{$this->img_name}' width='{$this->img_w}' height='{$this->img_h}' alt='{$this->img_alt}' class='sign' />" : '').$this->msg.'</div>'; }

	private $class;
	private $img_name;
	private $img_w;
	private $img_h;
	private $img_alt;
	private $msg;
}

class MSUISuccessMsg extends MSUIMsg
{
	final public function __construct($msg, $class) { parent::__construct($msg, 'success'.($class ? " $class" : ''), false); }
}

class MSUIErrorMsg extends MSUIMsg
{
	final public function __construct($msg, $class) { parent::__construct($msg, 'error'.($class ? " $class" : ''), 'error.png', 22, 22, 'Ошибка'); }
}

class MSUIWarningMsg extends MSUIMsg
{
	final public function __construct($msg, $class) { parent::__construct($msg, 'warning'.($class ? " $class" : ''), 'warning.png', 24, 24, 'Предупреждение'); }
}

class MSUIForm
{
	final public function __construct()
	 {
		ResourceManager::AddCSS('lib.ui');
		$this->tag = html::form('action', 'core.php', 'class', 'form');
		if($args = func_get_args()) call_user_func_array(array($this->tag, 'SetAttr'), $args);
		$this->top = html::div('class', 'form__top')->RemoveIfEmpty();
		$this->middle = html::div('class', 'form__middle');
		$this->bottom = html::div('class', 'form__bottom');
		$this->tag->Append($this->top, $this->middle, $this->bottom);
	 }

	final public function SetAttr($name, $value)
	 {
		call_user_func_array(array($this->tag, 'SetAttr'), func_get_args());
		return $this;
	 }

	final public function SetData($name, $value)
	 {
		call_user_func_array([$this->tag, 'SetData'], func_get_args());
		return $this;
	 }

	final public function SetCaption($text)
	 {
		$this->top->SetHTML($text);
		return $this;
	 }

	final public function SetMiddle($text)
	 {
		$this->middle->SetHTML($text);
		return $this;
	 }

	final public function SetBottom($text)
	 {
		$this->bottom->SetHTML($text);
		return $this;
	 }

	final public function __toString() { return $this->tag->__toString(); }

	private $tag;
	private $top;
	private $middle;
	private $bottom;
}

class HTMLFilesInput
{
	public function __construct($name, $id = null)
	 {
		$this->name = $name;
		$this->id = $id;
		ResourceManager::AddCSS('lib.uploader');
		ResourceManager::AddJS('lib.uploader');
	 }

	public function __toString() { return '<div class="msui_mfile"><input type="file" multiple="multiple" name="'.$this->GetName().'"'.($this->GetId() ? ' id="'.$this->GetId().'"' : '').' /></div>'; }

	final protected function GetName() { return $this->name; }
	final protected function GetId() { return $this->id; }

	private $name;
	private $id;
}

class HTMLFileInput
{
	public function __construct($name, $id = null, $accept = false)
	 {
		$this->name = $name;
		$this->id = $id;
		$this->accept = $accept;
	 }

	public function Make($btn_del = true)
	 {
		ResourceManager::AddCSS('lib.uploader');
		ResourceManager::AddJS('lib.file_input');
		return '<div class="msui_file_input"><div class="msui_file_input__inner"><div class="msui_file_input__label">Выбрать...</div><input type="file"'.(($id = $this->GetId()) ? " id='$id'" : '').' name="'.$this->GetName().'"'.($this->accept ? " accept='{$this->accept}'" : '').' /></div>'.($btn_del ? '<input type="button" class="msui_file_input__clear _hidden" title="Очистить" value="×" />' : '').'<div class="msui_file_input__name"></div></div>';
	 }

	public function __toString() { return $this->Make(); }

	protected function GetName() { return $this->name; }
	protected function GetId() { return $this->id; }

	private $name;
	private $id;
	private $accept;
}

class HTMLImageInput extends HTMLFileInput
{
	public function Make($btn_del = true)
	 {
		return '<div class="msui_image_input">'.parent::Make($btn_del).'<input type="text" class="msui_input msui_image_input__url" name="'.$this->GetName().'" disabled="disabled" autocomplete="off" /><div class="msui_image_input__bottom"><span class="msui_image_input__toggle pseudolink">загрузить по ссылке</span></div></div>';
	 }
}

class ui implements IMSUI
{
	final public static function Button(...$args) { return self::CreateButton($args, 'Button'); }
	final public static function Submit(...$args) { return self::CreateButton($args, 'Submit'); }
	final public static function FileInput($name, $id = null, $accept = false) { return new HTMLFileInput($name, $id, $accept); }
	final public static function ImageInput($name, $id = null) { return new HTMLImageInput($name, $id); }
	final public static function FilesInput($name, $id = null) { return new HTMLFilesInput($name, $id); }
	final public static function SuccessMsg($msg, $class = false) { return new MSUISuccessMsg($msg, $class); }
	final public static function ErrorMsg($msg, $class = false) { return new MSUIErrorMsg($msg, $class); }
	final public static function WarningMsg($msg, $class = false) { return new MSUIWarningMsg($msg, $class); }
	final public static function DeleteBlock($class = '') { return '<button type="button" class="delete_block'.($class ? " $class" : '').'">×</button>'; }
	final public static function DragRow($class = '') { return '<span class="msui_drag_row'.($class ? " $class" : '').'"></span>'; }
	final public static function InfoPopup($text) { return "<div class='info_popup'>?<div class='info_popup__text'>$text</div></div>"; }
	final public static function PhoneHref($phone_num, $class = '') { return "<a href='tel:".Format::AsPhoneHref($phone_num)."' class='$class'>".Format::AsPhoneNum($phone_num).'</a>'; }
	final public static function FAction($value) { return "<input type='hidden' name='__mssm_action' value='$value' />"; }

	final public static function FRedirect($url = true, $params = '')
	 {
		if(true === $url) $url = MSLoader::GetId().'/';
		return "<input type='hidden' name='__redirect' value='/$url$params' />";
	 }

	final public static function FormRow($label, $input, $rc = '', $lc = '')
	 {
		if($rc) $rc = " $rc";
		if($lc) $lc = " $lc";
		if($label) $label = "<label class='form__label$lc'>$label</label>";
		else $rc .= " _no_label";
		return "<div class='form__row$rc'>$label$input</div>";
	 }

	final public static function Date(...$args)
	 {
		$n = count($args);
		if($n % 2) throw new Exception("Number of arguments must be even! Odd given: $n");
		$attrs = [];
		for($i = 0; $i < $n; $i += 2) $attrs[$args[$i]] = $args[$i + 1];
		if(!isset($attrs['value'])) $attrs['value'] = null;
		if($time = !empty($attrs['time']))
		 {
			$v = $attrs['value'] ?: DB::Now();
			list($v, $t) = explode(' ', $v);
			$t = explode(':', $t);
		 }
		else
		 {
			$v = Format::IsDate($attrs['value']) ? $attrs['value'] : DB::CurDate();
		 }
		// $html = '';
		$i0 = html::Hidden('value', $v);
		// $html = "<input type='hidden' value='$v'";
		if(empty($attrs['name'])) $name = null;
		else
		 {
			$name = $attrs['name'];
			// $html .= " name='$name".($time ? '[date]' : '')."'";
			$i0->SetAttr('name', $name.($time ? '[date]' : ''));
		 }
		if(!empty($attrs['id'])) $i0->SetAttr('id', $attrs['id']);//$html .= " id='$attrs[id]'";
		// $html .= ' />';
		$i1 = html::Text('class', 'msui_input _date _autoinit', 'value', Format::AsDate($v), 'readonly', true);
		foreach(['min', 'max'] as $i) if(!empty($attrs[$i])) $i1->SetData($i, 'now' === $attrs[$i] ? DB::CurDate() : $attrs[$i]);
		$html = $i0.$i1;//'<input type="text" class="msui_input _date _autoinit" value="'.Format::AsDate($v).'" readonly="readonly" />';
		if($time) foreach(['h', 'm', 's'] as $i => $n) $html .= ($i ? ':' : '&nbsp;').'<input class="msui_input _time" type="number" min="0" max="'.($i ? 59 : 24).'" name="'.$name.'['.$n.']" value="'.$t[$i].'" />';
		if(!empty($attrs['is_null'])) $html .= '<label class="set_date_null"><input type="checkbox" name="'.$attrs['null_name'].'"'.(null === $attrs['value'] ? ' checked="checked"' : '').' value="null" /> '.(@$attrs['null_label'] ?: 'Не указывать дату').'</label>';
		return $html;
	 }

	final public static function Textarea()
	 {
		if($args = func_get_args())
		 {
			$args[] = 'class';
			$args[] = 'msui_textarea';
			return call_user_func_array('html::Textarea', $args);
		 }
		else return html::Textarea('class', 'msui_textarea');
	 }

	final public static function Text(...$args)
	 {
		if(count($args))
		 {
			// if(false === ($i = array_search('class', $args, true)))
			 // {
				$args[] = 'class';
				$args[] = 'msui_input';
			 // }
			// else $args[$i + 1] .= ' msui_input';
			return html::Text(...$args);
		 }
		else return html::Text('class', 'msui_input');
	 }

	final public static function Search()
	 {
		if($args = func_get_args())
		 {
			$args[] = 'class';
			$args[] = 'msui_input';
			return call_user_func_array('html::Search', $args);
		 }
		else return html::Search('class', 'msui_input');
	 }

	final public static function Number()
	 {
		if($args = func_get_args())
		 {
			$args[] = 'class';
			$args[] = 'msui_input';
			return call_user_func_array('html::Number', $args);
		 }
		else return html::Number('class', 'msui_input');
	 }

	final public static function Password()
	 {
		if($args = func_get_args())
		 {
			$args[] = 'class';
			$args[] = 'msui_input';
			return call_user_func_array('html::Password', $args);
		 }
		else return html::Password('class', 'msui_input');
	 }

	final public static function Progbar($percent, $precision = 2)
	 {
		ResourceManager::AddCSS('lib.progbar');
		$percent = round($percent, $precision);
		$percent_2 = 100 - $percent;
		return '<div class="progress_bar"><div class="progress_bar__value _completed" style="right:'.$percent_2.'%;"><span class="progress_bar__label">&nbsp;'.$percent.'%&nbsp;</span></div><div class="progress_bar__value _running" style="left:'.$percent.'%;">&nbsp;'.$percent_2.'%&nbsp;</div></div>';
	 }

	final public static function Form()
	 {
		if($args = func_get_args())
		 {
			$c = new ReflectionClass('MSUIForm');
			return $c->newInstanceArgs($args);
		 }
		else return new MSUIForm();
	 }

	final public static function Year($start, $end, $key = false)
	 {
		foreach(['start', 'end'] as $n)
		 {
			$$n = str_replace('now', '', $$n, $count);
			if($count)
			 {
				static $y_now = null;
				if(null === $y_now) $y_now = date('Y');
				$$n = $y_now + intval($$n);
			 }
		 }
		$r = range($start, $end);
		$sel = new Select(array_combine($r, $r));
		$c = '';
		if($key)
		 {
			$args = func_get_args();
			$cnt = count($args);
			if($cnt % 2) throw new Exception('Number of arguments must be even (attribute - value)!');
			$args = array_slice($args, 2);
			$cnt -= 2;
			$o = [];
			for($i = 0; $i < $cnt; $i += 2) $o[$args[$i]] = $args[$i + 1];
			if(!empty($o['value'])) $sel->SetSelected($o['value']);
			if(!empty($o['name'])) $sel->SetName($o['name']);
			if(!empty($o['id'])) $sel->SetId($o['id']);
			if(!empty($o['default']))
			 if(true === $o['default']) $sel->SetDefaultOption('', '—');
			 else $sel->SetDefaultOption($o['default'][0], $o['default'][1]);
			if(!empty($o['class'])) $c = " $o[class]";
		 }
		return $sel->SetClassName("msui_select _year$c")->Make();
	 }

	final public static function PNav(MSPageNav $nav)
	 {
		if($nav->IsVisible())
		 {
			$html = '';
			while($b = $nav->FetchBtn()) $html .= self::PNavBtn($b);
			return "<div class='msui_p_nav'>$html</div>";
		 }
	 }

	final public static function PNavBtn(stdClass $b) { return $b->this_page ? "<span class='msui_p_nav__item _selected'>$b->title</span>" : '<a'.($b->type ? " data-type='$b->type'" : '')." class='msui_p_nav__item _href' href='$b->href'>$b->title</a>"; }

	final public static function FGroupBtn(stdClass $b) { return "<a class='$b->class' href='$b->href' data-index='$b->index'>$b->title</a>"; }

	final private static function CreateButton(array $args, $type)
	 {
		$tag = HTML::$type(...$args);
		return $tag->SetAttr('class', 'msui_button'.(($class = $tag->GetAttr('class')) ? " $class" : ''));
	 }
	
	final private function __construct() {}

	private static $instance;
}
?>