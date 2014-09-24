<?php

namespace BoxView\Response\Entity;

/**
 * Class Session
 * @package BoxView\Response
 */
class Session extends AbstractEntity
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $type;

    /** @var array */
    protected $urls = [];

    /** @var \DateTime */
    protected $expiresAt;

    /** @var array */
    protected $_dateProperties = ['expiresAt'];

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
     * @return array
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }
} 
