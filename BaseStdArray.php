<?php
abstract class BaseStdArray extends stdClass implements ArrayAccess,Countable
{
    public function __construct(array $storage = [])
    {
        $this->merge($storage);
    }
    public function merge(array $storage = []){
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
    }
    public function offsetExists($offset) : bool {
        return isset($this->{$offset});
    }
    public function offsetUnset($offset) : void{
        unset($this->{$offset});
    }
    public function offsetGet($offset) {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }
    public function count() : int
    {
        return count((array)$this);
    }
}