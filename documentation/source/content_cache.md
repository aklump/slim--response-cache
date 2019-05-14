


## Required Settings

| setting name | description | example value |
|----------|----------|----------|
| response_cache.dir | The filepath to the content cache  | /path/to/cache/dir  |
| response_cache.lifetime | The number of seconds for cache lifetime for a resource content | 3600 |

    <?php
    return [
      'settings' => [
        'response_cache' => [
          'file_directory' => WEB_ROOT . '/api/v1/cache',
          'lifetime_in_seconds' => 3600,
          'footer' => '<div class="cache-notice">Updated on: %s</div>',
          'footer_timezone' => 'America/Los_Angeles',
          'footer_date_format' => 'r',
        ],
      ],
    ];  
