<?php
/**
 * This file is part of the SatoshiPay WordPress plugin.
 *
 * (c) SatoshiPay <hello@satoshipay.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SatoshiPay;

use const SatoshiPay\Constants\META_KEY_SATOSHIPAY_PRICE;
use wpdb;

/**
 * Class SatoshiPayMigrator
 * @package SatoshiPay
 */
class SatoshiPayMigrator
{
	/** @var wpdb|null */
	protected static $wpdb = null;


	/**
	 * @return SatoshiPayMigrator
	 */
	public static function getInstance()
	{
		static $instance = null;

		if (is_null($instance)) {
			$instance = new self();
		}

		return $instance;
	}


	/**
	 * Migrates satoshipay placeholders from classic version to gutenberg blocks
	 *
	 * @param int $postId
	 * @param bool $cleanUp set to true if you want to clean old fragments
	 * @param bool $autosave set to false to suppress automatic saving
	 *
	 * @return \WP_Post
	 */
	public function migratePostById($postId, $cleanUp = false, $autosave = true)
	{
		$post = get_post($postId);
		return $this->migratePost($post, $cleanUp, $autosave);
	}


	/**
	 * Migrates satoshipay placeholders from classic version to gutenberg blocks
	 *
	 * @param \WP_Post $post
	 * @param bool $cleanUp set to true if you want to clean old fragments
	 * @param bool $autosave set to false to suppress automatic saving
	 *
	 * @return \WP_Post
	 */
	public function migratePost(\WP_Post $post, $cleanUp = false, $autosave = true)
	{
		$changed = false;
		$initialContent = $post->post_content;

		$placeholderMarkups = $this->getPlaceholderMarkupFromHtml($post->post_content);
		$countPlaceholders = count(array_filter($placeholderMarkups, function($chunk) { return $chunk['type'] == 'satoshipay-placeholder';}));

		$paywallMarkups = $this->getPaywallMarkupFromHtml($post->post_content);
		$countPaywalls = count(array_filter($paywallMarkups, function($chunk) { return $chunk['type'] == 'paywall';}));


		if ($this->isSatoshiPayUsedForPost($post->ID) && !$countPaywalls) {
			// no paywall found but the post should be paid
			// -> adding new paywall at the beginning of the post

			array_unshift($placeholderMarkups, array(
				'type'          => 'satoshipay-placeholder',
				'contenttype'   => 'start',
				'data'          => '',
				'attributes_s'  => '',
				'attributes'    => array(),
			));

			$countPlaceholders++;
		}


		if ($countPlaceholders) {
			// there were classic placeholders found an need to be converted
			foreach ($placeholderMarkups as $k => $chunk) {
				if ($chunk['type'] != 'satoshipay-placeholder') {
					continue;
				}

				$emptyAttrs = array(
					'type'      => $chunk['contenttype'],
					'id'        => $chunk['attributes']['attachment-id'],
					'height'    => '',
					'width'     => '',
					'autoplay'  => '',
					'preview'   => '',
					'asset'     => '',
				);
				// used this replacement from former migration version
				// @todo it would be better to report missing attributes to the user instead of assuming something
				$attributes = array_merge($emptyAttrs, $chunk['attributes']);

				if ('start' == $attributes['type']) {
					// paywall found
					$attributes['id'] = $post->ID;
				}

				$gutenbergPlaceholder = $this->convertClassicPlaceholderToGutenbergBlock($attributes);
				if (false !== $gutenbergPlaceholder) {
					$chunk['data'] = $gutenbergPlaceholder;
				}

				$placeholderMarkups[$k] = $chunk;
			}
		}

		// merging all segments back to one string
		$newContent = implode('', array_map(function($chunk) {return $chunk['data'];}, $placeholderMarkups));
		$post->post_content = $newContent;

		$changed = ($initialContent != $newContent) || $changed;

		if ($cleanUp) {
			// if cleanup is wanted
			$changed = $this->processCleanUpObsoleteInactivePaywallsFromPost($post) || $changed;
		}


		if ($autosave && $changed) {
			wp_update_post(array(
				'ID'           => $post->ID,
				'post_content' => $post->post_content
			));
		}

		return $post;
	}


	/**
	 * @return wpdb
	 */
	protected function db()
	{
		if (is_null(static::$wpdb)) {
			/** @var wpdb $wpdb */
			global $wpdb;
			static::$wpdb = $wpdb;
		}

		return static::$wpdb;
	}


