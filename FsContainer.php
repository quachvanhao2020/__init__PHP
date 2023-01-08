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

class FsContainer extends AbstractContainer{
    public function __construct(string $path = null)
    {
        if($path){
            $this->namespace = $path;
        }
        $storage = unserialize(@file_get_contents($path));
        if(!$storage instanceof StdArray){
            $storage = new StdArray;
        };
        $this->storage = $storage;
    }
    public function ___destruct()
    {
        $this->storage->setValidation(null);
        $data = serialize($this->storage);
        file_put_contents($this->namespace,$data);
    }
    public function shutdown()
    {
        $entity = $this->entity;
        if(isset($entity) && $entity instanceof StdArray && $entity->getModified()){
            $this->storage[$entity['id']] = $entity;
            return $this->___destruct();
        }
        if(!$this->storage->getModified()) return;
        return $this->___destruct();
    }
    public function get($id){
        return $this->storage[$id];
    }
    public function has($id){
        return isset($this->storage[$id]);
    }
    public function list(array $query = null){
        return $this->storage;
    }
    public function remove(string $id){
        unset($this->storage[$id]);
        return true;
    }
    public function find(string $id){
        return ;
    }
    public function findBy(string $key,$value = null){
        return ;
    }
}