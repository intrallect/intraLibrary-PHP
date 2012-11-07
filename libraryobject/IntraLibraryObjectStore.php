<?php

/**
 * IntraLibraryObjectStore is a cache for IntraLibrary objects, both global
 * and user-specific
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibraryObjectStore
{

	private $username;

	/**
	 * Create an IntraLibrary Object Store
	 */
	public function __construct()
	{
		$this->username = IntraLibraryConfiguration::get('username');
	}

	/**
	 * Get all of the objects the current user has access to.
	 *
	 * @param string  $resourceType "Type of Resource" to filter by
	 * @param string  $taxonSource  The taxon source
	 * @param integer $limit        the maximum number of items to get
	 * @return array<IntraLibraryObject> an array of items/records
	 */
	public function getObjectsByType($resourceType, $taxonSource = NULL, $limit = FALSE)
	{
		return $this->getObjects(array('type' => $resourceType, 'source' => $taxonSource), $limit);
	}

	/**
	 * Retrieve objects from intralibrary
	 *
	 * @param array   $params an associative array of metadata query parameters
	 * @param integer $limit  the maximum number of items to get
	 * @return array<IntraLibraryObject>
	 */
	public function getObjects(array $params, $limit = FALSE)
	{
		// Generate the cache key
		$key 		= "objects//user:$this->username";

		foreach ($params as $name => $value)
		{
			$key   .= "//$name:$value";
		}

		if ($limit !== FALSE)
		{
			$key   .= "//limit:$limit";
		}

		// Try the cache
		if (($cached = IntraLibraryCache::load($key)) !== FALSE)
		{
			return $cached;
		}

		// Shortcuts to metadata params
		$paramMap = array(
			'type' => 'lom.educational_learningResourceType',
			'taxon' => 'lom.classification_taxonpath_taxon_id',
			'source' => 'lom.classification_taxonpath_source',
			'catalog' => 'lom.general_catalogentry_entry'
		);

		$xsResp = new IntraLibrarySRWResponse('lom');
		$xsReq 	= new IntraLibraryXSearchRequest($xsResp);

		// generate query conditions
		$conditions	= array();
		array_walk($params, function($value, $key) use ($paramMap, &$conditions) {
			$conditions[] = (isset($paramMap[$key]) ? $paramMap[$key] : $key) . '=' . $value;
		});

		$xsReq->query(array('query' => implode(' AND ', $conditions), 'limit' => $limit));

		$data   = $xsResp->getRecords();

		// Sort objects by title
		usort($data, function($objectA, $objectB) {
			return strnatcmp($objectA->get('title'), $objectB->get('title'));
		});

		IntraLibraryCache::save($key, $data);

		return $data;
	}

	/**
	 * Get an IntraLibrary object by it's catalog entry
	 *
	 * @param string $catalogEntry the catalog entry
	 * @return array<IntraLibraryObject>
	 */
	public function getObjectByCatalogEntry($catalogEntry)
	{
		if (!$catalogEntry)
		{
			return NULL;
		}

		$key = 'object//catalogEntry:' . $catalogEntry;

		$object = IntraLibraryCache::load($key);
		if ($object !== FALSE)
		{
			return $object;
		}

		$xsResp = new IntraLibrarySRWResponse('lom');
		$xsReq  = new IntraLibraryXSearchRequest($xsResp);

		$xsReq->query(array('query' => 'lom.general_catalogentry_entry=' . $catalogEntry));

		$data  = $xsResp->getRecords();
		$data  = isset($data[0]) ? $data[0] : NULL;

		IntraLibraryCache::save($key, $data);

		return $data;
	}

	/**
	 * Get the user groups
	 *
	 * @return array
	 */
	public function getGroups()
	{
		$key = 'groups';

		$cached = IntraLibraryCache::load($key);
		if ($cached !== FALSE)
		{
			return $cached;
		}

		$req  = new IntraLibraryRESTRequest();
		$data = $req->adminGet('Group')->getData();
		$groups = array();

		foreach ($data['list']['group'] as $group)
		{
			$groups[$group['id']] = $group;
		}
		IntraLibraryCache::save($key, $groups);

		return $groups;
	}

}
