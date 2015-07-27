<?php

namespace IntraLibrary\LibraryObject;

use \IntraLibrary\IntraLibraryException;
use \IntraLibrary\Cache;
use \IntraLibrary\Configuration;
use \IntraLibrary\Service\RESTRequest;

/**
 * CollectionData is used to retrieve information
 *
 * @package IntraLibrary_PHP
 * @author  Bence Laky, <b.laky@intrallect.com>
 *
 */
class CollectionData
{

    /**
     * Create a new collection
     *
     * @param string $name
     * @param string $description
     * @param string $identifier
     * @param array $permissions array of the following permissions: find, preview, contribute, export, annotate
     * @param string $externallySearchable
     * @return array
     */
    public function createCollection(
        $name,
        $description,
        $identifier,
        $permissions = array('find', 'contribute'),
        $externallySearchable = true
    ) {
        $req = new RESTRequest();
        $data = $req->adminGet(
            'Collection/create',
            array(
                'collection_name' => $name,
                'collection_description' => $description,
                'collection_identifier' => $identifier,
                'collection_permissions' => join(',', $permissions),
                'collection_externally_searchable' => $externallySearchable
            )
        )->getData();
        return $data;
    }

    /**
     * Get collection id, name associative array
     *
     * @param boolean $usingAdmin if true, will use the admin account to retrive taxonomies
     * @param boolean $useCache   if true will attempt to use cached taxonomies
     * @return array
     */
    public function getAvailableCollections($usingAdmin = false, $useCache = true, $details = false)
    {
        $key = $usingAdmin ? 'collection//admin' : 'collection//user:' . Configuration::get('username');

        if ($useCache) {
            // Check if it's cached..
            $collections = Cache::load($key);
            if ($collections !== false) {
                return $collections;
            }
        }

        // Query the Collection REST service
        $restReq 	= new RESTRequest();
        $response 	= $usingAdmin ? $restReq->adminGet('Collection') : $restReq->get('Collection');

        // Response contains usable data
        $data = $response->getData();
        $data = $data['list']['collection'];

        $collections = array();

        if (isset($data['_attributes'])) {
            $data = array($data);
        }

        foreach ($data as $col) {
            $collections[ $col["_attributes"]["id"] ] = $details ? $col["_attributes"] : $col["_attributes"]["name"];
        }

        Cache::save($key, $collections);

        return $collections;
    }

    /**
     *  Set (or remove) a collection permission override for a group
     *
     * @param string $collectionIdentifier
     * @param int $groupId
     * @param array $permissions array of the following permissions: find, preview, contribute, export, annotate
     * @param string $removeGroupOverride
     * @return array
     */
    public function overrideGroupPermissions(
        $collectionIdentifier,
        $groupId,
        $permissions = array(),
        $removeGroupOverride = false
    ) {
        $req = new RESTRequest();
        $data = $req->adminGet(
            'Collection/overrideGroupPermissions',
            array(
                'collection_identifier' => $collectionIdentifier,
                'group_id' => $groupId,
                'collection_permissions' => join(',', $permissions),
                'collection_remove_group_override' => $removeGroupOverride
            )
        )->getData();
        return $data;
    }
}

