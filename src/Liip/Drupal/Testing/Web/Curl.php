<?php

namespace Liip\Drupal\Testing\Web;

use Liip\Drupal\Testing\Debug\AbstractDebugable;
use Monolog\Logger;

class Curl extends AbstractDebugable
{
    const USER_AGENT = 'liip-testing';

    protected $curlHandle;

    protected $cookieFile = NULL;

    protected $additionalCurlOptions;

    protected $httpAuthMethod;

    protected $httpAuthCredentials;

    public function __construct($additionalCurlOptions = array(), $httpAuthMethod = null, $httpAuthCredentials = null)
    {
        $this->log('Curl::__construct()', Logger::DEBUG, array($additionalCurlOptions, $httpAuthMethod, $httpAuthMethod));

        $this->additionalCurlOptions = $additionalCurlOptions;
        $this->httpAuthMethod = $httpAuthMethod;
        $this->httpAuthCredentials = $httpAuthCredentials;
    }

    protected function init()
    {
        $this->log('Curl::init()', Logger::DEBUG);

        if (!isset($this->curlHandle)) {

            $this->curlHandle = curl_init();
            $curl_options = array(
                CURLOPT_COOKIEJAR => $this->cookieFile,
                CURLOPT_FOLLOWLOCATION => FALSE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_SSL_VERIFYPEER => FALSE, // Required to make the tests run on https.
                CURLOPT_SSL_VERIFYHOST => FALSE, // Required to make the tests run on https.
                CURLOPT_USERAGENT => self::USER_AGENT,
                CURLOPT_HEADER => true,
            );

            if (!is_null($this->httpAuthCredentials)) {
                $curl_options[CURLOPT_HTTPAUTH] = $this->httpAuthMethod;
                $curl_options[CURLOPT_USERPWD] = $this->httpAuthCredentials;
            }

            curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);
        }

        curl_setopt($this->curlHandle, CURLOPT_USERAGENT, self::USER_AGENT);
    }

    public function exec($curl_options)
    {
        $this->init();

        $this->log('Curl::exec()', Logger::DEBUG, $curl_options);
        //$this->logger->addDebug(sprintf('Client::curlExec([%s])', Debug::dumpArray($curl_options)));

        if (!empty($curl_options[CURLOPT_POST])) {
            // This is a fix for the Curl library to prevent Expect: 100-continue
            // headers in POST requests, that may cause unexpected HTTP response
            // codes from some webservers (like lighttpd that returns a 417 error
            // code). It is done by setting an empty "Expect" header field that is
            // not overwritten by Curl.
            $curl_options[CURLOPT_HTTPHEADER][] = 'Expect:';
        }

        $url = isset($curl_options[CURLOPT_URL])
            ? $curl_options[CURLOPT_URL]
            : curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL);

        curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);

        $content = curl_exec($this->curlHandle);

        $response = new Response();
        $response->setUrl($url);
        $response->setMethod(!empty($curl_options[CURLOPT_NOBODY]) ? 'HEAD' : (empty($curl_options[CURLOPT_POSTFIELDS]) ? 'GET' : 'POST'));
        $response->setStatus($content ? curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE) : 404);
        $response->setLength(strlen($content));
        $response->setContent($content);

        $this->log(sprintf('%s %s', $response->getMethod(), $response->getUrl()), Logger::INFO, array($response->getStatus()));

        return $response;
    }

}
