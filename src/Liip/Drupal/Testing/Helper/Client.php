<?php

namespace Liip\Drupal\Testing\Helper;

define('DRUPAL_ROOT', '/home/dev/drupal-test/src');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
require 'NetHelper.php';
$_SERVER['REQUEST_METHOD'] = 'get';
$_SERVER['REMOTE_ADDR'] = NetHelper::getServerAddress();
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

class Client
{
    const USER_AGENT = 'liip-test';

    const MAX_REDIRECTS = 5;

    protected $curlHandle;

    protected $additionalCurlOptions = array();

    protected $session_id;

    protected $headers;

    protected $redirect_count;

    protected $content;
    
    public function __construct()
    {
    }

    /**
     * Retrieves a Drupal path or an absolute path.
     *
     * @param $path
     *   Drupal path or URL to load into internal browser
     * @param $options
     *   Options to be forwarded to url().
     * @param $headers
     *   An array containing additional HTTP request headers, each formatted as
     *   "name: value".
     * @return
     *   The retrieved HTML string, also available as $this->drupalGetContent()
     */
    public function get($path, array $options = array(), array $headers = array())
    {
      $options['absolute'] = TRUE;

      // We re-using a CURL connection here. If that connection still has certain
      // options set, it might change the GET into a POST. Make sure we clear out
      // previous options.
      $out = $this->curlExec(array(CURLOPT_HTTPGET => TRUE, CURLOPT_URL => url($path, $options), CURLOPT_NOBODY => FALSE, CURLOPT_HTTPHEADER => $headers));

      // Replace original page output with new output from redirected page(s).
      if ($new = $this->checkForMetaRefresh()) {
        $out = $new;
      }
//      $this->verbose('GET request to: ' . $path .
//                     '<hr />Ending URL: ' . $this->getUrl() .
//                     '<hr />' . $out);
      return $out;
    }

    protected function curlInit()
    {
        if (!isset($this->curlHandle)) {

            $this->curlHandle = curl_init();
            $curl_options = array(
                CURLOPT_COOKIEJAR => $this->cookieFile,
//          CURLOPT_URL => $base_url,
                CURLOPT_FOLLOWLOCATION => FALSE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_SSL_VERIFYPEER => FALSE, // Required to make the tests run on https.
                CURLOPT_SSL_VERIFYHOST => FALSE, // Required to make the tests run on https.
//                CURLOPT_HEADERFUNCTION => array(&$this, 'curlHeaderCallback'),
                CURLOPT_USERAGENT => self::USER_AGENT,
            );

//            if (isset($this->httpauth_credentials)) {
//                $curl_options[CURLOPT_HTTPAUTH] = $this->httpauth_method;
//                $curl_options[CURLOPT_USERPWD] = $this->httpauth_credentials;
//            }

            curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);
        }

