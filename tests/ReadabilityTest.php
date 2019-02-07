<?php

namespace Tests\Readability;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Readability\Readability;

class ReadabilityTest extends \PHPUnit\Framework\TestCase
{
    public $logHandler;
    public $logger;

    /**
     * @requires extension tidy
     */
    public function testConstructDefault()
    {
        $readability = $this->getReadability('');

        $this->assertNull($readability->url);
        $this->assertInstanceOf('DomDocument', $readability->dom);
    }

    public function testConstructHtml5Parser()
    {
        $readability = $this->getReadability('<html/>', 'http://0.0.0.0', 'html5lib');

        $this->assertEquals('http://0.0.0.0', $readability->url);
        $this->assertInstanceOf('DomDocument', $readability->dom);
        $this->assertEquals('<html/>', $readability->original_html);
    }

    /**
     * @requires extension tidy
     */
    public function testConstructSimple()
    {
        $readability = $this->getReadability('<html/>', 'http://0.0.0.0');

        $this->assertEquals('http://0.0.0.0', $readability->url);
        $this->assertInstanceOf('DomDocument', $readability->dom);
        $this->assertEquals('<html/>', $readability->original_html);
        $this->assertTrue($readability->tidied);
    }

    public function testConstructDefaultWithoutTidy()
    {
        $readability = $this->getReadability('', null, 'libxml', false);

        $this->assertNull($readability->url);
        $this->assertEquals('', $readability->original_html);
        $this->assertFalse($readability->tidied);

        $this->assertInstanceOf('DomDocument', $readability->dom);
    }

    public function testConstructSimpleWithoutTidy()
    {
        $readability = $this->getReadability('<html/>', 'http://0.0.0.0', 'libxml', false);

        $this->assertEquals('http://0.0.0.0', $readability->url);
        $this->assertInstanceOf('DomDocument', $readability->dom);
        $this->assertEquals('<html/>', $readability->original_html);
        $this->assertFalse($readability->tidied);
    }

