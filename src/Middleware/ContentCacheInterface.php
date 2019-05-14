<?php

namespace AKlump\Slim\Middleware;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface ContentCacheInterface.
 *   For classes that handle content caching.
 *
 * @package AKlump\Slim
 */
interface ContentCacheInterface {

  /**
   * Get a cached timestamp and value.
   *
   * @param string $cache_id
   *   The content cache id.
   *
   * @return array
   *   - modified int The last modified timestamp.
   *   - headers array The response headers.
   *   - body string The response body.
   */
  public function get(string $cache_id): array;

  /**
   * Set a cached value.
   *
   * @param string $cache_id
   *   The content cache id.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response to cache.
   *
   * @return \AKlump\Slim\Middleware\ContentCacheInterface
   *   Self for chaining.
   */
  public function set(string $cache_id, ResponseInterface $response): ContentCacheInterface;

}