        curl_setopt($this->curlHandle, CURLOPT_USERAGENT, self::USER_AGENT);
    }

    protected function curlExec($curl_options, $redirect = false)
    {
      $this->curlInit();

      // cURL incorrectly handles URLs with a fragment by including the
      // fragment in the request to the server, causing some web servers
      // to reject the request citing "400 - Bad Request". To prevent
      // this, we strip the fragment from the request.
      // TODO: Remove this for Drupal 8, since fixed in curl 7.20.0.
      if (!empty($curl_options[CURLOPT_URL]) && strpos($curl_options[CURLOPT_URL], '#')) {
        $original_url = $curl_options[CURLOPT_URL];
        $curl_options[CURLOPT_URL] = strtok($curl_options[CURLOPT_URL], '#');
      }

      $url = empty($curl_options[CURLOPT_URL]) ? curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL) : $curl_options[CURLOPT_URL];

      if (!empty($curl_options[CURLOPT_POST])) {
        // This is a fix for the Curl library to prevent Expect: 100-continue
        // headers in POST requests, that may cause unexpected HTTP response
        // codes from some webservers (like lighttpd that returns a 417 error
        // code). It is done by setting an empty "Expect" header field that is
        // not overwritten by Curl.
        $curl_options[CURLOPT_HTTPHEADER][] = 'Expect:';
      }
      curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);

      if (!$redirect) {
        // Reset headers, the session ID and the redirect counter.
        $this->session_id = NULL;
        $this->headers = array();
        $this->redirect_count = 0;
      }

      $this->content = curl_exec($this->curlHandle);
      $this->status = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

      // cURL incorrectly handles URLs with fragments, so instead of
      // letting cURL handle redirects we take of them ourselves to
      // to prevent fragments being sent to the web server as part
      // of the request.
      // TODO: Remove this for Drupal 8, since fixed in curl 7.20.0.
      if (in_array($this->status, array(300, 301, 302, 303, 305, 307)) && $this->redirect_count < self::MAX_REDIRECTS) {
        if ($this->drupalGetHeader('location')) {
          $this->redirect_count++;
          $curl_options = array();
          $curl_options[CURLOPT_URL] = $this->drupalGetHeader('location');
          $curl_options[CURLOPT_HTTPGET] = TRUE;
          return $this->curlExec($curl_options, TRUE);
        }
      }

      isset($original_url) ? $original_url : curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL);
      $message_vars = array(
        '!method' => !empty($curl_options[CURLOPT_NOBODY]) ? 'HEAD' : (empty($curl_options[CURLOPT_POSTFIELDS]) ? 'GET' : 'POST'),
        '@url' => isset($original_url) ? $original_url : $url,
        '@status' => $this->status,
        '!length' => format_size(strlen($this->content))
      );
      //$message = t('!method @url returned @status (!length).', $message_vars);
      //$this->assertTrue($this->content !== false, $message, t('Browser'));
      return $this->content;
    }

    /**
     * Gets the HTTP response headers of the requested page. Normally we are only
     * interested in the headers returned by the last request. However, if a page
     * is redirected or HTTP authentication is in use, multiple requests will be
     * required to retrieve the page. Headers from all requests may be requested
     * by passing TRUE to this function.
     *
     * @param $all_requests
     *   Boolean value specifying whether to return headers from all requests
     *   instead of just the last request. Defaults to FALSE.
     * @return
     *   A name/value array if headers from only the last request are requested.
     *   If headers from all requests are requested, an array of name/value
     *   arrays, one for each request.
     *
     *   The pseudonym ":status" is used for the HTTP status line.
     *
     *   Values for duplicate headers are stored as a single comma-separated list.
     */
    protected function drupalGetHeaders($all_requests = FALSE) {
      $request = 0;
      $headers = array($request => array());
      foreach ($this->headers as $header) {
        $header = trim($header);
        if ($header === '') {
          $request++;
        }
        else {
          if (strpos($header, 'HTTP/') === 0) {
            $name = ':status';
            $value = $header;
          }
          else {
            list($name, $value) = explode(':', $header, 2);
            $name = strtolower($name);
          }
          if (isset($headers[$request][$name])) {
            $headers[$request][$name] .= ',' . trim($value);
          }
          else {
            $headers[$request][$name] = trim($value);
          }
        }
      }
      if (!$all_requests) {
        $headers = array_pop($headers);
      }
      return $headers;
    }

    /**
     * Gets the value of an HTTP response header. If multiple requests were
     * required to retrieve the page, only the headers from the last request will
     * be checked by default. However, if TRUE is passed as the second argument,
     * all requests will be processed from last to first until the header is
     * found.
     *
     * @param $name
     *   The name of the header to retrieve. Names are case-insensitive (see RFC
     *   2616 section 4.2).
     * @param $all_requests
     *   Boolean value specifying whether to check all requests if the header is
     *   not found in the last request. Defaults to FALSE.
     * @return
     *   The HTTP header value or FALSE if not found.
     */
    protected function drupalGetHeader($name, $all_requests = FALSE) {
      $name = strtolower($name);
      $header = FALSE;
      if ($all_requests) {
        foreach (array_reverse($this->drupalGetHeaders(TRUE)) as $headers) {
          if (isset($headers[$name])) {
            $header = $headers[$name];
            break;
          }
        }
      }
      else {
        $headers = $this->drupalGetHeaders();
        if (isset($headers[$name])) {
          $header = $headers[$name];
        }
      }
      return $header;
    }

    protected function curlHeaderCallback($curlHandler, $header)
    {
        // Header fields can be extended over multiple lines by preceding each
        // extra line with at least one SP or HT. They should be joined on receive.
        // Details are in RFC2616 section 4.
        if ($header[0] == ' ' || $header[0] == "\t") {
            // Normalize whitespace between chucks.
            $this->headers[] = array_pop($this->headers) . ' ' . trim($header);
        }
        else {
            $this->headers[] = $header;
        }

        // Errors are being sent via X-Drupal-Assertion-* headers,
        // generated by _drupal_log_error() in the exact form required
        // by DrupalWebTestCase::error().
        if (preg_match('/^X-Drupal-Assertion-[0-9]+: (.*)$/', $header, $matches)) {
            // Call DrupalWebTestCase::error() with the parameters from the header.
            call_user_func_array(array(&$this, 'error'), unserialize(urldecode($matches[1])));
        }

        // Save cookies.
        if (preg_match('/^Set-Cookie: ([^=]+)=(.+)/', $header, $matches)) {
            $name = $matches[1];
            $parts = array_map('trim', explode(';', $matches[2]));
            $value = array_shift($parts);
            $this->cookies[$name] = array('value' => $value, 'secure' => in_array('secure', $parts));
            if ($name == $this->session_name) {
                if ($value != 'deleted') {
                    $this->session_id = $value;
                }
                else {
                    $this->session_id = NULL;
                }
            }
        }
    }

    /**
     * Check for meta refresh tag and if found call drupalGet() recursively. This
     * function looks for the http-equiv attribute to be set to "Refresh"
     * and is case-sensitive.
     *
     * @return
     *   Either the new page content or FALSE.
     */
    protected function checkForMetaRefresh() {
//      if (strpos($this->content, '<meta ')) {
//        $refresh = $this->xpath('//meta[@http-equiv="Refresh"]');
//        if (!empty($refresh)) {
//          // Parse the content attribute of the meta tag for the format:
//          // "[delay]: URL=[page_to_redirect_to]".
//          if (preg_match('/\d+;\s*URL=(?P<url>.*)/i', $refresh[0]['content'], $match)) {
//            return $this->get($this->getAbsoluteUrl(decode_entities($match['url'])));
//          }
//        }
//      }
      return FALSE;
    }

    /**
     * Takes a path and returns an absolute path.
     *
     * @param $path
     *   A path from the internal browser content.
     * @return
     *   The $path with $base_url prepended, if necessary.
     */
    protected function getAbsoluteUrl($path) {
      global $base_url, $base_path;

      $parts = parse_url($path);
      if (empty($parts['host'])) {
        // Ensure that we have a string (and no xpath object).
        $path = (string) $path;
        // Strip $base_path, if existent.
        $length = strlen($base_path);
        if (substr($path, 0, $length) === $base_path) {
          $path = substr($path, $length);
        }
        // Ensure that we have an absolute path.
        if ($path[0] !== '/') {
          $path = '/' . $path;
        }
        // Finally, prepend the $base_url.
        $path = $base_url . $path;
      }
      return $path;
    }
}

