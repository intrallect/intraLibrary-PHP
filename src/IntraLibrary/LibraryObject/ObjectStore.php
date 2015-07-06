<?php

namespace IntraLibrary\LibraryObject;

use \IntraLibrary\Configuration;
use \IntraLibrary\Cache;
use \IntraLibrary\Service\XSearchRequest;
use \IntraLibrary\Service\RESTRequest;
use \IntraLibrary\Service\SRWResponse;

/**
 * ObjectStore is a cache for IntraLibrary objects, both global
 * and user-specific
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class ObjectStore
{
    private $username;

    /**
     * Create an IntraLibrary Object Store
     *
     * @param string $username the intralibrary user that should access.
     *                         if null, will use the username set in the Configuration
     */
    public function __construct($username = null)
    {
        $this->username = $username ? $username : Configuration::get('username');
    }

    /**
     * Get all of the objects the current user has access to.
     *
     * @param string  $resourceType "Type of Resource" to filter by
     * @param string  $taxonSource  The taxon source
     * @param integer $limit        the maximum number of items to get
     * @return array<Object> an array of items/records
     */
    public function getObjectsByType($resourceType, $taxonSource = null, $limit = false)
    {
        return $this->getObjects(array('type' => $resourceType, 'source' => $taxonSource), $limit);
    }

    /**
     * Retrieve objects from intralibrary
     *
     * @param array   $params an associative array of metadata query parameters
     * @param integer $limit  the maximum number of items to get
     * @return array<Object>
     */
    public function getObjects(array $params, $limit = false)
    {
        // Generate the cache key
        $key 		= "objects//user:$this->username";

        foreach ($params as $name => $value) {
            $key   .= "//$name:$value";
        }

        if ($limit !== false) {
            $key   .= "//limit:$limit";
        }

        // Try the cache
        if (($cached = Cache::load($key)) !== false) {
            return $cached;
        }

        // Shortcuts to metadata params
        $paramMap = array(
            'type' => 'lom.educational_learningResourceType',
            'taxon' => 'lom.classification_taxonpath_taxon_id',
            'source' => 'lom.classification_taxonpath_source',
            'catalog' => 'lom.general_catalogentry_entry'
        );

        $xsResp = new SRWResponse('lom');
        $xsReq = new XSearchRequest($xsResp);
        $xsReq->setXSearchUsername($this->username);

        // generate query conditions
        $conditions	= array();
        array_walk(
            $params,
            // @codingStandardsIgnoreStart
            function ($value, $key) use ($paramMap, &$conditions) {
                $conditions[] = (isset($paramMap[$key]) ? $paramMap[$key] : $key) . '="' . $value . '"';
            }
            // @codingStandardsIgnoreEnd
        );

        $xsReq->query(array('query' => implode(' AND ', $conditions), 'limit' => $limit));

        $data = $xsResp->getRecords();

        Cache::save($key, $data);

        return $data;
    }

    /**
     * Get an IntraLibrary object by it's catalog entry
     *
     * @param string $catalogEntry the catalog entry
     * @return \IntraLibrary\LibraryObject\Record
     */
    public function getObjectByCatalogEntry($catalogEntry)
    {
        if (!$catalogEntry) {
            return null;
        }

        $data = $this->getObjects(array('catalog' => $catalogEntry));

        return isset($data[0]) ? $data[0] : null;
    }

    /**
     * Get the user groups
     *
     * @return array
     */
    public function getGroups($useCache = true)
    {
        $key = 'groups';

        $cached = Cache::load($key);
        if ($cached !== false && $useCache) {
            return $cached;
        }

        $req  = new RESTRequest();
        $data = $req->adminGet('Group')->getData();
        $groups = array();

        // if there's only one group, need to wrap it in an array
        if (isset($data['list']['group']['id'])) {
            $data['list']['group'] = array($data['list']['group']);
        }

        foreach ($data['list']['group'] as $group) {
            $groups[$group['id']] = $group;
        }
        Cache::save($key, $groups);

        return $groups;
    }

    /**
     * Create a new group
     *
     * @param string $name
     * @param string $description
     * @return array
     */
    public function createGroup($name, $description)
    {
        $req = new RESTRequest();
        $data = $req->adminGet('Group/create', array('group_name' => $name, 'group_description' => $description))->getData();
        return $data;
    }

    public function deleteResource($resourceId)
    {
        $req = new RESTRequest();
        $data = $req->adminGet('LearningObject/delete/' . $resourceId)->getData();
        return $data;
    }
}

