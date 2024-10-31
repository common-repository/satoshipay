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

require_once __DIR__ . '/Api/Client.php';
require_once __DIR__ . '/SatoshiPayMigrator.php';



use SatoshiPay\Api\Client as ApiClient;
use StdClass;
use wpdb;
use const SatoshiPay\Constants\META_KEY_SATOSHIPAY_ID;

/**
 * Class SatoshiPayInstall
 * @package SatoshiPay
 */
class SatoshiPayInstall
{
    /**
     * {@inheritdoc}
     */
    public static function install()
    {
        global $wpdb;

        // Disable Ad blocker detection (v1.4)
        update_option('satoshipay_ad_blocker_detection', array('enabled' => false, 'price' => ''));

        // query for all posts (including attachments like videos, images etc.)
        $sqlQuery =
            "SELECT " .
            "  `" . $wpdb->posts . "`.`ID` " .
            "FROM " .
            "  `" . $wpdb->posts . "` " .
            "WHERE " .
            "  `" . $wpdb->posts . "`.`post_type` IN ('attachment', 'page', 'post') AND ".
            "  `" . $wpdb->posts . "`.`post_status` != 'auto-draft'"
        ;
        $postIds = $wpdb->get_col($sqlQuery);

        if ($postIds) {
            foreach ($postIds as $postId) {
                // for all posts

                // get the post
                $post = get_post($postId);

                $satoshipayId = get_post_meta($post->ID, '_satoshipay_id', true);
                $asset = get_post_meta($postId, '_satoshipay_asset', true);
                $satoshiPayPricing = get_post_meta($post->ID, '_satoshipay_pricing', true);

                if ($satoshipayId && $satoshiPayPricing['enabled'] === true && $asset !== 'XLM') {
                    // it's a good thats not an XLM good - we update it.

                    // Get post metadata
                    $satoshiPaySecret = get_post_meta($post->ID, '_satoshipay_secret', true);
                    $satoshiPayId = get_post_meta($post->ID, '_satoshipay_id', true);

                    // set lumenPricing
                    $lumenPricing = $satoshiPayPricing;
                    $lumenPricing['satoshi'] = (int)round((int)$satoshiPayPricing['satoshi'] * 0.000625 + .5);

                    // Create a SatoshiPay good for provider API
                    /** @noinspection PhpUndefinedVariableInspection */
                    $satoshiPayGood = array(
                        'goodId' => $post->ID,
                        'price' => $lumenPricing['satoshi'],
                        'sharedSecret' => $satoshiPaySecret,
                        'title' => $post->post_title,
                        'url' => get_permalink($post->ID),

                        'spmeta' => json_encode($metaData), // @todo $metaData seems to be undefined
                    );

                    try {
                        // set apiCredentials
                        $apiCredentials = get_option('satoshipay_api');

                        $apiClient = new ApiClient($apiCredentials);

                        // Update the good with the API
                        $satoshiPayId = $apiClient->updateGood($satoshiPayId, $satoshiPayGood);

                        // Update metadata `_satoshipay_id` for post
                        update_post_meta($post->ID, '_satoshipay_id', $satoshiPayId);

                        // Update metadata `_satoshipay_pricing` for post
                        update_post_meta($post->ID, '_satoshipay_pricing', $lumenPricing);

                        // Update metadata `_satoshipay_asset` for post
                        update_post_meta($post->ID, '_satoshipay_asset', 'XLM');
                    } /** @noinspection PhpRedundantCatchClauseInspection */ catch (Exception $e) { // @todo it seems this Exception will never be thrown
                        WP_die($e->getMessage());
                    }
                }
            }
        }
    }

