<?php
abstract class n
{
	final public static function ReplaceHTMLEntities($str)
	 {
		return preg_replace('/&([^#])/u', '&amp;$1', strtr($str, self::$ent));
	 }

	final public static function Before(DOMNode $ref_node, DOMNode $new_node) { return $ref_node->parentNode->insertBefore($new_node, $ref_node); }

	final public static function After(DOMNode $ref_node, DOMNode $new_node)
	 {
		return $ref_node->nextSibling ? $ref_node->parentNode->insertBefore($new_node, $ref_node->nextSibling) : $ref_node->parentNode->appendChild($new_node);
	 }

	final public static function InsertFirst(DOMNode $parent, DOMNode $new_node)
	 {
		if($parent->childNodes->length) $parent->insertBefore($new_node, $parent->firstChild);
		else $parent->appendChild($new_node);
	 }

	final public static function AddComment(DOMNode $parent, $text, $pos = null, array $o = null)
	 {
		$doc = $parent->ownerDocument;
		$c = $doc->createComment($text);
		self::InsertPos($parent, $c, $pos);
		if(!empty($o['eol_before'])) for($i = 0; $i < $o['eol_before']; ++$i) self::Before($c, $doc->createTextNode(PHP_EOL));
		if(!empty($o['eol_after'])) for($i = 0; $i < $o['eol_after']; ++$i) self::After($c, $doc->createTextNode(PHP_EOL));
	 }

	final public static function AddEOL(DOMNode $parent, $pos = null, $num = 1)
	 {
		for($i = 0; $i < $num; ++$i) self::InsertPos($parent, $parent->ownerDocument->createTextNode(PHP_EOL), $pos);
	 }

	final public static function InsertPos(DOMNode $ref_node, DOMNode $new_node, $pos)
	 {
		if('first' === $pos) self::InsertFirst($ref_node, $new_node);
		elseif('before' === $pos) self::Before($ref_node, $new_node);
		elseif('after' === $pos) self::After($ref_node, $new_node);
		else $ref_node->appendChild($new_node);
	 }

	final public static function Node(DOMDocument $doc, $name, array $attrs = null, DOMNode $n = null, $action = 'append')
	 {
		$node = $doc->createElement($name, null); 
		if($attrs) foreach($attrs as $key => $value) $node->setAttribute($key, $value);
		if($n) switch($action)
		 {
			case 'append': $node->appendChild($n); break;
			case 'appendto': $n->appendChild($node); break;
			case 'before': self::Before($n, $node); break;
		 }
		return $node;
	 }

	final public static function Replace(DOMNode $node, DOMNode $new_node = null)
	 {
		if($new_node) $node->parentNode->replaceChild($new_node, $node);
		elseif($node->childNodes->length)
		 {
			$tmp = $node->ownerDocument->createDocumentFragment();
			self::MoveChildNodes($node, $tmp);
			self::Replace($node, $tmp);
		 }
		else self::Remove($node);
	 }

	final public static function MoveChildNodes(DOMNode $src, DOMNode $dest)
	 {
		while($node = $src->firstChild) $dest->appendChild($node);
		return $dest;
	 }

	final public static function GetTagsFromFragment(DOMDocumentFragment $fragment, $tag)
	 {
		$nodes = [];
		foreach($fragment->childNodes as $child)
		 {
			if($child->nodeName === $tag) $nodes[] = $child;
			elseif($child->nodeType === XML_ELEMENT_NODE) foreach($child->GetElementsByTagName($tag) as $node) $nodes[] = $node;
		 }
		return $nodes;
	 }

	final public static function GetLayoutsFromFragment(DOMDocumentFragment $fragment, $tag)
	 {
		$nodes = [];
		foreach($fragment->childNodes as $child)
		 {
			if($child->nodeName === $tag) $nodes[] = $child;
			elseif($child->nodeType === XML_ELEMENT_NODE)
			 foreach($child->GetElementsByTagName($tag) as $node)
			  {
				$a = $node->getAttribute('name');
				if('' === $a) throw new Exception("Empty attribute 'name' for tag '$tag'!");
				if(':' === $a[0])
				 {
					$n = $node;
					while($n = $n->parentNode) if($n->nodeName === $tag) continue 2;
				 }
				$nodes[] = $node;
			  }
		 }
		return $nodes;
	 }

