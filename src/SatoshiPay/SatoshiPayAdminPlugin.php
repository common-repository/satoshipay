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
require_once __DIR__ . '/Plugin/PluginAbstract.php';
require_once __DIR__ . '/SatoshiPayException.php';
require_once __DIR__ . '/Utils/IsGutenberg.php';

// Included to use is_plugin_active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

use WP_Ajax_Response;
use WP_Post;
use WP_Error;

use SatoshiPay\Api\Client as ApiClient;
use SatoshiPay\Plugin\PluginAbstract;
use SatoshiPay\Utils;

class SatoshiPayAdminPlugin extends PluginAbstract
{
    /**
     * Ajax actions used in tinymce.util.XHR requests in tinemce JS
     * @var array
     */
    protected $ajaxActions = array(
        'satoshipay-set-pricing'            => 'onAjaxSetPricing',
        'satoshipay-create-donation'        => 'onAjaxCreateDonationPost',
        'satoshipay-get-donation'           => 'onAjaxGetDonationPost',
        'satoshipay-migration-countpages'   => 'onAjaxMigrationCountPages',
        'satoshipay-migration-processposts' => 'onAjaxMigrationProcessPosts',
    );

    /**
     * {@inheritdoc}
     */
    protected $styles = array(
        "satoshipay_style_admin" => SATOSHIPAY_STYLE_ADMIN,
    );

	/**
	 * {@inheritdoc}
	 */
	protected $scripts = array(
		'satoshipay_script_admin'           => SATOSHIPAY_SCRIPT_ADMIN,
		'satoshipay_script_admin_migrator'  => SATOSHIPAY_SCRIPT_ADMIN_MIGRATOR,
		'satoshipay_script_post'            => SATOSHIPAY_SCRIPT_POST,
	);

	/**
     * @var array
     */
    protected $defaultApiSettings = array(
        'auth_key' => '',
        'auth_secret' => '',
    );

    /**
     * @var array
     */
    protected $defaultAdBlockerDetectionSettings = array(
        'enabled' => 0,
    );

    /**
     * @var array
     */
    protected $defaultBrowserDetectionSettings = array(
        'enabled' => array(),
    );

    /**
     * @var array
     */
    protected $defaultClientSettings = array(
        'client_url' => SATOSHIPAY_CLIENT_URL,
    );

    /**
     * @var array
     */
    protected $defaultPricing = array(
        'enabled' => false,
        'satoshi' => '',
    );

