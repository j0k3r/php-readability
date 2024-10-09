<?php

namespace Tests\Readability;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Readability\JSLikeHTMLElement;
use Readability\Readability;

class ReadabilityTest extends \PHPUnit\Framework\TestCase
{
    /** @var TestHandler */
    public $logHandler;
    /** @var LoggerInterface */
    public $logger;

    /**
     * @requires extension tidy
     */
    public function testConstructDefault(): void
    {
        $readability = $this->getReadability('');
        $readability->init();

        $this->assertNull($readability->url);
        $this->assertInstanceOf('DomDocument', $readability->dom);
    }

    public function testConstructHtml5Parser(): void
    {
        $readability = $this->getReadability('<html/>', 'http://0.0.0.0', 'html5lib');
        $readability->init();

        $this->assertSame('http://0.0.0.0', $readability->url);
        $this->assertInstanceOf('DomDocument', $readability->dom);
        $this->assertSame('<html/>', $readability->original_html);
    }

    /**
     * @requires extension tidy
     */
    public function testConstructSimple(): void
    {
        $readability = $this->getReadability('<html/>', 'http://0.0.0.0');
        $readability->init();

        $this->assertSame('http://0.0.0.0', $readability->url);
        $this->assertInstanceOf('DomDocument', $readability->dom);
        $this->assertSame('<html/>', $readability->original_html);
        $this->assertTrue($readability->tidied);
    }

    public function testConstructDefaultWithoutTidy(): void
    {
        $readability = $this->getReadability('', null, 'libxml', false);
        $readability->init();

        $this->assertNull($readability->url);
        $this->assertSame('', $readability->original_html);
        $this->assertFalse($readability->tidied);

        $this->assertInstanceOf('DomDocument', $readability->dom);
    }

    public function testConstructSimpleWithoutTidy(): void
    {
        $readability = $this->getReadability('<html/>', 'http://0.0.0.0', 'libxml', false);
        $readability->init();

        $this->assertSame('http://0.0.0.0', $readability->url);
        $this->assertInstanceOf('DomDocument', $readability->dom);
        $this->assertSame('<html/>', $readability->original_html);
        $this->assertFalse($readability->tidied);
    }

