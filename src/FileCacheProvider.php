<?php

namespace AKlump\Slim\Middleware;

use Psr\Http\Message\ResponseInterface;

/**
 * Handler for caching content using the filesystem.
 *
 * The modified value comes from the file's modified timestamp.
 *
 * @link https://www.unix.com/tips-and-tutorials/20526-mtime-ctime-atime.html.
 */
class FileCacheProvider implements ResponseCacheProviderInterface {

  /**
   * Path to the cache directory.
   *
   * @var string
   */
  protected $pathToCacheDir;

  /**
   * FileCache constructor.
   *
   * @param string $path_to_cache
   *   The filepath to an existing directory to use for caching.
   */
  public function __construct(string $path_to_cache) {
    $this->pathToCacheDir = $path_to_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $cache_id): array {
    $filepath = $this->pathToCacheDir . '/' . $cache_id . '.json';
    $data = [];
    if (file_exists($filepath)) {
      $data = json_decode(file_get_contents($filepath), TRUE);
      $data['status'] = 'hit';
      $data['modified'] = filemtime($filepath);
    }

    return $data + [
        'status' => 'miss',
        'modified' => time(),
        'headers' => [],
        'body' => '',
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $cache_id, ResponseInterface $request, int $modified = NULL): ResponseCacheProviderInterface {
    if (!is_writable($this->pathToCacheDir)) {
      throw new \RuntimeException("The content file cache directory \"{$this->pathToCacheDir}\" does not exist or is not writable.");
    }
    $cache_file = $this->pathToCacheDir . '/' . $cache_id . '.json';
    file_put_contents($cache_file, json_encode([
      'headers' => $request->getHeaders(),
      'body' => strval($request->getBody()),
    ]));

    if ($modified) {
      touch($cache_file, $modified);
    }

    return $this;
  }

}
