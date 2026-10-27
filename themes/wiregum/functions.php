<?php
/** Keep Twenty Twenty-Five's typography, palettes, patterns and global styles. */
if (!defined('ABSPATH')) { exit; }
add_action('after_setup_theme', function () {
    load_child_theme_textdomain('wiregum-starter', get_stylesheet_directory() . '/languages');
});

// The child owns only its small presentation layer; the parent owns global styles.
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('wiregum-theme', get_stylesheet_uri(), array(), filemtime(get_stylesheet_directory() . '/style.css'));
    wp_enqueue_script('wiregum-wordmark', get_stylesheet_directory_uri() . '/assets/wordmark.js', array(), filemtime(get_stylesheet_directory() . '/assets/wordmark.js'), true);
});
add_action('after_setup_theme', function () { add_editor_style('style.css'); });

// Keep the native, editable Site Title block and add the progressive wordmark effect.
add_filter('render_block_core/site-title', function ($html, $block) {
    if (!in_array('wg-wordmark', explode(' ', $block['attrs']['className'] ?? ''), true)) { return $html; }
    $processor = new WP_HTML_Tag_Processor($html);
    if ($processor->next_tag('A')) { $processor->set_attribute('data-gummy', ''); }
    return $processor->get_updated_html();
}, 10, 2);

add_filter('render_block_wiregum/shop-link', function ($html) {
    if (!is_post_type_archive('wg_product')) { return $html; }
    $processor = new WP_HTML_Tag_Processor($html);
    if ($processor->next_tag('A')) { $processor->set_attribute('aria-current', 'page'); }
    return $processor->get_updated_html();
});
