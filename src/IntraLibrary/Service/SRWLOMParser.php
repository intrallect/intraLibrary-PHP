<?php

namespace IntraLibrary\Service;

use \DOMXPath;
use \DOMElement;
use \IntraLibrary\IntraLibraryException;
use \IntraLibrary\Service\XMLResponse;

/**
 * XPath helper used to parse SRW Requests with a LOM record schema
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class SRWLOMParser extends SRWParser
{

    /**
     * Configure the LOM namespace
     *
     * @param DOMXPath $xPath the xpath object that will be used to consume the dom
     * @return void
     */
    public function initialise(DOMXPath $xPath)
    {
        $xPath->registerNamespace('lom', 'http://ltsc.ieee.org/xsd/LOM');
    }

    // @codingStandardsIgnoreStart
    /**
     * (non-PHPdoc)
     *
     * @see SWRLOMXPathHelper::getXPathMapping()
     *
     * @return array the XPath mapping
     */
    public function getXPathMapping()
    // @codingStandardsIgnoreEnd
    {
        return array(
            'id'          => './/lom:general/lom:identifier/lom:entry',
            'catalog'	  => './/lom:general/lom:identifier/lom:catalog',
            'title'       => './/lom:general/lom:title/lom:string',
            'description' => './/lom:general/lom:description/lom:string',
            'format'      => './/lom:technical/lom:format',
            'size'        => './/lom:technical/lom:size',
            'technical_location' => './/lom:technical/lom:location',
            'type'        => './/lom:educational/lom:learningResourceType/lom:value'
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see SRWXParser::getClassifications()
     *
     * @param XMLResponse $xmlResponse the xml response
     * @param DOMElement  $domElement  the dom element
     * @return array
     */
    public function getClassifications(XMLResponse $xmlResponse, DOMElement $domElement)
    {
        $classifications = array();
        if (!($taxonPaths = $xmlResponse->xQuery('.//lom:classification/lom:taxonPath', $domElement))) {
            return $classifications;
        }

        foreach ($taxonPaths as $taxonPath) {
            if (!($lomSource = $xmlResponse->getText('.//lom:source/lom:string', $taxonPath))
                    || !($taxons = $xmlResponse->xQuery('.//lom:taxon', $taxonPath))) {
                continue;
            }

            if (empty($classifications[$lomSource])) {
                $classifications[$lomSource] = array();
            }

            $lomClassifications = array();
            foreach ($taxons as $taxon) {
                $refId 	= $xmlResponse->getText('.//lom:id', $taxon);
                $name 	= $xmlResponse->getText('.//lom:entry/lom:string', $taxon);
                $lomClassifications[$refId] = $name;
            }

            $classifications[$lomSource][] = $lomClassifications;
        }

        return $classifications;
    }
}

