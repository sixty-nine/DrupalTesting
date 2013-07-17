<?php

namespace Liip\Drupal\Testing\Test;

use Goutte\Client;

use Symfony\Component\DomCrawler\Form,
    Symfony\Component\DomCrawler\Crawler;

abstract class WebTestCase extends DebuggableTestCase
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $guzzleClient = $this->client->getClient();
        $guzzleClient->setConfig(array('curl.CURLOPT_SSL_VERIFYHOST' => false));
        $guzzleClient->setConfig(array('curl.CURLOPT_SSL_VERIFYPEER' => false));
    }

    /**
     * This method is called before each test is ran
     *
     * NB: if you override setUp remember to call parent::setUp()
     */
    protected function setUp()
    {
        // clear Drupal's static cache before each test run
        drupal_static_reset();

        $cache_object = _cache_get_object('cache');
        if (is_a($cache_object, 'DrupalInMemoryCache')) {
            \DrupalInMemoryCache::enableTempStorage();
        }
        parent::setUp();
    }

    /**
     * Restore the original (bootstrapped) cache state after each test
     */
    protected function tearDown()
    {
        $cache_object = _cache_get_object('cache');
        if (is_a($cache_object, 'DrupalInMemoryCache')) {
            \DrupalInMemoryCache::disableTempStorage();
        }
        parent::tearDown();
    }

    /**
     * Get a crawler for the given URL.
     * @param string $url
     * @param string $method
     * @return \Symfony\Component\BrowserKit\Crawler
     */
    protected function getCrawler($url, $method = 'GET')
    {
        return $this->client->request($method, $url);
    }

    /**
     * Submit a form with the given values and assert the response status is 200
     * @param \Symfony\Component\DomCrawler\Form $form
     * @param array $values
     * @return void
     */
    protected function submitForm(Form $form, $values = array())
    {
        foreach ($values as $key => $val) {
            if (!$form->has($key)) {
                $this->fail(sprintf("The form does not have a field with name %s", $key));
            }
            $form[$key] = $val;
        }
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusEquals(200);
        return $crawler;
    }

    /**
     * Get the options of a <select> given its ID
     * @param \Symfony\Component\DomCrawler\Crawler $crawler A crawler for the page
     * @param string $selectId The ID of the select
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getSelectOptions(Crawler $crawler, $selectId)
    {
        return $crawler->filterXPath(sprintf('//select[@id="%s"]//option', $selectId));
    }

    // ----- DEBUG HELPER FUNCTIONS -------------------------------------------

    protected function dumpSelect(Crawler $crawler, $selectId)
    {
        $options = $this->getSelectOptions($crawler, $selectId);
        echo sprintf("\nDump select %s:\n", $selectId);
        foreach ($options as $option) {
            echo sprintf("%s --> %s\n", $option->getAttribute('value'), $option->nodeValue);
        }
        echo "\n";
    }

    protected function getCrawlerContent(Crawler $crawler)
    {
        $res = '';
        foreach ($crawler as $key => $element) {
            $res .= $element->ownerDocument->saveXml($element) . "\n";
        }
        return $res;
    }

    protected function dumpCrawler(Crawler $crawler)
    {
        echo "\nDump crawler:\n";
        echo $this->getCrawlerContent($crawler);
        echo "\n";
    }

    // ----- ASSERTIONS -------------------------------------------------------

    /**
     * Assert the status of the response to the request of the given URL.
     * @param string $url
     * @param int $expectedStatus
     * @param string $method
     * @return void
     */
    protected function assertResponseStatusEquals($expectedStatus)
    {
        $status = $this->client->getResponse()->getStatus();
        $uri = $this->client->getRequest()->getUri();

        $this->assertEquals(
            $expectedStatus,
            $status,
            sprintf("Requested '%s', expected response status %s, got %s", $uri, $expectedStatus, $status)
        );
    }

    /**
     * Assert that all the options specified as key => value are present in the <select> specified by its ID
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     * @param string $selectId The ID of the <select>
     * @param array $expectedOptionsKeysValues
     * @return void
     */
    protected function assertSelectHasOptions(Crawler $crawler, $selectId, $expectedOptionsKeysValues)
    {
        $options = $this->getSelectOptions($crawler, $selectId);
        $this->assertEquals(count($expectedOptionsKeysValues), count($options));

        $selectOptions = array();
        foreach ($options as $option) {
            $selectOptions[$option->getAttribute('value')] = $option->nodeValue;
        }

        foreach ($expectedOptionsKeysValues as $expectedKey => $expectedValue) {
            $this->assertTrue(isset($selectOptions[$expectedKey]), "The key $expectedKey is not set");
            $this->assertEquals(
                $expectedValue,
                $selectOptions[$expectedKey],
                sprintf("The value '%s' does not match '%s'", $selectOptions[$expectedKey], $expectedValue)
            );
        }
    }

    protected function assertSelectedOption(Crawler $crawler, $selectId, $expectedSelectedValue)
    {
        // Search for an option with the selected attribute
        $options = $this->getSelectOptions($crawler, $selectId);
        foreach ($options as $option) {
            if ($option->getAttribute('selected') === 'selected') {
                $this->assertEquals($expectedSelectedValue, $option->getAttribute('value'));
                return;
            }
        }

        // No option is selected so the selected one is the first one (if any)
        if (count($options)) {
            $this->assertEquals($expectedSelectedValue, $options->first()->attr('value'));
            return;
        }

        // There are no options in the combo
        $this->fail("No selected option was found for combo $selectId");
    }

    /**
     * Assert that the given combo does not contain an option with the given value
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     * @param string $selectId The ID of the combo
     * @param string $optionValueExpectedNotToBeThere The value of the option that should not be in the combo
     * @return void
     */
    protected function assertSelectDoesNotHaveOption(Crawler $crawler, $selectId, $optionValueExpectedNotToBeThere)
    {
        $options = $this->getSelectOptions($crawler, $selectId);
        foreach ($options as $option) {
            $this->assertNotEquals(
                $optionValueExpectedNotToBeThere,
                $option->getAttribute('value'),
                sprintf('Failed to assert that the combo %s does not contain an option with the value %s', $selectId, $optionValueExpectedNotToBeThere)
            );
        }
    }

    /**
     * Assert that the given combo contains an option with the given value
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     * @param string $selectId The ID of the combo
     * @param string $optionValueExpectedToBeThere The value of the option that should be in the combo
     * @return void
     */
    protected function assertSelectHasOption(Crawler $crawler, $selectId, $optionValueExpectedToBeThere)
    {
        $options = $this->getSelectOptions($crawler, $selectId);
        $found = false;
        foreach ($options as $option) {
            $found = $found || ($option->getAttribute('value') == $optionValueExpectedToBeThere);
        }
        $this->assertTrue($found, sprintf("The combo %s does not contain an option with value %s", $selectId, $optionValueExpectedToBeThere));
    }

    protected function assertContainsText(Crawler $crawler, $expectedText)
    {
        $this->assertTrue(false != preg_match('/' . preg_quote($expectedText) . '/', $crawler->text()));
    }

    protected function assertDoesNotContainText(Crawler $crawler, $expectedText)
    {
        $this->assertTrue(false == preg_match('/' . preg_quote($expectedText) . '/', $crawler->text()));
    }

}
