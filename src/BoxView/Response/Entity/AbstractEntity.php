<?php

namespace BoxView\Response\Entity;

/**
 * Class AbstractEntity
 * @package BoxView\Response
 */
class AbstractEntity
{
    /**
     * Properties that should be converted to dates.
     *
     * @var array
     */
    protected $_dateProperties = [];

    /**
     * @param array $properties
     */
    public function __construct(array $properties = [])
    {
        $this->fill($properties);
    }

    /**
     * Fill the entity with an array of properties.
     *
     * @param  array  $properties
     * @return $this
     */
    public function fill(array $properties)
    {
        foreach ($properties as $key => $value)
        {
            if ($this->isFillable($key))
            {
                $this->setProperty($key, $value, false);
            }
        }

        return $this;
    }

    /**
     * Determine if the given property may be mass assigned.
     *
     * @param  string  $key
     * @return bool
     */
    public function isFillable($key)
    {
        return $key != '' && strpos($key, '_') !== 0;
    }

    /**
     * Set a given property on the entity.
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function setProperty($key, $value)
    {
        $propertyName = self::camel($key);

        if (in_array($propertyName, $this->_dateProperties)) {
            $value = new \DateTime($value);
        }

        if (property_exists($this, self::camel($key))) {
            $this->{self::camel($key)} = $value;
        }

        return $this;
    }

    /**
     * Convert a value to camel case.
     *
     * @param  string  $value
     * @return string
     */
    public static function camel($value)
    {
        return lcfirst(static::studly($value));
    }

    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     * @return string
     */
    public static function studly($value)
    {
        $value = ucwords(str_replace(array('-', '_'), ' ', $value));

        return str_replace(' ', '', $value);
    }
} 
