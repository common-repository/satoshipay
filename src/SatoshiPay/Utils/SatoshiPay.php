<?php

/**
 * Helper functions related to the SatoshiPay API,
 * like credentials validation, registering/deleting goods
 * @since 1.6.0
 */

namespace SatoshiPay\Utils\SatoshiPay;

require_once __DIR__ . '/../Api/Client.php';

use SatoshiPay\Api\Client as ApiClient;

/**
 * Checks if a set of credentials is valid
 * @param array $credentials
 * @param bool $cache
 * @return bool
 */
function validCredentials($credentials = null, $cache = false)
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
