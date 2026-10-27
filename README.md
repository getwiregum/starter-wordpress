# WireGum × WordPress starter

A small storefront starter for [WordPress](https://wordpress.org) and [WireGum](https://wiregum.com), built as a child theme of **Twenty Twenty-Five**. The demo is called **WordPressGum**; its animated wordmark uses the native Site Title, so you can rename it in WordPress Settings → General.

WordPress owns pages, product stories and images. WireGum owns live prices, sellable variants, stock, cart, orders and Stripe Checkout. The parent theme keeps its typography, palettes, patterns and Site Editor. No WooCommerce, build step for assets, or API secret is required.

Integration guide: [wiregum.com/docs/wordpress](https://wiregum.com/docs/wordpress).

## Is the plugin required?

**WireGum itself does not require a WordPress plugin.** You can keep your existing theme and integrate the storefront widget directly: load its script and public configuration, then add product and cart markup with `data-wiregum-*` attributes. The [manual WordPress guide](https://wiregum.com/docs/wordpress) describes that approach.

The separate [WireGum Storefront plugin](https://github.com/getwiregum/wiregum-storefront) is a convenience layer: it provides the editorial Products section, connection settings, ready-made blocks and cart markup. **This starter’s catalog, product routes and commerce blocks depend on that plugin.** Removing it is not a way to keep the same starter storefront running; a manual integration supplies its own content structure and markup.

Theme and plugin are maintained in separate repositories. **If you only want to add WireGum to your existing theme, install [getwiregum/wiregum-storefront](https://github.com/getwiregum/wiregum-storefront); you do not need this starter.** This repository contains WordPressGum, example content and development tooling. It does not vendor the plugin source.

## Included

- A light block child theme: header, footer, home, shop archive and product template. All other templates and styles come from Twenty Twenty-Five.
- A development environment that installs the separate [WireGum Storefront plugin](https://github.com/getwiregum/wiregum-storefront), providing editorial products, public references, fallback prices, settings and four native commerce blocks.
- A responsive catalog and a native dialog cart with keyboard focus handling, Escape, background isolation and return focus.
- Four optional example products with bundled images, an editable block home page and a thank-you page. Importing is explicit and repeatable; it preserves existing edits and refuses unrelated slug collisions.
- Safe initial state: purchasing is disabled until connected. Images, descriptions and fallback prices remain available if JavaScript or the storefront service fails.

## Quick start with Docker

Requirements: Docker running, Node.js 24.18+ and npm 11.16+. WordPress, the parent theme and the separate WireGum Storefront plugin are downloaded by the official `@wordpress/env` development tool; none is vendored here. The default plugin source is `getwiregum/wiregum-storefront#main`. This follows the plugin’s development branch; switch that reference to a release tag or commit when freezing a project. Both repositories must be published for the default remote setup. Before publication, use the sibling-folder setup below.

```sh
npm ci
npm start
npm run seed
```

Open <http://localhost:8888>. Admin: <http://localhost:8888/wp-admin>, user `admin`, password `password` (local development only). `wp-env` activates the plugin and child theme. `npm stop` stops the environment without deleting its data. `npm run wp -- <command>` runs WP-CLI inside it.

The initial examples are **editorial placeholders**, not a preconnected live catalog. Their fallback prices are illustrative. Connect real WireGum products before testing purchases; no demo workspace or authorized local domain is assumed.

## Existing WordPress / Herd / Valet

Requirements: WordPress 6.7+, PHP 8.2+, a WordPress-supported database, WP-CLI for the optional content import.

1. Install a normal WordPress site using your preferred local host or hosting provider.
2. Install the latest stable Twenty Twenty-Five parent theme.
3. Copy `themes/wiregum` from this repository to `wp-content/themes/wiregum`. Install the separate [WireGum Storefront plugin](https://github.com/getwiregum/wiregum-storefront) from its release ZIP, or copy that repository root to `wp-content/plugins/wiregum-storefront`. The main plugin PHP file lives at the root of its own repository.
4. Activate the WireGum Storefront plugin and WireGum Starter theme.
5. Optionally import the example content from the WordPress installation directory:

```sh
wp eval-file /absolute/path/to/starter-wordpress/scripts/seed.php
```

Keep `content/products` next to the starter's `scripts` directory while importing. The importer adds Home, Thank you and four products, uploads their images, chooses Home only if no static front page is configured, and enables pretty permalinks only if unset. It never removes existing posts or changes the workspace or theme. Untouched default site names become WordPressGum; a chosen site name is preserved. Use a fresh installation for the intended demo layout. Re-running skips existing seeded content and restores missing featured images.

Without importing, create products yourself, choose a static home page, and create a `thank-you` page. Do not create a page with slug `shop`: `/shop/` is the product archive. With plain permalinks, generated shop/product links still work.

## Develop the plugin and theme together

Keep the two repositories side by side:

```text
Sites/
├── starter-wordpress/
└── wiregum-storefront/          Main plugin PHP file, assets and blocks at its root
```

From `starter-wordpress`, opt into the local plugin source:

```sh
cp .wp-env.override.example.json .wp-env.override.json
npm ci
npm start
npm run seed
```

The override is Git-ignored and replaces the remote plugin entry with `../wiregum-storefront`; `wp-env` mounts that checkout directly into WordPress. There is no second plugin source tree in the starter. If you already have a custom override, merge its `plugins` field instead of overwriting the file. After switching sources in an existing environment, run `npm start -- --update`.

For Herd or another existing installation, install a ZIP built in the plugin repository (`php scripts/build.php`) or copy its root to `wp-content/plugins/wiregum-storefront`. A symlink to that checkout is also useful for local development. Do not activate a second copy under another folder name. Keep the canonical plugin folder and main file name when updating, so the existing activation, products and workspace settings continue to work.

## Connect WireGum

1. Settings → WireGum: set your widget URL (`https://wiregum.com/api/storefront/widget` by default), workspace slug and storefront locale (`en`, `it`, etc.).
2. In WireGum → Storefront, authorize the exact site origin, including scheme and port, and configure shipping rates. Connect Stripe for test checkout.
3. Products → edit each product → WireGum product: paste its **public** WireGum ID (`wg_…`) or slug. Use a distinct reference for each actual product. Set the display-only fallback price and use WordPress blocks, excerpt and featured image for editorial content.
4. Visit the published storefront, choose a variant, add a product, open the cart and adjust its quantity, choose the shipping country and complete Stripe test checkout. Verify the resulting order in WireGum, including success and cancellation returns.

No automatic product or order synchronization is performed. Prices shown before hydration are not the checkout price; WireGum validates the cart server-side. Stock and variants are managed in WireGum.

For deployment-specific overrides, define `WIREGUM_WIDGET_URL`, `WIREGUM_ORGANIZATION`, and `WIREGUM_LOCALE` in `wp-config.php`, or provide environment variables with those names. Constants take priority over environment variables, which take priority over saved admin settings. No `.env` loader is included. These are public settings: never place Stripe secrets or private API keys in the frontend.

```php
define('WIREGUM_ORGANIZATION', 'your-workspace-slug');
define('WIREGUM_LOCALE', 'en');
```

The widget is loaded once in the footer; JSON config is safely encoded in the head. Checkout returns use the site's generated `thank-you` and shop URLs, including WordPress subdirectory installs. If the thank-you page is missing, success returns to the homepage. The thank-you page is informational and never asserts payment success.

## Editing

- Appearance → Editor: edit the header, footer, commerce templates, parent patterns, typography and palette using normal WordPress tools.
- Pages → Home: edit the hero, explanatory content and product grid. The initial home shows two products; its headline and explanatory paragraph sit side by side on desktop and stack on mobile. Set the product count/pagination in the grid block sidebar. On the shop archive, pagination follows the main WordPress query and the page size in Settings → Reading.
- Products: change titles, images, excerpts, body, references and fallback prices. Ordering uses Page attributes → Order, then title.
- Insert a **WireGum buy box** into other pages and set its WordPress product post ID in block settings. `0` uses the current product template context. The WireGum public reference belongs in that product's sidebar, not the block's numeric post ID setting.
- The **WireGum cart** block inserts a trigger for the one shared cart dialog. The **WireGum shop link** block generates a portable archive link.

The blocks render previews on the server; purchasing only runs on the published frontend. Site Editor template changes saved in the database override filesystem templates. Reset a customized template in the editor to see subsequent file changes.

The plugin owns content and commerce integration so products remain accessible when switching themes. The child theme owns presentation. If deactivating the plugin, its blocks and product routes become unavailable; reactivation restores them without deleting content.

## Structure

```text
themes/wiregum/                  Twenty Twenty-Five child theme
scripts/seed.php                Optional WP-CLI import
content/products/               Bundled example images
.wp-env.json                    WordPress environment + remote plugin dependency
.wp-env.override.example.json   Example for a sibling local plugin checkout
```

## Deployment

Install the plugin, child theme and current Twenty Twenty-Five on your PHP host. Migrate the WordPress database and uploads using your usual WordPress deployment tools; the repository does not contain the database, uploads, WordPress core or parent theme. Set production workspace settings and authorize the production origin in WireGum. Keep WordPress and its parent theme updated independently.

Clear WordPress/CDN caches after changing settings. If an optimization plugin delays or combines scripts, exclude the widget and its configuration. If a widget request is blocked or fails, the authored content remains and purchase controls stay disabled. A valid workspace and authorized origin are required for the full commerce flow.

## Verification

On a local seeded installation, run:

```sh
wp eval-file /absolute/path/to/starter-wordpress/tests/smoke.php
```

This checks product registration, native blocks, rendered buy boxes and grids, seeded content/images, disabled initial controls and checkout return paths. Re-run the seed before this check to verify repeatability. Test the published site with a real authorized WireGum workspace for checkout; the smoke checks do not contact Stripe.

The header uses the same elastic Mona Sans wordmark as the Kirby and Astro starters, with reduced-motion support. Manrope and the global palette are inherited from Twenty Twenty-Five.

## License

Mona Sans is included under the SIL Open Font License; see `themes/wiregum/assets/fonts/MonaSans-OFL.txt`. The wordmark script is adapted from the MIT-licensed WireGum Kirby starter; its notice is in `content/NOTICE.md`. Starter code is GPL-2.0-or-later, compatible with WordPress. See [LICENSE](LICENSE). WordPress, Twenty Twenty-Five and development tools retain their own licenses and are installed separately. Example images are reused from the MIT-licensed WireGum Astro starter; see [content/NOTICE.md](content/NOTICE.md).
