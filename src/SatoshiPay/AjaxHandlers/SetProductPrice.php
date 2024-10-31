<?php
/**
 * Set good price ajax handler.
 * Action: 'set_product_price'
 * @since 1.6.0
 */

namespace SatoshiPay\AjaxHandlers;

require_once __DIR__ . '/../Utils/Metadata.php';
require_once __DIR__ . '/../Constants.php';
require_once __DIR__ . '/../Api/Client.php';

use SatoshiPay\Utils\Metadata as MetadataUtils;
use SatoshiPay\Constants;
use SatoshiPay\Api\Client as ApiClient;

function set_product_price_ajax_handler()
{
	// Verify $_POST values
	if (!(isset($_POST['post_id']))) {
		return wp_send_json_error(array(
			'error' => 'post_id is required.'
		));
	}

	// Parse $_POST values
	$postId = absint($_POST['post_id']);
	$goodPrice = absint($_POST['price']);
	$enabled = absint($_POST['enabled']);

	$priceData = array(
		'enabled' => $enabled ? true : false,
		'satoshi' => $enabled ? $goodPrice : 0
	);

	// Get post object to get values required for registering the good
	$post = get_post($postId);

	if (!$postId || !$post) {
		return wp_send_json_error(array(
			'error' => 'post doesn\'t exist.'
		));
	}

	if(!goodPrice) {
		return wp_send_json_error(array(
			'error' => 'price is not set.'
		));
	}

	// Validate that the current user is allowed to edit
	if (!current_user_can('edit_post', $post)) {
		return wp_send_json_error(array(
			'error' => 'user not allowed to edit post.'
		));
	}

	// Update the Wordpress database metadata with the good price
	MetadataUtils\set_good_metadata($postId, Constants\META_KEY_SATOSHIPAY_PRICE, $priceData);


	// Get good satoshipay metadata
	$satoshiPaySecret = get_post_meta($postId, Constants\META_KEY_SATOSHIPAY_SECRET, true);
	$satoshiPayId = get_post_meta($postId, Constants\META_KEY_SATOSHIPAY_ID, true);


	// Create a SatoshiPay good for the provider API
	$satoshiPayGood = array(
		'goodId' => $postId,
		'price' => $goodPrice,
		'sharedSecret' => $satoshiPaySecret,
		'title' => $post->post_title,
		'url' => get_permalink($postId)
	);

	try {
		$apiCredentials = get_option('satoshipay_api');
		$apiClient = new ApiClient($apiCredentials);

		// If post has `_satoshipay_id` metadata then update otherwise create
		if ($satoshiPayId) {
			$satoshiPayId = $apiClient->updateGood($satoshiPayId, $satoshiPayGood);
		} else {
			$satoshiPayId = $apiClient->createNewGood($satoshiPayGood);
		}
	} catch (Exception $e) {
		WP_die($e->getMessage());
	}

    // Update metadata `_satoshipay_id` for post
    update_post_meta($postId, Constants\META_KEY_SATOSHIPAY_ID, $satoshiPayId, true);

    // Update metadata `_satoshipay_asset` for post
    update_post_meta($postId, Constants\META_KEY_SATOSHIPAY_ASSET, 'XLM', true);

	return wp_send_json_success(
		array(
			'post_id' => $postId,
			'satoshipay_pricing' => $goodPrice,
		)
	);
}