	/**
	 * @param int $postId
	 *
	 * @return bool
	 */
	public function isSatoshiPayUsedForPost($postId)
	{
		$meta = get_post_meta($postId, META_KEY_SATOSHIPAY_PRICE, true);

		return (is_array($meta) && array_key_exists('enabled', $meta) && $meta['enabled']);
	}


	/**
	 * @param int $postId
	 *
	 * @return mixed
	 */
	public function getSatoshiPayPriceForPost($postId)
	{
		if (!$this->isSatoshiPayUsedForPost($postId)) {
			return 0;
		}
		$meta = get_post_meta($postId, META_KEY_SATOSHIPAY_PRICE, true);

		return (is_array($meta) && array_key_exists('satoshi', $meta)) ? $meta['satoshi'] : 0;
	}


	/**
	 * Generate Gutenberg Block from Classic Placeholder
	 *
	 * @param array $attributes
	 *
	 * @return bool|string
	 */
	protected function convertClassicPlaceholderToGutenbergBlock(array $attributes)
	{
		$mediaPrice     = get_post_meta($attributes['id'], '_satoshipay_pricing', true);
		$attachedFile   = get_attached_file($attributes['id']);
		$mediaTitle     = basename($attachedFile);
		$mediaSize      = filesize($attachedFile);
		$preview        = $attributes['preview']
			? $attributes['preview']
			: "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='100%' height='100%'><rect width='100%' height='100%' fill='%23F3F3F4'/></svg>";

		// definition of supported types and classes
		$blockTypes     = array(
			'image'     => array(
				'type'      => 'wp:satoshipay/block-paid-media',
				'class'     => 'wp-block-satoshipay-block-paid-media',
			),
			'video'     => array(
				'type'      => 'wp:satoshipay/block-paid-media',
				'class'     => 'wp-block-satoshipay-block-paid-media',
			),
			'audio'     => array(
				'type'      => 'wp:satoshipay/block-paid-media',
				'class'     => 'wp-block-satoshipay-block-paid-media',
			),
			'download'  => array(
				'type'      => 'wp:satoshipay/block-paid-file',
				'class'     => 'wp-block-satoshipay-block-paid-file',
			),
			'donation'  => array(
				'type'      => 'wp:satoshipay/block-donation',
				'class'     => 'wp-block-satoshipay-block-donation',
			),
			'start'     => array(
				'type'      => 'wp:satoshipay/block-article-paywall',
				'class'     => 'wp-block-satoshipay-block-article-paywall',
			),
		);

		$isMediaBlock   = in_array($attributes['type'], array('image', 'video', 'audio',));
		$blockType      = array_key_exists($attributes['type'], $blockTypes) ? $blockTypes[$attributes['type']] : null;

		if (!$blockType) {
			return false;
		}

		// collect data for later placeholder generation
		$args = array(
			'blocktype'     => $blockType['type'],  // e.g. wp:satoshipay/block-paid-...
			'blockclass'    => $blockType['class'], // e.g. wp-block-satoshipay-block-paid-...
			'type'          => $attributes['type'], // e.g. image | video | ...
			'json-data'     => array(), // JSON
			'attr-data'     => array(), // ATTRS
		);

		// prepare empty data targets
		$jsonData = array();
		$attrData = array();

		if ($isMediaBlock) {
			// setting common data for media types
			$mimeType = get_post_mime_type($attributes['id']);
			$mediaUrl = wp_get_attachment_url($attributes['id']);

			$jsonData['mediaId']        = (int)$attributes['id'];
			$jsonData['mediaPrice']     = (int)$mediaPrice['satoshi'];
			$jsonData['mediaType']      = $attributes['type'];
			$jsonData['mediaMime']      = $mimeType;
			$jsonData['mediaUrl']       = $mediaUrl;
			$jsonData['mediaTitle']     = $mediaTitle;
			$jsonData['mediaSize']      = $mediaSize;
		}

		// setting common attachment id for all types
		$attrData['attachment-id']  = $attributes['id'];


		// handling extra data of the different types
		switch ($attributes['type']) {
			case 'image':
				$jsonData['mediaWidth']             = (int)$attributes['width'];
				$jsonData['mediaHeight']            = (int)$attributes['height'];
				if ($attributes['preview']) {
					$jsonData['coverType']              = 'COVER_TYPE_FILE';
					$jsonData['coverUrl']               = $attributes['preview'];
					$jsonData['coverTitle']             = 'Custom image';
				}

				$attrData['width']                  = (int)$attributes['width'];
				$attrData['height']                 = (int)$attributes['height'];
				$attrData['preview']                = $preview;

				break;


			case 'video':
				$jsonData['mediaWidth']             = (int)$attributes['width'];
				$jsonData['mediaHeight']            = (int)$attributes['height'];
				$jsonData['mediaAutoPlay']          = ($attributes['autoplay'] == 'true');
				if ($attributes['preview']) {
					$jsonData['coverType']              = 'COVER_TYPE_FILE';
					$jsonData['coverUrl']               = $attributes['preview'];
					$jsonData['coverTitle']             = 'Custom image';
				}

				$attrData['width']                  = (int)$attributes['width'];
				$attrData['height']                 = (int)$attributes['height'];
				$attrData['autoplay']               = $attributes['autoplay'];
				$attrData['preview']                = $preview;
				break;


			case 'audio':
				$jsonData['mediaAutoPlay']          = ($attributes['autoplay'] == 'true');

				$attrData['autoplay']               = $attributes['autoplay'];
				break;


			case 'download':
				$jsonData['fileId']                 = (int)$attributes['id'];
				$jsonData['fileTitle']              = $mediaTitle;
				$jsonData['filePrice']              = (int)$mediaPrice['satoshi'];
				$jsonData['fileSize']               = $mediaSize;
				break;


			case 'donation':
				$jsonData['donationValue']          = (int)$mediaPrice['satoshi'];
				$jsonData['donationCurrency']       = $attributes['asset'];
				$jsonData['placeholderId']          = (int)$attributes['id'];
				$jsonData['enabled']                = true;
				$jsonData['creatingPlaceholder']    = false;
				$jsonData['coverWidth']             = (int)$attributes['width'];
				$jsonData['coverHeight']            = (int)$attributes['height'];
				if ($attributes['preview']) {
					$jsonData['coverType']              = 'COVER_TYPE_FILE';
					$jsonData['coverUrl']               = $attributes['preview'];
					$jsonData['coverTitle']             = 'Custom image';
				}

				$attrData['width']                  = (int)$attributes['width'];
				$attrData['height']                 = (int)$attributes['height'];
				$attrData['preview']                = $preview;
				$attrData['asset']                  = $attributes['asset'];
				break;

			case 'start': // this means "paywall"
				$jsonData = array(
					'postId'    => (int)$attributes['id'],
					'price'     => (int)$mediaPrice['satoshi'],
					'enabled'   => (boolean)$mediaPrice['enabled'],
				);

				$attrData = array();
				break;
		}

		// adding collected data
		$args['json-data'] = $jsonData;
		$args['attr-data'] = $attrData;

		// converting json to string
		$args['json-data'] = json_encode($args['json-data']);

		// converting attributes to string
		$args['attr-data'] = implode(' ', array_map(
			function($attr, $value) {
				return sprintf('%s="%s"', $attr, $value);
			},
			array_keys($args['attr-data']),
			array_values($args['attr-data'])
		));


		// creates a string like
		// <!-- BLOCKTYPE JSON-DATA -->
		// <div class="BLOCKCSSCLASS"><!--satoshipay:TYPE ATTRIBUTES-DATA-->
		// <!-- /BLOCKTYPE -->

		$mediaPattern =
			'<!-- %1$s %4$s -->' . PHP_EOL .
			'<div class="%2$s"><!--satoshipay:%3$s %5$s--></div>' . PHP_EOL .
			'<!-- /%1$s -->';

		$paywallPattern =
			'<!-- %1$s %4$s -->' . PHP_EOL .
			'<div class="%2$s"><div><!--satoshipay:%3$s--></div></div>' . PHP_EOL .
			'<!-- /%1$s -->';

		$paywallPatternInactive =
			'<!-- %1$s %4$s -->' . PHP_EOL .
			'<div class="%2$s"></div>' . PHP_EOL .
			'<!-- /%1$s -->';


		$pattern = $mediaPattern;

		if ('start' == $attributes['type']) {
			$pattern = $jsonData['enabled'] ? $paywallPattern : $paywallPatternInactive;
		}

		$gutenbergBlock = vsprintf($pattern, $args);

		// @todo wrapping div needed for paywall?

		return $gutenbergBlock;
	}


