##Basic use

To install the CakePHP3xMarkdown plugin modify your bootstrap file:

```php
// src/config/bootstrap.php
Plugin::load('CakePHP3xMarkdown', ['autoload' => TRUE]);
```

The CakePHP3xMarkdownHelper can be loaded into the controller(s) of your choice by including it in the controllers `$helpers` array:

```php
// src/Controllers/ArticlesController.php
public $helpers = [‘Form’, ‘Html’, ‘CakePHP3xMarkdown’];
```

This is all it takes to install for the simplest use case - providing markdown text and receiving HTML output. With this installation, here is an example view:

```php
// src/Templates/Articles/view.ctp
$this->CakePHP3xMarkdown->transform(article->text);
```
##Caching your Markdown output

Parsing Markdown adds a lot of overhead to your render so you may want to use the caching features of the Helper. To use caching you’ll need to pass an object (typically your Entity) to `CakePHP3xMarkdown::transform()` rather than a string. And you’ll need to make your Entity object implement the MarkdownInterface.

You’ll also need to create a Cache configuration, typically in your `config/app.php`.


Here is an example Cache config:

```php
// src/config/app.php
Cache = [
    /**
    * Mardown cache to reduce rendering overhead
    */
    '_markdown_' => [
        'className' => 'File',
        'prefix' => 'markdown_output',
        'path' => CACHE . 'markdown',
        'duration' => ‘+1 weeks’
    ]
],
```

Here is the Interface your Entity will need to implement:

```php
<?php
namespace Cake3xMarkdown\Model\Entity\Interfaces;

interface MarkdownInterface {
        
    /**
     * Should caching be used
     * 
     * @return boolean Indicate whether the output should be cached
     */
    public function markdownCaching($options = NULL);
    
    /**
     * Return the markdown text
     * 
     * @return string The raw text-markdown
     */
    public function markdownSource($options = NULL);
    
   /**
     * Return the cache key to use
     * 
     * @return string The cache key
     */
    public function markdownCacheKey($options = NULL);
    
    /**
     * Return the config to use for caching
     * 
     * @return string Then name of the cache config
     */
    public function markdownCacheConfig($options = NULL);
    
}
```

Here is an example of how you might implement the interface in your Entity:

```php
<?php
// src/Model/Entity/Article.php

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake3xMarkdown\Model\Entity\Interfaces\MarkdownInterface;
use Cake3xMarkdown\Model\Entity\Interfaces\GeshiInterface;

/**
 * Article Entity.
 */

class Article extends Entity implements MarkdownInterface {
    
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        'text' => true,
    ];

    /**
     * Turn caching on or off
     *
     * No arguments are passed from the Helper
     */
    public function markdownCaching($options = NULL) {
        return TRUE;
    }

    /**
     * This is the Cache config that will be used
     *
     * The Helper object is passed in on $options so you can use
     * other Entity values and rendering context values to make
     * decisions about the cache to use
     */
    public function markdownCacheConfig($options = NULL) {
        return '_markdown_';
    }

    /**
     * This key will be appended to the Cache prefix
     *
     * The Helper object is passed in on $options so you can use
     * other Entity values and rendering context values to make
     * decisions about the key to return
     */
    public function markdownCacheKey($options = NULL) {
        return '_article' . $this->id . ‘_’ . $this->modified->toUnixString();
    }

   /**
     * Return the column that contains the markdown
     *
     * The Helper object is passed in on $options so
     * you can use the rendering context values to make
     * decisions about what to return
     */
    public function markdownSource($options = NULL) {
        return $this->text;
    }

}
```

With this setup your article output will be cached for 1 week or until `articles->modified` changes.

##Highlighting source code in your markdown

Markdown is a bit limited if you’re text contains source code. This article is a perfect example of the mixed content situation. There is an excellent PHP source-code highlighting utility, GeSHi, that can correct this deficit. I’ve written the GeshiHelper plugin to directly output highlighted source code in your pages. But I’ve also integrated that plugin into CakePHP3xMarkdown to provide a seamless way to render your markdown which also contains source code.

Install the Geshi plugin as described here. Then modify the Controller’s `$helpers` property to use the Geshi plugin.

```php
// src/Controllers/ArticlesController.php
public $helpers = [‘Form’, ‘Html’, ‘CakePHP3xMarkdown’ => ['helpers' => 'Geshi.Geshi']];
```

Calling `CakePHP3xMarkdown::transform()` is done exactly as before using either a string or an object depending on your needs. But now if you pass pass your mixed markdown and source code, the code will be highlighted. Here is a mixed content text block illustrating how to delimit your code blocks:

