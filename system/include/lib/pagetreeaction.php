<?php
class PageTreeAction
{
	use \TOptions;

	public function __construct($msdoc_id, array $options = null)
	 {
		$this->msdoc_id = $msdoc_id;
		$this->AddOptionsMeta([/* 'href' => [], */ 'class' => ['type' => 'string'], 'title' => ['type' => 'string']]);
		$this->SetOptionsData($options);
		$this->data['title'] = $this->GetOption('title');
		$this->data['class'] = $this->GetOption('class');
		$this->data['menu_type'] = 'icon '.$this->data['class'];
	 }

	public function __invoke(\stdClass $page)
	 {
		$this->data['href'] = "/$this->msdoc_id/?page_id=$page->id";
		return $this->data;
	 }

	final public function GetMSDocId() { return $this->msdoc_id; }

	private $msdoc_id;
	private $data = [];
}
?>