    public function testInitNoContent(): void
    {
        $readability = $this->getReadability('<html/>', 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertFalse($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('Sorry, Readability was unable to parse this page for content.', $readability->getContent()->getInnerHtml());
    }

    public function testInitP(): void
    {
        $readability = $this->getReadability(str_repeat('<p>This is the awesome content :)</p>', 7), 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertStringContainsString('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is the awesome content :)', $readability->getContent()->getInnerHtml());
    }

    public function testInitDivP(): void
    {
        $readability = $this->getReadability('<div>' . str_repeat('<p>This is the awesome content :)</p>', 7) . '</div>', 'http://0.0.0.0');
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertStringContainsString('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is the awesome content :)', $readability->getContent()->getInnerHtml());
    }

    public function testInitDiv(): void
    {
        $readability = $this->getReadability('<div>' . str_repeat('This is the awesome content :)', 7) . '</div>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertStringContainsString('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is the awesome content :)', $readability->getContent()->getInnerHtml());
    }

    public function testWithFootnotes(): void
    {
        $readability = $this->getReadability('<div>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '</div>', 'http://0.0.0.0');
        $readability->debug = true;
        $readability->convertLinksToFootnotes = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertStringContainsString('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringContainsString('readabilityFootnoteLink', $readability->getContent()->getInnerHtml());
        $this->assertStringContainsString('readabilityLink-3', $readability->getContent()->getInnerHtml());
    }

    public function testStandardClean(): void
    {
        $readability = $this->getReadability('<div><h2>Title</h2>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '<a href="#nofollow" rel="nofollow">will NOT be removed</a></div>', 'http://0.0.0.0');
        $readability->debug = true;
        $readability->lightClean = false;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertStringContainsString('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringContainsString('will NOT be removed', $readability->getContent()->getInnerHtml());
        $this->assertStringNotContainsString('<h2>', $readability->getContent()->getInnerHtml());
    }

    public function testWithIframe(): void
    {
        $readability = $this->getReadability('<div><h2>Title</h2>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '<p>This is an awesome text with some links, here there are <iframe src="http://youtube.com/test" href="#nofollow" rel="nofollow"></iframe><iframe>http://soundcloud.com/test</iframe></p></div>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertStringContainsString('<div readability=', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringContainsString('nofollow', $readability->getContent()->getInnerHtml());
    }

    public function testWithArticle(): void
    {
        $readability = $this->getReadability('<article><p>' . str_repeat('This is an awesome text with some links, here there are: the awesome', 20) . '</p><p>This is an awesome text with some links, here there are <iframe src="http://youtube.com/test" href="#nofollow" rel="nofollow"></iframe></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertStringContainsString('alt="article"', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringContainsString('nofollow', $readability->getContent()->getInnerHtml());
    }

    public function testWithAside(): void
    {
        $readability = $this->getReadability('<article>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '<footer><aside>' . str_repeat('<p>This is an awesome text with some links, here there are</p>', 8) . '</aside></footer></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringNotContainsString('<aside>', $readability->getContent()->getInnerHtml());
        $this->assertStringContainsString('<footer readability="9"/>', $readability->getContent()->getInnerHtml());
    }

    public function testWithClasses(): void
    {
        $readability = $this->getReadability('<article>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '<div style="display:none">' . str_repeat('<p class="clock">This text should be removed</p>', 10) . '</div></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertStringContainsString('alt="article"', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringNotContainsString('This text should be removed', $readability->getContent()->getInnerHtml());
    }

    public function testWithClassesWithoutLightClean(): void
    {
        $readability = $this->getReadability('<article>' . str_repeat('<p>This is an awesome text with some links, here there are: <a href="http://0.0.0.0/test.html">the awesome</a></p>', 7) . '<div style="display:none">' . str_repeat('<p class="clock">This text should be removed</p>', 10) . '</div></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $readability->lightClean = false;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertStringContainsString('alt="article"', $readability->getContent()->getInnerHtml());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringNotContainsString('This text should be removed', $readability->getContent()->getInnerHtml());
    }

    public function testWithTd(): void
    {
        $readability = $this->getReadability('<table><tr>' . str_repeat('<td><p>This is an awesome text with some links, here there are the awesome</td>', 7) . '</tr></table>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
    }

    public function testWithSameClasses(): void
    {
        $readability = $this->getReadability('<article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<div class="awesomecontent">This text is also an awesome text and you should know that !</div></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringContainsString('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testWithScript(): void
    {
        $readability = $this->getReadability('<article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<p><script>This text is also an awesome text and you should know that !</script></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertEmpty($readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringNotContainsString('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testTitle(): void
    {
        $readability = $this->getReadability('<title>this is my title</title><article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertSame('this is my title', $readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringNotContainsString('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testTitleWithDash(): void
    {
        $readability = $this->getReadability('<title> title2 - title3 </title><article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertSame('title2 - title3', $readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringNotContainsString('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testTitleWithDoubleDot(): void
    {
        $readability = $this->getReadability('<title> title2 : title3 </title><article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertSame('title2 : title3', $readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringNotContainsString('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testTitleTooShortUseH1(): void
    {
        $readability = $this->getReadability('<title>too short</title><h1>this is my h1 title !</h1><article class="awesomecontent">' . str_repeat('<p>This is an awesome text with some links, here there are the awesome</p>', 7) . '<p></p></article>', 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
        $this->assertSame('this is my h1 title !', $readability->getTitle()->getInnerHtml());
        $this->assertStringContainsString('This is an awesome text with some links, here there are', $readability->getContent()->getInnerHtml());
        $this->assertStringNotContainsString('This text is also an awesome text and you should know that', $readability->getContent()->getInnerHtml());
    }

    public function testAutoClosingIframeNotThrowingException(): void
    {
        $oldErrorReporting = error_reporting(\E_ALL);
        $oldDisplayErrors = ini_set('display_errors', '1');
        // dummy function to be used to the next test
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            throw new \Exception($errstr, $errno);
        });

        try {
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
            $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
            $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
            $this->assertStringContainsString('<iframe src="https://www.youtube.com/embed/PUep6xNeKjA" width="560" height="315" frameborder="0" allowfullscreen="allowfullscreen"> </iframe>', $readability->getContent()->getInnerHtml());
            $this->assertStringContainsString('3D Touch', $readability->getTitle()->getInnerHtml());
        } finally {
            restore_error_handler();
            if (false !== $oldDisplayErrors) {
                ini_set('display_errors', $oldDisplayErrors);
            }
            error_reporting($oldErrorReporting);
        }
    }

    /**
     * This should generate an Exception "DOMElement::setAttribute(): ID post-60 already defined".
     */
    public function testAppendIdAlreadyHere(): void
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
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getContent());
        $this->assertInstanceOf(JSLikeHTMLElement::class, $readability->getTitle());
    }

    public function testPostFilters(): void
    {
        $readability = $this->getReadability('<div>' . str_repeat('<p>This <strong>is</strong> the awesome content :)</p>', 10) . '</div>', 'http://0.0.0.0');
        $readability->addPostFilter('!<strong[^>]*>(.*?)</strong>!is', '');

        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertStringContainsString('This  the awesome content :)', $readability->getContent()->getInnerHtml());
    }

    public function testPreFilters(): void
    {
        $readability = $this->getReadability('<div>' . str_repeat('<p>This <b>is</b> the awesome and WONDERFUL content :)</p>', 7) . '</div>', 'http://0.0.0.0');
        $readability->addPreFilter('!<b[^>]*>(.*?)</b>!is', '');

        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertStringContainsString('This the awesome and WONDERFUL content :)', $readability->getContent()->getInnerHtml());
    }

    public function testChildNodeGoneNull(): void
    {
        // from http://www.ayyaantuu.net/ethiopia-targets-opposition-lawmakers/
        $html = (string) file_get_contents('tests/fixtures/childNodeGoesNull.html');

        $readability = $this->getReadability($html, 'http://0.0.0.0');
        $readability->debug = true;
        $readability->convertLinksToFootnotes = true;
        $res = $readability->init();

        $this->assertTrue($res);
    }

    public function testKeepFootnotes(): void
    {
        // from https://www.schreibdichte.de/blog/feed-aggregator-und-spaeter-lesen-dienst-im-team
        $html = (string) file_get_contents('tests/fixtures/keepFootnotes.html');

        $readability = $this->getReadability($html, 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertStringContainsString('<sup id="fnref1:fnfeed_2"><a href="#fn:fnfeed_2" class="footnote-ref">2</a></sup>', $readability->getContent()->getInnerHtml());
        $this->assertStringContainsString('<a href="#fnref1:fnfeed_2" rev="footnote"', $readability->getContent()->getInnerHtml());
    }

    public function testWithWipedBody(): void
    {
        // from https://www.cs.cmu.edu/~rgs/alice-table.html
        $html = (string) file_get_contents('tests/fixtures/wipedBody.html');

        $readability = $this->getReadability($html, 'http://0.0.0.0', 'libxml', false);
        $readability->debug = true;
        $res = $readability->init();

        $this->assertTrue($res);
        $this->assertStringContainsString('<a href="alice-I.html">Down the Rabbit-Hole</a>', $readability->getContent()->getInnerHtml());
    }

    public function dataForVisibleNode(): array
    {
        return [
            'visible node' => [
                '<div>' . str_repeat('<p>This <b>is</b> the awesome and WONDERFUL content :)</p>', 7) . '</div>',
                true,
            ],
            'display=none' => [
                '<div style="display:none;">' . str_repeat('<p>This <b>is</b> the awesome and WONDERFUL content :)</p>', 7) . '</div>',
                false,
            ],
            'display=inline' => [
                '<div style="display:inline;">' . str_repeat('<p>This <b>is</b> the awesome and WONDERFUL content :)</p>', 7) . '</div>',
                true,
            ],
            'hidden attribute' => [
                '<div hidden>' . str_repeat('<p>This <b>is</b> the awesome and WONDERFUL content :)</p>', 7) . '</div>',
                false,
            ],
            'missing display' => [
                '<div style="color:#ccc;">' . str_repeat('<p>This <b>is</b> the awesome and WONDERFUL content :)</p>', 7) . '</div>',
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataForVisibleNode
     */
    public function testVisibleNode(string $content, bool $shouldBeVisible): void
    {
        $readability = $this->getReadability($content, 'http://0.0.0.0');
        $readability->debug = true;
        $res = $readability->init();

        if ($shouldBeVisible) {
            $this->assertStringContainsString('WONDERFUL content', $readability->getContent()->getInnerHtml());
        } else {
            $this->assertStringNotContainsString('WONDERFUL content', $readability->getContent()->getInnerHtml());
        }
    }

    private function getReadability(string $html, ?string $url = null, string $parser = 'libxml', bool $useTidy = true): Readability
    {
        $readability = new Readability($html, $url, $parser, $useTidy);

        $this->logHandler = new TestHandler();
        $this->logger = new Logger('test', [$this->logHandler]);
        $readability->setLogger($this->logger);

        return $readability;
    }
}
