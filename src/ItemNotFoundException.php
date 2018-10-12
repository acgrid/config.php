<?php


namespace ACGrid\Config;


class ItemNotFoundException extends \InvalidArgumentException
{
    /**
     * @var Collection
     */
    protected $collection;

    public function __construct(string $name, Collection $collection)
    {
        $this->collection = $collection;
        $collectionName = get_class($collection);
        parent::__construct("Configuration item '$name is not defined in '$collectionName'.");
    }

    /**
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

}