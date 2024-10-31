<?php
/**
 * This file is part of the SatoshiPay WordPress plugin.
 *
 * (c) SatoshiPay <hello@satoshipay.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SatoshiPay\Api;

require_once __DIR__ . '/../SatoshiPayException.php';

class Client
{
    /**
     * @var string
     */
    protected $productServiceUrl = '';

    /**
     * @var string
     */
    protected $publisherServiceUrl = '';

    /**
     * @var string
     */
    protected $authKey = '';

    /**
     * @var string
     */
    protected $authSecret = '';

    /**
     * @var string
     */
    protected $userAgent = '';

    /**
     * @var int
     */
    protected $defaultMaxProductPrice = 0;

    /**
     * Constructor.
     *
     * @param array $options $productServiceUrl
     * @param array $options $publisherServiceUrl
     * @param string $authKey
     * @param string $authSecret
     */
    public function __construct(array $config = array())
    {
        $this->productServiceUrl = SATOSHIPAY_PRODUCT_SERVICE_URL;
        $this->publisherServiceUrl = SATOSHIPAY_PUBLISHER_SERVICE_URL;
        $this->defaultMaxProductPrice = SATOSHIPAY_DEFAULT_MAX_PRODUCT_PRICE;

        if (isset($config['auth_key']) && !empty($config['auth_key'])) {
            $this->authKey = $config['auth_key'];
        }

        if (isset($config['auth_secret']) && !empty($config['auth_secret'])) {
            $this->authSecret = $config['auth_secret'];
        }

        $this->userAgent = 'WordPress/' . get_bloginfo('version') . ' SatoshiPay/' . SATOSHIPAY_VERSION . '; ' . get_bloginfo('url');
    }

    /**
     * Creates new SatoshiPay good.
     *
     * @param array $goodData
     * @return string
     */
    public function createNewGood(array $goodData)
    {
        // set asset to XLM (Lumens)
        $goodData['asset'] = 'XLM';

        // transform price from lumen to stroops
        $goodData['price'] *= 10000000;

        $url = rtrim($this->productServiceUrl, '/') . '/goods';
        $body = json_encode($goodData);
        $responseData = json_decode($this->post($url, $body), true);

        return isset($responseData['id']) ? $responseData['id'] : 0;
    }

    /**
     * Updates existing SatoshiPay good.
     *
     * @param int $goodId
     * @param array $goodData
     * @return string
     */
    public function updateGood($goodId, array $goodData)
    {
        if (empty($goodId)) {
            return;
        }
        // set asset to XLM (Lumens)
        $goodData['asset'] = 'XLM';

        // transform price from lumen to stroops
        $goodData['price'] *= 10000000;

        $url = rtrim($this->productServiceUrl, '/') . '/goods/' . (string)$goodId;
        $body = json_encode($goodData);
        $responseData = json_decode($this->put($url, $body), true);

        return isset($responseData['id']) ? $responseData['id'] : 0;
    }

    /**
     * Deletes existing SatoshiPay good.
     *
     * @param int $goodId
     * @return string
     */
    public function deleteGood($goodId)
    {
        if (empty($goodId)) {
            return;
        }
        $url = rtrim($this->productServiceUrl, '/') . '/goods/' . (string)$goodId;
        $responseData = json_decode($this->delete($url), true);
        return isset($responseData['id']) ? $responseData['id'] : 0;
    }

    /**
     * Checks if the auth credentials provided through satoshipay-admin-menu-page/options-page are valid.
     *
     * @param bool $cache
     * @return bool
     */
    public function testCredentials()
    {
        $url = rtrim($this->productServiceUrl, '/') . '/permissions';
        $args = array(
            'method' => 'GET'
        );

        try {
            $request = $this->request($url, $args);
        } catch (\Exception $e) {
            return false;
        }

        return (int)$request['response']['code'];
    }

    /**
     * Submits SatoshiPay goods (posts) as batch process.
     *
     * @param array $batchObjects
     * @return array
     */
    public function batch(array $batchObjects)
    {
        $url = rtrim($this->productServiceUrl, '/') . '/batch';
        $body = json_encode(array("requests" => $batchObjects));
        $responseData = json_decode($this->post($url, $body), true);
        return isset($responseData['responses']) ? $responseData['responses'] : array();
    }

    /**
     * Gets max product price for a publisher with $publisherId.
     *
     * @param string $publisherId
     * @return string
     */
    public function getPublisherMaxProductPrice($publisherId)
    {
        if (empty($publisherId)) {
            return;
        }
        $url = rtrim($this->publisherServiceUrl, '/') . '/' . (string)$publisherId . '/limits';
        $responseData = json_decode($this->get($url), true);
        return isset($responseData['maxProductPrice']) ? $responseData['maxProductPrice'] : $this->defaultMaxProductPrice;
    }

    /**
     * Returns an array with request headers.
     *
     * @return array
     */
    protected function getRequestHeaders()
    {
        return array(
            'Authorization' => 'Basic ' . base64_encode($this->authKey . ':' . $this->authSecret),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        );
    }

    /**
     * Send GET request to $url.
     *
     * @param string $url
     * @return string
     */
    protected function get($url)
    {
        $args = array(
            'method' => 'GET'
        );

        return $this->requestBody($url, $args);
    }

    /**
     * Send POST request to $url with $body.
     *
     * @param string $url
     * @param string $body
     * @return string
     */
    protected function post($url, $body)
    {
        $args = array(
            'method' => 'POST',
            'body' => $body,
        );

        return $this->requestBody($url, $args);
    }

    /**
     * Send PUT request to $url with $body.
     *
     * @param string $url
     * @param string $body
     * @return string
     */
    protected function put($url, $body)
    {
        $args = array(
            'method' => 'PUT',
            'body' => $body,
        );

        return $this->requestBody($url, $args);
    }

    /**
     * Send DELETE request to $url.
     *
     * @param string $url
     * @return string
     */
    protected function delete($url)
    {
        $args = array(
            'method' => 'DELETE'
        );

        return $this->requestBody($url, $args);
    }

    /**
     * Send HTTP request.
     *
     * @param string $url
     * @param string $args
     * @return array
     */
    protected function request($url, $args)
    {
        $args['headers'] = $this->getRequestHeaders();
        $args['user-agent'] = $this->userAgent;

        $result = wp_remote_request($url, $args);

        if (is_wp_error($result)) {
            $exceptionMessage = 'API request failed. We got the following error: "' . $result->get_error_message() . '"';

            throw new \SatoshiPay\Exception($exceptionMessage);
        }

        return $result;
    }

    /**
     * Send HTTP request, test for errors and return body.
     *
     * @param string $url
     * @param string $args
     * @return string
     */
    protected function requestBody($url, $args)
    {
        $request = $this->request($url, $args);
        $body = wp_remote_retrieve_body($request);

        // Throw exception on error
        if ($request['response']['code'] != 200) {
            $json = json_decode($body, true);
            if ($json) {
                $message = '';
                if (array_key_exists('name', $json)) {
                    $message .= $json['name'] . ' / ';
                }
                if ($json['message']) {
                    $message .= $json['message'];
                }
            } else {
                $message = $body;
            }
            $exceptionMessage = 'API request failed. We got the following error: "' . $message . '" (HTTP status code ' . $request['response']['code'] . ')';
            throw new \SatoshiPay\Exception($exceptionMessage);
        }

        return $body;
    }
}
