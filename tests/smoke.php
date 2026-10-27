<?php
/** Run on an installed, seeded starter: wp eval-file /absolute/path/tests/smoke.php */
if (!defined('WP_CLI') || !WP_CLI) { exit; }
$GLOBALS['wg_smoke_checks'] = 0;
function wg_assert($condition, $message) {
    if (!$condition) { WP_CLI::error($message); }
    $GLOBALS['wg_smoke_checks']++;
}
wg_assert(post_type_exists('wg_product'), 'Product type is registered.');
wg_assert(get_post_type_object('wg_product')->show_in_rest, 'Products support the block editor.');
wg_assert(wp_get_theme()->get_template() === 'twentytwentyfive', 'Theme inherits Twenty Twenty-Five.');
$registry = WP_Block_Type_Registry::get_instance();
foreach (array('products', 'buy-box', 'shop-link', 'cart-link') as $name) {
    $block = $registry->get_registered('wiregum/' . $name);
    wg_assert($block && $block->is_dynamic(), 'Dynamic block exists: ' . $name);
}
$products = get_posts(array('post_type' => 'wg_product', 'posts_per_page' => -1, 'meta_key' => '_wiregum_starter_seed', 'meta_value' => '1'));
wg_assert(count($products) === 4, 'Exactly four seeded products; repeated imports do not duplicate them.');
foreach ($products as $product) {
    wg_assert(has_post_thumbnail($product), 'Product has an image: ' . $product->post_title);
    $html = WireGum\Starter\buy_box($product->ID);
    wg_assert(str_contains($html, 'data-wiregum-price'), 'Fallback price hook exists.');
    wg_assert(str_contains($html, 'data-wiregum-add-to-cart type="button" disabled'), 'Controls start disabled until widget hydration.');
    wg_assert(str_contains($html, '<noscript>'), 'No-JavaScript explanation exists.');
}
$html = render_block(array('blockName' => 'wiregum/buy-box', 'attrs' => array('productId' => $products[0]->ID), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array()));
wg_assert(str_contains($html, 'wg-buy-box'), 'Buy box renders from a page using explicit post ID.');
wg_assert(WireGum\Starter\buy_box(0) === '', 'Invalid post context cannot render a buy box.');
$html = WireGum\Starter\product_grid(array('perPage' => 2));
wg_assert(substr_count($html, '<article ') === 2, 'Grid respects its product count.');
foreach (array('home', 'thank-you') as $slug) {
    $page = get_page_by_path($slug);
    wg_assert($page && !has_block('core/html', $page), 'Seeded page uses native blocks: ' . $slug);
}
$_SERVER['SERVER_NAME'] = wp_parse_url(home_url(), PHP_URL_HOST);
ob_start(); do_action('wp_head'); $head = ob_get_clean();
wg_assert(substr_count($head, 'data-wiregum-config') === 1, 'Exactly one JSON config is emitted.');
wg_assert(preg_match('/data-wiregum-config>(.*?)<\/script>/s', $head, $match) === 1 && is_array(json_decode($match[1], true)), 'Config is valid JSON.');
$config = json_decode($match[1], true);
wg_assert($config['cancelPath'] === wp_make_link_relative(get_post_type_archive_link('wg_product')), 'Cancellation uses the generated archive URL.');
wg_assert($config['successPath'] === wp_make_link_relative(get_permalink(get_page_by_path('thank-you'))), 'Success uses the generated thank-you URL.');
WP_CLI::success($GLOBALS['wg_smoke_checks'] . ' starter smoke checks passed.');
