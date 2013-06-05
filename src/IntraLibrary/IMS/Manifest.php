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
    private function setVariable($variable, $value)
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
        if (strpos($name, 'set') === 0 && count($arguments) > 0) {
            $variable = substr($name, 3);
            if (strlen($variable) > 0) {
                $this->setVariable($variable, $this->sanitiseForXml($arguments[0]));
            }
        } elseif (strpos($name, 'get') === 0) {
            $variable = substr($name, 3);
            if (strlen($variable) > 0) {
                return isset($this->variables[$variable]) ? $this->variables[$variable] : null;
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
    private function sanitiseForXml($data)
    {
        if (is_array($data)) {
            $clean = array();
            foreach ($data as $key => $value) {
                $key = $this->sanitiseForXml($key);
                $value = $this->sanitiseForXml($value);
                $clean[$key] = $value;
            }
        } else {
            $data 		= trim((string) $data);
            $hasData 	= $data !== '';
            $clean 		= @htmlentities($data, ENT_QUOTES, $this->charset);

            if ($hasData && $clean === '') {
                $message = "Are there illegal characters in your metadata?\n---------\n$data\n---------\n";
                throw new \Exception("Unable to sanitise data for the IMS Manifest. $message");
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
                'source' => $this->sanitiseForXml($source),
                'taxons' => $this->sanitiseForXml($taxons)
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
        $this->variables['Descriptions'][] = $this->sanitiseForXml($description);
    }

    /**
     * Save the ims manifest
     *
     * @param string $filepath the filepath to save to
     * @return integer|false the number of bytes written, or false if failed
     */
    public function save($filepath)
    {
        // and write out
        return file_put_contents($filepath, $this->getXml());
    }

    /**
     * Get the ims manifest in xml
     *
     * @return string
     */
    public function getXml()
    {
        if (!isset($this->xml)) {

            // set a random identifier if we haven't received one yet
            if (empty($this->variables['MainIdentifier'])) {
                $this->variables['MainIdentifier'] = md5(uniqid('', true));
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

