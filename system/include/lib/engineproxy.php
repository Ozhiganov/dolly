<?php
MSConfig::RequireFile('iengine');

class EngineProxy implements IEngine
{
	use TEngine;

	public function __construct(array $options = null)
	 {
		$this->Init($options);
	 }

	public function AddPathHandler($path, $handler) {}
	public function AddMetaTag(EngineMetaTag $tag) {}
	public function SetPageFilter($callback, $method = null) {}
}
?>