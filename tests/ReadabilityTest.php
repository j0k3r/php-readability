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
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->innerHTML);
        $this->assertContains('Sorry, Readability was unable to parse this page for content.', $readability->getContent()->innerHTML);
    }

    public function testInitP()
    {
        $readability = new ReadabilityTested(str_repeat('<p>This is the awesome content :)</p>', 7), 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
        $this->assertContains('This is the awesome content :)', $readability->getContent()->innerHTML);
    }

    public function testInitDivP()
    {
        $readability = new ReadabilityTested('<div>'.str_repeat('<p>This is the awesome content :)</p>', 7).'</div>', 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
        $this->assertContains('This is the awesome content :)', $readability->getContent()->innerHTML);
    }

    public function testInitDiv()
    {
        $readability = new ReadabilityTested('<div>'.str_repeat('This is the awesome content :)', 7).'</div>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
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
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertContains('readabilityFootnoteLink', $readability->getContent()->innerHTML);
        $this->assertContains('readabilityLink-3', $readability->getContent()->innerHTML);
    }

    public function testStandardClean()
    {
        $readability = new ReadabilityTested('<div><h2>Title</h2>'.str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7).'<a href="#nofollow" rel="nofollow">will NOT be removed</a></div>', 'http://0.0.0.0');
        $readability->debug = true;
        $readability->lightClean = false;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertContains('will NOT be removed', $readability->getContent()->innerHTML);
        $this->assertNotContains('<h2>', $readability->getContent()->innerHTML);
    }

    public function testWithIframe()
    {
        $readability = new ReadabilityTested('<div><h2>Title</h2>'.str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7).'<p>This is an awesome text with some links, here there are <iframe src="http://youtube.com/test" href="#nofollow" rel="nofollow"></iframe><iframe>http://soundcloud.com/test</iframe></p></div>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
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
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
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
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
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
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
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
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="tr"', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
    }

    public function testWithSameClasses()
    {
        $readability = new ReadabilityTested('<article class="awesomecontent">'.str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7).'<div class="awesomecontent">This text is also an awesome text and you should know that !</div></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
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
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertEmpty($readability->getTitle()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertNotContains('This text is also an awesome text and you should know that', $readability->getContent()->innerHTML);
    }

    public function testTitle()
    {
        $readability = new ReadabilityTested('<title>this is my title</title><article class="awesomecontent">'.str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7).'<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertEquals('this is my title', $readability->getTitle()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertNotContains('This text is also an awesome text and you should know that', $readability->getContent()->innerHTML);
    }

    public function testTitleWithDash()
    {
        $readability = new ReadabilityTested('<title> title2 - title3 </title><article class="awesomecontent">'.str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7).'<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertEquals('title2 - title3', $readability->getTitle()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertNotContains('This text is also an awesome text and you should know that', $readability->getContent()->innerHTML);
    }

    public function testTitleWithDoubleDot()
    {
        $readability = new ReadabilityTested('<title> title2 : title3 </title><article class="awesomecontent">'.str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7).'<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertEquals('title2 : title3', $readability->getTitle()->innerHTML);
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->innerHTML);
        $this->assertNotContains('This text is also an awesome text and you should know that', $readability->getContent()->innerHTML);
    }

    public function testTitleTooShortUseH1()
    {
        $readability = new ReadabilityTested('<title>too short</title><h1>this is my h1 title !</h1><article class="awesomecontent">'.str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7).'<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->innerHTML);
        $this->assertEquals('this is my h1 title !', $readability->getTitle()->innerHTML);
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

    // dummy function to be used to the next test
    public function error2Exception($code, $string, $file, $line, $context)
    {
        throw new \Exception($string, $code);
    }

    public function testAutoClosingIframeNotThrowingException()
    {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', true);
        set_error_handler(array($this, 'error2Exception'), E_ALL | E_STRICT);

        $data = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml" lang="ru-RU" prefix="og: http://ogp.me/ns#">

            <head profile="http://gmpg.org/xfn/11">
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />

            </head>
            <body class="single single-post postid-22030 single-format-standard">
              <div id="wrapper">
                <div id="content">
                  <div class="post-22030 post type-post status-publish format-standard has-post-thumbnail hentry category-video category-reviews tag-193" id="post-22030">
                    <h1>3D Touch &#8212; будущее мобильных игр</h1>
                    <div class="postdate">Автор:  <strong>Сергей Пак</strong> | Просмотров: 1363 | Опубликовано:  14 сентября 2015 </div>
                    <div class="entry">
                      <p>Компания Apple представила новую технологию 3D Touch, которая является прямым потомком более ранней версии Force Touch &#8212; последняя, напомним, используется сейчас в трекпадах Macbook Pro и Macbook 2015. Теперь управлять устройством стало в разы проще, и Force Touch открывает перед пользователями новые возможности, но при этом 3D Touch &#8212; это про другое. Дело в том, что теперь и на мобильных устройствах интерфейс будет постепенно меняться, кардинальные перемены ждут мобильный гейминг, потому что здесь разработчики действительно могут разгуляться.<span id="more-22030"></span></p>
                      <p><iframe src="https://www.youtube.com/embed/PUep6xNeKjA" width="560" height="315" frameborder="0" allowfullscreen="allowfullscreen"></iframe></p>
                      <p>Итак, просто представьте себе, что iPhone 6S &#8212; это, по большому счету, отличная игровая приставка, которую вы носите с собой, а еще она может выдавать невероятной красоты картинку. Но проблема заключается, пожалуй, в том, что управлять персонажем в играх довольно трудно &#8212; он неповоротлив, обладает заторможенной реакцией, а игровой клиент зачастую требует перегруза интерфейса для того, чтобы обеспечить максимально большое количество возможностей. Благодаря трехуровневому нажатию можно избавиться от лишних кнопок и обеспечить более качественный обзор местности, и при этом пользователь будет закрывать пальцами минимальное пространство.</p>
                    </div>
                  </div>
                </div>
              </div>
            </body>
            </html>';

        $readability = new ReadabilityTested($data, 'http://iosgames.ru/?p=22030');
        $readability->debug = true;

        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<iframe src="https://www.youtube.com/embed/PUep6xNeKjA" width="560" height="315" frameborder="0" allowfullscreen="allowfullscreen"> </iframe>', $readability->getContent()->innerHTML);
        $this->assertContains('3D Touch', $readability->getTitle()->innerHTML);
    }
}
