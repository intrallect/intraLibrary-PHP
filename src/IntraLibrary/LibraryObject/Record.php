<?php

namespace IntraLibrary\LibraryObject;

use \IntraLibrary\IntraLibraryException;
use IntraLibrary\Configuration;

/**
 * A Record representing an intraLibrary resource, created by an XSearch request
 * This is meant to replace the \IntraLibrary\LibraryObject\Object class, which is deprecated
 * because it was originally written without internal intraLibrary IDs in mind.
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class Record
{
    private $identifier;
    private $catalog;
    private $data;

    /**
     * Create an Object object
     *
     * @param array $data the object data
     */
    public function __construct($data)
    {
        $this->identifier	= $data['id']; // this is actually the catalog ID!
        $this->catalog 		= $data['catalog']; // this is the catalog name
        $this->data			= $data;
    }

    /**
     * Get the objects internal intraLibrary package id
     *
     * @return integer
     */
    public function getId()
    {
        return (integer) $this->get('packageId');
    }

    /**
     * Get this object's IntraLibrary catalog Id
     *
     * @param string $catalog [optional] if not null, return the Id associated with this catalog
     * @return string
     */
    public function getCatalogId($catalog = null)
    {
        if ($catalog != null) {
            if (is_array($this->catalog)) {
                $key = array_search($catalog, $this->catalog);
                if ($key !== false && isset($this->identifier[$key])) {
                    return $this->identifier[$key];
                }

                return null;
            } else {
                // If there's only one catalog entry, make sure it's the one being requested
                return $this->catalog == $catalog ? $this->identifier : null;
            }
        }

        // If no catalog is specified, return the first ID
        return is_array($this->identifier) ? $this->identifier[0] : $this->identifier;
    }

    public function getCatalog()
    {
        return $this->catalog;
    }

    /**
     * Get data for this object
     *
     * @param string $name the name of the data
     * @return mixed
     */
    public function get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * Get classifications for this object as an array keyed by their LOM source.
     *
     * @param integer $level [Optional] the classification level to retrieve (or null for all)
     * @return array<string>
     */
    public function getClassifications($level = null)
    {
        $rawClassifications = $this->get('classifications');
        $requestingLevel	= is_integer($level);
        if (!$requestingLevel) {
            return $rawClassifications;
        }

        $classifications = array();
        foreach ($rawClassifications as $lomSource => $taxonGroups) {
            if (empty($classifications[$lomSource])) {
                $classifications[$lomSource] = array();
            }

            foreach ($taxonGroups as $taxons) {
                if ($requestingLevel) {
                    $numTaxons = count($taxons);

                    if ($level > 0 && $level <= $numTaxons) {
                        $refIds = array_keys($taxons);
                        $refId = $refIds[$numTaxons - $level];
                        $taxon = $taxons[$refId];

                        $classifications[$lomSource][$refId] = $taxon;
                    }
                } else {
                    $classifications[$lomSource][] = $taxons;
                }
            }
        }

        return $classifications;
    }

    /**
     * Get the intraLibrary resource page url
     *
     * @return string
     */
    public function getUrl()
    {
        $packageId 	= $this->getId();
        $hostname 	= parse_url(Configuration::get('hostname'), PHP_URL_HOST);
        if (!$hostname) {
            $message = "Unable to generate record Url because intraLibrary hostname is not configured";
            throw new IntraLibraryException($message);
        }

        return "http://$hostname/IntraLibrary?command=work-area&resourcehome=$packageId";
    }
}

