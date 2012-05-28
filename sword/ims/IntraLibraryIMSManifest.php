<?php

/**
 * IMS Manifest builder
 */
class IntraLibraryIMSManifest
{
	private $variables;
	
	public function __construct()
	{
		$this->variables = array(
				'TaxonPaths' => array(),
				'Descriptions' => array()
		);
	}
	
	/**
	 * Set a template variable
	 * 
	 * @param unknown_type $variable
	 * @param unknown_type $value
	 */
	private function _set($variable, $value)
	{
		$this->variables[$variable] = $value;
	}
	
	/**
	 * Magic function to intercept 'set*' & 'get*' function calls
	 * 
	 * @param string $name
	 * @param array  $arguments
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
	 * @param mixed $data
	 * @throws Exception if sanitisation failed
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
			$clean 		= htmlentities($data, ENT_QUOTES, 'UTF-8');
			
			if ($hasData && $clean === '')
			{
				throw new Exception("Unable to sanitise data for the IMS Manifest. Are there illegal characters in your metadata?\n---------\n$data\n---------\n");
			}
		}
		
		return $clean;
	}
	
	/**
	 * Add a taxonomy classification
	 * 
	 * @param string $refId
	 * @param string $name
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
	 * @param string $description
	 * @return void
	 */
	public function addDescription($description)
	{
		$this->variables['Descriptions'][] = $description;
	}
	
	/**
	 * Save the ims manifest
	 * 
	 * @param string $filepath
	 */
	public function save($filepath)
	{
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
		$manifestXML = ob_get_contents();
		ob_end_clean();
		
		// and write out
		return file_put_contents($filepath, $manifestXML);
	}
}