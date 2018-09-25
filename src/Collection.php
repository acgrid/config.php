<?php


namespace ACGrid\Config;


class Collection
{
    /**
     * The variable (section) name in the generated configuration file
     */
    const VARIABLE = 'CONFIG';

    /**
     * @var Manager
     */
    protected $manager;
    /**
     * @var array of Item
     */
    protected $items;

    public function __construct(Manager $config)
    {
        $this->manager = $config;
    }

    /**
     * @param string $name
     * @param null $default
     * @param callable|null $reader
     * @param callable|null $writer
     * @return Item
     */
    protected function item(string $name, $default = null, callable $reader = null, callable $writer = null)
    {
        if(!isset($this->items[$name])) $this->items[$name] = new Item($this, $name, $default, $reader, $writer);
        return $this->items[$name];
    }

    /**
     * @param string $name
     * @return Item
     */
    protected function getItem($name)
    {
        try{
            return $this->items[$name] ?? $this->$name();
        }catch (\Error $e){
            throw new \InvalidArgumentException("Undefined configuration item '$name' in section " . static::VARIABLE);
        }
    }

    public function raw(string $name)
    {
        return $this->manager->getRaw(static::VARIABLE, $name);
    }

    public function value(string $name)
    {
        return $this->manager->get(static::VARIABLE, $name);
    }

    /**
     * Read the persistent format of configuration and convert to runtime value.
     * @param string $id
     * @param mixed $raw
     *
     * @return mixed
     */
    public function read(string $id, $raw)
    {
        return $this->getItem($id)->read($raw);
    }

    /**
     * Write the runtime value to persistent format
     * @param string $id
     * @param mixed $value
     *
     * @return mixed
     */
    public function write(string $id, $value)
    {
        return $this->getItem($id)->write($value, $this->manager->get(static::VARIABLE, $id));
    }

    /**
     * Receive standardized configuration variables (already runtime value) and pass to configuration pool
     * @param array $data
     *
     * @return Manager
     */
    public function updateRuntime(array $data)
    {
        foreach ($data as $name => $value) $this->manager->set(static::VARIABLE, $name, $value);
        return $this->manager;
    }

    /**
     * Receive raw configuration variables and pass to configuration pool
     * @param array $data
     *
     * @return Manager
     */
    public function updateRaw(array $data)
    {
        $this->processRaw($data);
        foreach ($data as $name => $value) $data[$name] = $this->read($name, $value);
        return $this->updateRuntime($data);
    }

    /**
     * For override
     * @param $data
     */
    protected function processRaw(&$data) {}

    /**
     * Return the string representation of this collection of configuration variables
     * It will parse all if not parsed/set before and then convert runtime value to persistent format.
     * Finally it return the string by var_export for saving as a PHP script.
     *
     * @return string
     */
    public function export()
    {
        $parsed = $this->manager->getParsed(static::VARIABLE);
        $keys = array_keys($parsed);
        return var_export(array_combine($keys, array_map(function($name, $value){
                return $this->write($name, $value);
            }, $keys, array_values($parsed))) + $this->manager->getRaw(static::VARIABLE), true);
    }

}