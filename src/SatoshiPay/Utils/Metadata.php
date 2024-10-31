<?php
/**
 * Helper functions related to the Wordpress post (good) metadata
 * like adding/updating/validating/getting a metadata
 * @since 1.6.0
 */

namespace SatoshiPay\Utils\Metadata;

require_once __DIR__ . '/SatoshiPay.php';

use SatoshiPay\Utils\SatoshiPay as SatoshiPayUtils;

/**
 * Returns randomized and hashed string.
 * Used to generate satoshipay Secret
 * @return string
 */
function generateSecret()
{
	mt_srand(microtime(true));
	$randomValue = (string)mt_rand() . uniqid();

	// Create better random value if possible
	if (function_exists('openssl_random_pseudo_bytes')) {
		$randomValue = openssl_random_pseudo_bytes(1024);
	}
	return md5($randomValue);
}

/**
 * Validate good's satoshipay secret and add if not exist
 * @since 1.6.0
 */
function validate_good_satoshipay_secret($post_id)
{
	if (!get_post_meta($post_id, '_satoshipay_secret', true)) {
		add_post_meta($post_id, '_satoshipay_secret', generateSecret(), true);
	}
}

/**
 * Set good metadata
 * @since 1.6.0
 */
function set_good_metadata($post_id, $meta_key, $meta_value)
{
	// Always validate satoshipay secret
	validate_good_satoshipay_secret($post_id);

    // Always validate SatoshiPay credentials
    if(SatoshiPayUtils\validCredentials()) {
        update_post_meta($post_id, $meta_key, $meta_value);
    }
}