    public function testInitNoContent()
    {
        $readability = $this->getReadability('<html/>', 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertFalse($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('Sorry, Readability was unable to parse this page for content.', $readability->getContent()->getInnerHtml());
    }

    public function testInitP()
    {
        $readability = $this->getReadability(str_repeat('<p>This is the awesome content :)</p>', 7), 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is the awesome content :)', $readability->getContent()->getInnerHtml());
    }

    public function testInitDivP()
    {
        $readability = $this->getReadability('<div>' . str_repeat('<p>This is the awesome content :)</p>', 7) . '</div>', 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is the awesome content :)', $readability->getContent()->getInnerHtml());
    }

    public function testInitDiv()
    {
        $readability = $this->getReadability('<div>' . str_repeat('This is the awesome content :)', 7) . '</div>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is the awesome content :)', $readability->getContent()->getInnerHtml());
    }

    public function testWithFootnotes()
    {
        $readability = $this->getReadability('<div>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '</div>', 'http://0.0.0.0');
        $readability->debug = true;
        $readability->convertLinksToFootnotes = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertContains('readabilityFootnoteLink', $readability->getContent()->getInnerHtml());
        $this->assertContains('readabilityLink-3', $readability->getContent()->getInnerHtml());
    }

    public function testStandardClean()
    {
        $readability = $this->getReadability('<div><h2>Title</h2>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '<a href="#nofollow" rel="nofollow">will NOT be removed</a></div>', 'http://0.0.0.0');
        $readability->debug = true;
        $readability->lightClean = false;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertContains('will NOT be removed', $readability->getContent()->getInnerHtml());
        $this->assertNotContains('<h2>', $readability->getContent()->getInnerHtml());
    }

    public function testWithIframe()
    {
        $readability = $this->getReadability('<div><h2>Title</h2>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '<p>This is an awesome text with some links, here there are <iframe src="http://youtube.com/test" href="#nofollow" rel="nofollow"></iframe><iframe>http://soundcloud.com/test</iframe></p></div>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertContains('nofollow', $readability->getContent()->getInnerHtml());
    }

    public function testWithArticle()
    {
        $readability = $this->getReadability('<article><p>' . str_repeat('This is an awesome text with some links, here there are: the awesome', 20) . '</p><p>This is an awesome text with some links, here there are <iframe src="http://youtube.com/test" href="#nofollow" rel="nofollow"></iframe></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertContains('nofollow', $readability->getContent()->getInnerHtml());
    }

    public function testWithAside()
    {
        $readability = $this->getReadability('<article>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '<footer><aside>' . str_repeat('<p>This is an awesome text with some links, here there are</p>', 8) . '</aside></footer></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertNotContains('<aside>', $readability->getContent()->getInnerHtml());
        $this->assertContains('<footer readability="4"/>', $readability->getContent()->getInnerHtml());
    }

    public function testWithClasses()
    {
        $readability = $this->getReadability('<article>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '<div style="display:none">' . str_repeat('<p class="clock">This text should be removed</p>', 10) . '</div></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertNotContains('This text should be removed', $readability->getContent()->getInnerHtml());
    }

    public function testWithClassesWithoutLightClean()
    {
        $readability = $this->getReadability('<article>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '<div style="display:none">' . str_repeat('<p class="clock">This text should be removed</p>', 10) . '</div></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $readability->lightClean = false;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('alt="article"', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertNotContains('This text should be removed', $readability->getContent()->getInnerHtml());
    }

    public function testWithTd()
    {
        $readability = $this->getReadability('<table><tr>' . str_repeat('<td><p>This is an awesome text with some links, here there are the awesome</td>', 7) . '</tr></table>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
    }

    public function testWithSameClasses()
    {
        $readability = $this->getReadability('<article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<div class="awesomecontent">This text is also an awesome text and you should know that !</div></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertContains('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testWithScript()
    {
        $readability = $this->getReadability('<article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<p><script>This text is also an awesome text and you should know that !</script></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertNotContains('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testTitle()
    {
        $readability = $this->getReadability('<title>this is my title</title><article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertEquals('this is my title', $readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertNotContains('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testTitleWithDash()
    {
        $readability = $this->getReadability('<title> title2 - title3 </title><article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertEquals('title2 - title3', $readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertNotContains('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testTitleWithDoubleDot()
    {
        $readability = $this->getReadability('<title> title2 : title3 </title><article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertEquals('title2 : title3', $readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertNotContains('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testTitleTooShortUseH1()
    {
        $readability = $this->getReadability('<title>too short</title><h1>this is my h1 title !</h1><article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertEquals('this is my h1 title !', $readability->getTitle()->getInnerHtml());
        $this->assertContains('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertNotContains('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    // dummy function to be used to the next test
    public function error2Exception($code, $string, $file, $line, $context)
    {
        throw new \Exception($string, $code);
    }

    public function testAutoClosingIframeNotThrowingException()
    {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', true);
        set_error_handler([$this, 'error2Exception'], E_ALL | E_STRICT);

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

        $readability = $this->getReadability($data, 'http://iosgames.ru/?p=22030');
        $readability->debug = true;

        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
        $this->assertContains('<iframe src="https://www.youtube.com/embed/PUep6xNeKjA" width="560" height="315" frameborder="0" allowfullscreen="allowfullscreen"> </iframe>', $readability->getContent()->getInnerHtml());
        $this->assertContains('3D Touch', $readability->getTitle()->getInnerHtml());
    }

    /**
     * This should generate an Exception "DOMElement::setAttribute(): ID post-60 already defined".
     */
    public function testAppendIdAlreadyHere()
    {
        $data = '<!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0">
            </head>
            <body>
                <div class="container">
                    <header class="header sml-text-center med-text-left" role="banner">
                        <h1 class="no-margin"><a class="maintitle" href="https://0.0.0.0" title="Bloc-notes">Bloc-notes</a></h1>
                        <h2 class="h5 no-margin"></h2>
                    </header>

                    <nav class="nav" role="navigation">
                        <div class="responsive-menu">
                            <label for="menu">Menu</label>
                            <input type="checkbox" id="menu">
                        </div>
                    </nav>

                    <article class="article" role="article" id="post-60">
                        <section>
                            This is an awesome text with some links, here there are<br/>
                            This is an awesome text with some links, here there are<br/>
                            This is an awesome text with some links, here there are<br/>
                            This is an awesome text with some links, here there are<br/>
                            This is an awesome text with some links, here there are<br/>
                            This is an awesome text with some links, here there are<br/>
                            This is an awesome text with some links, here there are<br/>
                            This is an awesome text with some links, here there are<br/>
                            This is an awesome text with some links, here there are<br/>
                            This is an awesome text with some links, here there are<br/>
                            This is an awesome text with some links, here there are
                        </section>
                        <footer>
                            <small>
                                Classé dans : <a class="noactive" title="Services réseaux">Services réseaux</a>
                            </small>
                        </footer>
                    </article>
                </div>
            </body>
            </html>';

        $readability = $this->getReadability($data, 'http://0.0.0.0');
        $readability->debug = true;

        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getContent());
        $this->assertInstanceOf('Readability\JSLikeHTMLElement', $readability->getTitle());
    }

    public function testPostFilters()
    {
        $readability = $this->getReadability('<div>' . str_repeat('<p>This <strong>is</strong> the awesome content :)</p>', 10) . '</div>', 'http://0.0.0.0');
        $readability->addPostFilter('!<strong[^>]*>(.*?)</strong>!is', '');

        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertContains('This  the awesome content :)', $readability->getContent()->getInnerHtml());
    }

    public function testPreFilters()
    {
        $this->markTestSkipped('Won\'t work until loadHtml() is moved in init() instead of __construct()');

        $readability = $this->getReadability('<div>' . str_repeat('<p>This <b>is</b> the awesome and WONDERFUL content :)</p>', 7) . '</div>', 'http://0.0.0.0');
        $readability->addPreFilter('!<b[^>]*>(.*?)</b>!is', '');

        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertContains('This the awesome and WONDERFUL content :)', $readability->getContent()->getInnerHtml());
    }

    public function testChildNodeGoneNull()
    {
        // from http://www.ayyaantuu.net/ethiopia-targets-opposition-lawmakers/
        $html = file_get_contents('tests/fixtures/childNodeGoesNull.html');

        $readability = $this->getReadability($html, 'http://0.0.0.0');
        $readability->debug = true;
        $readability->convertLinksToFootnotes = true;
        $res = $readability->init();

        $this->assertTrue($res);
    }

    public function testKeepFootnotes()
    {
        // from https://www.schreibdichte.de/blog/feed-aggregator-und-spaeter-lesen-dienst-im-team
        $html = file_get_contents('tests/fixtures/keepFootnotes.html');

        $readability = $this->getReadability($html, 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertContains('<sup id="fnref1:fnfeed_2"><a href="#fn:fnfeed_2" class="footnote-ref">2</a></sup>', $readability->getContent()->getInnerHtml());
        $this->assertContains('<a href="#fnref1:fnfeed_2" rev="footnote"', $readability->getContent()->getInnerHtml());
    }

    private function getReadability($html, $url = null, $parser = 'libxml', $useTidy = true)
    {
        $readability = new Readability($html, $url, $parser, $useTidy);

        $this->logHandler = new TestHandler();
        $this->logger = new Logger('test', [$this->logHandler]);
        $readability->setLogger($this->logger);

        return $readability;
    }
}
