<?php

namespace IntraLibrary\IMS;

/**
 * IMS Manifest builder
 *
 * @package IntraLibrary_PHP
 */
class Manifest
{
	private $xml;
	private $variables;
	private $charset;

	/**
	 * Create a Manfiest object
	 *
	 * @param string $charset the charset to use
	 */
	public function __construct($charset = 'UTF-8')
	{
		$this->charset = $charset;
		$this->variables = array(
				'TaxonPaths' => array(),
				'Descriptions' => array()
		);
	}

	/**
	 * Set a template variable
	 *
	 * @param string $variable the variable name
	 * @param mixed  $value    the value
	 * @return void
	 */
	private function _set($variable, $value)
	{
		$this->variables[$variable] = $value;
	}

	/**
	 * Magic function to intercept 'set*' & 'get*' function calls
	 *
	 * @param string $name      the name of the function
	 * @param array  $arguments the function arguments
	 * @return mixed
	 */
	public function __call($name, $arguments = array())
	{
		if (strpos($name, 'set') === 0 && count($arguments) > 0)
		{
			$variable = substr($name, 3);
			if (strlen($variable) > 0)
			{
				$this->_set($variable, $this->_sanitiseForXML($arguments[0]));
			}
		}
		else if (strpos($name, 'get') === 0)
		{
			$variable = substr($name, 3);
			if (strlen($variable) > 0)
			{
				return isset($this->variables[$variable]) ? $this->variables[$variable] : NULL;
			}
		}
	}

	/**
	 * Sanitise data for XML
	 *
	 * @param mixed $data the data to sanitize
	 * @throws Exception if sanitisation failed
	 * @return string the clean data
	 */
	private function _sanitiseForXML($data)
	{
		if (is_array($data))
		{
			$clean = array();
			foreach ($data as $key => $value)
			{
				$key = $this->_sanitiseForXML($key);
				$value = $this->_sanitiseForXML($value);
				$clean[$key] = $value;
			}
		}
		else
		{
			$data 		= trim((string) $data);
			$hasData 	= $data !== '';
			$clean 		= @htmlentities($data, ENT_QUOTES, $this->charset);

			if ($hasData && $clean === '')
			{
				throw new Exception("Unable to sanitise data for the IMS Manifest. Are there illegal characters in your metadata?\n---------\n$data\n---------\n");
			}

			// convert named to numeric, for XML support
			$clean 		= EncodingUtils::htmlConvertEntities($clean);
		}

		return $clean;
	}

	/**
	 * Add a taxonomy classification
	 *
	 * @param string $source the source of the classification
	 * @param array  $taxons thte taxons for this classification
	 * @return void
	 */
	public function addClassification($source, $taxons)
	{
		$this->variables['TaxonPaths'][] = array(
				'source' => $this->_sanitiseForXML($source),
				'taxons' => $this->_sanitiseForXML($taxons)
		);
	}

	/**
	 * Add a description
	 *
	 * @param string $description the description
	 * @return void
	 */
	public function addDescription($description)
	{
		$this->variables['Descriptions'][] = $this->_sanitiseForXML($description);
	}

	/**
	 * Save the ims manifest
	 *
	 * @param string $filepath the filepath to save to
	 * @return integer|FALSE the number of bytes written, or FALSE if failed
	 */
	public function save($filepath)
	{
		// and write out
		return file_put_contents($filepath, $this->getXML());
	}

	/**
	 * Get the ims manifest in xml
	 *
	 * @return string
	 */
	public function getXML() {

		if (!isset($this->xml)) {

			// set a random identifier if we haven't received one yet
			if (empty($this->variables['MainIdentifier']))
			{
				$this->variables['MainIdentifier'] = md5(uniqid('', TRUE));
			}

			// bring all variables into scope
			extract($this->variables);

			// process the template into a variable
			ob_start();
			include dirname(__FILE__) . '/imsmanifest.xml.php';
			$this->xml = ob_get_contents();
			ob_end_clean();
		}

		return $this->xml;
	}
}
