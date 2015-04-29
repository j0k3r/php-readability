<?php

namespace Tests\Readability;

use Readability\Readability;

class ReadabilityTested extends Readability
{
    public function getDebugText()
    {
        return $this->debugText;
    }

    public function getDomainRegexp()
    {
        return $this->domainRegExp;
    }
}

class ReadabilityTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructDefault()
    {
        $readability = new ReadabilityTested('');

        $this->assertNull($readability->url);
        $this->assertContains('Parsing URL', $readability->getDebugText());
        $this->assertContains('Tidying document', $readability->getDebugText());
        $this->assertNull($readability->getDomainRegexp());
        $this->assertInstanceOf('DomDocument', $readability->dom);
    }

    public function testConstructSimple()
    {
        $readability = new ReadabilityTested('<html/>', 'http://0.0.0.0');

        $this->assertEquals('http://0.0.0.0', $readability->url);
        $this->assertContains('Parsing URL', $readability->getDebugText());
        $this->assertContains('Tidying document', $readability->getDebugText());
        $this->assertEquals('/0\.0\.0\.0/', $readability->getDomainRegexp());
        $this->assertInstanceOf('DomDocument', $readability->dom);
    }

    public function testInitNoContent()
    {
        $readability = new ReadabilityTested('<html/>', 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertFalse($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('Sorry, Readability was unable to parse this page for content.', $readability->getContent()->innerHTML);
    }

    public function testInitP()
    {
        $readability = new ReadabilityTested(str_repeat('<p>This is the awesome content :)</p>', 7), 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertContains('This is the awesome content :)', $readability->getContent()->innerHTML);
    }

    public function testInitDivP()
    {
        $readability = new ReadabilityTested('<div>'.str_repeat('<p>This is the awesome content :)</p>', 7).'</div>', 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertContains('This is the awesome content :)', $readability->getContent()->innerHTML);
    }

    public function testInitDiv()
    {
        $readability = new ReadabilityTested('<div>'.str_repeat('This is the awesome content :)', 7).'</div>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertContains('This is the awesome content :)', $readability->getContent()->innerHTML);
    }

    public function testWithFootnotes()
    {
        $readability = new ReadabilityTested('<div>'.str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7).'</div>', 'http://0.0.0.0');
        $readability->debug = true;
        $readability->convertLinksToFootnotes = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertContains('readabilityFootnoteLink', $readability->getContent()->innerHTML);
        $this->assertContains('readabilityLink-3', $readability->getContent()->innerHTML);
    }

    public function testStandardClean()
    {
        $readability = new ReadabilityTested('<div><h2>Title</h2>'.str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7).'<a href="#nofollow" rel="nofollow">will be removed</a></div>', 'http://0.0.0.0');
        $readability->debug = true;
        $readability->lightClean = false;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertNotContains('will be removed', $readability->getContent()->innerHTML);
        $this->assertNotContains('<h2>', $readability->getContent()->innerHTML);
    }

    public function testWithIframe()
    {
        $readability = new ReadabilityTested('<div><h2>Title</h2>'.str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7).'<p>This is an awesome text with some links, here there are <iframe src="http://youtube.com/test" href="#nofollow" rel="nofollow"></iframe><iframe>http://soundcloud.com/test</iframe></p></div>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertContains('nofollow', $readability->getContent()->innerHTML);
    }

    public function testWithArticle()
    {
        $readability = new ReadabilityTested('<article><p>'.str_repeat('This is an awesome text with some links, here there are: the awesome', 20).'</p><p>This is an awesome text with some links, here there are <iframe src="http://youtube.com/test" href="#nofollow" rel="nofollow"></iframe></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertContains('nofollow', $readability->getContent()->innerHTML);
    }

    public function testWithAside()
    {
        $readability = new ReadabilityTested('<article>'.str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7).'<footer><aside>'.str_repeat('<p>This is an awesome text with some links, here there are</p>', 8).'</aside></footer></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertNotContains('<aside>', $readability->getContent()->innerHTML);
        $this->assertContains('<footer/>', $readability->getContent()->innerHTML);
    }

    public function testWithClasses()
    {
        $readability = new ReadabilityTested('<article>'.str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7).'<div style="display:none">'.str_repeat('<p class="clock">This text should be removed</p>', 10).'</div></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertNotContains('This text should be removed', $readability->getContent()->innerHTML);
    }

    public function testWithTd()
    {
        $readability = new ReadabilityTested('<table><tr>'.str_repeat('<td><p>This is an awesome text with some links, here there are the awesome</td>', 7).'</tr></table>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('alt="tr"', $readability->getContent()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
    }

    public function testWithSameClasses()
    {
        $readability = new ReadabilityTested('<article class="awesomecontent">'.str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7).'<div class="awesomecontent">This text is also an awesome text and you should know that !</div></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertContains('This text is also an awesome text and you should know that', $readability->getContent()->innerHTML);
    }

    public function testWithScript()
    {
        $readability = new ReadabilityTested('<article class="awesomecontent">'.str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7).'<p><script>This text is also an awesome text and you should know that !</script></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertNotContains('This text is also an awesome text and you should know that', $readability->getContent()->innerHTML);
    }

    // public function testConstructParser()
    // {
    //     $readability = new ReadabilityTested('<html/>', 'http://0.0.0.0', 'html5lib');

    //     $this->assertEquals('http://0.0.0.0', $readability->url);
    //     $this->assertContains('Parsing URL', $readability->getDebugText());
    //     $this->assertContains('Tidying document', $readability->getDebugText());
    //     $this->assertEquals('/0\.0\.0\.0/', $readability->getDomainRegexp());
    //     $this->assertInstanceOf('DomDocument', $readability->dom);
    // }
}
