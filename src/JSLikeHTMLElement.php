<?php

// SPDX-FileCopyrightText: 2011 Keyvan Minoukadeh - http://www.keyvan.net - keyvan@keyvan.net
// SPDX-License-Identifier: Apache-2.0

namespace Readability;

/**
 * Wrapper for DOMElement adding methods for accessing string representation of inner HTML contents.
 *
 * Inspired by JavaScript innerHTML property.
 * https://developer.mozilla.org/en-US/docs/Web/API/Element/innerHTML
 *
 * Example usage:
 *
 * ```php
 * $doc = new DOMDocument();
 * $doc->loadHTML('<div><p>Para 1</p><p>Para 2</p></div>');
 * $elem = $doc->getElementsByTagName('div')->item(0);
 *
 * // Get inner HTML
 * assert($elem->getInnerHtml() === '<p>Para 1</p><p>Para 2</p>');
 *
 * // Set inner HTML
 * $elem->setInnerHtml('<a href="http://fivefilters.org">FiveFilters.org</a>');
 * assert($elem->getInnerHtml() === '<a href="http://fivefilters.org">FiveFilters.org</a>');
 *
 * // print document (with our changes)
 * echo $doc->saveXML();
 * ```
 */
final class JSLikeHTMLElement extends \DOMElement
{
    /**
     * Sets inner HTML.
     */
    public function setInnerHtml(string $value): void
    {
        // first, empty the element
        if (isset($this->childNodes)) {
            for ($x = $this->childNodes->length - 1; $x >= 0; --$x) {
                $this->removeChild($this->childNodes->item($x));
            }
        }

        // $value holds our new inner HTML
        $value = trim($value);
        if (empty($value)) {
            return;
        }

        // ensure bad entity won't generate warning
        $previousError = libxml_use_internal_errors(true);

        $f = $this->ownerDocument->createDocumentFragment();

        // appendXML() expects well-formed markup (XHTML)
        $result = $f->appendXML($value);
        if ($result) {
            if ($f->hasChildNodes()) {
                $this->appendChild($f);
            }
        } else {
            // $value is probably ill-formed
            $f = new \DOMDocument();

            // Using <htmlfragment> will generate a warning, but so will bad HTML
            // (and by element point, bad HTML is what we've got).
            // We use it (and suppress the warning) because an HTML fragment will
            // be wrapped around <html><body> tags which we don't really want to keep.
            // Note: despite the warning, if loadHTML succeeds it will return true.
            $result = $f->loadHTML('<meta charset="utf-8"><htmlfragment>' . $value . '</htmlfragment>');

            if ($result) {
                $import = $f->getElementsByTagName('htmlfragment')->item(0);

                foreach ($import->childNodes as $child) {
                    $importedNode = $this->ownerDocument->importNode($child, true);
                    $this->appendChild($importedNode);
                }
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previousError);
    }

    /**
     * Gets inner HTML.
     */
    public function getInnerHtml(): string
    {
        $inner = '';

        if (isset($this->childNodes)) {
            foreach ($this->childNodes as $child) {
                $inner .= $this->ownerDocument->saveXML($child);
            }
        }

        return $inner;
    }
}
