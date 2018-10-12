<?php

namespace ACGridTest;

use ACGrid\Config\Collection;
use ACGrid\Config\Dumper;
use ACGrid\Config\Helpers;
use ACGrid\Config\ItemNotFoundException;
use ACGrid\Config\Loader;
use PHPUnit\Framework\TestCase;

class Main extends Collection{
    public function foo()
    {
        return $this->make(__FUNCTION__, 5, Helpers::json(), Helpers::asJson());
    }

    public function enum()
    {
        return $this->make(__FUNCTION__, function(){
            return 'x';
        }, Helpers::enum(['x', 'y', 'z']));
    }

    public function users()
    {
        return $this->make(__FUNCTION__, [233], Helpers::typedArray(function($value){
            return $value > 0 ? intval($value) : 0;
        }));
    }

    public function ratios()
    {
        return $this->make(__FUNCTION__, ['u' => 1, 'd' => 0, 'c' => 1, 'x' => 50], Helpers::typedHash([
            'u' => Helpers::float(-1),
            'd' => Helpers::unsignedFloat(),
            'c' => Helpers::unsignedInteger(),
            'x' => Helpers::rangedInt(0, 1048),
        ]));
    }
}

class Sub extends Collection{
    public function bar()
    {
        return $this->make(__FUNCTION__, [], Helpers::csv(), Helpers::asCsv());
    }

    public function more()
    {
        return $this->make(__FUNCTION__, false, Helpers::boolean());
    }
}

class CollectionTest extends TestCase
{
    /**
     * @var Loader
     */
    protected $loader;
    /**
     * @var Main
     */
    protected $main;
    /**
     * @var Sub
     */
    protected $sub;

    protected function setUp()
    {
        $this->loader = new Loader(function(){
            return [
                Main::class => ['foo' => '4', 'bar' => 0, 'enum' => 'i', 'users' => [-2, '4', 6]],
                Sub::class => ['bar' => 'p,o,i'],
            ];
        });
        $this->main = new Main($this->loader);
        $this->sub = new Sub($this->loader);
    }

    public function testMain()
    {
        $foo = $this->main->foo();
        $this->assertSame('foo', $foo->name());
        $this->assertSame('9', $foo->write(9, 4));
        $this->assertSame('4', $foo->raw());
        $this->assertSame(4, $foo->value());
        $this->assertSame(4, $foo());
        $this->assertSame('4', strval($foo));
        $this->assertSame(5, $foo->def());
        $this->assertSame('x', $this->main->enum()());
        $this->assertSame([0, 4, 6], $this->main->users()());
        $this->assertSame(['u' => 1.0, 'd' => 0.0, 'c' => 1, 'x' => 50], $this->main->ratios()());
    }

    public function testSub()
    {
        $bar = $this->sub->bar();
        $this->assertSame(['p', 'o', 'i'], $bar->value());
        $this->sub->update(['bar' => ['k', 'o', 'i']]);
        $this->assertSame(['bar' => ['k', 'o', 'i'], 'more' => false], $this->sub->all());
        $this->assertFalse($this->sub->more()());
        try{
            $this->sub->value('foo');
        }catch (ItemNotFoundException $e){
            $this->assertSame($this->sub, $e->getCollection());
        }

        $dumper = new Dumper($this->loader);
        $this->assertSame(0, count($dumper));
        $dumper[] = $this->main;
        $this->assertSame($this->main, $dumper[0]);
        $dumper[] = $this->sub;
        $this->assertCount(2, $dumper);
        $this->assertTrue(isset($dumper[1]));
        $this->assertFalse(isset($dumper[2]));
        $this->assertSame(<<<'PHP'
<?php

return array (
  'ACGridTest\\Main' => 
  array (
    'foo' => '4',
    'enum' => 'x',
    'users' => 
    array (
      0 => 0,
      1 => 4,
      2 => 6,
    ),
    'ratios' => 
    array (
      'u' => 1.0,
      'd' => 0.0,
      'c' => 1,
      'x' => 50,
    ),
  ),
  'ACGridTest\\Sub' => 
  array (
    'bar' => 'k,o,i',
    'more' => false,
  ),
);

PHP
            , $dumper->save());
        unset($dumper[1]);
        $this->assertCount(1, $dumper);
    }

    public function testNotFound()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->sub->value('foo');
        $this->expectExceptionMessage("Configuration item 'foo' is not defined in 'ACGridTest\Sub'.");
    }

}
