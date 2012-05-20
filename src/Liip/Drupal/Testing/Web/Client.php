<?php

namespace Liip\Drupal\Testing\Web;

use Liip\Drupal\Testing\Helper\NetHelper;

use Liip\Drupal\Testing\Debug\AbstractDebugable;
use Monolog\Logger;

class Client extends AbstractDebugable
{
    protected $curl;

    public function __construct(Curl $curl)
    {
        $this->log('Client::__construct()', Logger::DEBUG);
        $this->curl = $curl;
    }

    /**
     * Retrieves a Drupal path or an absolute path.
     *
     * @param $path URL to load into internal browser
     * @param array $headers An array containing additional HTTP request headers, each formatted as "name: value".
     * @return \Liip\Drupal\Testing\Web\Response The retrieved HTML string, also available as $this->drupalGetContent()
     */
    public function get($path, array $headers = array())
    {
        $this->log('Client::get()', Logger::DEBUG, array($path, $headers));

        $options['absolute'] = TRUE;

        $resp = $this->curl->exec(
            array(
                 CURLOPT_HTTPGET => TRUE,
                 CURLOPT_URL => $path,
                 CURLOPT_NOBODY => FALSE,
                 CURLOPT_HTTPHEADER => $headers
            )
        );

        return $resp;
    }

    public function post($path, array $post = array(), array $headers = array()) {

        $this->log('Client::post()', Logger::DEBUG, array($path, $post, $headers));
        
        foreach ($post as $key => $value) {
            // Encode according to application/x-www-form-urlencoded
            // Both names and values needs to be urlencoded, according to
            // http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
            $post[$key] = urlencode($key) . '=' . urlencode($value);
        }
        $post = implode('&', $post);

        $resp = $this->curl->exec(
            array(
                 CURLOPT_URL => $path,
                 CURLOPT_POST => TRUE,
                 CURLOPT_POSTFIELDS => $post,
                 CURLOPT_HTTPHEADER => $headers
            )
        );

        return $resp;
    }

}

