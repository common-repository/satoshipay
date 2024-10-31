<?php
/**
 * Set good price ajax handler.
 * Action: 'set_good_price'
 * @since 1.6.0
 */

namespace SatoshiPay\AjaxHandlers;

require_once __DIR__ . '/../Utils/DownloadRemoteImage.php';

use SatoshiPay\Utils\DownloadRemoteImage;

function upload_media_from_url_ajax_handler() {

	// Verify $_POST values
	if (!(isset($_POST['url']))) {
		return wp_send_json_error(array(
			'error' => 'url is required.'
		));
	}

	$url = $_POST['url'];

	$download_remote_image = new DownloadRemoteImage( $url );

	$attachment = $download_remote_image->download();

	$media = get_post($attachment['attachment_id']);

	$media_meta = wp_get_attachment_metadata($attachment['attachment_id']);

	return wp_send_json_success(
		array(
			'id' => $attachment['attachment_id'],
			'media' => $media,
			'file_size' => $attachment['file_size'],
			'media_meta' => $media_meta
		)
	);
}
