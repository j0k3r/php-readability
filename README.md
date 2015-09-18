# Readability

[![Build Status](https://travis-ci.org/j0k3r/php-readability.svg?branch=master)](https://travis-ci.org/j0k3r/php-readability)
[![Code Coverage](https://scrutinizer-ci.com/g/j0k3r/php-readability/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/j0k3r/php-readability/?branch=master)

This is an extract of the Readability class from the [full-text-rss](https://github.com/Dither/full-text-rss) fork. It kind be defined as a better version of the original [php-readability](https://bitbucket.org/fivefilters/php-readability/overview).

## Differences

The default php-readability lib is really old and needs to be improved. I found a great fork of [full-text-rss](http://fivefilters.org/content-only/) from @Dither which improve the Readability class.

 - I've extracted the class from its fork to be able to use it out of the box
 - I've added some simple tests
 - and changed the CS, run `php-cs-fixer` and added a namespace

**But** the code is still really hard to understand / read ...

## Usage

```php
use Readability\Readability;

$url = 'http://www.medialens.org/index.php/alerts/alert-archive/alerts-2013/729-thatcher.html';

// you can use whatever you want to retrieve the html content (Guzzle, Buzz, cURL ...)
$html = file_get_contents($url);

$readability = new Readability($html, $url);
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
