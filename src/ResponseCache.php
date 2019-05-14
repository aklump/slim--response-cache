<?php

namespace AKlump\Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Body;
use Slim\Http\Response;
use Slim\HttpCache\CacheProvider;

/**
 * Stores content in a file-based cache and serves it up based on headers.
 *
 * You must do something like the following to establish a content cache id in
 * a middlewear that runs before this one.  The cache id should be based on the
 * parameters of the request that distinguish it's response to be different
 * from others, e.g. user id.
 *
 * @code
 *   $params = $request->getQueryParams();
 *   $user_id = isset($params['top']) ? $params['top'] : NULL;
 *   $matrix_id = $request->getAttribute('program_id');
 *   $cache_id = CacheLayer::id([$program_id, $user_id]);
 *   $request = $request->withAttribute('response_cache_id', $cache_id);
 * @endcode
 *
 * @package AKlump\Slim
 */
final class ResponseCache {

  /**
   * The HTTP cache provider.
   *
   * @var \Slim\HttpCache\CacheProvider
   */
  protected $cacheProvider;

  /**
   * The object handling the caching.
   *
   * @var \AKlump\Slim\Middleware\ResponseCacheInterface
   */
  protected $cache;

  /**
   * The number of seconds to allow content to live in cache.
   *
   * @var int
   */
  protected $lifetime;

  /**
   * A callable that may modify the content just before caching.
   *
   * @var callable|null
   */
  protected $onBeforeCache;

  /**
   * ContentCache constructor.
   *
   * @param \Slim\HttpCache\CacheProvider $cache_provider
   *   An instance of a cache provider.
   * @param \AKlump\Slim\Middleware\ResponseCacheInterface $cache
   *   The cache object that does the actual caching of content.
   * @param int $lifetime
   *   The number of seconds to persist the cached content.
   * @param callable|null $on_before_cache
   *   An optional callable that receives (\DateTime $last_modified, string
   *   $cacheable_content) as arguments and must return $cacheable_content.
   *   Use this to alter the content just prior to caching.  For example, add a
   *   footer note about when this content was last updated.
   */
  public function __construct(
    CacheProvider $cache_provider,
    ResponseCacheInterface $cache,
    int $lifetime,
    callable $on_before_cache = NULL
  ) {
    $this->cacheProvider = $cache_provider;
    $this->cache = $cache;
    $this->lifetime = $lifetime;
    $this->onBeforeCache = $on_before_cache;
  }

  /**
   * Middleware to handle the content, file-based cache.
   *
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *   PSR7 request.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   PSR7 response.
   * @param callable $next
   *   Next middleware.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The new response object.
   */
  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface {
    // Check for the response_cache_id attribute which should have been set by
    // another middlewear that has analyzed the request and figured the cid that
    // would work for this.  Withtout this piece we will not allowing caching.
    $cache_id = $request->getAttribute('response_cache_id');
    $is_cacheable = $this->lifetime && !empty($cache_id);
    $cache_control = $request->getHeader('cache-control');
    $cache_control = reset($cache_control);
    if ($is_cacheable && !empty($cache_control)) {
      // Determine the values that will make us not be able to cache by
      // leveraging the cacheProvider assuming it knows best.
      $temp = $this->cacheProvider->denyCache(new Response());
      $not_cacheable = explode(',', $temp->getHeader('cache-control')[0]);
      foreach ($not_cacheable as $declaration) {
        if (strstr($cache_control, $declaration) !== FALSE) {
          $is_cacheable = FALSE;
          break;
        }
      }
    }

    // If not cacheable then this middlewear has done it's thing.
    if (!$is_cacheable) {
      return $this->cacheProvider->denyCache($next($request, $response));
    }

    // When we're at this point we skip the route processing that is why we
    // return the response right here.
    $response = $this->cacheProvider->allowCache($response, 'public', $this->lifetime);
    $cached = $this->cache->get($cache_id);
    $last_modified = $cached['modified'];
    $is_expired = $last_modified + $this->lifetime < time();
    if (!$is_expired && $cached['body']) {
      $response = $response->withBody(new Body(fopen('php://temp', 'r+')))
        ->write($cached['body']);
      foreach ($cached['headers'] as $k => $v) {
        $response = $response->withHeader($k, $v);
      }
      $response = $this->addResponseHeaders($response, $last_modified);

      return $response;
    }

    // Set the cache for next time.
    $response = $next($request, $response);

    // Allow modification of the content just before it's cached.  This is here
    // so you can do something like adding a "last modified on: ..." phrase to
    // the cached cotent.
    $cacheable_content = $before = $response->getBody();
    if (is_callable($this->onBeforeCache)) {
      $callback = $this->onBeforeCache;
      $cacheable_content = $callback(date_create()->setTimestamp($last_modified), $cacheable_content);
      if ($before !== $cacheable_content) {
        $response = $response->withBody(new Body(fopen('php://temp', 'r+')))
          ->write($cacheable_content);
      }
    }
    $this->cache->set($cache_id, $response);

    return $this->addResponseHeaders($response);
  }

  /**
   * Add HTTP cache headers to a response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response object.
   * @param int $last_modified
   *   The last modified timestamp.
   *
   * @return \Slim\Http\Response
   *   The response with cached headers added.
   */
  private function addResponseHeaders(ResponseInterface $response, int $last_modified = NULL): ResponseInterface {
    $last_modified = $last_modified ? $last_modified : time();
    $response = $this->cacheProvider
      ->withExpires($response, $last_modified + $this->lifetime);
    $response = $this->cacheProvider
      ->withLastModified($response, $last_modified);
    $response = $this->cacheProvider
      ->withEtag($response, md5(strval($response->getBody())));

    return $response;
  }

}
