<?php

namespace PHPUnitWrapper\Test;

class DrupalTestCase extends \PHPUnit_Framework_TestCase {

    /**
     * Initializes the cURL connection.
     *
     * If the simpletest_httpauth_credentials variable is set, this function will
     * add HTTP authentication headers. This is necessary for testing sites that
     * are protected by login credentials from public access.
     * See the description of $curl_options for other options.
     */
    protected function curlInitialize() {
      global $base_url;

      if (!isset($this->curlHandle)) {
        $this->curlHandle = curl_init();
        $curl_options = array(
          CURLOPT_COOKIEJAR => NULL,
          CURLOPT_URL => $base_url,
          CURLOPT_FOLLOWLOCATION => FALSE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_SSL_VERIFYPEER => FALSE, // Required to make the tests run on https.
          CURLOPT_SSL_VERIFYHOST => FALSE, // Required to make the tests run on https.
          CURLOPT_HEADERFUNCTION => array(&$this, 'curlHeaderCallback'),
          CURLOPT_USERAGENT => $this->databasePrefix,
        );
        if (isset($this->httpauth_credentials)) {
          $curl_options[CURLOPT_HTTPAUTH] = $this->httpauth_method;
          $curl_options[CURLOPT_USERPWD] = $this->httpauth_credentials;
        }
        curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);

        // By default, the child session name should be the same as the parent.
        $this->session_name = session_name();
      }
      // We set the user agent header on each request so as to use the current
      // time and a new uniqid.
      if (preg_match('/simpletest\d+/', $this->databasePrefix, $matches)) {
        curl_setopt($this->curlHandle, CURLOPT_USERAGENT, drupal_generate_test_ua($matches[0]));
      }
    }

    /**
     * Initializes and executes a cURL request.
     *
     * @param $curl_options
     *   An associative array of cURL options to set, where the keys are constants
     *   defined by the cURL library. For a list of valid options, see
     *   http://www.php.net/manual/function.curl-setopt.php
     * @param $redirect
     *   FALSE if this is an initial request, TRUE if this request is the result
     *   of a redirect.
     *
     * @return
     *   The content returned from the call to curl_exec().
     *
     * @see curlInitialize()
     */
    protected function curlExec($curl_options, $redirect = FALSE) {
      $this->curlInitialize();

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

      $content = curl_exec($this->curlHandle);
      $status = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

      // cURL incorrectly handles URLs with fragments, so instead of
      // letting cURL handle redirects we take of them ourselves to
      // to prevent fragments being sent to the web server as part
      // of the request.
      // TODO: Remove this for Drupal 8, since fixed in curl 7.20.0.
      if (in_array($status, array(300, 301, 302, 303, 305, 307)) && $this->redirect_count < variable_get('simpletest_maximum_redirects', 5)) {
        if ($this->drupalGetHeader('location')) {
          $this->redirect_count++;
          $curl_options = array();
          $curl_options[CURLOPT_URL] = $this->drupalGetHeader('location');
          $curl_options[CURLOPT_HTTPGET] = TRUE;
          return $this->curlExec($curl_options, TRUE);
        }
      }

      $this->drupalSetContent($content, isset($original_url) ? $original_url : curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL));
      $message_vars = array(
        '!method' => !empty($curl_options[CURLOPT_NOBODY]) ? 'HEAD' : (empty($curl_options[CURLOPT_POSTFIELDS]) ? 'GET' : 'POST'),
        '@url' => isset($original_url) ? $original_url : $url,
        '@status' => $status,
        '!length' => format_size(strlen($this->drupalGetContent()))
      );
      $message = t('!method @url returned @status (!length).', $message_vars);
      $this->assertTrue($this->drupalGetContent() !== FALSE, $message, t('Browser'));
      return $this->drupalGetContent();
    }

    /**
     * Gets the current raw HTML of requested page.
     */
    protected function drupalGetContent() {
      return $this->content;
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
    protected function drupalGet($path, array $options = array(), array $headers = array()) {
      $options['absolute'] = TRUE;

      // We re-using a CURL connection here. If that connection still has certain
      // options set, it might change the GET into a POST. Make sure we clear out
      // previous options.
      $out = $this->curlExec(array(CURLOPT_HTTPGET => TRUE, CURLOPT_URL => url($path, $options), CURLOPT_NOBODY => FALSE, CURLOPT_HTTPHEADER => $headers));
      $this->refreshVariables(); // Ensure that any changes to variables in the other thread are picked up.

//      // Replace original page output with new output from redirected page(s).
//      if ($new = $this->checkForMetaRefresh()) {
//        $out = $new;
//      }
//      $this->verbose('GET request to: ' . $path .
//                     '<hr />Ending URL: ' . $this->getUrl() .
//                     '<hr />' . $out);
      return $out;
    }

    /**
     * Refresh the in-memory set of variables. Useful after a page request is made
     * that changes a variable in a different thread.
     *
     * In other words calling a settings page with $this->drupalPost() with a changed
     * value would update a variable to reflect that change, but in the thread that
     * made the call (thread running the test) the changed variable would not be
     * picked up.
     *
     * This method clears the variables cache and loads a fresh copy from the database
     * to ensure that the most up-to-date set of variables is loaded.
     */
    protected function refreshVariables() {
      global $conf;
      cache_clear_all('variables', 'cache_bootstrap');
      $conf = variable_initialize();
    }

}