    /**
     * Get singleton instance.
     *
     * @param string $mainPluginFile
     * @return $this
     */
    public static function getInstance($mainPluginFile)
    {
        if (null === self::$instance) {
            self::$instance = new self($mainPluginFile);
        }

        return self::$instance;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        load_plugin_textdomain($this->name, false, WP_PLUGIN_DIR . '/' . $this->name . '/languages/' );

        // Register admin scripts / styles hooks.
        add_action('admin_enqueue_scripts', array($this, 'enqueueStyles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));

        // Register admin notice function
        add_action('admin_notices', array($this, 'adminNotices'));

        if( !Utils\isGutenberg() ) {
            // Disable post hooks if Gutenberg is enabled
            // check namespace SatoshiPay\GutenbergEditor for Gutenberg handlers

            // Register edit post function
            add_action('load-post.php', array($this, 'onEditPost'));

            // Register update post function
            add_action('post updated', array($this, 'onUpdatePost'));
            add_action('save_post', array($this, 'onSavePost'));
            add_action('edit_attachment', array($this, 'onSavePost'));
            add_action('add_meta_boxes', array($this, 'onAddMetaBoxes'));
        }

        add_action('admin_menu', array($this, 'onAdminMenu'));
        add_action('admin_init', array($this, 'onAdminInit'));
        add_action('admin_head', array($this, 'onAdminHead'));

        add_action('get_post', array($this, 'onPrepareAttachmentForJavascript'));
        add_action('before_delete_post', array($this, 'onBeforeDeletePost'));

        add_action('updated_option', array($this, 'onUpdatedOptions'));

        foreach ($this->ajaxActions as $actionName => $functionName) {
            if (method_exists($this, $functionName)) {
                add_action('wp_ajax_' . $actionName, array($this, $functionName));
            }
        }

        add_filter('plugin_action_links_' . SATOSHIPAY_PLUGIN_ROOT_FILE, array($this, 'addSettingsLink'));
        add_filter('wp_prepare_attachment_for_js', array($this, 'onPrepareAttachmentForJavascript'));

        add_action( 'init', array($this, 'create_donation_post_type'));
    }

    function create_donation_post_type() {
      register_post_type( 'sp_donation',
        array(
          'labels' => array(
            'name' => __( 'Donations' ),
            'singular_name' => __( 'Donation' )
          ),
          'public' => false,
          'has_archive' => false,
        )
      );
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueScripts($scope = '', $filter = false)
    {
      if (strpos($scope, 'SatoshiPayAdminPlugin') !== false) {
        return parent::enqueueScripts($scope, 'satoshipay_script_admin');
      }
      if ('toplevel_page_satoshipay_settings_page' == $scope) {
        return parent::enqueueScripts($scope, 'satoshipay_script_admin_migrator');
      }
      if (strpos($scope, 'post') !== false) {
        return parent::enqueueScripts($scope, 'satoshipay_script_post');
      }

      return $this;
    }

    /**
     * Callback function for admin_notices
     */
    public function adminNotices()
    {
        // define the transients to be checked
        $transients = array(
            'api_credentials_invalid',
            'save_post_missinginfo_error',
            'save_post_multiple_tags'
        );

        // check all transients
        foreach($transients as $transient){
            // get transient names
            $transientNames = array(
                $this->getTransientName($transient,get_the_ID()),
                $this->getTransientName($transient)
            );

            // check for post specific and post independent transients
            foreach($transientNames as $transientName){
                if($error = get_transient($transientName)){
                    // we output the error
                    echo '<div class="error"><p>'.$error->get_error_message().'</p></div>';

                    // we remove the transient
                    delete_transient($transient);
                }
            }
        }
    }

    /**
     * Callback function for action 'admin_menu'.
     */
    public function onAdminMenu()
    {
        $this->addAdminMenu();
    }

    /**
     * Callback function for action 'admin_init'.
     */
    public function onAdminInit()
    {
    }

    /**
     * Callback function for action 'admin_head'.
     */
    public function onAdminHead()
    {
        $this->setupTinyMcePlugin();
    }

    /**
     * Callback function for action 'add_meta_boxes'.
     */
    public function onAddMetaBoxes()
    {
        $this->addMetaBoxes();
    }

    /**
     * Callback function for action 'load-edit.php'.
     *
     * @param string $postId
     */
    public function onEditPost(){
        if(!isset($_GET['post'])) {
          return;
        }

        // get the post id
        $postId = $_GET['post'];

        // get the post
        $post = get_post($postId);

        // Ignore invalid posts
        if (!$post) {
            return;
        }

        // check api
        $this->checkApiData();

        // check post metadata
        $this->checkSatoshipayMetadata($post);
    }

    /**
     * Callback function for action 'update_post'.
     *
     * @param string $postId
     */
    public function onUpdatePost($postId)
    {
        // Check user permissions
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $post = get_post($postId);
        // Ignore invalid posts
        if (!$post) {
            return;
        }

        $this->checkSatoshipayMetadata($post);
    }

    /**
     * Callback function for action 'save_post'.
     *
     * @param string $postId
     */
    public function onSavePost($postId)
    {
        // Check user permissions
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $post = get_post($postId);
        // Ignore invalid posts
        if (!$post) {
            return;
        }

        $this->saveMetadata($post);
    }

    /**
     * Callback function for action 'before_delete_post'.
     *
     * Deletes provider API and post meta data. Has to be done before deleting
     * the post in WordPress database because after it was deleted by WordPress
     * all meta data would be deleted too (no chance to get the regarding
     * SatoshiPay ID for requesting the provider API).
     *
     * @param string $postId
     */
    public function onBeforeDeletePost($postId)
    {
        // Check user permissions
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $post = get_post($postId);
        // Ignore invalid posts
        if (!$post) {
            return;
        }

        // Get SatoshiPay settings for `satoshipay_api`
        $apiCredentials = get_option('satoshipay_api');
        if ($this->validCredentials($apiCredentials)) {
            $satoshiPayId = get_post_meta($post->ID, '_satoshipay_id', true);
            if ($satoshiPayId) {
                try {
                    $apiClient = new ApiClient($apiCredentials);
                    $apiClient->deleteGood($satoshiPayId);
                } catch (Exception $e) {
                    $this->apiError($e->getMessage());
                }

                delete_post_meta($post->ID, '_satoshipay_id', $satoshiPayId);
            }
        }
    }

    /**
     * Callback function for rendering meta box.
     */
    public function onRenderMetaBox($post)
    {
        $pricing = get_post_meta($post->ID, '_satoshipay_pricing', true);
        if (empty($pricing)) {
            $pricing = $this->defaultPricing;
        }
        $goodSecret = get_post_meta($post->ID, '_satoshipay_secret', true);
        $goodId = get_post_meta($post->ID, '_satoshipay_id', true);

        $maxPrice = SATOSHIPAY_DEFAULT_MAX_PRODUCT_PRICE / 10000000;

        // Get publisher max product price from API
        $apiCredentials = get_option('satoshipay_api');
        $validCredentials = $this->validCredentials($apiCredentials, true);
        if ($validCredentials) {
            try {
                $apiClient = new ApiClient($apiCredentials);
                $maxPrice = (int)$apiClient->getPublisherMaxProductPrice($apiCredentials['auth_key']) / 10000000;
            } catch (Exception $e) {
                $this->apiError($e->getMessage());
            }
        }

        require_once __DIR__ . '/../../views/admin/posts/metabox.phtml';
    }

    /**
     * Callback function for post update options actions.
     *
     * Fires after the value of an option has been successfully updated.
     * More details see https://developer.wordpress.org/reference/hooks/updated_option/
     *
     * @param string $option
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return void
     */
    public function onUpdatedOptions($optionName, $oldValue = null, $newValue = null)
    {
    }

    /**
     * Add new error
     */
    public function addSatoshiPayError($message)
    {
        add_settings_error( 'sp_messages', 'sp_error', $message );
    }

    /**
     * Add new success
     */
    public function addSatoshiPaySuccess($message)
    {
        add_settings_error( 'sp_messages', 'sp_success', $message, 'updated' );
    }

    /**
     * Callback function for (admin) AJAX request "set-pricing".
     */
    public function onAjaxSetPricing()
    {
        if (!isset($_POST['post_id'])) {
            return wp_send_json_error();
        }

        $postId = absint($_POST['post_id']);
        $post = get_post($postId);

        if (!$postId || !$post) {
            return wp_send_json_error();
        }

        if (!current_user_can('edit_post', $post)) {
            return wp_send_json_error();
        }

        $this->saveMetadata($post);

        return wp_send_json_success(
            array(
                'post_id' => $post->ID,
                'satoshipay_pricing' => $this->getPricing($post->ID, array()),
            )
        );
    }

    /**
    * Callback function for (admin) AJAX request "satoshipay-create-donation".
    */
    public function onAjaxCreateDonationPost()
    {
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

    /**
    * Callback function for (admin) AJAX request "satoshipay-get-donation".
    */
    public function onAjaxGetDonationPost()
    {
        if (!isset($_POST['post_id'])) {
            return wp_send_json_error();
        }

        $postId = absint($_POST['post_id']);
        $post = get_post($postId);
        $post->id = $postId;

        if (!$postId || !$post) {
            return wp_send_json_error();
        }

        $postArray = (array) $post;
        $fullPost = $this->onPrepareAttachmentForJavascript($postArray);

        return wp_send_json_success($fullPost);
    }

    /**
     * Adds 'API Settings' section to 'SatoshiPay' plugin’s listing under ‘Plugins’.
     *
     * @return $actions
     */
    public function addSettingsLink($actions)
    {
      $sp_admin_url = admin_url('admin.php?page=satoshipay_settings_page');
      array_unshift($actions, '<a href="'.$sp_admin_url.'">Settings</a>');

      return $actions;
    }

    /**
     * Filters the attachment data prepared for JavaScript.
     *
     * @param array $response
     * @return array
     */
    public function onPrepareAttachmentForJavascript(array $response)
    {
        if (array_key_exists('id', $response)) {
            $pricing = $this->getPricing($response['id'], array());
            if ($pricing['enabled'] && $pricing['satoshi']) {
                $response['price'] = $pricing['satoshi'];
            }
        }

        return $response;
    }

    /**
     * Adds 'SatoshiPay' metaboxes for post types.
     *
     * @return $this
     */
    protected function addMetaBoxes()
    {
        $screens = array('attachment', 'page', 'post');

        foreach ($screens as $screen) {
            add_meta_box(
                'satoshipay_metabox',
                __('SatoshiPay', $this->textdomain),
                array($this, 'onRenderMetaBox'),
                $screen,
                'side',
                'high'
            );
        }

        return $this;
    }

    /**
     * Handle API errors.
     *
     * @param int $message
     */
    function apiError($message)
    {
        WP_die($message);
    }

    /**
     * Returns randomized and hashed string.
     *
     * @return string
     */
    protected function generateSecret()
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
     * Returns only pricing data from data array or default pricing.
     *
     * @param string $postId
     * @param array $data
     * @return array
     */
    protected function getPricing($postId, array $data)
    {
        $pricing = get_post_meta($postId, '_satoshipay_pricing', true);

        // If no metadata exists then apply default data
        if (empty($pricing)) {
            $pricing = $this->defaultPricing;
        }

        if (array_key_exists('satoshipay_pricing_enabled', $data)) {
            $pricing['enabled'] = (bool)$data['satoshipay_pricing_enabled'];
        } else {
            if (array_key_exists('satoshipay_pricing_disabled', $data)) {
                $pricing['enabled'] = false;
            }
        }
        if (array_key_exists('satoshipay_pricing_satoshi', $data)) {
            $pricing['satoshi'] = $data['satoshipay_pricing_satoshi'];
        }

        return $pricing;
    }

    /**
     * Adds / updates satoshi metadata for $post.
     *
     * @param WP_Post $post
     * @return $this
     */
    protected function saveMetadata(WP_Post $post)
    {
        // Ignore invalid posts (status === 'auto-draft' || type === 'revision')
        if (($post->post_status === 'auto-draft') || ($post->post_type === 'revision')) {
            return $this;
        }

        // Add secret metadata if not exist.
        if (!get_post_meta($post->ID, '_satoshipay_secret', true)) {
            add_post_meta($post->ID, '_satoshipay_secret', $this->generateSecret(), true);
        }

        // Get SatoshiPay settings for `satoshipay_api`
        $apiCredentials = get_option('satoshipay_api');
        if ($this->validCredentials($apiCredentials)) {
            $metaData = array(
                'adblock' => false,
            );

            // Sanitizing pricing data (from database or request)
            $pricing = $this->sanitizePricing($this->getPricing($post->ID, $_POST));
            // Add / update pricing metadata.
            update_post_meta($post->ID, '_satoshipay_pricing', $pricing);

            // Get default data from pricing settings
            $pricingEnabled = $pricing['enabled'];
            $pricingPrice = $pricing['satoshi'];

            // If pricing (paid content || ad blocker detection) is enabled && price is valid
            if ($pricingEnabled && $pricingPrice) {
                // Get post metadata
                $satoshiPaySecret = get_post_meta($post->ID, '_satoshipay_secret', true);
                $satoshiPayId = get_post_meta($post->ID, '_satoshipay_id', true);

                // Create a SatoshiPay good for provider API
                $satoshiPayGood = array(
                    'goodId' => $post->ID,
                    'price' => $pricingPrice,
                    'sharedSecret' => $satoshiPaySecret,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                    'spmeta' => json_encode($metaData)
                );

                try {
                    $apiClient = new ApiClient($apiCredentials);

                    // If post has `_satoshipay_id` metadata then update otherwise create
                    if ($satoshiPayId) {
                        $satoshiPayId = $apiClient->updateGood($satoshiPayId, $satoshiPayGood);
                    } else {
                        $satoshiPayId = $apiClient->createNewGood($satoshiPayGood);
                    }
                } catch (Exception $e) {
                    $this->apiError($e->getMessage());
                }

                // Update metadata `_satoshipay_id` for post
                update_post_meta($post->ID, '_satoshipay_id', $satoshiPayId, true);

                // Update metadata `_satoshipay_asset` for post
                update_post_meta($post->ID, '_satoshipay_asset', 'XLM', true);
            }
        }

        return $this;
    }

    /**
     * Checks if a set of credentials is valid
     * @param array $credentials
     * @param bool $cache
     * @return bool
     */
    protected function validCredentials($credentials = null, $cache = false)
    {
        if (is_bool($credentials)) {
            $cache = $credentials;
            $credentials = null;
        }
        if (!$credentials) {
            $credentials = get_option('satoshipay_api');
        }

        if (true == $cache && get_option('checkedCredentials')) {
            return get_option('validCredentials');
        }

        $apiClient = new ApiClient(
            array(
                'auth_key' => $credentials['auth_key'],
                'auth_secret' => $credentials['auth_secret'],
            )
        );

        add_option('checkedCredentials', true);

        switch($apiClient->testCredentials()) {
            case 200:
                update_option('validCredentials', true);
                return true;
        }
    }

    /**
     * Validate Ad Blocker Detection option.
     *
     * @param array $input
     * @return boolean|integer
     */
    protected function validateAdBlockerDetectionOption($input)
    {
        $validatedValue = false;

        // Check for existing input field
        if (isset($input['enabled'])) {
            $inputValue = trim($input['enabled']);
            // Check for integer value
            if (ctype_digit($inputValue)) {
                $inputValue = (int)$inputValue;
                // Check for valid value
                if (($inputValue >= 0) && ($inputValue <= 1)) {
                    $validatedValue = $inputValue;
                }
            }
        }

        return $validatedValue;
    }

    /**
     * Validate Ad Blocker Detection price.
     *
     * @param array $input
     * @return boolean|integer
     */
    protected function validateAdBlockerDetectionPrice($input)
    {
        $validatedValue = false;

        // Check for existing input field
        if (isset($input['price'])) {
            $inputValue = trim(strip_tags(stripslashes($input['price'])));

            // Check for integer value
            if (ctype_digit($inputValue)) {
                $inputValue = (int)$inputValue;

                // Get publisher max product price from API
                $apiCredentials = get_option('satoshipay_api');
                if ($this->validCredentials($apiCredentials, true)) {
                    try {
                        $apiClient = new ApiClient($apiCredentials);
                        $maxPrice = (int)$apiClient->getPublisherMaxProductPrice($apiCredentials['auth_key']) / 10000000;
                    } catch (Exception $e) {
                        $this->apiError($e->getMessage());
                    }
                }

                // Check for valid value
                if (($inputValue > 0) && ($inputValue <= $maxPrice)) {
                    $validatedValue = $inputValue;
                }
            }
        }

        return $validatedValue;
    }

    /**
     * Sanitize pricing settings.
     *
     * @param array $pricing
     * @return array
     */
    protected function sanitizePricing($pricing)
    {
        // Set disabled pricing without price values as default pricing
        $result = array(
            'enabled' => false,
            'price' => 0,
            'satoshi' => 0
        );

        // Return default pricing for missing 'enabled' field
        if (!isset($pricing['enabled'])) {
            return $result;
        }

        // Return default pricing for disabled pricing
        if ((bool)$pricing['enabled'] === false) {
            return $result;
        }

        // Return default pricing for missing 'satoshi' field
        if (!isset($pricing['satoshi'])) {
            return $result;
        }

        $price = trim(strip_tags(stripslashes($pricing['satoshi'])));

        // Return default pricing for non-integer price
        if (!ctype_digit($price)) {
            return $result;
        }

        $price = (int)$price;

        // Get publisher max product price from API
        $apiCredentials = get_option('satoshipay_api');
        if ($this->validCredentials($apiCredentials, true)) {
            try {
                $apiClient = new ApiClient($apiCredentials);
                $maxPrice = (int)$apiClient->getPublisherMaxProductPrice($apiCredentials['auth_key']) / 10000000;
            } catch (Exception $e) {
                $this->apiError($e->getMessage());
            }
        }

        // Return default pricing for invalid price (negative, zero, greater than max)
        if (($price <= 0) || ($price > $maxPrice)) {
            return $result;
        }

        // Define 'enabled' && 'satoshi' with valid values
        $result['enabled'] = true;
        $result['satoshi'] = $price;

        return $result;
    }

    /**
     * @param array $plugins Array of registered TinyMCE Plugins
     *
     * @return array Modified array of registered TinyMCE Plugins
     */
    public function addTinyMcePlugin($plugins)
    {
        $plugins['satoshipay'] = plugins_url('assets/js/tinymce_satoshipay.js', SATOSHIPAY_PLUGIN_ROOT_FILE);
        return $plugins;
    }

    /**
     * Adds TinyMCE button.
     *
     * @param array $buttons Array of registered buttons.
     *
     * @return array Array of buttons.
     */
    public function addTinyMceButtons($buttons)
    {
        array_push($buttons, 'satoshipay_start');

        return $buttons;
    }

    /**
     * Adds TinyMCE editor window CSS.
     *
     * @return string Comma-separated list of CSS paths.
     */
    public function addTinyMceCss($styles)
    {
        if (!empty($styles)) {
            $styles .= ',';
        }
        $styles .= plugins_url('assets/css/style_tinymce.css', SATOSHIPAY_PLUGIN_ROOT_FILE);

        return $styles;
    }

    /**
     * Set up TinyMCE plugin.
     */
    protected function setUpTinyMcePlugin()
    {
        if (!current_user_can('edit_posts') &&
            !current_user_can('edit_pages') &&
            !get_user_option('rich_editing')) {
           return;
        }

        add_filter('mce_external_plugins', array($this, 'addTinyMcePlugin'));
        add_filter('mce_buttons', array($this, 'addTinyMceButtons'));
        add_filter('mce_css', array($this, 'addTinyMceCss'));
    }

    /**
     * Checks if api settings are existant
     *
     * @return void
     */
    protected function checkApiData()
    {
        // check if the API credentials are valid
        if(!$this->validCredentials()){

            // invalid credentials - create an error
            $error = new WP_Error('api_credentials_invalid', '<strong>SatoshiPay Warning:</strong> No API credentials set. To use SatoshiPay, API Key and Secret need to be supplied in the SatoshiPay Settings.');

            // get a name for the transient
            $transientName = $this->getTransientName('api_credentials_invalid');

            // set the transient
            set_transient($transientName, $error, 10);
        }
    }

    /**
     * Checks a posts metadata for missing information (i.e. price/checkbox)
     *
     * @return void
     */
    protected function checkSatoshipayMetadata($post)
    {
        // get the _satoshipay_pricing metadata
        $postSatoshipayMetadata = get_post_meta($post->ID,'_satoshipay_pricing');
        if(!$postSatoshipayMetadata){
          return;
        }

        if(strstr($post->post_content,'<!--satoshipay:start-->')){
            // we have a start tag: the post should have
            // the checkbox "Paid content" and a price in metadata

            // check if pricing / checkbox are missing
            if(!(
                $postSatoshipayMetadata[0]['enabled'] &&
                $postSatoshipayMetadata[0]['satoshi']>0
            )){
                // either checkbox or price are missing

                // we have to set an error
                $error = new WP_Error('satoshipay_check_or_price_missing', '<strong>SatoshiPay Warning:</strong> Start Tag will be ignored, because no price was set. When using the SatoshiPay Start Tag, the "Paid ' . ucfirst(get_post_type($post->ID)) . '" checkbox on the right needs to be activated and a price must be set.');

                // get transient name
                $transientName = $this->getTransientName('save_post_missinginfo_error',$post->ID);

                // we set a transient
                set_transient($transientName, $error, 10);
            }

            // get content after start tag
            $temp = explode('<!--satoshipay:start-->',$post->post_content,2);
            $satoshipayContent = $temp[1];
            unset($temp);

            // check if there are satoshipay-tags in satoshipay content
            if(strstr($satoshipayContent,'<!--satoshipay:')){
                // we have to set an error
                $error = new WP_Error('satoshipay_multiple_tags_in_content', '<strong>SatoshiPay Warning:</strong> Paid items below Start Tag will not be displayed. When using the SatoshiPay Start Tag, paid audios, downloads, images or videos must be placed above the Start Tag.');

                // get transient name
                $transientName = $this->getTransientName('save_post_multiple_tags',$post->ID);

                // we set a transient
                set_transient($transientName, $error, 10);
            }
        } else if ($postSatoshipayMetadata[0]['enabled'] && $postSatoshipayMetadata[0]['satoshi'] > 0) {
            // the whole post is paid content, check if there are satoshipay tags in it
            if (strstr($post->post_content, '<!--satoshipay:')) {
                // we have to set an error
                $error = new WP_Error('satoshipay_multiple_tags_in_content', '<strong>SatoshiPay Warning:</strong> Paid items will not be displayed. When activating the "Paid ' . ucfirst(get_post_type($post->ID)) . '" checkbox on the right and setting a price, all media has to be included as regular content.');

                // get transient name
                $transientName = $this->getTransientName('save_post_multiple_tags',$post->ID);

                // we set a transient
                set_transient($transientName, $error, 10);
            }
        }
    }

    /**
     * Returns the transient name for current user and depending on prefix and post_id
     * will return a string like "satoshipay_prefix_{123}_{1}"
     *
     * @param string $prefix
     * @param int $postId
     *
     * @return string $transientName
     */
    protected function getTransientName($prefix,$postId=0){

        // initialize $transientName
        $transientName = 'satoshipay_';

        // add prefix
        $transientName .= $prefix;

        if($postId){
            // add $postId if given
            $transientName .= '_{'.$postId.'}';
        }

        // add current_user_id
        $transientName .= '_{'.get_current_user_id().'}';

        return $transientName;
    }

    protected function addAdminMenu()
    {
        $page_title = 'SatoshiPay Settings';
        $menu_title = 'SatoshiPay';
        $capability = 'manage_options';
        $menu_slug = 'satoshipay_settings_page';
        $function = array($this, 'renderAdminPage');
        $icon_url = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PHN2ZyAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIiAgIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyIgICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL3NvZGlwb2RpLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiICAgeG1sbnM6aW5rc2NhcGU9Imh0dHA6Ly93d3cuaW5rc2NhcGUub3JnL25hbWVzcGFjZXMvaW5rc2NhcGUiICAgaWQ9InN2ZzMwOTEiICAgcGFnZUFsaWdubWVudD0ibm9uZSIgICBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCA1MDAgNTAwIiAgIHhtbDpzcGFjZT0icHJlc2VydmUiICAgaGVpZ2h0PSI1MDBweCIgICB2aWV3Qm94PSIwIDAgNTAwIDUwMCIgICB3aWR0aD0iNTAwcHgiICAgdmVyc2lvbj0iMS4xIiAgIHk9IjBweCIgICB4PSIwcHgiICAgY2xhc3M9IiIgICBpbmtzY2FwZTp2ZXJzaW9uPSIwLjQ4LjQgcjk5MzkiICAgc29kaXBvZGk6ZG9jbmFtZT0ic2F0b3NoaXBheS1sb2dvLXdoaXRlLnN2ZyI+PGRlZnMgICAgIGlkPSJkZWZzMTAiIC8+PHNvZGlwb2RpOm5hbWVkdmlldyAgICAgcGFnZWNvbG9yPSIjZmZmZmZmIiAgICAgYm9yZGVyY29sb3I9IiM2NjY2NjYiICAgICBib3JkZXJvcGFjaXR5PSIxIiAgICAgb2JqZWN0dG9sZXJhbmNlPSIxMCIgICAgIGdyaWR0b2xlcmFuY2U9IjEwIiAgICAgZ3VpZGV0b2xlcmFuY2U9IjEwIiAgICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAiICAgICBpbmtzY2FwZTpwYWdlc2hhZG93PSIyIiAgICAgaW5rc2NhcGU6d2luZG93LXdpZHRoPSIxNzE2IiAgICAgaW5rc2NhcGU6d2luZG93LWhlaWdodD0iOTk3IiAgICAgaWQ9Im5hbWVkdmlldzgiICAgICBzaG93Z3JpZD0iZmFsc2UiICAgICBpbmtzY2FwZTp6b29tPSIwLjk0NCIgICAgIGlua3NjYXBlOmN4PSIyMjUuOTIwNjgiICAgICBpbmtzY2FwZTpjeT0iMjMyLjA2MzY4IiAgICAgaW5rc2NhcGU6d2luZG93LXg9IjYzIiAgICAgaW5rc2NhcGU6d2luZG93LXk9IjQ0MSIgICAgIGlua3NjYXBlOndpbmRvdy1tYXhpbWl6ZWQ9IjAiICAgICBpbmtzY2FwZTpjdXJyZW50LWxheWVyPSJzdmczMDkxIiAvPjxtZXRhZGF0YSAgICAgaWQ9Im1ldGFkYXRhMzEwNyI+PHJkZjpSREY+PGNjOldvcmsgICAgICAgICByZGY6YWJvdXQ9IiI+PGRjOmZvcm1hdD5pbWFnZS9zdmcreG1sPC9kYzpmb3JtYXQ+PGRjOnR5cGUgICAgICAgICAgIHJkZjpyZXNvdXJjZT0iaHR0cDovL3B1cmwub3JnL2RjL2RjbWl0eXBlL1N0aWxsSW1hZ2UiIC8+PGRjOnRpdGxlIC8+PC9jYzpXb3JrPjwvcmRmOlJERj48L21ldGFkYXRhPjxnICAgICBpZD0iZzI5OTAiICAgICB0cmFuc2Zvcm09Im1hdHJpeCgwLjg3OTk5NTU3LDAsMCwwLjg4LDI5Ljk5OTk5OCwzMCkiICAgICBzdHlsZT0iZmlsbDojZmZmZmZmIj48cGF0aCAgICAgICBpZD0icGF0aDM5MTAiICAgICAgIGQ9Ik0gMjUwLjAwMDAxLDAgQyAxMTUuOTU3NDUsMCA2LjUzMzE2ODgsMTA1LjIzNzUgMi4xMjVlLTYsMjM3LjUgSCAxMTIuODc2MSBjIDYuMzQ1NDMsLTY5Ljk1IDY1LjQ1NjgyLC0xMjUgMTM3LjEyMTQxLC0xMjUgNzEuNjYzMzMsMCAxMzAuNzc1OTcsNTUuMDQ1IDEzNy4xMjE0LDEyNSBoIDExMi44NzYxIEMgNDkzLjQ2MTg1LDEwNS4yMzc1IDM4NC4wMzc1NiwwIDI0OS45OTUsMCB6IiAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIiAgICAgICBzdHlsZT0iZmlsbDojZmZmZmZmIiAvPjxwYXRoICAgICAgIGlkPSJwYXRoMzkwOCIgICAgICAgZD0ibSA1MDAuMDAwMDIsMjYyLjUgLTExMi44NzYxLDAgYyAtNi4zNDU0Myw2OS45NSAtNjUuNDU2ODIsMTI1IC0xMzcuMTIxNDEsMTI1IC03MS42NjQ1OCwwIC0xMzAuNzc1OTcsLTU1LjA1IC0xMzcuMTIxNCwtMTI1IEggMC4wMDI1MDUyNSBDIDYuNTMwOTE2LDM5NC43NjI1IDExNS45NTk5Niw1MDAgMjUwLjAwMjUxLDUwMCAzODQuMDQ1MDcsNTAwIDQ5My40NjkzNiwzOTQuNzU3NSA1MDAuMDAyNTIsMjYyLjUgeiIgICAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIgICAgICAgc3R5bGU9ImZpbGw6I2ZmZmZmZiIgLz48cGF0aCAgICAgICBpZD0icGF0aDMwOTciICAgICAgIGQ9Im0gMjUwLjAwMDAxLDEzNy41IGMgLTYyLjM1Nzk1LDAgLTExMi42NDA4LDUwLjIyIC0xMTIuNjQwOCwxMTIuNSAwLDYyLjI4IDUwLjI4Mjg1LDExMi41IDExMi42NDA4LDExMi41IDYyLjM1Nzk1LDAgMTEyLjY0MDgxLC01MC4yMiAxMTIuNjQwODEsLTExMi41IDAsLTYyLjI4IC01MC4yODI4NiwtMTEyLjUgLTExMi42NDA4MSwtMTEyLjUgeiIgICAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIgICAgICAgc3R5bGU9ImZpbGw6I2ZmZmZmZiIgLz48L2c+PC9zdmc+';

        add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url );
    }

    public function renderAdminPage()
    {
        $api = $this->handleApiSection();

        require_once __DIR__ . '/../../views/admin/options/page.phtml';
    }

    /**
    * handle api updates, and return the current api values
    * @return Array API config including key and secret
    */
    public function handleApiSection()
    {
        // Validation & Update if valid
        if (isset($_POST['submit'])) {
            $values = array(
                'auth_key' => $_POST['satoshipay_api']['auth_key'],
                'auth_secret' => $_POST['satoshipay_api']['auth_secret']
            );
            if($this->validateApiSection($values) === true){
                update_option('satoshipay_api', $values);
                $this->addSatoshiPaySuccess('API credentials were successfully updated');
                return $values;
            }
        }

        return get_option('satoshipay_api');
    }

    /**
     * Validate API Section and add errors if invalid
     */
    public function validateApiSection($input)
    {
        $output = array();

        if (isset($input['auth_key']) && !empty($input['auth_key'])) {
            $output['auth_key'] = strip_tags(stripslashes($input['auth_key']));
        } else {
            $this->addSatoshiPayError('Please enter an API Key.');
            return false;
        }

        if (isset($input['auth_secret']) && !empty($input['auth_secret'])) {
            $output['auth_secret'] = strip_tags(stripslashes($input['auth_secret']));
        } else {
            $this->addSatoshiPayError('Please enter an API Secret.');
            return false;
        }

        if (false == $this->validCredentials($output)) {
            $this->addSatoshiPayError('The new API key/secret credentials are invalid and were not saved.');
            return false;
        }

        return true;
    }


	/**
	 * Ajax Callback to get number of posts
	 */
	public function onAjaxMigrationCountPages()
	{
		$pagesize = (int)$_POST['pagesize'] ?: 50;

		$pagesize = min($pagesize, 5000);

		return wp_send_json_success(
			array(
				'pagesize'  => $pagesize,
				'pages'     => SatoshiPayMigrator::getInstance()->getPosts(0, $pagesize,true),
				'posts'     => SatoshiPayMigrator::getInstance()->getPosts(0, 1,true),
			)
		);
	}


	/**
	 * Ajax Callback to migrate posts
	 */
	public function onAjaxMigrationProcessPosts()
	{
		$pagesize = (int)$_POST['pagesize'] ?: 50;
		$page = (int)$_POST['page'] - 1;

		$pagesize = min($pagesize, 5000);

		$posts = SatoshiPayMigrator::getInstance()->getPosts($page, $pagesize);

		foreach ($posts as $post) {
			SatoshiPayMigrator::getInstance()->migratePostById($post->ID, true, true);
		}

		return wp_send_json_success();
	}

}
