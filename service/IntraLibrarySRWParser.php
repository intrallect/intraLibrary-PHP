<?php

/**
 * An interface for SRW Response DOM consumers
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
interface IntraLibrarySRWParser
{

	/**
	 * Initialise the XPath helper
	 *
	 * @param DOMXPath $xPath the xpath object that will be used to consume the dom
	 * @return void
	 */
	public function initialise(DOMXPath $xPath);

	/**
	 * Get a mapping of all relevant values [valueName => xPath]
	 * To be used in context (ie. as children and subchildren) of SWR:record
	 *
	 * @return void
	 */
	public function getXPathMapping();
	
	/**
	 * Get all classifications under a dom element
	 *
	 * @param IntraLibraryXMLResponse $xmlResponse the xml response
	 * @param DOMElement              $domElement  the dom element
	 * @return array
	 */
	public function getClassifications(IntraLibraryXMLResponse $xmlResponse, DOMElement $domElement);
}
