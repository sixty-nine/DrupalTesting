<?php

namespace Liip\Drupal\Testing\Web;

use Liip\Drupal\Testing\Helper\SimpleXml;

class Crawler
{
    protected $response;

    protected $html;

    protected $elements;

    public function __construct(Response $response)
    {
        $this->response = $response;
        $this->html = $response->getContent();
    }

    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Perform an xpath search on the contents of the internal browser. The search
     * is relative to the root element (HTML tag normally) of the page.
     *
     * @param $xpath
     *   The xpath string to use in the search.
     * @return
     *   The return value of the xpath search. For details on the xpath string
     *   format and return values see the SimpleXML documentation,
     *   http://us.php.net/manual/function.simplexml-element-xpath.php.
     */
    public function xpath($xpath, array $arguments = array())
    {
        if ($this->parse()) {
            $xpath = $this->buildXPathQuery($xpath, $arguments);
            $result = $this->elements->xpath($xpath);
            // Some combinations of PHP / libxml versions return an empty array
            // instead of the documented FALSE. Forcefully convert any falsish values
            // to an empty array to allow foreach(...) constructions.
            return $result ? $result : array();
        }
        else {
            return FALSE;
        }
    }

    public function getForm($formId)
    {
        return $this->xpath(sprintf('//form[@id="%s"]', $formId));
    }

    public function getFields($formId)
    {
        $form = reset($this->getForm($formId));

        if (false === $form) {
            return false;
        }

        $fields = array();

        foreach (array_merge($form->xpath('//input'), $form->xpath('//select'), $form->xpath('//textarea')) as $el) {
            $name = SimpleXml::getAttribute($el, 'name');
            $val = $this->getFieldValue($el);
            $fields[$name] = $val;
        }

        return $fields;
    }

    public function getFieldValue(\SimpleXmlElement $el) {
        
        switch ($el->getName()) {
            case 'input':
                return SimpleXml::getAttribute($el, 'value');
            case 'textarea':
                return (string)$el;
            case 'select':
                foreach ($el->children() as $option) {
                    if (SimpleXml::getAttribute($option, 'selected') === 'selected') {
                        return SimpleXml::getAttribute($option, 'value');
                    }
                }
        }
        return false;
    }

    public function getInputValue($formId, $inputName) {

        $form = reset($this->getForm($formId));
        if (false === $form) {
            return false;
        }

        $el = reset($form->xpath(sprintf('//input[@name="%s"]', $inputName)));
        if (false === $el) {
            return false;
        }

        return (string)$el['value'];
    }

    /**
     * Parse content returned from curlExec using DOM and SimpleXML.
     *
     * @return
     *   A SimpleXMLElement or FALSE on failure.
     */
    protected function parse()
    {
        if (!$this->elements) {
            $htmlDom = new \DOMDocument();
            @$htmlDom->loadHTML($this->html);
            if ($htmlDom) {
                $this->elements = simplexml_import_dom($htmlDom);
            }
        }

        if (!$this->elements) {
            throw new \Exception('Invalid HTML');
        }

        return $this->elements;
    }

    /**
     * Builds an XPath query.
     *
     * Builds an XPath query by replacing placeholders in the query by the value
     * of the arguments.
     *
     * XPath 1.0 (the version supported by libxml2, the underlying XML library
     * used by PHP) doesn't support any form of quotation. This function
     * simplifies the building of XPath expression.
     *
     * @param $xpath
     *   An XPath query, possibly with placeholders in the form ':name'.
     * @param $args
     *   An array of arguments with keys in the form ':name' matching the
     *   placeholders in the query. The values may be either strings or numeric
     *   values.
     * @return
     *   An XPath query with arguments replaced.
     */
    protected function buildXPathQuery($xpath, array $args = array())
    {
        // Replace placeholders.
        foreach ($args as $placeholder => $value) {
            // XPath 1.0 doesn't support a way to escape single or double quotes in a
            // string literal. We split double quotes out of the string, and encode
            // them separately.
            if (is_string($value)) {
                // Explode the text at the quote characters.
                $parts = explode('"', $value);

                // Quote the parts.
                foreach ($parts as &$part) {
                    $part = '"' . $part . '"';
                }

                // Return the string.
                $value = count($parts) > 1 ? 'concat(' . implode(', \'"\', ', $parts) . ')' : $parts[0];
            }
            $xpath = preg_replace('/' . preg_quote($placeholder) . '\b/', $value, $xpath);
        }
        return $xpath;
    }

}
