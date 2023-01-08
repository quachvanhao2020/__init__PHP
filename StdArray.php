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
class StdArray extends BaseStdArray
{
    public $id = "-1a";
    private $validation;
    public function offsetSet($offset,$value) : void {
        if(@$this->validation instanceof Validation){
            $this->validation->validate($value);
        }
        parent::offsetSet($offset,$value);
    }
    public function setValidation(Validation $validation = null){
        $this->validation = $validation;
    }
}
class Validation{
    public $validation;
    public function __construct(array $validation)
    {
        $this->validation = $validation;
    }
    public function validate(&$data){
        foreach ($data as $key => $value) {
            if(isset($this->validation[$key])){
                $val = $this->validation[$key];
                $type = gettype($val);
                switch ($val) {
                    case "string":
                        if($type != "string"){
                            throw new Exception($key,1);
                        }
                        break;
                    case "integer":
                        $v = intval($value);
                        if($type != "integer" && !$v){
                            throw new Exception($key,1);
                        }
                        $data[$key] = $v;
                        break;
                    default:
                }
                if(is_array($val)){
                    if(!in_array($value,$val)){
                        throw new Exception($key,1);
                    }
                }
                if($val instanceof ArrayAccess){
                    if($value && !isset($val[$value])){
                        throw new Exception($key,1);
                    }
                }
            }
        }
    }
}
