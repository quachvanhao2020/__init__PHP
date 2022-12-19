<?php
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
        $entity = $this->entity;
        unset($this->storage->validation);
        if(isset($entity)){
            $this->storage[$entity['id']] = $entity;
        }
        file_put_contents($this->namespace,serialize($this->storage));
    }
    public function shutdown()
    {
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