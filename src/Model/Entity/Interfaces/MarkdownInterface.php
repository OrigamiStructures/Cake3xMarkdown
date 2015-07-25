<?php
namespace Cake3xMarkdown\Model\Entity\Interfaces;

/**
 *
 * @author dondrake
 */
interface MarkdownInterface {
		
	/**
	 * Return the markdown text
	 * 
	 * @return string The raw markdown text
	 */
	public function markdownSource($options = NULL);
	
	/**
	 * Should caching be used
	 * 
	 * @return boolean Indicate whether the output should be cached
	 */
	public function markdownCaching($options = NULL);
	
	/**
	 * Return the cache key to use
	 * 
	 * @return string The cache key
	 */
	public function markdownCacheKey($options = NULL);
	
	/**
	 * Return the config to use for this cache
	 * 
	 * @return string Then name of the cache config
	 */
	public function markdownCacheConfig($options = NULL);
	
}
