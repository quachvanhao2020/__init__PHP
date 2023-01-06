<?php
abstract class BaseStdArray extends stdClass implements ArrayAccess,Countable
{
    private $modified = false;
    public function __construct(array $storage = [])
    {
        $this->merge($storage);
    }
    public function merge($storage = []){
        if(!is_array($storage)) return;
        foreach ($storage as $key => $value) {
            if(!isset($this->{$key}))
            $this->{$key} = $value;
        }
    }
    public function offsetSet($offset, $value) : void {
        if (is_null($offset)) {
            $this[] = $value;
        } else {
            $this->{$offset} = $value;
        }
        $this->modified = true;
    }
    public function getModified(){
        return $this->modified;
    }
    public function offsetExists($offset) : bool {
        return isset($this->{$offset});
    }
    public function offsetUnset($offset) : void{
        unset($this->{$offset});
        $this->modified = true;
    }
    public function offsetGet($offset) {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }
    public function count() : int
    {
        return count((array)$this);
    }
}