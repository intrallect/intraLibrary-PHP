<?php 

/**
 * IntraLibrary SRW response class
 * 
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibrarySRWResponse extends IntraLibraryXMLResponse
{
	private static $swrParserFields = array('id', 'catalog', 'title', 'description', 'type');
	
	/**
	 * The XPath Mapping helper
	 * 
	 * @var IntraLibrarySRWParser
	 */
	private $srwParser;
	private $recordSchema;
	private $records = array();
	private $totalRecords = 0;
	
	/**
	 * Create an IntraLibrary SRW Response object
	 * 
	 * @param string $recordSchema the record schema requested
	 * @throws Exception if the supplied record schema is not supported
	 */
	public function __construct($recordSchema = 'lom')
	{
		switch ($recordSchema)
		{
			case 'lom':
				$this->srwParser = new IntraLibrarySRWLOMParser();
				break;
			default:
				throw new Exception("No support for $recordSchema recordSchema");
		}
		
		$this->recordSchema = $recordSchema;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see IntraLibraryXMLResponse::consumeDOM()
	 * 
	 * @return void
	 */
	protected function consumeDOM()
	{
		$this->srwParser->initialise($this->xPath);
		
		$this->xPath->registerNamespace('package', 'info:srw/extension/13/package-v1.0');
		
		$this->records = array();
		$this->totalRecords = (int) $this->getText('/SRW:searchRetrieveResponse/SRW:numberOfRecords');
		
		$recordList = $this->xPath->query('/SRW:searchRetrieveResponse/SRW:records/SRW:record');
		if (!$recordList)
		{
			return;
		}
		
		$xPathMapping = $this->srwParser->getXPathMapping();
		
		foreach ($recordList as $recordElement)
		{
			$record = array();
			
			foreach (self::$swrParserFields as $field)
			{
				$record[$field] = $this->getText($xPathMapping[$field], $recordElement);
			}
			
			$record['classifications'] = $this->srwParser->getClassifications($this, $recordElement);
			
			// recordSchema agnostic fields
			$record['preview'] = $this->getText('.//package:packagePreviewLocator', $recordElement);
			$record['download'] = $this->getText('.//package:packageDownloadLocator', $recordElement);
			
			$this->records[] = new IntraLibraryObject($record);
		}
	}
	
	/**
	 * Get the records contained in the response
	 * 
	 * @return array an array of records from the response
	 */
	public function getRecords()
	{
		return $this->records;
	}
	
	/**
	 * Get the total number of records available
	 * 
	 * @return integer
	 */
	public function getTotalRecords()
	{
		return $this->totalRecords;
	}
	
	
	/**
	 * Get the record schema
	 * 
	 * @return string
	 */
	public function getRecordSchema()
	{
		return $this->recordSchema;
	}
}
