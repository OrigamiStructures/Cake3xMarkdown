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
	
	/**
	 * An instance of Markdown
	 *
	 * @var object
	 */
	protected $Parser = NULL;
	
	/**
	 * Delimeter identifying the start of a source-code block in the markdown
	 *
	 * @var regex
	 */
	protected $code_start_delimeter = '([\n|\r]```([a-z0-9\-]*)[\n|\r])|^(```([a-z0-9\-^]*)[\n|\r])';
	
	/**
	 * Delimeter identifying the end of a source-code block in the markdown
	 *
	 * @var regex
	 */
	protected $code_end_delimeter = '([\n|\r]```[\n|\r])';
	
	/**
	 * If Geshi is in play, this will hold the markdown/source code chunks
	 * 
	 * The text will be preg_split using the code delimeters
	 *
	 * @var array
	 */
	protected $chunked_text;


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
	 * Perform cache-aware, Geshi-aware processing of markdown text
	 * 
	 * Pass your markdown text and get the html for output. 
	 * 
	 * If the GeshiHelper has been installed, properly delimeted source code 
	 * in your markdown will be nicely highlighted.
	 * 
	 * To use caching of the output you'll need to pass an object instead of 
	 * a text string. The object must implement the MarkdownInterface.
	 * 
	 * To gain greater control over the geshi output you'll need to pass an object 
	 * that implements the GeshiInterface
	 *
	 * @param  object|string $source Text in markdown format or an object
	 * @return string
	 */
	public function transform($source) {
		if (is_object($source)) {
			if (!$source instanceof MarkdownInterface) {
				throw new \BadFunctionCallException(
						'CakeMarkdown::transform() argument must be an object that implements MardownInterface or a string');
			}
			if ($source->markdownCaching()) {
				$output = Cache::read($source->markdownCacheKey($this), $source->markdownCacheConfig($this));
				if ($output) {
					return $output;
				}
			}			
		}
		$output = $this->chooseTransform($source);
		if (is_object($source) && $source->markdownCaching()) {
			Cache::write($source->markdownCacheKey($this), $output, $source->markdownCacheConfig($this));
		}
		return $output;
	}
	
	/**
	 * Route the input for pure-markdown proceesing or mixed markdown-geshi
	 * 
	 * If the Geshi Helper is in play, process assuming the markdown has 
	 * source code in it needing highlighting. Otherwise,do straight markdown.
	 * 
	 * @param oject|string $source
	 * @return object|string
	 */
	private function chooseTransform($source) {
		if ($this->Geshi) {
			return $this->transformMixed($source);
		} else {
			return $this->transformMarkdown($source);
		}
	}

	/**
	 * Transform pure markdown
	 * 
	 * @param string $source
	 * @return object|string
	 */
	private function transformMarkdown($source) {
		if (is_null($this->Parser)) {
			$this->Parser = new Markdown();
		}
		$text = is_object($source) ? $source->markdownSource($this) : $source;
		return $this->Parser->transform($text);
	}
	
	/**
	 * Split the text on code-block delimeters
	 * 
	 * If GeshiHelper is in use the output text will be analized for the presence 
	 * of source code. The code delimeters are used to split the text into an array.
	 * The delimeters are designed with capture blocks so that code blocks will 
	 * become four array elements:
	 *	[first delimeter(FD), language(L), source code(SC), last delimeter(LD)]
	 * Regular markdown text will be in single elements:
	 *	[non code block(NCB)]
	 * 
	 * So a mixed text block with content in this pattern
	 *	'text php-code text sql-code php-code text' 
	 * would yield an array like this
	 *	[NBC, FD, L, SC, LD, NBC, FD, L, SC, LD,  FD, L, SC, LD, NBC]
	 * 
	 * @param object|string $source
	 */
	private function transformMixed($source) {
		$pattern = '/'.$this->code_start_delimeter.'|'.$this->code_end_delimeter.'/';
		$text = is_object($source) ? $source->markdownSource($this) : $source;
		$this->chunked_text = preg_split($pattern, $text, NULL, PREG_SPLIT_DELIM_CAPTURE);

		$result = [];
		$end = count($this->chunked_text);
		for ($i = 0; $i < $end; $i++) {
			list ($result[], $i) = $this->handleTextChunk($source, $i);
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
	 * @param object|string $source
	 * @param int $pointer
	 * @return array [output_text, pointer]
	 */
	private function handleTextChunk($source, $pointer) {
		
		if (preg_match("/$this->code_start_delimeter/", $this->chunked_text[$pointer])) {
			$code_chunk = [$this->chunked_text[$pointer],
				$this->chunked_text[++$pointer],
				$this->chunked_text[++$pointer],
				$this->chunked_text[++$pointer]];
			return [$this->transformGeshi($source, $code_chunk), $pointer];
		}
		return [$this->transformMarkdown($this->chunked_text[$pointer]), $pointer];
	}
	
	/**
	 * Delegate processing of source-code to the GeshiHelper
	 * 
	 * If a string was supplied as input, just accept Geshi defaults
	 * 
	 * If an object was supplied and it implements GeshiInterface, a template 
	 * will be used and that can contain custom formatting
	 * 
	 * @param object|string $source
	 * @param array $code_chunk
	 * @return string
	 */
	private function transformGeshi($source, $code_chunk) {
		list($start_delimeter, $language, $source_code, $end_delimeter) = $code_chunk;
		debug($source_code);
		$source_code = trim($source_code, "\n\r");
		debug($source_code);
		if (is_object($source) && $source instanceof GeshiInterface) {
			$template = ucfirst($source->geshiTemplate($code_chunk));
			$make_method = "make$template";
			return $this->Geshi->$make_method($source_code, $source->geshiLanguage($code_chunk))->parse_code();
		}
		return $this->Geshi->parse($source_code, $language);
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
	 * @param string $delimeter_name
	 * @param string $regex
	 * @return array
	 */
	public function delimeter($delimiter_name = NULL, $regex = NULL) {
		if (is_null($name)) {
			return ['code_start_delimiter' => $this->code_start_delimiter, 'code_end_delimiter' => $this->code_end_delimiter];
		}
		$start_words = ['start', 'begin', 'first', 'open'];
		if (!is_null($regex)) {
			$delimiter_name = in_array($name, $start_words) ? 'code_start_delimiter' : 'code_end_delimiter';
			$this->$delimiter_name = $regex;
		}
	}
}

