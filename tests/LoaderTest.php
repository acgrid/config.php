<?php

namespace ACGridTest;

use ACGrid\Config\Loader;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{

    public function testLoad()
    {
        $loader = new Loader(function(){
            return ['a' => ['x' => 0, 'y' => 'foo', 'z' => 4.1], 'c' => ['x' => ['2', '3', 3], 'y' => null, 'z' => false]];
        });
        $this->assertFalse($loader->isApcuEnabled());
        $this->assertSame(0, $loader->raw('a', 'x'));
        $this->assertSame('foo', $loader->raw('a', 'y'));
        $this->assertSame(4.1, $loader->raw('a', 'z'));
        $this->assertSame($c = ['x' => ['2', '3', 3], 'y' => null, 'z' => false], $loader->raw('c'));
        $this->assertNull($loader->raw('c', 'y'));
        $this->assertFalse($loader->loaded('a', 'x'));
        $this->assertSame($loader, $loader->store('c', 'y', true));
        $this->assertTrue($loader->loaded('c', 'y'));
        $this->assertSame($loader, $loader->replace('c', ['w' => -1, 'z' => 'bar']));
        $this->assertSame(-1, $loader->fetch('c', 'w'));
        $this->assertNull($loader->fetch('c', 'x'));
        $this->assertTrue($loader->fetch('c', 'y'));
        $this->assertSame('bar', $loader->fetch('c', 'z'));
        $loader->reload();
        $this->assertFalse($loader->loaded('c', 'y'));
        $this->assertFalse($loader->raw('c', 'z'));
    }

    public function testApcu()
    {
        if(!function_exists('apcu_enabled') || !apcu_enabled()) $this->markTestSkipped('Apcu is not enabled in CLI by default, turn it on by `-d apc.enable_cli=1`.');
        $loader = new Loader(function(){
            return ['a' => ['x' => 3, 'y' => 5, 'z' => 1]];
        });
        $this->assertSame($loader, $loader->enableApcu('foo'));
        $this->assertTrue($loader->isApcuEnabled());
        $loader->reload();
        $this->assertSame(3, $loader->raw('a', 'x'));
        $this->assertSame(5, $loader->raw('a', 'y'));
        $this->assertSame(1, $loader->raw('a', 'z'));
        $loader->store('a', 'x', 6);
        unset($loader);
        $loader2 = new Loader(function(){
            return []; // if apcu does work, this will not affect anything
        });
        $loader2->enableApcu('foo');
        $this->assertSame(3, $loader2->raw('a', 'x'));
        $this->assertSame(5, $loader2->raw('a', 'y'));
        $this->assertSame(1, $loader2->raw('a', 'z'));
        $this->assertSame(6, $loader2->fetch('a', 'x'));
        $loader2->reload();
        $this->assertFalse($loader2->loaded('a', 'x'));
    }

}
