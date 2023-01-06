<?php
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