    // Migrate Classic Editor products to Gutenberg Blocks
    static function migrateGutenbergBlocks()
    {

        // Generate Gutenberg Block from Clsasic Placeholder
        function classicPlaceholderToBlock($attributes)
        {
            $mediaPrice = get_post_meta($attributes['id'], '_satoshipay_pricing', true);
            $attachedFile = get_attached_file( $attributes['id'] );
            $mediaTitle = basename( $attachedFile );
            $mediaSize = filesize( $attachedFile );
            $preview = $attributes['preview'] ? $attributes['preview'] : "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='100%' height='100%'><rect width='100%' height='100%' fill='%23F3F3F4'/></svg>";
            $coverType = $attributes['preview'] ? ',"coverType":"COVER_TYPE_FILE","coverUrl":"' . $attributes['preview'] . '","coverTitle":"Custom image"' : '';

            switch ($attributes['type']) {
                case 'image':
                    $mimeType = get_post_mime_type( $attributes['id'] );
                    $mediaUrl = wp_get_attachment_url( $attributes['id'] );
                    return sprintf('<!-- wp:satoshipay/block-paid-media {"mediaId":%s,"mediaPrice":%s,"mediaType":"%s","mediaMime":"%s","mediaUrl":"%s","mediaTitle":"%s","mediaSize":"%s","mediaWidth":%s,"mediaHeight":%s%s} -->
<div class="wp-block-satoshipay-block-paid-media"><!--satoshipay:%s attachment-id="%s" width="%s" height="%s" preview="%s"--></div>
<!-- /wp:satoshipay/block-paid-media -->', $attributes['id'], $mediaPrice['satoshi'], $attributes['type'], $mimeType, $mediaUrl, $mediaTitle, $mediaSize, $attributes['width'], $attributes['height'], $coverType, $attributes['type'], $attributes['id'], $attributes['width'], $attributes['height'], $preview);
                break;

                case 'video':
                    $mimeType = get_post_mime_type( $attributes['id'] );
                    $mediaUrl = wp_get_attachment_url( $attributes['id'] );
                    return sprintf('<!-- wp:satoshipay/block-paid-media {"mediaId":%s,"mediaPrice":%s,"mediaType":"%s","mediaMime":"%s","mediaUrl":"%s","mediaTitle":"%s","mediaSize":"%s","mediaWidth":%s,"mediaHeight":%s,"mediaAutoPlay":%s%s} -->
<div class="wp-block-satoshipay-block-paid-media"><!--satoshipay:%s attachment-id="%s" width="%s" height="%s" autoplay="%s" preview="%s"--></div>
<!-- /wp:satoshipay/block-paid-media -->', $attributes['id'], $mediaPrice['satoshi'], $attributes['type'], $mimeType, $mediaUrl, $mediaTitle, $mediaSize, $attributes['width'], $attributes['height'], $attributes['autoplay'], $coverType, $attributes['type'], $attributes['id'], $attributes['width'], $attributes['height'], $attributes['autoplay'], $preview);
                break;

                case 'audio':
                    $mimeType = get_post_mime_type( $attributes['id'] );
                    $mediaUrl = wp_get_attachment_url( $attributes['id'] );
                    return sprintf('<!-- wp:satoshipay/block-paid-media {"mediaId":%s,"mediaPrice":%s,"mediaType":"%s","mediaMime":"%s","mediaUrl":"%s","mediaTitle":"%s","mediaSize":"%s","mediaAutoPlay":%s} -->
<div class="wp-block-satoshipay-block-paid-media"><!--satoshipay:%s attachment-id="%s" autoplay="%s"--></div>
<!-- /wp:satoshipay/block-paid-media -->', $attributes['id'], $mediaPrice['satoshi'], $attributes['type'], $mimeType, $mediaUrl, $mediaTitle, $mediaSize, $attributes['autoplay'] , $attributes['type'], $attributes['id'], $attributes['autoplay'] );
                break;

                case 'download':
                    return sprintf('<!-- wp:satoshipay/block-paid-file {"fileId":%s,"fileTitle":"%s","filePrice":%s,"fileSize":"%s"} -->
<div class="wp-block-satoshipay-block-paid-file"><!--satoshipay:download attachment-id="%s"--></div>
<!-- /wp:satoshipay/block-paid-file -->', $attributes['id'], $mediaTitle, $mediaPrice['satoshi'], $mediaSize, $attributes['id']);
                break;

                case 'donation':
                    return sprintf('<!-- wp:satoshipay/block-donation {"donationValue":%s, "donationCurrency":"%s","placeholderId":%s,"enabled":true,"creatingPlaceholder":false,"coverWidth":%s,"coverHeight":%s%s} -->
<div class="wp-block-satoshipay-block-donation"><!--satoshipay:donation attachment-id="%s" width="%s" height="%s" preview="%s" asset="%s"--></div>
<!-- /wp:satoshipay/block-donation -->', $mediaPrice['satoshi'], $attributes['asset'], $attributes['id'], $attributes['width'], $attributes['height'], $coverType, $attributes['id'], $attributes['width'], $attributes['height'], $preview, $attributes['asset']);
                break;
            }

            return false;
        }

        $useMigrator        = true;   // simple debug flags
        $useCleaner         = false;  // simple debug flags
        $avoidDuplicates    = false;  // simple debug flags

        global $wpdb;

        $pagesize = 250;
        $pagecount = null;

        // count the posts
        $sqlQuery = "
            SELECT COUNT(*) AS `posts`
            FROM $wpdb->posts
            WHERE $wpdb->posts.post_status != 'auto-draft'
            AND $wpdb->posts.post_type = 'post'
        ";
        $totalPosts = $wpdb->get_var($sqlQuery);
        $pagecount = ceil($totalPosts / $pagesize);

        for ($page = 0; $page < $pagecount; $page++) {
            $offset = $page * $pagesize;

        // Migrate all placeholders created by the Classic Editor to a Gutenberg Blocks
        $sqlQuery = "
            SELECT $wpdb->posts.*
            FROM $wpdb->posts
            WHERE $wpdb->posts.post_status != 'auto-draft'
            AND $wpdb->posts.post_type = 'post'
            LIMIT $offset, $pagesize
        ";
        $posts = $wpdb->get_results($sqlQuery, OBJECT);

        if ($posts) {
            foreach ($posts as $post) {

            	if ($useMigrator) {
		        	SatoshiPayMigrator::getInstance()->migratePostById($post->ID, true);
		        	continue;
	            }

                $content = $post->post_content;
                $newContent = $content;
                $classicPlaceholdersRegexWithCapture = '/<!--satoshipay:(image|audio|video|download|donation)(.*attachment-id="(\d+)")?(.*width="(\d+)")?(.*height="(\d+)")?(.*autoplay="(true|false)")?(.*preview="([^"]*)")?(.*asset="(.*)")?-->/';
                $classicPlaceholdersRegexWithoutCapture = '<!--satoshipay:(?:image|audio|video|download|donation)(?:.*attachment-id="(?:\d+)")?(?:.*width="(?:\d+)")?(?:.*height="(?:\d+)")?(?:.*autoplay="(?:true|false)")?(?:.*preview="(?:[^"]*)")?(?:.*asset="(?:.*)")?-->';
                $classicPlaceholdersPositionRegex = '/<!-- wp:satoshipay.*\n.+'. $classicPlaceholdersRegexWithoutCapture .'.+\n<!--.*(*SKIP)(*F)|'. $classicPlaceholdersRegexWithoutCapture .'/';
                $classicPaywallPlaceholderRegex = '/<!-- wp:satoshipay.*\n.+<!--satoshipay:start-->.+\n<!--.*(*SKIP)(*F)|<!--satoshipay:start-->/';

                preg_match_all(
                    $classicPlaceholdersPositionRegex,
                    $content,
                    $matches,
                    PREG_OFFSET_CAPTURE
                );

                foreach (array_reverse($matches[0]) as $match) {
                    if(count($match) !== 0){
                        preg_replace_callback(
                            $classicPlaceholdersRegexWithCapture,
                            function($attrs) use(&$newContent, $match) {
                                $classicPlaceholder = $match[0];
                                $classicPlaceholderPosition = $match[1];
                                $classicPlaceholderPositionEnd = strlen($classicPlaceholder);
                                $attributes = array(
                                    'placeholder' => isset($attrs[0]) ? $attrs[0] : '',
                                    'type' => isset($attrs[1]) ? $attrs[1] : '',
                                    'id' => isset($attrs[3]) ? $attrs[3] : '',
                                    'height' => isset($attrs[7]) ? $attrs[7] : '',
                                    'width' => isset($attrs[5]) ? $attrs[5] : '',
                                    'autoplay' => isset($attrs[9]) ? $attrs[9] : '',
                                    'preview' => isset($attrs[11]) ? $attrs[11] : '',
                                    'asset' => isset($attrs[13]) ? $attrs[13] : ''
                                );
                                $gutenbergBlock = classicPlaceholderToBlock($attributes);
                                $newContent = substr_replace($newContent, $gutenbergBlock, $classicPlaceholderPosition, $classicPlaceholderPositionEnd );
                            },
                            $match[0]
                        );

                    }
                }

                preg_replace_callback(
                    $classicPaywallPlaceholderRegex,
                    function($match) use(&$newContent, $post) {
                        if(count($match) > 0){
                            $mediaPrice = get_post_meta($post->ID, '_satoshipay_pricing', true);
                            $gutenbergBlock = sprintf('<!-- wp:satoshipay/block-article-paywall {"postId":%s,"price":%s,"enabled":%s} -->
<div class="wp-block-satoshipay-block-article-paywall">%s</div>
<!-- /wp:satoshipay/block-article-paywall -->', $post->ID, $mediaPrice ? $mediaPrice['satoshi'] : 0, $mediaPrice && $mediaPrice['enabled'] ? 'true' : 'false', $mediaPrice && $mediaPrice['enabled'] ? '<div><!--satoshipay:start--></div>' : '');
                            $newContent = str_replace(
                                '<!--satoshipay:start-->',
                                $gutenbergBlock,
                                $newContent
                            );
                        }
                    },
                    $content
                );

                if($content !== $newContent){
                    wp_update_post(array(
                        'ID'    => $post->ID,
                        'post_content' => $newContent
                    ));
                }
            }
        }
        }

	    if ($useMigrator) {
		    return;
	    }

        $pagesize = 250;
        // count the posts
        $sqlQuery = $wpdb->prepare( "
            SELECT COUNT(*) AS `posts`
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p
            ON p.ID = pm.post_id
            WHERE pm.meta_key = '%s'
            AND p.post_type = 'post'
        ", Constants\META_KEY_SATOSHIPAY_PRICE );
        $totalPosts = $wpdb->get_var($sqlQuery);
        $pagecount = ceil($totalPosts / $pagesize);


        for ($page = 0; $page < $pagecount; $page++) {
            $offset = $page * $pagesize;

        // Migrate full paid posts without start tag and add Paywall Block at the beginning of the post
        $products = $wpdb->get_results( $wpdb->prepare( "
            SELECT pm.post_id, pm.meta_value, p.post_content, p.post_type
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p
            ON p.ID = pm.post_id
            WHERE pm.meta_key = '%s'
            AND p.post_type = 'post'
            LIMIT $offset, $pagesize
        ", Constants\META_KEY_SATOSHIPAY_PRICE ) );

        foreach ($products as $product) {
            $mediaPrice = get_post_meta($product->post_id, '_satoshipay_pricing', true);
            if (!array_key_exists('enabled', $mediaPrice) || !$mediaPrice['enabled']) {
            	if ($avoidDuplicates) continue;
            }


            $classicPaywallPlaceholderRegex = '/<!--satoshipay:start-->/';

            preg_match(
                $classicPaywallPlaceholderRegex,
                $product->post_content,
                $match
            );
            if(count($match) == 0){
                $mediaPrice = get_post_meta($product->post_id, '_satoshipay_pricing', true);
                $gutenbergBlock = sprintf('<!-- wp:satoshipay/block-article-paywall {"postId":%s,"price":%s,"enabled":%s} -->
<div class="wp-block-satoshipay-block-article-paywall">%s</div>
<!-- /wp:satoshipay/block-article-paywall -->', $product->post_id, $mediaPrice ? $mediaPrice['satoshi'] : 0, $mediaPrice && $mediaPrice['enabled'] ? 'true' : 'false', $mediaPrice && $mediaPrice['enabled'] ? '<div><!--satoshipay:start--></div>' : '');
                $newContent = $gutenbergBlock . $product->post_content;
                wp_update_post(array(
                    'ID'    => $product->post_id,
                    'post_content' => $newContent
                ));
            }
        }
        }

        if ($useCleaner) static::cleanUpObsoleteInactivePaywalls();
    }




    /**
     * Wrapper to get database object
     *
     * @return wpdb
     */
    protected static function db()
    {
        global $wpdb;
        return $wpdb;
    }


    /**
    * @param int $page
    * @param int $pagesize
    * @param bool $countPages
    *
    * @return StdClass[]|int
    */
    protected static function getPostsWithPossiblePaywall(
        $page = 0,
        $pagesize = 250,
        $countPages = false
    )
    {
        $db = static::db();
        $args = array($db->posts);

	    if (!$countPages || !$pagesize) {
		    $qry = 'SELECT %1$s.* ';
	    } else {
		    $qry = 'SELECT COUNT(*) ';
	    }

	    $qry .= 'FROM %1$s '.
	            'WHERE %1$s.post_status != \'auto-draft\' '.
	            'AND %1$s.post_type = \'post\' '.
	            'AND %1$s.post_content LIKE "%%paywall%%"';

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


    /**
     * @return void
     */
    public static function cleanUpObsoleteInactivePaywalls()
    {
        $pagecount = static::getPostsWithPossiblePaywall(0, 250, true);

        for ($page = 0; $page < $pagecount; $page++) {
        foreach (static::getPostsWithPossiblePaywall() as $post) {
            $origContent = $post->post_content;
            static::processCleanUpObsoleteInactivePaywallsFromPost($post);
            $newContent = $post->post_content;

            if ($origContent != $newContent) {
                wp_update_post(array(
                    'ID'            => $post->ID,
                    'post_content'  => $newContent
                ));
            }
        }
        }
    }


    /**
     * @param int $postId
     *
     * @return bool
     */
    public static function isSatoshiPayUsedForPost($postId)
    {
        $meta = get_post_meta($postId, META_KEY_SATOSHIPAY_ID, true);

        return ($meta && array_key_exists('enabled', $meta) && $meta['enabled']);
    }


    /**
     * @param int $postId
     *
     * @return mixed
     */
    public static function getSatoshiPriceForPost($postId)
    {
        if (!static::isSatoshiPayUsedForPost($postId)) {
            return 0;
        }
        $meta = get_post_meta($postId, META_KEY_SATOSHIPAY_ID, true);

        return ($meta && array_key_exists('satoshi', $meta)) ? $meta['satoshi'] : 0;
    }


    /**
     * Splits given html string into chunks
     *
     * each chunk is either a html segment or represents a paywall segment
     *
     * @param string $html
     *
     * @return array
     */
    public static function getPaywallMarkupFromHtml($html)
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
     * @param StdClass $post
     * @param int $maxDepth
     */
    protected static function processCleanUpObsoleteInactivePaywallsFromPost( StdClass $post, $maxDepth = 100)
    {
        // only use meta status if you are sure you can trust the stored data, otherwise you could destroy innocent
        // well configured paywalls
        $ignoreMetaStatus = true;
        $paywallTag = '<!--satoshipay:start-->';


        // first detect if satoshipay is used for the current post
        $spIsUsed = static::isSatoshiPayUsedForPost($post->ID);

        $paywallMarkups = static::getPaywallMarkupFromHtml($post->post_content);

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
                    $paywall['attributes']['price'] = static::getSatoshiPriceForPost($post->ID);
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
                static::processCleanUpObsoleteInactivePaywallsFromPost($post, $maxDepth - 1);
            }
        }
    }
}
