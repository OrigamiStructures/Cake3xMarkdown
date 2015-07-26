<?php
namespace Cake3xMarkdown\Model\Entity\Interfaces;

/**
 * Interface 
 *
 * @author dondrake
 */
interface GeshiInterface {
	
	/**
	 * Retrieve the template name to use when rendering the current source code
	 * 
	 * CakePHP3xMarkdown Helper provides a 4 element array at $args. 
	 * I recommend the first line of you function be:
	 *	list($start_delimeter, $language, $source_code, $end_delimeter) = $args;
	 * 
	 * @param type $args
	 */
	public function geshiTemplate($args);
	
	/**
	 * Retrieve the language to use when highlighting the current source code
	 * 
	 * CakePHP3xMarkdown Helper provides a 4 element array at $args. 
	 * I recommend the first line of you function be:
	 *	list($start_delimeter, $language, $source_code, $end_delimeter) = $args;
	 * 
	 * @param type $args
	 */
	public function geshiLanguage($args);
	
}
