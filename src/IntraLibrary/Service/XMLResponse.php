<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;
use \DOMDocument;
use \DOMXPath;
use \DOMNode;

/**
 * IntraLibrary XML Response base class
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
abstract class XMLResponse
{

    protected $xPath;

    /**
     * Load XML into the response object
     *
     * @param string $xmlResponse the XML response
     * @return void
     */
    public function load($xmlResponse)
    {
        if (empty($xmlResponse)) {
            return;
        }

        $xmlDocument = new DOMDocument();
        $xmlDocument->loadXML($xmlResponse);

        $this->xPath = new DOMXPath($xmlDocument);

        $this->consumeDom();
    }

    /**
     * Run an xPath query
     *
     * @param string  $expression  the xpath expression
     * @param DOMNode $contextNode the context node
     * @return DOMNodeList
     */
    public function xQuery($expression, DOMNode $contextNode = null)
    {
        if ($this->xPath) {
            return $this->xPath->query($expression, $contextNode);
        }

        return new \DOMNodeList();
    }

    /**
     * Get the text value of a node (or an array of values if multiple nodes are matched
     * by the expression)
     *
     * @param string  $expression  the xpath expression targeting the text node
     * 		                       element (without the trailing '/text()' function)
     * @param DOMNode $contextNode the context node used in the xpath query
     * @param boolean $wrap        if true, single results will be wrapped in an array
     * @return string | array
     */
    public function getText($expression, DOMNode $contextNode = null, $wrap = false)
    {
        if (!$this->xPath) {
            return null;
        }

        // XXX: PHP 5.3.2 seems to require $contextNode to be omitted
        // from the function's arguments if it is null
        // 5.3.6 seems to handle this naturally...
        if ($contextNode === null) {
            $domNodeList = $this->xPath->query($expression . '/text()');
        } else {
            $domNodeList = $this->xPath->query($expression . '/text()', $contextNode);
        }


        if ($domNodeList && $domNodeList->length > 0) {
            if ($domNodeList->length == 1 && !$wrap) {
                return $domNodeList->item(0)->wholeText;
            }

            $text = array();
            foreach ($domNodeList as $item) {
                $text[] = $item->wholeText;
            }
            return $text;
        }

        return null;
    }

    /**
     * Subclasses should use this function to traverse the dom using xpath
     * and store any data for future access.
     *
     * @return void
     */
    abstract protected function consumeDom();
}

