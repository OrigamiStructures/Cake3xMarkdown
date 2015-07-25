<?php

/*
 * Copyright 2015 Origami Structures
 */

namespace Cake3xMarkdown\View\Helper;

use Cake\View\View;
use Cake\View\Helper;
use Cake3xMarkdown\Vendor\PhpMarkdown\Michelf\Markdown;
use Cake3xMarkdown\Model\Entity\Interfaces\MarkdownInterface;
use Cake3xMarkdown\Model\Entity\Interfaces\GeshiInterface;
use \Cake\Cache\Cache;

/**
 * CakePHP MarkdownHelper
 * @author jasont
 */
class CakeMarkdownHelper extends Helper {
	
	protected $defaultConfig = ['helpers' => []];
	
	protected $Parser = NULL;
	
	protected $code_start_delimeter = '([\n|\r]```([a-z0-9\-]*)[\n|\r])|^(```([a-z0-9\-^]*)[\n|\r])';
	protected $code_end_delimeter = '([\n|\r]```[\n|\r])';
	
	protected $markdownInterface = FALSE;
	protected $geshiInterface = FALSE;

	/**
	 * Construct, optionally with GeshiHelper for code highlighting
	 * 
	 * @param \Cake\View\View $View
	 * @param array $config
	 */
	public function __construct(View $View, array $config = array()) {
		$config += $this->defaultConfig;
		$this->helpers += (array) $config['helpers'];
		parent::__construct($View, $config);
		if (!is_object($this->Geshi)) {
			$this->Geshi = FALSE;
		}
	}

	/**
	 * Choose Markdown only or Geshi+Markdown text processing
	 *
	 * @param  object|string $source Text in markdown format or an Entity
	 * @return string
	 */
	public function transform($source) {
		if (is_object($source)) {
//			debug($source);
//			debug($source->modified->nice());die;
			return $this->transformObject($source);
		} else {
			return $this->chooseTransform($source);
		}
	}
	
	/**
	 * Deal with the markdown arriving as data in an object
	 * 
	 * This option allows the use of output caching and is also the gateway 
	 * to advance Geshi implementation if Geshi is being used.
	 * 
	 * @param MarkdownInterface $source
	 * @return type
	 */
	private function transformObject(MarkdownInterface $source) {
		if ($source->markdownCaching()) {
			$output = Cache::read($source->markdownCacheKey(), $source->markdownCacheConfig());
//			debug($output);die;
			if ($output === false) {
				$output = $this->chooseTransform($source->markdownSource());
				debug(strlen($output));
				Cache::write($source->markdownCacheKey(), $output, $source->markdownCacheConfig());
				debug(strlen($output));
			}
			debug(strlen($output));
			return $output;
		} else {
			return $this->chooseTransform($source->markdownSource());
		}
	}

	/**
	 * Take raw text and route it to the proper processing method
	 * 
	 * If the Geshi Helper is in play, process assuming the markdown has 
	 * source code in it needing highlighting. Otherwise,do straight markdown.
	 * 
	 * @param string $text
	 * @return string
	 */
	private function chooseTransform($text) {
		if ($this->Geshi) {
			return $this->transformMixed($text);
		} else {
			return $this->transformMarkdown($text);
		}
	}

	/**
	 * Transform pure markdown
	 * 
	 * @param string $text
	 * @return string
	 */
	private function transformMarkdown($text) {
		if (is_null($this->Parser)) {
			$this->Parser = new Markdown();
		}
		return $this->Parser->transform($text);
	}
	
	/**
	 * Split the text on code-block delimeters
	 * 
	 * If GeshiHelper is in use the output text will be analized for the presence 
	 * of source code. The code delimeters are used to split the text into an array.
	 * The delimeters are designed with capture blocks so that code blocks will 
	 * become four elements:
	 *	[first delimeter(FD)][language(L)][source code(SC)][last delimeter(LD)]
	 * Regular markdown text will be in single elements:
	 *	[non code block(NCB)]
	 * 
	 * So a mixed text block will be something like this:
	 *	'text php-code text sql-code php-code text' would yield 
	 *	[NBC, FD, L, SC, LD, NBC, FD, L, SC, LD,  FD, L, SC, LD, NBC]
	 * 
	 * @param string $text The full block for output
	 */
	private function transformMixed($text) {
		$pattern = '/'.$this->code_start_delimeter.'|'.$this->code_end_delimeter.'/';
		$this->exploded_text = preg_split($pattern, $text, NULL, PREG_SPLIT_DELIM_CAPTURE);

		$result = [];
		$end = count($this->exploded_text);
		for ($i = 0; $i < $end; $i++) {
			list ($result[], $i) = $this->handleTextChunk($i);
		}
		return implode("\n", $result);
	}
	
