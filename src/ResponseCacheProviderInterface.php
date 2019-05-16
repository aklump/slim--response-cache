<?php

namespace AKlump\Slim\Middleware;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface ContentCacheInterface.
 *
 * For classes that handle content caching.
 *
 * @package AKlump\Slim
 */
interface ResponseCacheProviderInterface {

  /**
   * Get cached response data.
   *
   * The status must be set to hit for the cached value to be considered.  If
   * set to 'miss' then the reset of the values should be ignored.
   *
   * @param string $cache_id
   *   The content cache id.
   *
   * @return array
   *   - status one of: 'hit', 'miss'
   *   - modified int The last modified timestamp.
   *   - headers array The response headers.
   *   - body string The response body.
   */
  public function get(string $cache_id): array;

  /**
   * Cache a response for later recall.
   *
   * @param string $cache_id
   *   The content cache id.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response to cache.
   * @param int $modified
   *   An optional Unix timestamp to preset the $modified time; if omitted the
   *   current time will be used.  E.g., 1558031060.
   *
   * @return \AKlump\Slim\Middleware\ResponseCacheProviderInterface
   *   Self for chaining.
   */
  public function set(string $cache_id, ResponseInterface $response, int $modified = NULL): ResponseCacheProviderInterface;

}
