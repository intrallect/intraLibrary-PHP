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
     * Get collection id, name associative array
     *
     * @param boolean $usingAdmin if true, will use the admin account to retrive taxonomies
     * @param boolean $useCache   if true will attempt to use cached taxonomies
     * @return array
     */
    public function getAvailableCollections($usingAdmin = false, $useCache = true)
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
            $collections[ $col["_attributes"]["id"] ] = $col["_attributes"]["name"];
        }

        Cache::save($key, $collections);

        return $collections;
    }
}

