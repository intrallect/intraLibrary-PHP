<?php

/**
 * An interface for SRW Response DOM consumers
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
abstract class IntraLibrarySRWParser
{
	private static $_PARSER_FIELDS = array(
			'id',
			'catalog',
			'title',
			'description',
			'type',
			'format'
	);
	
	/**
	 * Create a new IntraLibrarySRWParser and validate
	 * its implementation.
	 */
	public final function __construct()
	{
		$xPathFields = array_keys($this->getXPathMapping());
		if ($missingFields = array_diff(self::$_PARSER_FIELDS, $xPathFields))
		{
			throw new Exception(get_called_class() . ' does not support: ' . implode(',', $missingFields));
		}
	}
	
	/**
	 * Initialise the XPath helper
	 *
	 * @param DOMXPath $xPath the xpath object that will be used to consume the dom
	 * @return void
	 */
	abstract public function initialise(DOMXPath $xPath);

	/**
	 * Get a mapping of all relevant values [valueName => xPath]
	 * To be used in context (ie. as children and subchildren) of SWR:record
	 *
	 * @return void
	 */
	abstract public function getXPathMapping();
	
	/**
	 * Get all classifications under a dom element
	 *
	 * @param IntraLibraryXMLResponse $xmlResponse the xml response
	 * @param DOMElement              $domElement  the dom element
	 * @return array
	 */
	abstract public function getClassifications(IntraLibraryXMLResponse $xmlResponse, DOMElement $domElement);
}
