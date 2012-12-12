<?php
namespace ZendOpenId\Discovery;

use Zend\Http\Client as HttpClient;

abstract class AbstractDiscovery
{
    
    /**
     *
     * @var \Zend\Http\Client
     */
    protected $httpClient;

    public function setHttpClient(HttpClient $httpClient) {
        $this->httpClient = $httpClient;
    }

    public function getHttpClient() {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient();
        }
        return $this->httpClient;
    }
    
    abstract public function discover($claimed_identifier);
}
