<?php


namespace ACGrid\Config;


class Item
{
    /**
     * @var Collection
     */
    protected $collection;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var mixed
     */
    protected $value;
    /**
     * @var callable|null function($rawValue, $defaultValue)
     */
    protected $reader;
    /**
     * @var callable|null function($newValue, $oldValue)
     */
    protected $writer;
    /**
     * @var string|int|float|bool|array|callable
     */
    protected $default;

    public function __construct(Collection $collection, string $name, $default = null, callable $reader = null, callable $writer = null)
    {
        $this->collection = $collection;
        $this->name = $name;
        $this->default = $default;
        $this->reader = $reader;
        $this->writer = $writer;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Resolve the default value at the time of reading
     * @return array|bool|float|int|null|string
     */
    public function def()
    {
        return is_callable($this->default) ? call_user_func($this->default) : $this->default;
    }

    public function raw()
    {
        return $this->collection->raw($this->name);
    }

    /**
     * Get the parsed value of current configuration item
     * @return mixed
     */
    public function value()
    {
        return $this->collection->value($this->name);
    }

    /**
     * Read a item from configuration file
     * @param $rawValue
     * @return mixed
     */
    public function read($rawValue)
    {
        return isset($this->reader) ? call_user_func($this->reader, $rawValue, $this->def()) : ($rawValue ?? $this->def());
    }

    /**
     * Write a item into configuration file
     * @param mixed $currValue
     * @param mixed $oldValue
     * @return mixed
     */
    public function write($currValue, $oldValue)
    {
        return isset($this->writer) ? call_user_func($this->writer, $currValue, $oldValue) : $currValue;
    }

    public function writeCurrent()
    {
        $value = $this->value();
        return $this->write($value, $value);
    }

    public function __invoke()
    {
        return $this->value();
    }

    public function __toString()
    {
        return strval($this->value());
    }

}