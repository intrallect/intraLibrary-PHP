<?php 
/**
 * IntraLibraryTaxonomyData is used to retrieve information
 * (IntraLibraryTaxonomyObject objects) about the available taxonomies
 * 
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class IntraLibraryTaxonomyData
{
	const CACHE_PREFIX_ID 	  = 'Id';
	const CACHE_PREFIX_REFID  = 'RefId';
	const CACHE_PREFIX_SOURCE = 'Source';
	
	private static $runtimeCache = array();
	
	/**
	 * Get an object cached by the runtime
	 * 
	 * @param string $key The cache key of the object to retrieve
	 * @return IntraLibraryTaxonomyObject
	 */
	private static function _runtimeCached($key)
	{
		return isset(self::$runtimeCache[$key]) ? self::$runtimeCache[$key] : NULL;
	}
	
	private $retrieveState;
	
	/**
	 * Retrieve an IntraLibraryTaxonomyObject by its ref id and source taxonomy
	 * 
	 * @param string $objectRefId the object ref id
	 * @param string $source      the source taxonomy
	 * @return IntraLibraryTaxonomyObject
	 */
	public function retrieveByRefId($objectRefId, $source)
	{
		if (!$objectRefId || !$source)
			return NULL;
		
		$this->_setRetrieveState($objectRefId, self::CACHE_PREFIX_REFID, 'taxon', $source);
		return $this->_retrieve();
	}
	
	/**
	 * Retrieve an IntraLibraryTaxonomyObject by its ID
	 * 
	 * @param integer $objectId the object id
	 * @param string  $type     either taxon or taxonomy
	 * @return IntraLibraryTaxonomyObject
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
	 * @return IntraLibraryTaxonomyObject
	 */
	public function retrieveBySource($source)
	{
		if (!$source)
			return NULL;
		
		$this->_setRetrieveState($source, self::CACHE_PREFIX_SOURCE, 'taxonomy');
		return $this->_retrieve();
	}
	
	/**
	 * Internal retrieve IntraLibraryTaxonomyObject logic
	 * 
	 * @param boolean $rebuild if False, will only search the caches
	 * @return IntraLibraryTaxonomyObject 
	 */
	private function _retrieve($rebuild = TRUE)
	{
		// Check all caches
		$cacheKey 	= $this->_getRetrieveStateCacheKey();
		$cached 	= IntraLibraryCache::load($cacheKey);
		if ($cached instanceof IntraLibraryTaxonomyObject)
		{
			return $cached;
		}
		
		$cached 	= self::_runtimeCached($cacheKey);
		if ($cached instanceof IntraLibraryTaxonomyObject)
		{
			return $cached;
		}
		
		if ($rebuild)
		{
			// Rebuild and check all caches again (without a rebuild)
			$this->getAvailableTaxonomies();
			
			return $this->_retrieve(FALSE);
		}
		
		return NULL;
	}
	
	/**
	 * Get all taxonomy information available to the currrent user
	 * 
	 * @param boolean $usingAdmin if true, will use the admin account to retrive taxonomies
	 * @return array
	 */
	public function getAvailableTaxonomies($usingAdmin = FALSE)
	{
		// Check if it's cached..
		$key = $usingAdmin ? 'taxonomies//admin' : 'taxonomies//user:' . IntraLibraryConfiguration::get('username');
		
		$taxonomyIds = IntraLibraryCache::load($key);
		if ($taxonomyIds !== FALSE)
		{
			return $taxonomyIds;
		}
		
		// Query the Taxonomy REST service
		$restReq 	= new IntraLibraryRESTRequest();
		$response 	= $usingAdmin ? $restReq->adminGet('Taxonomy') : $restReq->get('Taxonomy');
		
		// Response contains usable data
		$data = $response->getData();
		if (isset($data['list']['taxonomy']))
		{
			// parse the new response data
			$taxonomyIds	= $this->_parseTaxonomyData($data['list']['taxonomy'], 'taxonomy');
			
			IntraLibraryCache::save($key, $taxonomyIds);
			
			return $taxonomyIds;
		}
		
		return array();
	}
	
	/**
	 * Recursively parse response data from the webservice call, generate
	 * IntraLibraryTaxonomyObject objects and cache them.
	 * 
	 * @SuppressWarnings(PHPMD.ShortVariable)
	 * 
	 * @param array                      $object    The data to parse
	 * @param string                     $type      The type of taxonomy object
	 * @param IntraLibraryTaxonomyObject $parentObj The parent object
	 * @param string                     $source    The taxonomy source
	 * @throws IntraLibraryException if the $type is invalid
	 * @return array an array of taxonomy object ids contained at the highest level of the $object data
	 */
	private function _parseTaxonomyData($object, $type, IntraLibraryTaxonomyObject $parentObj = NULL, $source = NULL)
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
			$taxonomyObj = new IntraLibraryTaxonomyObject($type, $object['_attributes']);
			$taxonomyObj->setParent($parentObj);
			
			// only update the 'source' if this is a TAXONOMY
			if ($type == IntraLibraryTaxonomyObject::TAXONOMY)
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
	 * @param IntraLibraryTaxonomyObject $object the taxonomy object
	 * @return string
	 */
	public function getSource(IntraLibraryTaxonomyObject $object)
	{
		if (!$object)
		{
			return NULL;
		}
		
		if ($object->getType() == IntraLibraryTaxonomyObject::TAXON)
		{
			$parent	= $this->retrieveById($object->getParentId(), $object->getParentType());
			return $this->getSource($parent);
		}
		
		if ($object->getType() == IntraLibraryTaxonomyObject::TAXONOMY)
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
	 * @param IntraLibraryTaxonomyObject $object the object
	 * @param string                     $source the taxonomy source of this object
	 * @return void
	 */
	private function _cacheObject(IntraLibraryTaxonomyObject $object, $source)
	{
		$type 		= $object->getType();
		
		// Use IntraLibrary's Caching mechanism
		// and fallback on the runtime cache if it fails
		$key_id 	= $this->_getCacheKey($object->getId(), self::CACHE_PREFIX_ID, $type);
		if (IntraLibraryCache::save($key_id, $object, 0) === FALSE)
		{
			self::$runtimeCache[$key_id] 	= $object;
		}
		
		if ($type == IntraLibraryTaxonomyObject::TAXON)
		{
			$key_refId	= $this->_getCacheKey($object->getRefId(), self::CACHE_PREFIX_REFID, $type, $source);
			if (IntraLibraryCache::save($key_refId, $object, 0) === FALSE)
			{
				self::$runtimeCache[$key_refId] = $object;
			}
		}
		else if ($type == IntraLibraryTaxonomyObject::TAXONOMY)
		{
			$key_source	= $this->_getCacheKey($object->getSource(), self::CACHE_PREFIX_SOURCE, $type);
			if (IntraLibraryCache::save($key_source, $object, 0) === FALSE)
			{
				self::$runtimeCache[$key_source] = $object;
			}
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