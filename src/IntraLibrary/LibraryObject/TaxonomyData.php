<?php

namespace IntraLibrary\LibraryObject;

use \IntraLibrary\IntraLibraryException;
use \IntraLibrary\Cache;
use \IntraLibrary\Configuration;
use \IntraLibrary\Service\RESTRequest;

/**
 * TaxonomyData is used to retrieve information
 * (TaxonomyObject objects) about the available taxonomies
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class TaxonomyData
{
	const CACHE_PREFIX_ID 	  = 'Id';
	const CACHE_PREFIX_REFID  = 'RefId';
	const CACHE_PREFIX_SOURCE = 'Source';

	private static $runtimeCache = array();

	/**
	 * Get an object cached by the runtime
	 *
	 * @param string $key The cache key of the object to retrieve
	 * @return TaxonomyObject
	 */
	private static function _runtimeCached($key)
	{
		return isset(self::$runtimeCache[$key]) ? self::$runtimeCache[$key] : NULL;
	}

	private $retrieveState;

	/**
	 * Retrieve an TaxonomyObject by its ref id and source taxonomy
	 *
	 * @param string $objectRefId the object ref id
	 * @param string $source      the source taxonomy
	 * @return TaxonomyObject
	 */
	public function retrieveByRefId($objectRefId, $source)
	{
		if (!$objectRefId || !$source)
			return NULL;

		$this->_setRetrieveState($objectRefId, self::CACHE_PREFIX_REFID, 'taxon', $source);
		return $this->_retrieve();
	}

	/**
	 * Retrieve an TaxonomyObject by its ID
	 *
	 * @param integer $objectId the object id
	 * @param string  $type     either taxon or taxonomy
	 * @return TaxonomyObject
	 */
	public function retrieveById($objectId, $type = 'taxon')
	{
		if (!$objectId)
			return NULL;

		$this->_setRetrieveState($objectId, self::CACHE_PREFIX_ID, $type);
		return $this->_retrieve();
	}

	/**
	 * Retrieve an IntraLibaryTaxonomyObject taxonomy by its SOURCE identifier
	 *
	 * @param string $source the taxonomy source
	 * @return TaxonomyObject
	 */
	public function retrieveBySource($source)
	{
		if (!$source)
			return NULL;

		$this->_setRetrieveState($source, self::CACHE_PREFIX_SOURCE, 'taxonomy');
		return $this->_retrieve();
	}

	/**
	 * Internal retrieve TaxonomyObject logic
	 *
	 * @param boolean $rebuild if False, will only search the caches
	 * @return TaxonomyObject
	 */
	private function _retrieve($rebuild = TRUE)
	{
		// Check all caches
		$cacheKey 	= $this->_getRetrieveStateCacheKey();
		$cached 	= Cache::load($cacheKey);
		if ($cached instanceof TaxonomyObject)
		{
			return $cached;
		}

		$cached 	= self::_runtimeCached($cacheKey);
		if ($cached instanceof TaxonomyObject)
		{
			return $cached;
		}

		if ($rebuild)
		{
			// trigger a rebuild of the caches
			$this->getAvailableTaxonomies(FALSE, FALSE);

			// and check the caches again
			return $this->_retrieve(FALSE);
		}

		return NULL;
	}

	/**
	 * Get all taxonomy information available to the currrent user
	 *
	 * @param boolean $usingAdmin if true, will use the admin account to retrive taxonomies
	 * @param boolean $useCache   if true will attempt to use cached taxonomies
	 * @return array
	 */
	public function getAvailableTaxonomies($usingAdmin = FALSE, $useCache = TRUE)
	{
		$key = $usingAdmin ? 'taxonomies//admin' : 'taxonomies//user:' . Configuration::get('username');

		if ($useCache)
		{
			// Check if it's cached..
			$taxonomyIds = Cache::load($key);
			if ($taxonomyIds !== FALSE)
			{
				return $taxonomyIds;
			}
		}

		// Query the Taxonomy REST service
		$restReq 	= new RESTRequest();
		$response 	= $usingAdmin ? $restReq->adminGet('Taxonomy') : $restReq->get('Taxonomy');

		// Response contains usable data
		$data = $response->getData();
		if (isset($data['list']['taxonomy']))
		{
			// parse the new response data
			$taxonomyIds	= $this->_parseTaxonomyData($data['list']['taxonomy'], 'taxonomy');

			Cache::save($key, $taxonomyIds);

			return $taxonomyIds;
		}

		return array();
	}

	/**
	 * Recursively parse response data from the webservice call, generate
	 * TaxonomyObject objects and cache them.
	 *
	 * @SuppressWarnings(PHPMD.ShortVariable)
	 *
	 * @param array          $object    The data to parse
	 * @param string         $type      The type of taxonomy object
	 * @param TaxonomyObject $parentObj The parent object
	 * @param string         $source    The taxonomy source
	 * @throws IntraLibraryException if the $type is invalid
	 * @return array an array of taxonomy object ids contained at the highest level of the $object data
	 */
	private function _parseTaxonomyData($object, $type, TaxonomyObject $parentObj = NULL, $source = NULL)
	{
		// This is an array of objects
		if (!empty($object[0]))
		{
			$taxonomyObjectIds = array();
			foreach ($object as $o)
			{
				$objectIds = $this->_parseTaxonomyData($o, $type, $parentObj, $source);
				$taxonomyObjectIds = array_merge($taxonomyObjectIds, $objectIds);
			}
			return $taxonomyObjectIds;
		}
		else if (!empty($object['_attributes']))
		{
			$taxonomyObj = new TaxonomyObject($type, $object['_attributes']);
			$taxonomyObj->setParent($parentObj);

			// only update the 'source' if this is a TAXONOMY
			if ($type == TaxonomyObject::TAXONOMY)
			{
				$source = $taxonomyObj->getSource();
			}

			// if the object has a 'taxon' value, search it for children
			if (!empty($object['taxon']))
			{
				$childIds = $this->_parseTaxonomyData($object['taxon'], 'taxon', $taxonomyObj, $source);
				$taxonomyObj->setChildrenIds($childIds);
			}

			$this->_cacheObject($taxonomyObj, $source);

			return array($taxonomyObj->getId());
		}
		else
		{
			throw new IntraLibraryException('Invalid taxon data');
		}
	}

	/**
	 * Get the SOURCE of a taxonomy object
	 *
	 * @param TaxonomyObject $object the taxonomy object
	 * @return string
	 */
	public function getSource(TaxonomyObject $object)
	{
		if (!$object)
		{
			return NULL;
		}

		if ($object->getType() == TaxonomyObject::TAXON)
		{
			$parent	= $this->retrieveById($object->getParentId(), $object->getParentType());
			return $this->getSource($parent);
		}

		if ($object->getType() == TaxonomyObject::TAXONOMY)
		{
			return $object->getSource();
		}

		return NULL;
	}

	/**
	 * Get the source of an object from its ID
	 *
	 * @param string $objectId the object id
	 * @param string $type     the object type
	 * @return string
	 */
	public function getSourceFromId($objectId, $type = 'taxon')
	{
		return $this->getSource($this->retrieveById($objectId, $type));
	}

	/**
	 * Set the state for a retrieve action
	 *
	 * @param string $identifier  the identifier
	 * @param string $cachePrefix the cache prefix / identifier type
	 * @param string $type        the object type (taxon / taxonomy)
	 * @param string $source      the taxonomy source
	 * @return void
	 */
	private function _setRetrieveState($identifier, $cachePrefix, $type, $source = NULL)
	{
		$this->retrieveState = array(
			'type' => $type,
			'cachePrefix' => $cachePrefix,
			'identifier' => $identifier,
			'source' => $source
		);
	}

	/**
	 * Cache an intralibrary object
	 *
	 * @param TaxonomyObject $object the object
	 * @param string         $source the taxonomy source of this object
	 * @return void
	 */
	private function _cacheObject(TaxonomyObject $object, $source)
	{
		$type 		= $object->getType();

		$key_id 	= $this->_getCacheKey($object->getId(), self::CACHE_PREFIX_ID, $type);
		Cache::save($key_id, $object, 0);
		self::$runtimeCache[$key_id] = $object;

		if ($type == TaxonomyObject::TAXON)
		{
			$key_refId	= $this->_getCacheKey($object->getRefId(), self::CACHE_PREFIX_REFID, $type, $source);
			Cache::save($key_refId, $object, 0);
			self::$runtimeCache[$key_refId] = $object;
		}
		else if ($type == TaxonomyObject::TAXONOMY)
		{
			$key_source	= $this->_getCacheKey($object->getSource(), self::CACHE_PREFIX_SOURCE, $type);
			Cache::save($key_source, $object, 0);
			self::$runtimeCache[$key_source] = $object;
		}
	}

	/**
	 * Get a cache key based on the current retrieve state
	 *
	 * @return string
	 */
	private function _getRetrieveStateCacheKey()
	{
		$type = $cachePrefix = $identifier = $source = '';
		extract($this->retrieveState);

		return $this->_getCacheKey($identifier, $cachePrefix, $type, $source);
	}

	/**
	 * Get a cache key
	 *
	 * @param string $identifier  the identifier
	 * @param string $cachePrefix the cache prefix / identifier type
	 * @param string $type        the object type (taxon / taxonomy)
	 * @param string $source      the taxonomy source
	 * @return string
	 */
	private function _getCacheKey($identifier, $cachePrefix, $type, $source = '')
	{
		return "{$type}-{$cachePrefix}-{$source}_{$identifier}";
	}
}
