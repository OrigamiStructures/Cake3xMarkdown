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
class MarkdownHelper extends Helper {
	
	protected $Parser = NULL;
	
	/**
	 * Convert markdown to html
	 *
	 * @param  string $text Text in markdown format
	 * @return string
	 */
	public function transform($text) {
		if (is_null($this->Parser)) {
//			if (!class_exists('Markdown')) {
//				App::import('Vendor', 'Markdown.MarkdownExtra' . DS . 'Michelf' . DS . 'Markdown');
//			}
			$this->Parser = new Markdown();
		}
		return $this->Parser->transform($text);
	}

}
