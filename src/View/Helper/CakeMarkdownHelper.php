<?php

/*
 * Copyright 2015 Origami Structures
 */

namespace Cake3xMarkdown\View\Helper;

use Cake\View\Helper;
use Cake3xMarkdown\Vendor\PhpMarkdown\Michelf\Markdown;

/**
 * CakePHP MarkdownHelper
 * @author jasont
 */
class CakeMarkdownHelper extends Helper {
	
	protected $defaultConfig = ['helpers' => []];
	
	protected $Parser = NULL;
	
//	public $Geshi = FALSE;
	
	public function __construct(\Cake\View\View $View, array $config = array()) {
		$config += $this->defaultConfig;
		$this->helpers += (array) $config['helpers'];
		parent::__construct($View, $config);
		if (!is_object($this->Geshi)) {
			$this->Geshi = FALSE;
		}
	}


	/**
	 * Convert markdown to html
	 *
	 * @param  string $text Text in markdown format
	 * @return string
	 */
	public function transform($text) {
		if ($this->Geshi) {
			return $this->transformGeshi($text);
		} else {
			return $this->transformMarkdown($text);
		}
		
	}
	
	private function transformMarkdown($text) {
		if (is_null($this->Parser)) {
			$this->Parser = new Markdown();
		}
		return $this->Parser->transform($text);
	}
	
	private function transformGeshi($text) {
		$this->exploded_text = preg_split("/```[\n|\r]/", $text);
		debug(count($this->exploded_text));
		$result = [];
		foreach ($this->exploded_text as $chunk) {
			$result[] = $this->handleTextChunk($chunk);
		}
		echo implode("\n", $result);
	}
	
	private function handleTextChunk($chunk) {
//		debug();
		if (preg_match("/^[\n|\r]?\^([a-z0-9\-]*)\W{1}/", $chunk, $match)) {
			list($marker, $language) = $match;
			$source_code = str_replace($marker, '', $chunk);
			
			debug($match);
			if (!empty($language)) {
				return $this->Geshi->parse($source_code, $language);
			} //else {
//				$chunk = "```\n$source_code\n```\n";
//			}
		}
		return $this->transformMarkdown($chunk);
		
	}
	
//	private function geshiExtract($text) {
//		$amended_
//		$m = explode('```', $text);
//		foreach ($m as $chunk) {
//			
//		}
//	}

}
