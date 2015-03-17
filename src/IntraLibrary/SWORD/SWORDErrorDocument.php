<?php
/*
 * Modified from https://github.com/stuartlewis/swordapp-php-library/ (no longer exists)
 */
namespace IntraLibrary\SWORD;

class SWORDErrorDocument extends SWORDEntry
{
    // The error URI
    public $sac_erroruri;

    // Construct a new deposit response by passing in the http status code
    public function __construct($sac_newstatus, $sac_thexml)
    {
        // Call the super constructor
        parent::__construct($sac_newstatus, $sac_thexml);
    }

    // Build the error document hierarchy
    public function buildhierarchy($sac_dr, $sac_ns)
    {
        // Call the super version
        parent::buildhierarchy($sac_dr, $sac_ns);
        /*
         * foreach($sac_dr->attributes() as $key => $value) {
         * if ($key == 'href') {
         * //$this->sac_erroruri = (string)$value;
         * }
         * }
         */
        $this->sac_erroruri = (string) $sac_dr->attributes()->href;
    }
}

