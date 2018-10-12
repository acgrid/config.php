<?php


namespace ACGrid\Config;


interface LoaderInterface
{
    public function raw(string $collection, string $item = null);
    public function loaded(string $collection, string $item): bool;
    public function fetch(string $collection, string $item = null);
    public function store(string $collection, string $item, $value);
    public function replace(string $collection, array $items);
}