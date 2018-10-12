# Config.php
## Features
* Collection for configuration items
* Deferred item definition, item is never defined until it is used
* Self-descriptive, with IDE auto-completion powered
* Deferred resolution of current and default value 
* Enumeration and export for administrative script and manager
* Full control for populating pre-loaded data
* Caching via PHP's ultra-fast APCu with write-back on demand
* Shipped with some helpers for quick reader and writer

## Usage

### Definition
Extend `Collection` with each definition a public function

An Live Template of PHPStorm like this can be added to make a shortcut
```php
public function $CONF_NAME$(): Item
{
    return $this->make(__FUNCTION__, $DEF_VALUE$, $READER$, $WRITER$);
}
```
### Source
Instantiate your own `Loader` with a callback that return the array of raw data of configuration. The key shall be the Collection's FQN. Just `require` the dumped file in case of using a saved php configuration file. Call `setApcu('key_prefix')` to enable APCu caching.

### Routines
Instantiate needed children instance of `Collection`, which a DI container is suggested to fulfil the task. Pass the loader in previous step. Then you call get the configuration by `$collection->item()()`. Note that `__invoke()` is used. Otherwise what you get is a `Item` object having many useful methods like `def()` and `raw()`.

See tests for more detailed usage.