	/**
	 * Converts an attributes to an associative array
	 *
	 * Input: foo="bar" baz="bom"
	 * Output: ['foo' => 'bar', 'baz' => 'bom', ]
	 *
	 * @param string $attributes
	 *
	 * @return array
	 */
	public function attributeStringToArray($attributes)
	{
		$result = array();

		$element = new \SimpleXMLElement("<foo {$attributes}/>");
		foreach ($element->attributes() as $attr => $value) {
			$result[(string)$attr] = (string)$value;
		}

		return $result;
	}


	/**
	 * Splits given html string into chunks
	 *
	 * each chunk is either a html segment or represents a classic placeholder segment
	 *
	 * Output:
	 *      [
	 *          [
	 *              'type'          =>  'html',
	 *              'data'          =>  '...', // original string data at this point
	 *          ],
	 *          [
	 *              'type'          =>  'satoshipay-placeholder',
	 *              'data'          =>  '...', // original string data at this point
	 *              'contenttype'   =>  '...', // one of image, audio, video, ...
	 *              'attribute_s'   =>  '...', // the attributes string (e.g. 'width="100" height="200" ...')
	 *              'attributes'    =>  [],    // associative array of the attributes string
	 *          ],
	 *          [
	 *              'type'          =>  'html',
	 *              'data'          =>  '...', // original string data at this point
	 *          ],
	 *          // ...
	 *      ]
	 *
	 * @param string $html
	 *
	 * @return array
	 */
	public function getPlaceholderMarkupFromHtml($html)
	{
		$classicPlaceholderPattern = sprintf(
			'(?P<satoshipay_placeholder><!--satoshipay:(?P<type>%1$s)\s*(?P<attributes>(?:\s*[\w-]+="[^"]*"\s*)*)-->)',
			implode('|', array('image', 'audio', 'video', 'download', 'donation', 'start',))
		);

		$skipGutenbergPattern = sprintf('<!-- wp:satoshipay.*?\n.*?%1$s.*?\n.*?<!--(*SKIP)(*F)|%1$s', $classicPlaceholderPattern);

		preg_match_all("#{$skipGutenbergPattern}#J", $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

		$chunkedMarkup = array(); // will hold the whole content chunked in 'html' and 'satoshipay-placeholder' segments
		$lastPosition = 0;

		// walking through found placeholders and splitting the original html into segments
		foreach ($matches as $match) {
			$snippetPosition = $match['satoshipay_placeholder'][1]; // reading the position where the placeholder was found

			if ($snippetPosition > $lastPosition) {
				$chunk = substr($html, $lastPosition, $snippetPosition - $lastPosition);
				$chunkedMarkup[] = array(
					'type'  => 'html',
					'data'  => $chunk,
				);
			}

			$chunk = $match['satoshipay_placeholder'][0];

			$chunkedMarkup[] = array(
				'type'          => 'satoshipay-placeholder',
				'contenttype'   => $match['type'][0],
				'data'          => $chunk,
				'attributes_s'  => $match['attributes'][0],
				'attributes'    => $this->attributeStringToArray($match['attributes'][0]),
			);

			$lastPosition = $snippetPosition + strlen($chunk);
		}

		$chunk = substr($html, $lastPosition);
		if ($chunk != '') {
			$chunkedMarkup[] = array(
				'type'  => 'html',
				'data'  => $chunk,
			);
		}

		// at this point we have an array of types like [html, placeholder, placeholder, html, placeholder, html]
		return $chunkedMarkup;
	}








	/**
	 * Splits given html string into chunks
	 *
	 * each chunk is either a html segment or represents a paywall segment
	 *
	 * Output:
	 *      [
	 *          [
	 *              'type'          =>  'html',
	 *              'data'          =>  '...', // original string data at this point
	 *          ],
	 *          [
	 *              'type'          =>  'paywall',
	 *              'paywalltype'   =>  '...',  // one of 'classic' or 'gutenberg'
	 *              'data'          =>  '...', // original string data at this point
	 *              'blocktype'     =>  '...', // the signature of the gutenberg block type (only for gutenberg-type)
	 *              'attributejson' =>  '...', // the attributes string as json (only for gutenberg-type)
	 *              'attributes'    =>  [],    // associative array of the attributes string (only for gutenberg-type)
	 *              'innercontent'  =>  '...', // string data between the wrapping block tags (only for gutenberg-type)
	 *          ],
	 *          [
	 *              'type'          =>  'html',
	 *              'data'          =>  '...', // original string data at this point
	 *          ],
	 *          // ...
	 *      ]
	 *
	 * @param string $html
	 *
	 * @return array
	 */
	public function getPaywallMarkupFromHtml($html)
	{
		$gutenbergPaywallPattern = '#(?P<gutenbergpaywall><!--\s*(?P<blocktype>wp:satoshipay/block-(?:article-paywall))\s+'.
		                           '(?P<blockattributes>\{.*?\})\s*-->'.
		                           '\s*(?P<'.''.'innercontent>(?:(?!<!-- \2).)*?)\s*'.
		                           '<!'.''.'--\s*/\2\s*-->)#';
		$classicPaywallPattern = '#<!--\s*satoshipay:start\s*-->#';

		preg_match_all($gutenbergPaywallPattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

		$chunkedMarkup = array(); // will hold the whole content chunked in 'html' and 'paywall' segments
		$lastPosition = 0;

		// walking through found gutenberg-style-paywalls and splitting the original html into segments
		foreach ($matches as $match) {
			$snippetPosition = $match['gutenbergpaywall'][1];

			if ($snippetPosition > $lastPosition) {
				$chunk = substr($html, $lastPosition, $snippetPosition - $lastPosition);
				$chunkedMarkup[] = array(
					'type'  => 'html',
					'data'  => $chunk,
				);
			}

			$chunk = $match['gutenbergpaywall'][0];
			$chunkedMarkup[] = array(
				'type'          => 'paywall',
				'paywalltype'   => 'gutenberg',
				'data'          => $chunk,
				'blocktype'     => $match['blocktype'][0],
				'attributejson' => $match['blockattributes'][0],
				'attributes'    => json_decode($match['blockattributes'][0], true),
				'innercontent'  => $match['innercontent'][0],
			);
			$lastPosition = $snippetPosition + strlen($chunk);
		}

		$chunk = substr($html, $lastPosition);
		if ($chunk != '') {
			$chunkedMarkup[] = array(
				'type'  => 'html',
				'data'  => $chunk,
			);
		}

		// at this point we have an array of types like [html, paywall, paywall, html, paywall, html]

		// now we have to check the remaining html-segments if they contain classic paywall markup

		foreach ($chunkedMarkup as $k => $chunk) {
			if ($chunk['type'] != 'html') {
				// go on, we are only interrested in html chunks
				continue;
			}

			$splittedChunk = preg_split($classicPaywallPattern, $chunk['data']);
			if (count($splittedChunk) == 1) {
				// the html chunk does not contain any classic paywall markup, so go on
				continue;
			}

			// the $splittedChunk now consists of pure html segments. each break represents a classic paywall markup

			$newChunks = array();
			foreach ($splittedChunk as $n => $htmlChunk) {
				if ($htmlChunk != '') {
					$newChunks[] = array(
						'type'  => 'html',
						'data'  => $htmlChunk,
					);
				}

				if ($n < count($splittedChunk) - 1) {
					$newChunks[] = array(
						'type'          => 'paywall',
						'paywalltype'   => 'classic',
						'data'          => '<!--satoshipay:start-->',
					);
				}
			}

			// replacing the html segment with the new chunks
			array_splice($chunkedMarkup, $k, 1, $newChunks);
		}

		return $chunkedMarkup;
	}


	/**
	 * Removes accidently created paywalls
	 *
	 * returns true if something was changed, otherwise false if nothing happened
	 *
	 * @param \WP_Post $post
	 * @param int $maxDepth
	 *
	 * @return bool
	 */
	public function processCleanUpObsoleteInactivePaywallsFromPost(\WP_Post $post, $maxDepth = 100)
	{
		// only use meta status if you are sure you can trust the stored data, otherwise you could destroy innocent
		// well configured paywalls
		$ignoreMetaStatus = true;
		$paywallTag = '<!--satoshipay:start-->';


		// first detect if satoshipay is used for the current post
		$spIsUsed = $this->isSatoshiPayUsedForPost($post->ID);

		$paywallMarkups = $this->getPaywallMarkupFromHtml($post->post_content);

		$countPaywalls = count(array_filter($paywallMarkups, function($chunk) { return $chunk['type'] == 'paywall';}));
		$countHtml = count(array_filter($paywallMarkups, function($chunk) { return $chunk['type'] == 'html';}));

		$maxExpectedPaywalls = 1;
		if (!$spIsUsed) {
			// SatoshiPay is not used for the post, so either exactly one inactive paywall or no paywall should be found
			$expectedPaywallStatus = false;
		} else {
			// SatoshiPay is used for the post, so exactly one paywall should be found and the paywall should be active
			$expectedPaywallStatus = true;
		}


		// if at all, then only the last paywall could be correct. it must be not at the beginning of the post.
		$keepLast = true;

		if ($countPaywalls > $maxExpectedPaywalls) {
			// found more than expected paywalls

			// lets see if all paywalls are at the beginning of the post and if they should be disabled
			if ($countHtml == 1 && $paywallMarkups[0]['type'] == 'paywall' && !$expectedPaywallStatus) {
				// all paywalls are at the beginning, they will be removed
				$keepLast = false;
			}

			$countRemoved = 0;
			$usefullMarkup = array_values(array_filter(
				$paywallMarkups,
				function($chunk) use ($keepLast, &$countRemoved, $countPaywalls) {
					if ($chunk['type'] == 'html') {
						return true;
					}

					if (!$keepLast || $countRemoved < $countPaywalls - 1) {
						$countRemoved++;
						return false;
					}

					return true;

				}
			));

			// putting back the fixed data
			$paywallMarkups = $usefullMarkup;
		}


		if ($keepLast && !$ignoreMetaStatus) {
			// make sure the kept paywall has the correct status

			$kPaywall = key(array_filter($paywallMarkups, function($chunk) {return $chunk['type'] == 'paywall';}));
			$paywall = &$paywallMarkups[$kPaywall];


			if (!$expectedPaywallStatus && $paywall['attributes']['enabled']) {
				// oops, the paywall is enabled
				// so we have to disable it and maybe we have to remove the tag from the inner content

				// disabling the snippet
				$paywall['attributes']['enabled'] = false;
				$paywall['attributejson'] = json_encode($paywall['attributes']);

				// removing the paywall tag from inner content if present
				$paywall['innercontent'] = preg_replace(
					'#<div>\s*' . preg_quote($paywallTag) . '\s*</div>#',
					'',
					$paywall['innercontent']
				);
			} elseif ($expectedPaywallStatus && !$paywall['attributes']['enabled']) {
				// oops, the paywall is disabled
				// so we have to enable it and maybe we have to replace the inner content with the tag

				// enabling the snippet
				$paywall['attributes']['enabled'] = true;

				// checking the price
				if (!$paywall['attributes']['price']) {
					$paywall['attributes']['price'] = $this->getSatoshiPayPriceForPost($post->ID);
				}

				$paywall['attributejson'] = json_encode($paywall['attributes']);

				// searching for the tag
				if (false === strpos($paywall['innercontent'], $paywallTag)) {
					// tag is missed, lets add it
					$paywall['innercontent'] = sprintf(
						'<div class="wp-block-satoshipay-block-article-paywall"><div>%s</div></div>',
						$paywallTag
					);
				}
			}

			$paywall['data'] = sprintf(
				'<!-- %1$s %2$s -->' . PHP_EOL . '%3$s' . PHP_EOL . '<!-- /%1$s -->',
				$paywall['blocktype'],
				$paywall['attributejson'],
				$paywall['innercontent']
			);
		}

		// merging all segments back to one string
		$newContent = implode('', array_map(function($chunk) {return $chunk['data'];}, $paywallMarkups));

		$changed = $newContent != $post->post_content;

		$post->post_content = $newContent;

		if ($changed) {
			if ($maxDepth > 0) {
				$this->processCleanUpObsoleteInactivePaywallsFromPost($post, $maxDepth - 1);
			}
		}

		return $changed;
	}



	/**
	 * Returns number of pages or array of post objects
	 *
	 * @param int $page
	 * @param int $pagesize
	 * @param bool $countPages
	 *
	 * @return \StdClass[]|int
	 */
	public function getPosts(
		$page = 0,
		$pagesize = 50,
		$countPages = false
	)
	{
		$db = $this->db();
		$args = array($db->posts);

		if (!$countPages || !$pagesize) {
			$qry = 'SELECT %1$s.* ';
		} else {
			$qry = 'SELECT COUNT(*) ';
		}

		$qry .= 'FROM %1$s '.
		        'WHERE %1$s.post_status != \'auto-draft\' '.
		        'AND %1$s.post_type = \'post\' ';

		if (!$countPages && $pagesize) {
			$qry .= 'LIMIT %2$s, %3$s';
			$args[] = $page * $pagesize;
			$args[] = $pagesize;
		}

		$sqlQuery = vsprintf( $qry, $args);

		if ($countPages && $pagesize) {
			$result = $db->get_var($sqlQuery);
			$result = ceil($result / $pagesize);
		} else {
			$result = $db->get_results($sqlQuery, OBJECT);
		}

		return $result;
	}

}
