<?php

namespace Liip\Drupal\Testing\Tests\Web;

use Liip\Drupal\Testing\Web\Response,
    Liip\Drupal\Testing\Web\Crawler,
    Liip\Drupal\Testing\Helper\SimpleXml;

use Liip\Drupal\Testing\Web\Client,
    Liip\Drupal\Testing\Web\Curl;

class CrawlerTestCase extends \PHPUnit_Framework_TestCase
{
    protected $formHtml = <<<HTML
<html><body>
    <div>
        <form id="myform">
            <input type="text" name="mytext" value="textvalue"/>
            <textarea name="mytextarea">textareavalue</textarea>
            <select name="myselect"><option value="1" selected="selected">Option 1</option></select>
            <input type="Submit" name="mysubmit" value="Go">
        </form>
    </div>
</body></html>
HTML;

    public function testXpath()
    {
        $resp = new Response();
        $resp->setContent('<html><body><div id="mydiv"><span class="myclass">Whatever</span>foobar</div></body></html>');
        $crawler = $resp->getCrawler();

        // Some assertions about the span
        $el = $crawler->xpath('//span[@class="myclass"]');
        $this->assertNotEquals(false, $el);

        $span = reset($el);
        $this->assertEquals('Whatever', (string)$span);
        $this->assertEquals('myclass', SimpleXml::getAttribute($span, 'class'));

        // Some assertions about the div
        $el = $crawler->xpath('//div[@id="mydiv"]');
        $this->assertNotEquals(false, $el);

        $div = reset($el);
        $this->assertEquals('foobar', (string)$div);
        $this->assertEquals('mydiv', SimpleXml::getAttribute($div, 'id'));
        $this->assertEquals($span, SimpleXml::firstChild($div));
    }

    public function testGetForm()
    {
        $resp = new Response();
        $resp->setContent($this->formHtml);
        $crawler = $resp->getCrawler();

        $el = $crawler->getForm('myform');
        $this->assertNotEquals(false, $el);

        $form = reset($el);
        $this->assertEquals('form', $form->getName());
        $this->assertEquals('myform', SimpleXml::getAttribute($form, 'id'));
    }

    public function testGetFields()
    {
        $resp = new Response();
        $resp->setContent($this->formHtml);
        $crawler = $resp->getCrawler();

        $expected = array(
            'mytext' => 'textvalue',
            'mytextarea' => 'textareavalue',
            'myselect' => '1',
            'mysubmit' => 'Go'
        );
        $actual = $crawler->getFields('myform');
        $this->assertEquals($expected, $actual);
    }

    public function testGetInputValue()
    {
        $resp = new Response();
        $resp->setContent($this->formHtml);
        $crawler = $resp->getCrawler();
        $this->assertEquals('Go', $crawler->getInputValue('myform', 'mysubmit'));
    }
}
