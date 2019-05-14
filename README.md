# Content Cache Middleware

## Summary

This project provides a means of caching responses so that subsequent requests do not pass through the route callbacks.  It is helpful if the route callbacks involve expensive calculations to generate content.  It is built on the [HTTP Caching middleware](http://www.slimframework.com/docs/v3/features/caching.html) and should be used instead of that middleware.  It ships with a file-based cache storage, but supports other types of caching via `\AKlump\Slim\Middleware\ContentCacheInterface`.  

## Quick Start

    $container = $app->getContainer();
    $container['cache'] = function () {
      return new \Slim\HttpCache\CacheProvider();
    };
    $container['content_cache'] = function () {
      return new \AKlump\Slim\Middleware\FileCache('/path/to/cache/dir');
    };
    
    // Register this middleware.
    $app->add(new \AKlump\Slim\Middleware\ContentCache($container['cache'], $container['content_cache'], 3600));
    
## Requirements

1. The response body must be able to be cast to a string.    

## Contributing

If you find this project useful... please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Faklump_slim).

## Installation

1. This middleware replaces [HTTP Caching](http://www.slimframework.com/docs/v3/features/caching.html) therefore you should not add that middleware as shown in those instructions.  **Do not do the following:**
        
        $app->add(new \Slim\HttpCache\Cache('public', 86400));
        
1. However, you will need to register the service provider from that middleware as shown in the _Quick Start_ above.

## Advanced Usage

### How do I alter the body prior to caching?

If you want to alter the response body content before it is written to cache use the fourth argument callback as shown below, which adds a last modified note

    $app->add(new \AKlump\Slim\Middleware\ContentCache(
      $container['cache'],
      $container['content_cache'],
      3600,
      function (\DateTime $modified, $html_body) use ($settings) {
        return $html_body . sprintf("Last modified: %s', $modified->format('r'));
      }
    ));

### How do I use a database for caching content?

You may write your own class that implements `\AKlump\Slim\Middleware\ContentCacheInterface`.
