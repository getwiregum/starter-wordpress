<?php
/** Explicit, repeatable starter content import: wp eval-file /absolute/path/scripts/seed.php */
if (!defined('WP_CLI') || !WP_CLI) { exit("Run this file with WP-CLI.\n"); }
if (!post_type_exists('wg_product')) { WP_CLI::error('Activate wiregum-storefront before importing.'); }
if (!wp_get_theme('twentytwentyfive')->exists()) { WP_CLI::error('Install Twenty Twenty-Five first.'); }
$source = is_dir(dirname(__DIR__) . '/content/products') ? dirname(__DIR__) . '/content/products' : ABSPATH . 'starter-content/products';
$examples = array(
    array('arc', 'Arc', 'Framed screen print, 50 × 70 cm. Numbered edition of 100.', 'Concentric arcs over a quiet baseline, printed on heavyweight cotton paper and framed in black-stained oak.', '€150.00', 'Screen print of concentric arcs in a black oak frame'),
    array('bars', 'Bars', 'Framed screen print, 50 × 70 cm. Numbered edition of 50.', 'A rhythm of vertical bars, printed on heavyweight cotton paper and framed in black-stained oak.', '€220.00', 'Screen print of vertical bars in a black oak frame'),
    array('dots', 'Dots', 'Framed screen print, 50 × 70 cm. Open edition.', 'A regular grid of dots with a single accent, printed on heavyweight cotton paper and framed in black-stained oak.', '€90.00', 'Screen print of a dot grid in a black oak frame'),
    array('wiregum-t-shirt', 'WireGum t-shirt', 'A heavyweight cotton t-shirt for everyday wear.', 'Soft heavyweight cotton, with a small WireGum print. Connect a product with size and color options to try the variant selectors.', '€32.00', 'Folded cotton t-shirt with a WireGum print'),
);
// Check all collisions before writing anything; never adopt unrelated content.
foreach (array_merge(array(array('home'), array('thank-you')), $examples) as $item) {
    $type = in_array($item[0], array('home', 'thank-you'), true) ? 'page' : 'wg_product';
    $existing = get_page_by_path($item[0], OBJECT, $type);
    if ($existing && !get_post_meta($existing->ID, '_wiregum_starter_seed', true)) { WP_CLI::error('Existing ' . $item[0] . ' is not starter content. Import into a fresh site or resolve the slug collision.'); }
}
foreach ($examples as $item) {
    if (!is_readable($source . '/' . $item[0] . '.jpg')) { WP_CLI::error('Missing example image: ' . $item[0]); }
}
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
function wiregum_starter_insert($slug, $type, $title, $content, $extra = array()) {
    $existing = get_page_by_path($slug, OBJECT, $type);
    if ($existing) { return $existing->ID; } // Preserve edits on repeated imports.
    $id = wp_insert_post(array_merge(array('post_name' => $slug, 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $content, 'meta_input' => array('_wiregum_starter_seed' => '1')), $extra), true);
    if (is_wp_error($id)) { WP_CLI::error($id->get_error_message()); }
    return $id;
}
foreach ($examples as $index => $item) {
    list($slug, $title, $excerpt, $description, $price, $alt) = $item;
    $id = wiregum_starter_insert($slug, 'wg_product', $title, '<!-- wp:paragraph --><p>' . esc_html($description) . '</p><!-- /wp:paragraph -->', array('post_excerpt' => $excerpt, 'menu_order' => $index, 'meta_input' => array('_wiregum_starter_seed' => '1', '_wiregum_reference' => '', '_wiregum_fallback_price' => $price)));
    if (!has_post_thumbnail($id)) {
        $temp = wp_tempnam($slug . '.jpg');
        if (!$temp || !copy($source . '/' . $slug . '.jpg', $temp)) { WP_CLI::error('Cannot copy image: ' . $slug); }
        $attachment = media_handle_sideload(array('name' => $slug . '.jpg', 'tmp_name' => $temp), $id, $title);
        if (is_wp_error($attachment)) { @unlink($temp); WP_CLI::error($attachment->get_error_message()); }
        update_post_meta($attachment, '_wp_attachment_image_alt', $alt);
        set_post_thumbnail($id, $attachment);
    }
}
if (in_array(get_option('blogname'), array('WordPress', 'WordPress Develop', 'WordPress Test'), true)) { update_option('blogname', 'WordPressGum'); }
$home = wiregum_starter_insert('home', 'page', 'Home', '
<!-- wp:columns {"align":"wide","className":"wg-home-intro","verticalAlignment":"top","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70"},"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-top wg-home-intro" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
<!-- wp:column {"verticalAlignment":"top","width":"65%"} -->
<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:65%">
<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Simple things,<br>considered carefully.</h1><!-- /wp:heading -->
</div><!-- /wp:column -->
<!-- wp:column {"verticalAlignment":"top","width":"35%"} -->
<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:35%">
<!-- wp:paragraph {"className":"wg-home-lead"} --><p class="wg-home-lead">Stories, images and product pages belong in WordPress. Live prices, options, stock and orders live in WireGum. Payments are handled securely by Stripe Checkout.</p><!-- /wp:paragraph -->
</div><!-- /wp:column -->
</div><!-- /wp:columns -->
<!-- wp:heading {"align":"wide","className":"wg-section-title","fontSize":"large"} --><h2 class="wp-block-heading alignwide wg-section-title has-large-font-size">In the collection</h2><!-- /wp:heading -->
<!-- wp:wiregum/products {"align":"wide","perPage":2} /-->');
wiregum_starter_insert('thank-you', 'page', 'Thank you', '<!-- wp:paragraph --><p>Thank you for visiting. If you completed a purchase, your confirmation will arrive by email once payment is confirmed.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>This page does not verify payment or order status.</p><!-- /wp:paragraph --><!-- wp:wiregum/shop-link /-->');
if (!(int) get_option('page_on_front')) { update_option('show_on_front', 'page'); update_option('page_on_front', $home); }
if (!get_option('permalink_structure')) { update_option('permalink_structure', '/%postname%/'); }
flush_rewrite_rules();
WP_CLI::success('Starter content is ready. Existing edits were preserved. Connect your workspace in Settings → WireGum and set each Products → WireGum product reference.');