	/**
	 * Produce output from markdown-text or source code
	 * 
	 * Given the pointer into an array, determine if we are pointing to the 
	 * beginning of a code block or if it is simply text (markdown). 
	 * Source code is detected if the element matches code_start_delimeter. 
	 * If that is the case, the next element will be the language, the one after 
	 * that will be the source code to render and after that, the code_end_delimeter.
	 * 
	 * @param int $pointer Adjusted to point at the last accessed element
	 * @return array [output_text, index]
	 */
	private function handleTextChunk($pointer) {
		
		if (preg_match("/$this->code_start_delimeter/", $this->exploded_text[$pointer])) {
			// code sequence in the array will be
			// start-delimeter, language, source-code, end-delimeter
			$language = $this->exploded_text[++$pointer];
			$source_code = $this->exploded_text[++$pointer];
			return [$this->Geshi->parse($source_code, $language), ++$pointer]; // pointer moved to target the end delimeter
		}
		return [$this->transformMarkdown($this->exploded_text[$pointer]), $pointer];
	}
	
	/**
	 * Modify the code-block detection delimeters
	 * 
	 * Pass no arguements to see the current delimeters.
	 * 
	 * The delimeters are used in preg_split to separate source code blocks in 
	 * your text from markdown. This 'split' process is only used if you have 
	 * configured this helper to use Geshi for code highlighting.
	 * 
	 * preg_split is used with the PREG_SPLIT_DELIM_CAPTURE flag to make the 
	 * array that results from the split return parenthesized values from your 
	 * patterns.
	 * 
	 * Your regex must be designed to break the text into a specific array pattern 
	 * and the code blocks won't highlight if they don't fit the pattern
	 * 
	 * The delimeters are designed with capture blocks (parenthesized sections) 
	 * so that code blocks will become four elements:
	 *	first delimeter(FD), language(L), source code(SC), last delimeter(LD)
	 * Regular markdown text will be in single elements:
	 *	non code block(NCB)
	 * 
	 * So a mixed text block will be something like this:
	 *	'text php-code text sql-code php-code text' would yield 
	 *	[NBC, FD, L, SC, LD, NBC, FD, L, SC, LD,  FD, L, SC, LD, NBC]
	 * 
	 * The default expected pattern is the same patter github supports:
	 * 
	 *	```jquery			// beginning of string or newline + 3 backticks + language-name
	 *	source code here	// your language code for highlighting
	 *	```					// newline + 3 backticks + newline
	 * 
	 * The default regex is
	 * 	start_delimeter = '([\n|\r]```([a-z0-9\-]*)[\n|\r])|^(```([a-z0-9\-^]*)[\n|\r])'
	 *		roughly (¶```(jquery)¶) | ^(```(jquery)¶) 
	 *	end_delimeter = '([\n|\r]```[\n|\r])';
	 *		roughly (¶```¶)
	 * 
	 * So the start delimeter will create 2 array elements, then the code will 
	 * be in an element, then the end delimeter will be in one array element.
	 * 
	 * @param string $delimeter_name start, begin, first, open or some word for the 'end' delimeter
	 * @param string $regex
	 * @return array
	 */
	public function delimeter($delimeter_name = NULL, $regex = NULL) {
		if (is_null($name)) {
			return ['code_start_delimeter' => $this->code_start_delimeter, 'code_end_delimeter' => $this->code_end_delimeter];
		}
		$start_words = ['start', 'begin', 'first', 'open'];
		if (!is_null($regex)) {
			$delimeter_name = in_array($name, $start_words) ? 'code_start_delimeter' : 'code_end_delimeter';
			$this->$delimeter_name = $regex;
		}
	}
}

