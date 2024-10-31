<?php
/**
 * Create a placeholder post for donation ajax handler.
 * Action: 'satoshipay-create-donation'
 * @since 1.6.0
 */

namespace SatoshiPay\AjaxHandlers;

function create_donation_post_ajax_handler() {
  // Create hidden post with post-type sp_donation to be used as good item
  $donation_post_data = array(
	'post_title'    => 'SatoshiPay Donation Placeholder',
	'post_content' => 'SatoshiPay Donation Placeholder',
	'post_status'   => 'publish',
	'post_author'   => 1,
	'post_type' => 'sp_donation'
  );

  // Insert the post into the database
  $donation_post_id = wp_insert_post( $donation_post_data );
  $donation_post = get_post($donation_post_id);

  if (!$donation_post_id || !$donation_post) {
	  return wp_send_json_error();
  }

  if (!isset($donation_post_id)) {
	  return wp_send_json_error();
  }

  return wp_send_json_success($donation_post);
}
