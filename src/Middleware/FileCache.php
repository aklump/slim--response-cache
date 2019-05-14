<?php

namespace AKlump\Slim\Middleware;

use Psr\Http\Message\ResponseInterface;

/**
 * Handler for caching content using the filesystem.
 */
class FileCache implements ContentCacheInterface {

  /**
   * Path to the cache directory.
   *
   * @var string
   */
  protected $filepath;

  /**
   * FileCache constructor.
   *
   * @param string $path_to_cache
   *   The filepath to an existing directory to use for caching.
   */
  public function __construct(string $path_to_cache) {
    $this->filepath = $path_to_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $cache_id): array {
    $filepath = $this->filepath . '/' . $cache_id . '.json';
    if (file_exists($filepath)) {
      return json_decode(file_get_contents($filepath), TRUE);
    }

    return ['modified' => time(), 'headers' => [], 'body' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $cache_id, ResponseInterface $request): ContentCacheInterface {
    if (!is_writable($this->filepath)) {
      throw new \RuntimeException("The content file cache directory \"{$this->filepath}\" does not exist or is not writable.");
    }
    file_put_contents($this->filepath . '/' . $cache_id . '.json', json_encode([
      'modified' => time(),
      'headers' => $request->getHeaders(),
      'body' => strval($request->getBody()),
    ]));

    return $this;
  }

}