	final public static function CleanFragmentWith(DOMDocumentFragment $fragment, $tag, $func)
	 {
		for($i = 0; $i < $fragment->childNodes->length;)
		 {
			$child = $fragment->childNodes->item($i);
			if($child->nodeName === $tag)
			 {
				$func($child);
				continue;
			 }
			elseif($child->nodeType === XML_ELEMENT_NODE)
			 {
				$n = $child->GetElementsByTagName($tag);
				while($n->length) $func($n->item(0));
			 }
			++$i;
		 }
	 }

	final public static function WalkFragmentWith(DOMDocumentFragment $fragment, $tag, $func, &$count = null)
	 {
		$count = 0;
		for($i = 0; $i < $fragment->childNodes->length;)
		 {
			$child = $fragment->childNodes->item($i);
			if($child->nodeName === $tag)
			 {
				$r = $func($child);
				if(true === $r)
				 {
					++$count;
					continue;
				 }
				elseif(false === $r) return;
			 }
			elseif($child->nodeType === XML_ELEMENT_NODE)
			 {
				$n = $child->GetElementsByTagName($tag);
				for($j = 0; $j < $n->length;)
				 {
					$r = $func($n->item($j));
					if(true === $r)
					 {
						++$count;
						continue;
					 }
					elseif(false === $r) return;
					else ++$j;
				 }
			 }
			++$i;
		 }
	 }

	final public static function ReplaceIfElseNodes($rule, stdClass $data = null, DOMNode $node)
	 {
		$f = (true === $rule || false === $rule) ? $rule : $rule($data);
		if($node->nextSibling && $node->nextSibling->nodeName === 'else')
		 {
			if($f) self::Remove($node->nextSibling);
			else self::Replace($node->nextSibling);
		 }
		if($f) self::Replace($node);
		else self::Remove($node);
	 }

	final public static function Remove(DOMNode $node) { $node->parentNode->removeChild($node); }

	final public static function Wrap(DOMNode $node, DOMNode $wr_node, $name = false, array $attrs = null)
	 {
		if(($wr_node instanceof DOMDocument) && $name) $wr_node = self::Node($wr_node, $name, $attrs);
		$node->parentNode->replaceChild($wr_node, $node);
		$wr_node->appendChild($node);
		return $wr_node;
	 }

