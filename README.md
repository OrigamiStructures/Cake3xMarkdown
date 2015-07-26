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

Install the Geshi plugin as described here. then modify the Controller’s `$helpers` property to use the Geshi plugin.

```php
// src/Controllers/ArticlesController.php
public $helpers = [‘Form’, ‘Html’, ‘CakePHP3xMarkdown’ => ['helpers' => 'Geshi.Geshi']];
```

Now, if you are using the simple installation described first, you can pass your mixed markdown and source code as a string as before as in this example:

<pre>
    #This is an example
        
    This is standard markdown
    
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

The default delimiters for code blocks are the same as used on github. Open the block on a new line with 3 backticks (```) followed by the language you want highlighted, then on a new line start your code. End the code block with a new line containing 3 backticks.

Geshi supports over 100 languages and allows you to add language support if you need.


```php
// src/Templates/Articles/view.ctp
$this->CakePHP3xMarkdown->transform(article->text);
```

Or, if you’ve used the advanced techniques to use caching, you simply make the call passing the object rather than the string:

```php
// src/Templates/Articles/view.ctp
$this->CakePHP3xMarkdown->transform(article);
```

The default Geshi css (which is render in-line in the output HTML) is pretty good. So that may be all you need. But if you want to control the highlighting css to a greater degree you’ll need to pass your entity to `transform()` and add implementation for the GeshiInterface. 

This is the GeshiInterface you’ll need to support for advanced control of source code rendering:




