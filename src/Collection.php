<?php


namespace ACGrid\Config;


abstract class Collection
{
    /**
     * @var Loader
     */
    protected $loader;
    /**
     * @var array
     */
    protected $items = [];

    public function __construct(Loader $config)
    {
        $this->loader = $config;
    }

    /**
     * @param string $name
     * @param null $default
     * @param callable|null $reader
     * @param callable|null $writer
     * @return Item
     */
    protected function make(string $name, $default = null, callable $reader = null, callable $writer = null)
    {
        if(!isset($this->items[$name])) $this->items[$name] = new Item($this, $name, $default, $reader, $writer);
        return $this->items[$name];
    }

    /**
     * @param string $name
     * @return Item
     */
    protected function item($name): Item
    {
        try{
            return $this->items[$name] ?? $this->$name();
        }catch (\Error $e){
            throw new ItemNotFoundException($name, $this);
        }
    }

    public function items()
    {
        return array_filter(array_map(function(\ReflectionMethod $method){
            if($method->class !== self::class){
                return ($item = $method->invoke($this)) instanceof Item ? $item : null;
            }else{
                return null;
            }
        }, (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC)));
    }

    public function raw(string $item)
    {
        return $this->loader->raw(static::class, $item);
    }

    public function value(string $item)
    {
        if($this->loader->loaded(static::class, $item)) return $this->loader->fetch(static::class, $item);
        $this->loader->store(static::class, $item, $value = $this->item($item)->read($this->raw($item)));
        return $value;
    }

    /**
     * Receive standardized configuration variables (already runtime value) and pass to configuration pool
     * @param array $data
     *
     * @return Loader
     */
    public function update(array $data)
    {
        return $this->loader->replace(static::class, $data);
    }

    public function all($method = 'value'): array
    {
        $items = $this->items();
        return array_combine(array_map(function(Item $item){
            return $item->name();
        }, $items), array_map(function(Item $item) use ($method){
            return $item->$method();
        }, $items));
    }

    public function dump(): array
    {
        return $this->all('writeCurrent');
    }

}