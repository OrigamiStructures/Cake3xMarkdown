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
	
	protected $Geshi = FALSE;
	
	public function __construct(\Cake\View\View $View, array $config = array()) {
		$config += $this->defaultConfig;
		$this->helpers += (array) $config['helpers'];
		parent::__construct($View, $config);
	}


	/**
	 * Convert markdown to html
	 *
	 * @param  string $text Text in markdown format
	 * @return string
	 */
	public function transform($text) {
		if ($this->_View->Geshi) {
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
//		$this->exploded_text = explode("```", $text);
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
			array_push($match, str_replace($match[0], '', $chunk));
			debug($match);
			if (!empty($match[1])) {
				return $this->_View->Geshi->parse($chunk, $match[2]);
			} else {
				$chunk = "```\n{$match[2]}\n```\n";
			}
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
