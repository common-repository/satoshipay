<?php

namespace SatoshiPay\GutenbergEditor;

require_once __DIR__ . '/../AjaxHandlers/CreateDonationPost.php';
require_once __DIR__ . '/../AjaxHandlers/SetProductPrice.php';
require_once __DIR__ . '/../AjaxHandlers/GetProductPrice.php';
require_once __DIR__ . '/../AjaxHandlers/UploadMediaFromUrl.php';

use SatoshiPay\AjaxHandlers;

/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.6.0
 */

function init()
{

	// Hook: Block categories.
	add_filter( 'block_categories', 'SatoshiPay\GutenbergEditor\register_satoshipay_block_category', 10, 2 );

	// Hook: Frontend assets.
	add_action( 'enqueue_block_assets', 'SatoshiPay\GutenbergEditor\satoshipay_gutenberg_block_assets' );

	// Hook: Editor assets.
	add_action( 'enqueue_block_editor_assets', 'SatoshiPay\GutenbergEditor\satoshipay_gutenberg_editor_assets' );

	// Hook: register set good price ajax handler
	add_action('wp_ajax_set_product_price', 'SatoshiPay\AjaxHandlers\set_product_price_ajax_handler');

	// Hook: register get good price ajax handler
	add_action('wp_ajax_get_product_price', 'SatoshiPay\AjaxHandlers\get_product_price_ajax_handler');

	// Hook: upload media from url ajax handler
	add_action('wp_ajax_upload_media_from_url', 'SatoshiPay\AjaxHandlers\upload_media_from_url_ajax_handler');

	// Hook: create donation post ajax handler
	add_action('wp_ajax_create_donation_post', 'SatoshiPay\AjaxHandlers\create_donation_post_ajax_handler');
}

/**
 * Create Gutenberg blocks category for backend editor.
 *
 * @since 1.6.0
 */
function register_satoshipay_block_category( $categories, $post ) {
    if ( $post->post_type !== 'post' && $post->post_type !== 'page' ) {
        return $categories;
    }
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'satoshipay',
                'title' => __( 'SatoshiPay' ),
                'icon'  => `
					<svg width="25px" height="25px" viewBox="0 0 25 25" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
				        <g fill="#4A4A4A">
				            <path d="M12.4079,-0.0001 C5.7559,-0.0001 0.3229,5.2239 -0.0001,11.7879 L5.6009,11.7879 C5.9179,8.3159 8.8509,5.5839 12.4079,5.5839 C15.9639,5.5839 18.8969,8.3159 19.2139,11.7879 L24.8149,11.7879 C24.4919,5.2239 19.0589,-0.0001 12.4079,-0.0001"></path>
				            <path d="M24.8152,13.0282 L19.2132,13.0282 C18.8972,16.5002 15.9632,19.2322 12.4082,19.2322 C8.8512,19.2322 5.9182,16.5002 5.6012,13.0282 L0.0002,13.0282 C0.3232,19.5922 5.7552,24.8162 12.4082,24.8162 C19.0592,24.8162 24.4922,19.5922 24.8152,13.0282"></path>
				            <path d="M12.4079,6.8241 C9.3129,6.8241 6.8169,9.3171 6.8169,12.4081 C6.8169,15.4991 9.3129,17.9921 12.4079,17.9921 C15.5029,17.9921 17.9979,15.4991 17.9979,12.4081 C17.9979,9.3171 15.5029,6.8241 12.4079,6.8241"></path>
				        </g>
					</svg>
				`,
            ),
        )
    );
}

/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * `wp-blocks`: includes block type registration and related functions.
 *
 * @since 1.6.0
 */
function satoshipay_gutenberg_block_assets() {
	// Styles.
	wp_enqueue_style(
		'satoshipay-blocks-style-css', // Handle.
		plugins_url( 'dist/blocks.style.build.css', dirname( __FILE__ ) ), // Block style CSS.
		array( 'wp-blocks' ) // Dependency to include the CSS after it.
	);
} // End function satoshipay_gutenberg_block_assets().

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * `wp-blocks`: includes block type registration and related functions.
 * `wp-element`: includes the WordPress Element abstraction for describing the structure of your blocks.
 * `wp-i18n`: To internationalize the block's text.
 *
 * @since 1.6.0
 */
function satoshipay_gutenberg_editor_assets() {
	// Scripts.
	wp_enqueue_script(
		'satoshipay-blocks-js', // Handle.
		plugins_url( '/dist/blocks.build.js', dirname( __FILE__ ) ), // Block.build.js: We register the block here. Built with Webpack.
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ), // Dependencies, defined above.
		true // Enqueue the script in the footer.
	);

	// Styles.
	wp_enqueue_style(
		'satoshipay-blocks-editor-css', // Handle.
		plugins_url( 'dist/blocks.editor.build.css', dirname( __FILE__ ) ), // Block editor CSS.
		array( 'wp-edit-blocks' ) // Dependency to include the CSS after it.
	);
} // End function satoshipay_gutenberg_editor_assets().