<pre>
    #This is an example markdown h1
        
    This is example markdown paragraph
    
    - this
    - is
    - a
    - list
    
    ```php
        // this is a php code block
        $Thing = new Thing();
        $a = $Thing->method();
    ```
    ```javascript
        // this is a javascript code block
        var wonders = ‘never cease’;
        var prefix = ‘wonders’;
        var phrase = prefix + ‘ ‘ + wonders;
    ```
</pre>

The default delimiters for code blocks are the same as used on github. Open the block with a new line followed by 3 backticks (```) followed by the name of the language you want highlighted, then on a new line start your code. End the code block with a new line containing 3 backticks.

Geshi supports over 100 languages and allows you to add language support if you need.

Simply call transform with this as a string or an object and you’ll get beautiful mixed-content pages like the one you’re reading now.

```php
// src/Templates/Articles/view.ctp
$this->CakePHP3xMarkdown->transform(article->text);
$this->CakePHP3xMarkdown->transform(article);
```

##Accessing advanced GeSHi features

The default Geshi css (which is render in-line in the output HTML) is pretty good. So that may be all you need. That’s the limit of what you can get by passing a string argument. Passing an object allows you to use an expanded range of GeSHi features like enabling stylesheets or controlling formatting of your rendered code blocks. As with enabling the caching feature, you’ll need to implement an interface in your Entity to use the advanced GeSHi features. 

This is the GeshiInterface you’ll need to support for advanced control of source code rendering:

```php
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
     *    list($start_delimeter, $language, $source_code, $end_delimeter) = $args;
     * 
     * @param type $args
     */
    public function geshiTemplate($args);
    
    /**
     * Retrieve the language to use when highlighting the current source code
     * 
     * CakePHP3xMarkdown Helper provides a 4 element array at $args. 
     * I recommend the first line of you function be:
     *    list($start_delimeter, $language, $source_code, $end_delimeter) = $args;
     *     
     * @param type $args
     */
    public function geshiLanguage($args);
    
}
```

Here is what you might add to your Entity to implement the new interface:

```php
<?php

// change your class declaration to
class Article extends Entity implements MarkdownInterface, GeshiInterface {

// your class properties and methods as before
// plus these sample implementations of the new interface

    public function geshiTemplate($args) {
        list($start_delimeter, $language, $source_code, $end_delimeter) = $args;
        if ($language !== 'bash') {
            return 'template';
        } else {
            return 'bash';
        }
    }

    public function geshiLanguage($args) {
        list($start_delimeter, $language, $source_code, $end_delimeter) = $args;
        return $language;
    }

}
```

To use the enhanced Geshi features you must implement both interfaces, even if you don’t want to cache your output. 

Before calling the `transform()` method you’ll want to create the templates. This can be done anywhere that has access to GeshiHelper. Remember, CakePHP maintains Helpers as Singletons so you can do this on a Geshi helper object outside of the CakePHP3xMarkdown helper and the templates will also be available inside the Markdown helper on its Geshi instance.

```php
// create a template
$this->Geshi->template('Template');
// make changes to how it will render
$this->Geshi->template('Template')->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
$this->Geshi->template('Template')->set_overall_style('padding: 1em 1.5em; background-color: wheat');

// now all source code rendered through Template will have a 
// background color and line numbers. Any other template will
// have the default formatting.
```

For more information about the Geshi Plugin and the use of Geshi templates please see the documentation.

##The next level

You can change how code is delimited if you need to. Perhaps you have some markdown that uses some slight variation and you want to use it without modification. Or you want to send more information to the GeshiInterface methods to make sophisticated template selection possible.

<pre>
    // example of fancy delimiting
    ```php:plugin
        // php plugin code here
    ```
    ```php:implementation
        // php implementation code here
    ```
</pre>

With these delimiters, your system could use two different templates for php code to help your reader’s better understand your article. But these opening delimiters would not be recognized by the Helper. You can provide new regex to detect your custom delimiters.

```php
// set a new starting pattern
$this->CakePHP3xMarkdown->delimiter(‘start’, $start_pattern);
$this->CakePHP3xMarkdown->delimiter(‘end’, $end_pattern);
```

See the comments in the source code for details about the construction of delimiters and what they must return to maintain system functionality.

It’s worth noting though that the default delimiters can probably be used to accomplish your needs for exotic parameter passing. The language name is matched by this pattern:

```regex
[a-z0-9\-]*
```

There are no Geshi languages that contain double hyphens or digits surrounded by hyphens. So you could make language names like `php--plugin` and `php--implementation`, then have `CakePHP3xMarkdown->geshiTemplate()` and `CakePHP3xMarkdown->geshiLanguage()` parse the values you need.
