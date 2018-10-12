<?php


namespace ACGrid\Config;


class Dumper implements \ArrayAccess, \Countable
{
    /**
     * @var array
     */
    protected $collections = [];
    /**
     * @var Loader
     */
    protected $loader;

    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    public function offsetExists($offset)
    {
        return isset($this->collections[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->collections[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        return isset($offset) ? $this->collections[$offset] = $value : array_push($this->collections, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->collections[$offset]);
    }

    public function count()
    {
        return count($this->collections);
    }

    public function dump(): array
    {
        return array_combine(array_map('get_class', $this->collections), array_map(function(Collection $collection){
            return $collection->dump();
        }, $this->collections));
    }

    public function export(): string
    {
        return var_export($this->dump(), true);
    }

    public function save(): string
    {
        return sprintf("<?php\n\nreturn %s;\n", $this->export());
    }

}