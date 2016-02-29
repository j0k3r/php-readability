# Readability

[![Build Status](https://travis-ci.org/j0k3r/php-readability.svg?branch=master)](https://travis-ci.org/j0k3r/php-readability)
[![Coverage Status](https://coveralls.io/repos/j0k3r/php-readability/badge.svg?branch=master&service=github)](https://coveralls.io/github/j0k3r/php-readability/?branch=master)

This is an extract of the Readability class from this [full-text-rss](https://github.com/Dither/full-text-rss) fork. It can be defined as a better version of the original [php-readability](https://bitbucket.org/fivefilters/php-readability/overview).

## Differences

The default php-readability lib is really old and needs to be improved. I found a great fork of full-text-rss from [@Dither](https://github.com/Dither/full-text-rss) which improve the Readability class.

 - I've extracted the class from its fork to be able to use it out of the box
 - I've added some simple tests
 - and changed the CS, run `php-cs-fixer` and added a namespace

**But** the code is still really hard to understand / read ...

## Requirements

By default, this lib will use the [Tidy extension](https://github.com/htacg/tidy-html5) if it's available. Tidy is only used to cleanup the given HTML and avoid problems with bad HTML structure, etc ..

Since Composer doesn't support suggestion on PHP extension, I write this suggestion here.

## Usage

```php
use Readability\Readability;

$url = 'http://www.medialens.org/index.php/alerts/alert-archive/alerts-2013/729-thatcher.html';

// you can use whatever you want to retrieve the html content (Guzzle, Buzz, cURL ...)
$html = file_get_contents($url);

$readability = new Readability($html, $url);
// or without Tidy
// $readability = new Readability($html, $url, 'libxml', false);
$result = $readability->init();

if ($result) {
    // display the title of the page
    echo $readability->getTitle()->textContent;
    // display the *readability* content
    echo $readability->getContent()->textContent;
} else {
    echo 'Looks like we couldn\'t find the content. :(';
}
```

If you want to debug it, or check what's going on, you can inject a logger (which must follow `Psr\Log\LoggerInterface`, Monolog for example):

```php
use Readability\Readability;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$url = 'http://www.medialens.org/index.php/alerts/alert-archive/alerts-2013/729-thatcher.html';
$html = file_get_contents($url);

$logger = new Logger('readability');
$logger->pushHandler(new StreamHandler('path/to/your.log', Logger::DEBUG));

$readability = new Readability($html, $url);
$readability->setLogger($logger);
```
