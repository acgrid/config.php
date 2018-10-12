<?php


namespace ACGrid\Config;


class Loader implements LoaderInterface
{
    /**
     * @var string
     */
    private $apcuRawKey = null;
    private $apcuStoreKey = null;
    private $needUpdate = false;

    /**
     * @var callable
     */
    private $loader;

    /**
     * Persist form
     * @var array|null
     */
    private $raw = null;

    /**
     * Runtime form
     * @var array
     */
    private $store;

    public function __construct(callable $loader)
    {
        $this->loader = $loader;
    }

    public function __destruct()
    {
        if($this->needUpdate){
            if($this->apcuStoreKey) apcu_store($this->apcuStoreKey, $this->store);
            if($this->apcuRawKey) apcu_store($this->apcuRawKey, $this->raw);
        }
    }

    public function enableApcu(string $key)
    {
        if($key && function_exists('apcu_enabled') && apcu_enabled()){
            $this->apcuRawKey = "$key-raw";
            $this->apcuStoreKey = "$key-store";
        }
        return $this;
    }

    public function isApcuEnabled()
    {
        return !!$this->apcuRawKey;
    }

    public function reload()
    {
        if($this->apcuStoreKey){
            apcu_delete([$this->apcuStoreKey, $this->apcuRawKey]);
        }
        $this->raw = null;
        $this->store = [];
    }

    /**
     * Flush previously loaded configuration and load by current parsers
     * @return $this
     */
    public function load()
    {
        $this->store = [];
        if($this->apcuRawKey){
            if($cached = apcu_fetch([$this->apcuStoreKey, $this->apcuRawKey])){
                $this->store = $cached[$this->apcuStoreKey];
                $this->raw = $cached[$this->apcuRawKey];
            }
        }
        if(empty($this->raw)) $this->raw = call_user_func($this->loader);
        return $this;
    }

    /**
     * @param string $collection
     * @param string|null $item
     * @return array
     */
    public function raw(string $collection, string $item = null)
    {
        if(!isset($this->raw)) $this->load();
        return isset($item) ? $this->raw[$collection][$item] ?? null : $this->raw[$collection] ?? [];
    }

    public function loaded(string $collection, string $item): bool
    {
        return isset($this->store[$collection][$item]);
    }

    /**
     * @param string $collection
     * @param string|null $item
     *
     * @return mixed
     */
    public function fetch(string $collection, string $item = null)
    {
        return isset($item) ? $this->store[$collection][$item] ?? null : $this->store[$collection] ?? [];
    }

    /**
     * Set the runtime value and BYPASS ANY processing or validation
     *
     * @param string $collection
     * @param string $item
     * @param $value
     *
     * @return $this
     */
    public function store(string $collection, string $item, $value)
    {
        $this->needUpdate = true;
        $this->store[$collection][$item] = $value;
        return $this;
    }

    public function replace(string $collection, array $items)
    {
        $this->needUpdate = true;
        $this->store[$collection] = $items + ($this->store[$collection] ?? []);
        return $this;
    }

}