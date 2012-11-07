<?php

/**
 * IntraLibrary SRW response class
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibrarySRWResponse extends IntraLibraryXMLResponse
{
	private static $_namespaces = array(
			'SRW' =>            'http://www.loc.gov/zing/srw/',
			'DIAG' =>           'http://www.loc.gov/zing/srw/diagnostics',
			'package' => 		'info:srw/extension/13/package-v1.0',
			'intralibrary' => 	'info:srw/extension/13/intralibrary-v1.0',
			'review' => 		'info:srw/extension/13/review-v1.0',
			'record' => 		'http://srw.o-r-g.org/schemas/rec/1.0/'
	);

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
		foreach (self::$_namespaces as $prefix => $uri)
		{
			$this->xPath->registerNamespace($prefix, $uri);
		}

		$this->srwParser->initialise($this->xPath);

		$this->records = array();
		$this->totalRecords = (int) $this->getText('/SRW:searchRetrieveResponse/SRW:numberOfRecords');

		$recordList = $this->xPath->query('/SRW:searchRetrieveResponse/SRW:records/SRW:record');
		if (!$recordList)
		{
			return;
		}

		foreach ($recordList as $recordElement)
		{
			$record = array();

			foreach ($this->srwParser->getXPathMapping() as $field => $xPath)
			{
				$record[$field] = $this->getText($xPath, $recordElement);
			}

			$record['classifications'] = $this->srwParser->getClassifications($this, $recordElement);

			// record-schema-agnostic fields
			$record['packageId'] = $this->getText('.//package:packageResourceId', $recordElement);
			$record['preview'] = $this->getText('.//package:packagePreviewLocator', $recordElement);
			$record['download'] = $this->getText('.//package:packageDownloadLocator', $recordElement);
			$record['thumbnail'] = $this->getText('.//intralibrary:thumbnailLocation', $recordElement);
			$record['intralibraryType'] = $this->getText('.//intralibrary:type', $recordElement);
			$record['lastModified'] = $this->getText('.//record:record/record:lastModified', $recordElement);
			$record['created'] = $this->getText('.//record:record/record:created', $recordElement);

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

	/**
	 * Get the Query string
	 *
	 * @return Ambigous <string, NULL, multitype:NULL >
	 */
	public function getQuery()
	{
		return $this->getText('/SRW:searchRetrieveResponse/SRW:echoedSearchRetrieveRequest/SRW:query');
	}

	/**
	 * Get the diagnostics message, will be NULL unless there has been an error
	 *
	 * @return Ambigous <string, NULL, multitype:NULL > NULL unless there has been an error
	 */
	public function getDiagnosticsMessage()
	{
		return $this->getText('/SRW:searchRetrieveResponse/SRW:diagnostics/DIAG:diagnostic/DIAG:message');
	}

	/**
	 * Get the diagnostics details, will be NULL unless there has been an error
	 *
	 * @return Ambigous <string, NULL, multitype:NULL >
	 */
	public function getDiagnosticsDetails()
	{
		return $this->getText('/SRW:searchRetrieveResponse/SRW:diagnostics/DIAG:diagnostic/DIAG:details');
	}

	/**
	 * Get error data for this response
	 *
	 * @return NULL|string
	 */
	public function getError()
	{
		$error = $this->getDiagnosticsMessage();
		if (!$error)
		{
			return NULL;
		}

		if ($details = $this->getDiagnosticsDetails())
		{
			$error .= " [$details]";
		}

		if ($query = $this->getQuery())
		{
			$error .= " (query: $query)";
		}

		return $error;
	}
}
