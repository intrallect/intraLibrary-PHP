<?php

namespace IntraLibrary\LibraryObject;

use \IntraLibrary\IntraLibraryException;

/**
 * A lightweight object representing taxonomy data from the REST Taxonomy service
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class TaxonomyObject
{
    const TAXONOMY 	= 'taxonomy';
    const TAXON 	= 'taxon';

    private $type;
    private $identifier;
    private $name;
    private $description;
    private $refId;
    private $source;
    private $parentId 	= null;
    private $parentType = null;
    private $childIds 	= array();
    private $usefors		= null;

    /**
     * Create an IntraLibrary Taxonomy Object
     * (used to represent Taxons and Taxonomies)
     *
     * @param string $type The type of taxonomy object
     * @param array  $data The taxon object's data
     * @throws IntraLibraryException if the type does not match self::TAXON or self::TAXONOMY
     */
    public function __construct($type, $data)
    {
        $this->identifier	= $data['id'];
        $this->name 		= $data['name'];
        $this->description 	= $data['description'];
        $this->usefors 		= $data['usefors'];

        switch ($type) {
            case self::TAXON:
                $this->refId = $data['refId'];
                break;
            case self::TAXONOMY:
                $this->source = $data['source'];
                break;
            default:
                throw new IntraLibraryException('Invalid Taxonomy Object type');
        }

        $this->type 		= $type;
    }

    /**
     * Set this object's parent data
     *
     * @param TaxonomyObject $parent the parent object
     * @return void
     */
    public function setParent($parent)
    {
        if ($parent) {
            $this->parentId 	= $parent->getId();
            $this->parentType	= $parent->getType();
        } else {
            $this->parentId		= null;
            $this->parentType	= null;
        }
    }

    /**
     * Set the childen IDs
     *
     * @param array $childIds an array of children ids
     * @return void
     */
    public function setChildrenIds($childIds)
    {
        if (!is_array($childIds)) {
            throw new IntraLibraryException('Child IDs must be an array');
        }
        $this->childIds 	= $childIds;
    }

    /**
     * Get the type of Taxonomy object
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->identifier;
    }

    /**
     * Get the name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the ref id
     *
     * @return string
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * Get the source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get this object's parent ID
     *
     * @return mixed the ID of the parent or null if it has no parent
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Get this object's parent ID
     *
     * @return mixed the ID of the parent or null if it has no parent
     */
    public function getParentType()
    {
        return $this->parentType;
    }

    /**
     * Get the IDs of this object's children
     *
     * @return array an array of child IDs
     */
    public function getChildIds()
    {
        return $this->childIds;
    }
    public function getUseFors()
    {
        return $this->usefors;
    }
}