	private static $ent = [//&amp;, &lt;, &gt;, &quot;, &apos;
		'&quot;' => '&#34;',				'&amp;' => '&#38;',					'&apos;' => '&#39;',				'&lt;' => '&#60;',					'&gt;' => '&#62;',
		'&nbsp;' => '&#160;',				'&iexcl;' => '&#161;',				'&cent;' => '&#162;',				'&pound;' => '&#163;',				'&curren;' => '&#164;',
		'&yen;' => '&#165;',				'&brvbar;' => '&#166;',				'&sect;' => '&#167;',				'&uml;' => '&#168;',				'&copy;' => '&#169;',
		'&ordf;' => '&#170;',				'&laquo;' => '&#171;',				'&not;' => '&#172;',				'&shy;' => '&#173;',				'&reg;' => '&#174;',
		'&macr;' => '&#175;',				'&deg;' => '&#176;',				'&plusmn;' => '&#177;',				'&sup2;' => '&#178;',				'&sup3;' => '&#179;',
		'&acute;' => '&#180;',				'&micro;' => '&#181;',				'&para;' => '&#182;',				'&middot;' => '&#183;',				'&cedil;' => '&#184;',
		'&sup1;' => '&#185;',				'&ordm;' => '&#186;',				'&raquo;' => '&#187;',				'&frac14;' => '&#188;',				'&frac12;' => '&#189;',
		'&frac34;' => '&#190;',				'&iquest;' => '&#191;',				'&Agrave;' => '&#192;',				'&Aacute;' => '&#193;',				'&Acirc;' => '&#194;',
		'&Atilde;' => '&#195;',				'&Auml;' => '&#196;',				'&Aring;' => '&#197;',				'&AElig;' => '&#198;',				'&Ccedil;' => '&#199;',
		'&Egrave;' => '&#200;',				'&Eacute;' => '&#201;',				'&Ecirc;' => '&#202;',				'&Euml;' => '&#203;',				'&Igrave;' => '&#204;',
		'&Iacute;' => '&#205;',				'&Icirc;' => '&#206;',				'&Iuml;' => '&#207;',				'&ETH;' => '&#208;',				'&Ntilde;' => '&#209;',
		'&Ograve;' => '&#210;',				'&Oacute;' => '&#211;',				'&Ocirc;' => '&#212;',				'&Otilde;' => '&#213;',				'&Ouml;' => '&#214;',
		'&times;' => '&#215;',				'&Oslash;' => '&#216;',				'&Ugrave;' => '&#217;',				'&Uacute;' => '&#218;',				'&Ucirc;' => '&#219;',
		'&Uuml;' => '&#220;',				'&Yacute;' => '&#221;',				'&THORN;' => '&#222;',				'&szlig;' => '&#223;',				'&agrave;' => '&#224;',
		'&aacute;' => '&#225;',				'&acirc;' => '&#226;',				'&atilde;' => '&#227;',				'&auml;' => '&#228;',				'&aring;' => '&#229;',
		'&aelig;' => '&#230;',				'&ccedil;' => '&#231;',				'&egrave;' => '&#232;',				'&eacute;' => '&#233;',				'&ecirc;' => '&#234;',
		'&euml;' => '&#235;',				'&igrave;' => '&#236;',				'&iacute;' => '&#237;',				'&icirc;' => '&#238;',				'&iuml;' => '&#239;',
		'&eth;' => '&#240;',				'&ntilde;' => '&#241;',				'&ograve;' => '&#242;',				'&oacute;' => '&#243;',				'&ocirc;' => '&#244;',
		'&otilde;' => '&#245;',				'&ouml;' => '&#246;',				'&divide;' => '&#247;',				'&oslash;' => '&#248;',				'&ugrave;' => '&#249;',
		'&uacute;' => '&#250;',				'&ucirc;' => '&#251;',				'&uuml;' => '&#252;',				'&yacute;' => '&#253;',				'&thorn;' => '&#254;',
		'&yuml;' => '&#255;',				'&OElig;' => '&#338;',				'&oelig;' => '&#339;',				'&Scaron;' => '&#352;',				'&scaron;' => '&#353;',
		'&Yuml;' => '&#376;',				'&fnof;' => '&#402;',				'&circ;' => '&#710;',				'&tilde;' => '&#732;',				'&Alpha;' => '&#913;',
		'&Beta;' => '&#914;',				'&Gamma;' => '&#915;',				'&Delta;' => '&#916;',				'&Epsilon;' => '&#917;',			'&Zeta;' => '&#918;',
		'&Eta;' => '&#919;',				'&Theta;' => '&#920;',				'&Iota;' => '&#921;',				'&Kappa;' => '&#922;',				'&Lambda;' => '&#923;',
		'&Mu;' => '&#924;',					'&Nu;' => '&#925;',					'&Xi;' => '&#926;',					'&Omicron;' => '&#927;',			'&Pi;' => '&#928;',
		'&Rho;' => '&#929;',				'&Sigma;' => '&#931;',				'&Tau;' => '&#932;',				'&Upsilon;' => '&#933;',			'&Phi;' => '&#934;',
		'&Chi;' => '&#935;',				'&Psi;' => '&#936;',				'&Omega;' => '&#937;',				'&alpha;' => '&#945;',				'&beta;' => '&#946;',
		'&gamma;' => '&#947;',				'&delta;' => '&#948;',				'&epsilon;' => '&#949;',			'&zeta;' => '&#950;',				'&eta;' => '&#951;',
		'&theta;' => '&#952;',				'&iota;' => '&#953;',				'&kappa;' => '&#954;',				'&lambda;' => '&#955;',				'&mu;' => '&#956;',
		'&nu;' => '&#957;',					'&xi;' => '&#958;',					'&omicron;' => '&#959;',			'&pi;' => '&#960;',					'&rho;' => '&#961;',
		'&sigmaf;' => '&#962;',				'&sigma;' => '&#963;',				'&tau;' => '&#964;',				'&upsilon;' => '&#965;',			'&phi;' => '&#966;',
		'&chi;' => '&#967;',				'&psi;' => '&#968;',				'&omega;' => '&#969;',				'&thetasym;' => '&#977;',			'&upsih;' => '&#978;',
		'&piv;' => '&#982;',				'&ensp;' => '&#8194;',				'&emsp;' => '&#8195;',				'&thinsp;' => '&#8201;',			'&zwnj;' => '&#8204;',
		'&zwj;' => '&#8205;',				'&lrm;' => '&#8206;',				'&rlm;' => '&#8207;',				'&ndash;' => '&#8211;',				'&mdash;' => '&#8212;',
		'&lsquo;' => '&#8216;',				'&rsquo;' => '&#8217;',				'&sbquo;' => '&#8218;',				'&ldquo;' => '&#8220;',				'&rdquo;' => '&#8221;',
		'&bdquo;' => '&#8222;',				'&dagger;' => '&#8224;',			'&Dagger;' => '&#8225;',			'&bull;' => '&#8226;',				'&hellip;' => '&#8230;',
		'&permil;' => '&#8240;',			'&prime;' => '&#8242;',				'&Prime;' => '&#8243;',				'&lsaquo;' => '&#8249;',			'&rsaquo;' => '&#8250;',
		'&oline;' => '&#8254;',				'&frasl;' => '&#8260;',				'&euro;' => '&#8364;',				'&image;' => '&#8465;',				'&weierp;' => '&#8472;',
		'&real;' => '&#8476;',				'&trade;' => '&#8482;',				'&alefsym;' => '&#8501;',			'&larr;' => '&#8592;',				'&uarr;' => '&#8593;',
		'&rarr;' => '&#8594;',				'&darr;' => '&#8595;',				'&harr;' => '&#8596;',				'&crarr;' => '&#8629;',				'&lArr;' => '&#8656;',
		'&uArr;' => '&#8657;',				'&rArr;' => '&#8658;',				'&dArr;' => '&#8659;',				'&hArr;' => '&#8660;',				'&forall;' => '&#8704;',
		'&part;' => '&#8706;',				'&exist;' => '&#8707;',				'&empty;' => '&#8709;',				'&nabla;' => '&#8711;',				'&isin;' => '&#8712;',
		'&notin;' => '&#8713;',				'&ni;' => '&#8715;',				'&prod;' => '&#8719;',				'&sum;' => '&#8721;',				'&minus;' => '&#8722;',
		'&lowast;' => '&#8727;',			'&radic;' => '&#8730;',				'&prop;' => '&#8733;',				'&infin;' => '&#8734;',				'&ang;' => '&#8736;',
		'&and;' => '&#8743;',				'&or;' => '&#8744;',				'&cap;' => '&#8745;',				'&cup;' => '&#8746;',				'&int;' => '&#8747;',
		'&there4;' => '&#8756;',			'&sim;' => '&#8764;',				'&cong;' => '&#8773;',				'&asymp;' => '&#8776;',				'&ne;' => '&#8800;',
		'&equiv;' => '&#8801;',				'&le;' => '&#8804;',				'&ge;' => '&#8805;',				'&sub;' => '&#8834;',				'&sup;' => '&#8835;',
		'&nsub;' => '&#8836;',				'&sube;' => '&#8838;',				'&supe;' => '&#8839;',				'&oplus;' => '&#8853;',				'&otimes;' => '&#8855;',
		'&perp;' => '&#8869;',				'&sdot;' => '&#8901;',				'&lceil;' => '&#8968;',				'&rceil;' => '&#8969;',				'&lfloor;' => '&#8970;',
		'&rfloor;' => '&#8971;',			'&lang;' => '&#9001;',				'&rang;' => '&#9002;',				'&loz;' => '&#9674;',				'&spades;' => '&#9824;',
		'&clubs;' => '&#9827;',				'&hearts;' => '&#9829;',			'&diams;' => '&#9830;'];
}

/* class tags_from_fragment
{
	final public function __construct(DOMDocumentFragment $fragment, $tag)
	 {
		$this->fragment = $fragment;
		$this->tag = $tag;
	 }

	final public function Fetch()
	 {
		if($this->n)
		 {
			if($this->n->length) return $this->n->item(0);
			else
			 {
				$this->n = null;
				++$this->i;
			 }
		 }
		while($this->i < $this->fragment->childNodes->length)
		 {
			$child = $this->fragment->childNodes->item($this->i);
			if($child->nodeName === $this->tag) return $child;
			elseif($child->nodeType === XML_ELEMENT_NODE)
			 {
				$this->n = $child->GetElementsByTagName($this->tag);
				if($this->n->length) return $this->n->item(0);
			 }
			++$this->i;
		 }
	 }

	private $fragment;
	private $tag;
	private $i = 0;
	private $n;
} */
?>