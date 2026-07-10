<?php
namespace TSEMOU\Modules\HomepageBeta;

if (!defined('ABSPATH')) exit;

class Homepage_Beta {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_shortcode']);
    }

    public function register_shortcode() {
        add_shortcode('tsemou_homepage_beta', [$this, 'render_shortcode']);
    }

    private function prototype_base_path() {
      return TSEMOU_CORE_PATH . 'assets/homepage-beta/';
    }

    private function prototype_base_url() {
      return TSEMOU_CORE_URL . 'assets/homepage-beta/';
    }

    private function enqueue_assets() {
        $style_handle = 'tsemou-homepage-beta-style';
        $script_handle = 'tsemou-homepage-beta-prototype';

        wp_enqueue_style(
            $style_handle,
          $this->prototype_base_url() . 'styles.css',
            [],
            defined('TSEMOU_CORE_VERSION') ? TSEMOU_CORE_VERSION : null
        );

        // Keep prototype visual/runtime behavior, but lock backend fetches for non-feed areas.
        wp_enqueue_script(
            $script_handle,
          $this->prototype_base_url() . 'script.js',
            [],
            defined('TSEMOU_CORE_VERSION') ? TSEMOU_CORE_VERSION : null,
            true
        );

        $block_backend_fetch = <<<'JS'
(function () {
  const originalFetch = window.fetch;
  if (typeof originalFetch !== 'function') return;

  window.fetch = function (input, init) {
    const url = typeof input === 'string' ? input : (input && input.url ? input.url : '');

    if (url.indexOf('/wp-json/wp/v2/tsemou_proof') !== -1 ||
        url.indexOf('/wp-json/wp/v2/tsemou_relation') !== -1) {
      return Promise.resolve(new Response('[]', {
        status: 200,
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Total': '0'
        }
      }));
    }

    return originalFetch.call(this, input, init);
  };
})();
JS;
        wp_add_inline_script($script_handle, $block_backend_fetch, 'before');

        $posts_payload = $this->community_feed_posts_payload();
        $payload_json = wp_json_encode($posts_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $replace_feed = <<<'JS'
(function () {
          const liveNews = document.getElementById('liveNews');
          if (!liveNews) return;

          const feedItems = window.__TSEMOU_BETA_FEED_POSTS__;

  if (!Array.isArray(feedItems) || feedItems.length === 0) {
    // Block 1 rule: do not keep prototype demo feed data.
    if (typeof newsItems !== 'undefined') {
      newsItems = [];
    }
    liveNews.innerHTML = '';
    return;
  }

  function esc(input) {
    const text = String(input == null ? '' : input);
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function card(item) {
    let imageMarkup = '';
    if (item && item.image) {
      imageMarkup = '<img class="entry-thumb" src="' + esc(item.image) + '" alt="POST thumbnail">';
    }

    return [
      '<article class="news-item community-entry" tabindex="0">',
        '<div>',
          '<div class="entry-head">',
            '<span class="entry-type type-post">POST</span>',
          '</div>',
          '<h4 class="entry-title">' + esc(item.title) + '</h4>',
          '<p class="entry-author">' + esc(item.author) + ' • ' + esc(item.time) + '</p>',
          imageMarkup,
          '<p class="entry-link-wrap"><a href="' + esc(item.link) + '">Open</a></p>',
          '<div class="entry-actions">',
            '<button class="vote-btn tsemit-action" data-action-url="' + esc(item.actionUrl) + '" type="button" aria-label="TSEMIT this post">TSEMIT</button>',
            '<button class="vote-btn untsemit-action" data-action-url="' + esc(item.actionUrl) + '" type="button" aria-label="UNTSEMIT this post">UNTSEMIT</button>',
          '</div>',
        '</div>',
      '</article>'
    ].join('');
  }

  const normalizedItems = feedItems.map(function (item) {
    const type = item && item.type ? String(item.type) : 'POST';
    return {
      type: type,
      title: item && item.title ? item.title : '',
      author: item && item.author ? item.author : '',
      time: item && item.time ? item.time : '',
      image: item && item.image ? item.image : '',
      link: item && item.link ? item.link : '#',
      actionUrl: item && item.actionUrl ? item.actionUrl : '#'
    };
  });

  if (typeof newsItems !== 'undefined') {
    newsItems = normalizedItems;
  }

  liveNews.innerHTML = normalizedItems.map(card).join('');
})();
JS;

        wp_add_inline_script($script_handle, 'window.__TSEMOU_BETA_FEED_POSTS__ = ' . $payload_json . ';', 'before');
        wp_add_inline_script($script_handle, $replace_feed, 'after');
    }

    private function community_feed_posts_payload() {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 14,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($posts)) return [];

        $payload = [];
        foreach ($posts as $post) {
            $post_id = intval($post->ID);
          $thumb = get_the_post_thumbnail_url($post_id, 'thumbnail');

            $payload[] = [
                'title' => wp_strip_all_tags(get_the_title($post_id)),
                'author' => get_the_author_meta('display_name', intval($post->post_author)),
                'time' => human_time_diff(get_post_time('U', true, $post_id), current_time('timestamp')) . ' ago',
                'image' => $thumb ? esc_url_raw($thumb) : '',
                'link' => esc_url_raw(get_permalink($post_id)),
                'actionUrl' => esc_url_raw(get_permalink($post_id) . '#tsemit'),
            ];
        }

        return $payload;
    }

    private function prototype_markup() {
      $file = $this->prototype_base_path() . 'index.html';
        if (!file_exists($file)) {
            return '<p>TSEMOU homepage prototype not found.</p>';
        }

        $html = (string) file_get_contents($file);
        if ($html === '') {
            return '<p>TSEMOU homepage prototype is empty.</p>';
        }

        $body = $html;
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $match)) {
            $body = (string) $match[1];
        }

        // Remove local prototype script include; WordPress enqueues it safely.
        $body = preg_replace('/<script\s+src="script\.js"\s*><\/script>/i', '', $body);

        return $body;
    }

    public function render_shortcode($atts = []) {
        $this->enqueue_assets();

        return '<div class="tsemou-homepage-beta">' . $this->prototype_markup() . '</div>';
    }
}
