<?php

namespace BoxView\Response\Entity;

/**
 * Class Document
 * @package BoxView\Response
 */
class Document extends AbstractEntity
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $type;

    /** @var string */
    protected $status;

    /** @var string */
    protected $name;

    /** @var \DateTime */
    protected $createdAt;

    /** @var array */
    protected $_dateProperties = ['createdAt'];

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
} 
