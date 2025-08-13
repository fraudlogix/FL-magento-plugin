<?php

namespace FraudLogix\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use FraudLogix\Core\Logger\Logger as FraudLogger;
use FraudLogix\Core\Model\Config;

class ApiHelper extends AbstractHelper
{

    /**
     * @var FraudLogger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        FraudLogger $logger,
        Config $config

    ) {
        $this->logger = $logger;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Get the API endpoint URL.
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        return 'https://iplist.fraudlogix.com/v5';
    }

    /**
     * Get the correct client IP address.
     *
     * @return string
     */
    public function getClientIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // HTTP_X_FORWARDED_FOR can contain multiple IPs, take the first one 
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return $ip;
    }

    /**
     * Get the API key from configuration.
     *
     * @return string|null
     */
    public function getApiKey()
    {
        return $this->scopeConfig->getValue('fraudlogix/general/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function fetchData($endpoint, $ip, $event = 'none')
    {
        if (!$this->config->isEnabled()) {
            $this->logger->info('FraudLogix API is disabled. Skipping data fetch for event: ' . $event);
            return [];
        }
        if ($this->config->isDevModeEnabled()) {
            $devIp = $this->config->getDevIp();
            if ($devIp && $devIp !== '') {
                $ip = $devIp;
            }
        }
        $url = $this->getApiEndpoint() . $endpoint;
        $apiKey = $this->getApiKey();

        if (!$apiKey) {
            throw new \Exception('API key is not set in configuration.');
        }

        $params['ip'] = $ip;

        $headers = [
            'x-api-key: ' . $apiKey
        ];
        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        try {
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception('Curl error: ' . curl_error($ch));
            }
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
                throw new \Exception('API request failed with status code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE));
            }
            $this->logger->info('Data fetched successfully from FraudLogix API for IP: ' . $ip . ' - Response: ' . $response . ' - Event: ' . $event);
        }
        catch (\Exception $e) {
            $this->logger->error('Error fetching data from FraudLogix API: ' . $e->getMessage());
            return [];
        }
        curl_close($ch);

        return json_decode($response, true);
    }
}