<?php

class StdArrayRecursive extends StdArray implements RecursiveIterator{

    /**
     * Parent container
     *
     * @var StdArrayRecursive
     */
    public $parent;

    /**
     * Contains sub children
     *
     * @var array
     */
    public $children = [];

    /**
     * An index that contains the order in which to iterate children
     *
     * @var array
     */
    protected $index = [];


    public function addChildren(StdArray $entity,bool $takeParent = true)
    {
        $id = "";
        if (array_key_exists($id, $this->index)) {
            return $this;
        }
        $this->children[$id] = $entity;
        $this->index[$id] = true;
        return $this;
    }

    /**
     * Returns a child page matching $property == $value, or null if not found
     *
     * @param  string $property        name of property to match against
     * @param  mixed  $value           value to match property against
     * @return EntityFertility  matching page or null
     */
    public function find(string $id)
    {
        $iterator = new \RecursiveIteratorIterator($this, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $entity) {
            if($entity->getId() == $id){
                return $entity;
            }
        }
        return;
    }

    /**
     * Returns current page
     *
     * Implements RecursiveIterator interface.
     *
     * @return EntityFertility current page or null
     * @throws Exception\OutOfBoundsException  if the index is invalid
     */
    public function current()
    {
        current($this->index);
        $id = key($this->index);
        if(!is_array($this->children)){
            $this->children = [];
        }
        if (!isset($this->children[$id])) {
            //throw new \Exception('Corruption detected in container; ');
            return;
        }
        return $this->children[$id];
    }

    /**
     * Returns hash code of current page
     *
     * Implements RecursiveIterator interface.
     *
     * @return string  hash code of current page
     */
    public function key()
    {
        return key($this->index);
    }

    /**
     * Moves index pointer to next page in the container
     *
     * Implements RecursiveIterator interface.
     *
     * @return void
     */
    public function next()
    {
        next($this->index);
    }

    /**
     * Sets index pointer to first page in the container
     *
     * Implements RecursiveIterator interface.
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->index);
    }

    /**
     * Checks if container index is valid
     *
     * Implements RecursiveIterator interface.
     *
     * @return bool
     */
    public function valid()
    {
        return current($this->index) !== false;
    }

    /**
     * Proxy to haschildren()
     *
     * Implements RecursiveIterator interface.
     *
     * @return bool  whether container has any children
     */
    public function hasChildren()
    {
        return $this->valid() && $this->current()->children;
    }

    /**
     * Returns the child container.
     *
     * Implements RecursiveIterator interface.
     *
     * @return array
     */
    public function getChildren()
    {
        $hash = key($this->index);

        if (isset($this->children[$hash])) {
            return $this->children[$hash];
        }

        return $this->children;
    }

    // Countable interface:

    /**
     * Returns number of children in container
     *
     * Implements Countable interface.
     *
     * @return int  number of children in the container
     */
    public function count()
    {
        return count($this->index);
    }

}