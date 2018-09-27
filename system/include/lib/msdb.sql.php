<?php
namespace MSDB\SQL;

class IN
{
	final public function __construct(array $params = null, array $options = null, array &$p = null)
	 {
		$this->raw_params = $params;
		\MSConfig::RequireFile('datacontainer');
		$this->options = new \OptionsGroup($options, [
				'delay' => ['type' => 'bool', 'value' => false],
				'empty' => ['type' => 'bool', 'value' => false],
				'expr' => ['type' => 'string', 'value' => ''],
				'indexes' => ['type' => 'string', 'value' => ''],
				'not' => ['type' => 'bool', 'value' => false],
				'params' => ['type' => 'callback,null'],
				'prefix' => ['type' => 'string', 'value' => ''],
				'use_keys' => ['type' => 'bool', 'value' => false],
				'update' => ['type' => 'bool', 'value' => false],
			]);
		if($this->options->delay) $p = null;
		else
		 {
			$this->__invoke();
			$p = $this->GetParams($p);
		 }
	 }

	final public function GetParams(array $params = null)
	 {
		if(null === $this->sql_string) $this->__invoke();
		if($this->options->update)
		 {
			foreach($this->params as $k => $v)
			 {
				$k = "~$k";
				if(isset($params[$k])) throw new \Exception("Parameter with index `$k` exists!");
				else $params[$k] = $v;
			 }
		 }
		elseif($params)
		 {
			foreach($this->params as $k => $v)
			 if(isset($params[$k])) throw new \Exception("Parameter with index `$k` exists!");
			 else $params[$k] = $v;
		 }
		else return $this->params;
		return $params;
	 }

	final public function GetParamsRaw()
	 {
		if(null === $this->sql_string) $this->__invoke();
		if($this->options->update)
		 {
			$params = [];
			foreach($this->params as $k => $v)
			 {
				$k = "~$k";
				$params[$k] = $v;
			 }
			return $params;
		 }
		else return $this->params;
	 }

	final public function __invoke()
	 {
		if($this->options->params) $params = call_user_func($this->options->params, $this->raw_params);
		else $params = $this->raw_params;
		if($params)
		 {
			$this->sql_string = '';
			$this->params = $this->options->use_keys ? array_keys($params) : array_merge([], $params);
			if($this->options->indexes)
			 {
				$i = 0;
				if('to_string' === $this->options->indexes) $add_sign = function() use(&$i){
					$index = $this->options->prefix.'_'.$i++;
					$this->sql_string .= ":$index";
					return $index;
				};
				elseif('to_int' === $this->options->indexes) $add_sign = function() use(&$i){
					$this->sql_string .= '?';
					return $i++;
				};
				else throw new \Exception("Invalid value for option 'indexes'! Must be 'to_string' or 'to_int' if not empty.");
				foreach($this->params as $key => $v)
				 {
					if($this->sql_string) $this->sql_string .= ', ';
					unset($this->params[$key]);
					$this->params[$add_sign()] = $v;
				 }
			 }
			else $this->sql_string = implode(', ', array_fill(0, count($this->params), '?'));
			$this->sql_string = $this->options->expr.($this->options->not ? ' NOT' : '')." IN ($this->sql_string)";
		 }
		elseif($this->options->empty)
		 {
			$this->sql_string = '';
			return null;
		 }
		else throw new \Exception("Empty parameter list!");
		return $this->sql_string;
	 }

	final public function __toString()
	 {
		if(null === $this->sql_string) $this->__invoke();
		return $this->sql_string;
	 }

	final public function __debugInfo()
	 {
		return ['sql_string' => $this->sql_string, 'params' => $this->params, 'raw_params' => $this->raw_params];
	 }

	private $sql_string = null;
	private $params = [];
	private $raw_params;
	private $options;
}
?>