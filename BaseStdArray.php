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
    
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this[] = $value;
        } else {
            $this->{$offset} = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->{$offset});
    }

    public function offsetUnset($offset) {
        unset($this->{$offset});
    }

    public function offsetGet($offset) {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }

    /**
     * Get the number of public properties in the ArrayObject
     *
     * @return int
     */
    public function count()
    {
        return count((array)$this);
    }
}