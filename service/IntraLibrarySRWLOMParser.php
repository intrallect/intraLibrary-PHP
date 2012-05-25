<?php

/**
 * XPath helper used to parse SRW Requests with a LOM record schema
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibrarySRWLOMParser extends IntraLibrarySRWParser
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

	/**
	 * (non-PHPdoc)
	 *
	 * @see IntraLibrarySWRLOMXPathHelper::getXPathMapping()
	 *
	 * @return array the XPath mapping
	 */
	public function getXPathMapping()
	{
		return array(
			'id'          => './/lom:general/lom:identifier/lom:entry',
			'catalog'	  => './/lom:general/lom:identifier/lom:catalog',
			'title'       => './/lom:general/lom:title/lom:string',
			'description' => './/lom:general/lom:description/lom:string',
			'format'      => './/lom:technical/lom:format',
			'technical_location' => './/lom:technical/lom:location',
			'type'        => './/lom:educational/lom:learningResourceType/lom:value',
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see IntraLibrarySRWXParser::getClassifications()
	 * 
	 * @param IntraLibraryXMLResponse $xmlResponse the xml response
	 * @param DOMElement              $domElement  the dom element
	 * @return array
	 */
	public function getClassifications(IntraLibraryXMLResponse $xmlResponse, DOMElement $domElement)
	{
		$classifications = array();
		if (!($taxonPaths = $xmlResponse->xQuery('.//lom:classification/lom:taxonPath', $domElement)))
		{
			return $classifications;
		}
		
		foreach ($taxonPaths as $taxonPath)
		{
			if (!($lomSource = $xmlResponse->getText('.//lom:source/lom:string', $taxonPath)) ||
				!($taxons = $xmlResponse->xQuery('.//lom:taxon', $taxonPath)))
			{
				continue;
			}
			
			if (empty($classifications[$lomSource]))
			{
				$classifications[$lomSource] = array();
			}
			
			$lomClassifications = array();
			foreach ($taxons as $taxon)
			{
				$refId 	= $xmlResponse->getText('.//lom:id', $taxon);
				$name 	= $xmlResponse->getText('.//lom:entry/lom:string', $taxon);
				$lomClassifications[$refId] = $name;
			}
			
			$classifications[$lomSource][] = $lomClassifications;
		}
		
		return $classifications;
	}

}