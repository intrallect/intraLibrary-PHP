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
				'TaxonPaths' => array()
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
	 * Magic function to intercept 'set*' function calls
	 * 
	 * @param string $name
	 * @param array  $arguments
	 */
	public function __call($name, $arguments = array())
	{
		if (strpos($name, 'set') === 0 && count($arguments) > 0)
		{
			$variable = substr($name, 3);
			$this->_set($variable, $arguments[0]);
		}
	}
	
	/**
	 * Add a taxonomy classification
	 * 
	 * @param string $refId
	 * @param string $name
	 */
	public function addClassification($source, $taxons)
	{
		$this->variables['TaxonPaths'][] = array(
				'source' => $source, 
				'taxons' => $taxons
		);
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