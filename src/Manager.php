<?php


namespace ACGrid\Config;

use DI\Definition\Exception\InvalidDefinition;
use DI\Definition\Source\DefinitionSource;
use DI\Definition\ValueDefinition;

class Manager implements DefinitionSource
{
    public static $prefix = 'CONFIG';

    /**
     * Configuration section to parser FQN
     * @var array
     */
    private $sectionMap = [];
    /**
     * Parser FQN to section
     * @var array
     */
    private $parserMap = [];
    /**
     * Configuration in form as file
     * @var array|null
     */
    private $raw = null;
    /**
     * Parsed configuration
     * @var array
     */
    private $parsed;
    /**
     * Parsers
     * @var array of Collection|null
     */
    private $parsers;
    /**
     * Persistent configuration
     * @var string
     */
    private $path;

    /**
     * Config constructor.
     * @param string $path
     * @param array $parsers
     */
    public function __construct(string $path, array $parsers = [])
    {
        $this->path = $path;
        $this->parsers = array_fill_keys($parsers, null);
        $this->sectionMap = array_combine(array_map(function($parser){
            return constant("$parser::VARIABLE");
        }, $parsers), $parsers);
        $this->parserMap = array_flip($this->sectionMap);
    }

    /**
     * Flush previously loaded configuration and load by current parsers
     * @return $this
     */
    public function load()
    {
        $this->parsed = [];
        include $this->path;
        foreach (array_keys($this->sectionMap) as $section){
            $this->parsed[$section] = [];
        }
        $this->raw = count($this->sectionMap) ? compact(array_keys($this->sectionMap)) : [];
        return $this;
    }

    /**
     * @param string $section
     *
     * @return Collection
     */
    public function getParser(string $section)
    {
        $parser = $this->sectionMap[$section] ?? null;
        if(!$parser) throw new \InvalidArgumentException("Undefined configuration section: $section.");
        if(!isset($this->parsers[$parser])) $this->parsers[$parser] = new $parser($this);
        return $this->parsers[$parser];
    }

    public function walkParsers()
    {
        foreach($this->parserMap as $section) yield $this->getParser($section);
    }

    /**
     * @param string $section
     * @param string|null $item
     * @return array
     */
    public function getRaw(string $section, string $item = null)
    {
        return isset($item) ? $this->raw[$section][$item] ?? null : $this->raw[$section] ?? [];
    }

    /**
     * @param string $section
     * @return array
     */
    public function getParsed(string $section)
    {
        return $this->parsed[$section] ?? [];
    }

    /**
     * @param string $name
     * @throws InvalidDefinition
     * @return ValueDefinition|null
     */
    public function getDefinition(string $name)
    {
        $parts = explode('.', $name);
        if(count($parts) === 3){
            list($prefix, $scope, $id) = $parts;
            if(strtoupper($prefix) === static::$prefix){
                try{
                    $value = new ValueDefinition($this->get(strtoupper($scope), $id));
                    $value->setName($name);
                    return $value;
                }catch(\Throwable $t){
                    throw new InvalidDefinition("Configuration Item $id not found.", 0);
                }
            }
        }
        return null;
    }

    public function getDefinitions(): array
    {
        return [];
    }

    /**
     * @param string $section
     * @param string $id
     *
     * @return mixed
     */
    public function get(string $section, string $id)
    {
        if(!isset($this->raw)) $this->load();
        $section = strtoupper($section);
        if(!isset($this->parsed[$section][$id])){
            $this->parsed[$section][$id] = $this->getParser($section)->read($id, $this->raw[$section][$id] ?? null);
        }
        return $this->parsed[$section][$id];
    }

    /**
     * Shorthand to get collection object
     * @param $name
     * @return Collection
     */
    function __get($name)
    {
        return $this->getParser(strtoupper($name));
    }

    /**
     * Shorthand to get item value quickly
     * @param string $name
     * @param array $arguments
     * @return mixed;
     */
    function __call($name, $arguments) {
        return $this->get($name, array_pop($arguments));
    }

    /**
     * Set the runtime value and BYPASS ANY processing or validation
     *
     * @param string $section
     * @param string $id
     * @param $value
     *
     * @return $this
     */
    public function set(string $section, string $id, $value)
    {
        $this->parsed[$section][$id] = $value;
        return $this;
    }

    /**
     * Fetch the configuration file content based on current context
     *
     * @return string
     */
    public function export()
    {
        return array_reduce($this->parserMap, function($carry, $section){
            return $carry . sprintf("\$%s = %s;\n\n", $section, $this->getParser($section)->export());
        }, "<?php\n\n");
    }

    public function save()
    {
        return file_put_contents($this->path, $this->export(), LOCK_EX);
    }

}