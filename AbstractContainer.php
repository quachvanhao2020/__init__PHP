<?php
use Psr\Container\ContainerInterface;

abstract class AbstractContainer implements ContainerInterface{
    public $namespace;
    public $entity;
    public $storage;
    public abstract function list(array $query = null);
    public abstract function remove(string $id);
    public abstract function find(string $id);
    public abstract function findBy(string $key,$value = null);
    public abstract function shutdown();
}