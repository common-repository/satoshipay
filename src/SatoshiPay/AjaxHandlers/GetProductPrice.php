<?php
/**
 * Get good price ajax handler.
 * Action: 'get_product_price'
 * @since 1.9.0
 */

namespace SatoshiPay\AjaxHandlers;

require_once __DIR__ . '/../Utils/Metadata.php';
require_once __DIR__ . '/../Constants.php';
require_once __DIR__ . '/../Api/Client.php';

use SatoshiPay\Utils\Metadata as MetadataUtils;
use SatoshiPay\Constants;
use SatoshiPay\Api\Client as ApiClient;

function get_product_price_ajax_handler()
{
	// Verify $_POST values
	if (!(isset($_POST['post_id']))) {
		return wp_send_json_error(array(
			'error' => 'post_id is required.'
		));
	}

	$postId = absint($_POST['post_id']);

	// Get post object to get values required for registering the good
	$post = get_post($postId);

	if (!$postId || !$post) {
		return wp_send_json_error(array(
			'error' => 'post doesn\'t exist.'
		));
	}

    $price = get_post_meta($postId, Constants\META_KEY_SATOSHIPAY_PRICE, true);

    return wp_send_json_success(
        array(
            'price' => $price,
        )
    );
}
