<?php
interface IEngine
{
	public function __construct(array $options = null);
	public function AddPathHandler($path, $handler);
	public function AddMetaTag(EngineMetaTag $tag);
	public function SetPageFilter($callback, $method = null);
}

interface IPageType
{
	public function BeforeCreate(EventData $data);
	public function AfterCreate(EventData $data);
}

interface IPathHandler
{
	public function CreatePage($id, $subpath);
}

interface IEngineMetaTag
{
	public function __construct($attr, $value, $index, array $options = null);
	public function GetContent(stdClass $page);
}

trait TEngine
{
	use TEvents, TOptions;

	final public function GetTName($suffix = '') { return $this->tbl_name.$suffix; }
	final public function GetBase() { return $this->base; }
	final public function AddBase($href) { return $this->base.$href; }
	final public function MakeHref($sid) { return MSConfig::GetProtocol().$_SERVER['HTTP_HOST'].$this->base.($sid ? "$sid/" : ''); }
	final public function GetHref(stdClass $page) { return $this->MakeHref($page->sid); }
	final public function AddHref(stdClass $row) { $row->href = $this->MakeHref($row->sid); }
	final public function GetHrefByID($id) { if($page = DB::GetRowById($this->GetTName(), $id)) return $this->GetHref($page); }
	final public function GetURLFilter() { return $this->url_filter; }

	final protected function Init(array $options = null)
	 {
		$this->AddOptionsMeta(['base' => ['type' => 'string,len_gt0', 'value' => '/'], 'enable_page_tags' => ['type' => 'bool', 'value' => true], 'tbl_name' => ['type' => 'string,len_gt0', 'value' => 'page'], 'url_filter' => ['type' => 'callback', 'value' => 'Filter::GetValidURLPart']]);
		$this->SetOptionsData($options);
		if($this->base = $this->GetOption('base'))
		 {
			if('/' !== $this->base && '/' !== $this->base[mb_strlen($this->base, 'UTF-8') - 1]) $this->base .= '/';
		 }
		$this->url_filter = $this->GetOption('url_filter');
		$this->tbl_name = $this->GetOption('tbl_name');
	 }

	private $base;
	private $tbl_name;
	private $url_filter;
}
?>