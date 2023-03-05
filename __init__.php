<?php
use Psr\Container\ContainerInterface;
interface IID{
    public function id() : string;
}
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
        return;
    }
    public function findBy(string $key,$value = null){
        return;
    }
}
abstract class BaseStdArray extends stdClass implements ArrayAccess,Countable,IID
{
    private $_id = "";
    private $modified = false;
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
    public function id() : string{
        return !empty($this->_id) ? $this->_id : @$this['id'];
    }
    public function setID(string $id){
        $this->_id = $id;
    }
    public function offsetExists($offset) : bool {
        return isset($this->{$offset});
    }
    public function offsetUnset($offset) : void{
        unset($this->{$offset});
        $this->modified = true;
    }
    public function offsetGet(mixed $offset) : mixed {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }
    public function count() : int
    {
        return count((array)$this);
    }
}
class StdArray extends BaseStdArray
{
    private $validation;
    public function offsetSet($offset,$value) : void {
        if(@$this->validation instanceof Validation){
            $this->validation->factory = $this;
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
    public $factory;
    public static $mode = "low";
    public function __construct(array $validation)
    {
        $this->validation = $validation;
    }
    public function _validate($key,$val,$value,&$data){
        $type = gettype($value);
        switch ($val['type']) {
            case "string":
                if($type != "string"){
                    throw new Exception($key."_string",1);
                }
                if(isset($val['value'])){
                    if($data[$key] != $val['value']){
                        throw new Exception("value",1);
                    }
                }
                $l = strlen($value);
                if(isset($val['min']) && $l < $val['min']){
                    throw new Exception($key."_min", 1);
                }
                if(isset($val['max']) && $l > $val['max']){
                    throw new Exception($key."_min", 1);
                }
                break;
            case "integer":
                if($value == "0") {$data[$key] = 0; return;}
                $v = intval($value);
                if($type != "integer" && !$v){
                    throw new Exception($key."_integer",1);
                }
                if(isset($val['min']) && $v < $val['min']){
                    throw new Exception($key."_min", 1);
                }
                if(isset($val['max']) && $v > $val['max']){
                    throw new Exception($key."_min", 1);
                }
                $data[$key] = $v;
                break;
            case "array":
                if(is_array($val['value'])){
                    if(!in_array($value,$val['value'])){
                        throw new Exception($key."_array",1);
                    }
                }
                break;
            case "factory":
                if($val['value'] instanceof ArrayAccess){
                    if($value && !isset($val['value'][$value])){
                        throw new Exception($key."_entity",1);
                    }
                }
                break;
            case "bool":
                if($value == null || $value == "0"){
                    $data[$key] = false;
                }else{
                    $data[$key] = true;
                }
                break;
            default:
        }
        if(self::$mode != "high") return;
        if(@$val['unique']){
            foreach ($this->factory as $v) {
                if(is_array($v)){
                    if($v[$key] == $value){
                        throw new Exception($key."_unique", 1);
                    }
                }
            }
        }
    }
    public function validate(&$data){
        foreach ($this->validation as $key => $value) {
            $this->_validate($key,$value,@$data[$key],$data);
        }
    }
}
global $events;
$events = [];
function factory(string $name,BaseStdArray &$entity = null,BaseStdArray &$entitys = null,BaseStdArray &$metas = null,AbstractContainer $container = null,AbstractContainer $metaContainer = null){
    $entitys = $container->list();
    if(!$entity){
        $entity = new StdArray;
    };
    if(!$entitys){
        $entitys = new StdArray;
    };
    if(!$metas){
        $metas = new StdArray;
    };
    if($metas instanceof StdArray && $metaContainer){
        factory($name."_META",$meta,$metas,$null,$metaContainer);
    }    
    if(isset($entity->id)){
        $entity->merge($container->get($entity->id));
    }
    register_event($name,function($data) use ($entitys){
        foreach ($entitys as $key => $value) {
            if(is_callable($data)){
                $data($value);
            }
        }
    });
    register_shutdown_function(function() use($container,&$entity,&$entitys) {
        $container->storage = $entitys;
        $container->entity = $entity;
        $container->shutdown();
    });
}
function register_event(string $name,callable $callable){
    global $events;
    $events[$name] = $callable;
}
function run_event(string $name,&$data){
    global $events;
    foreach ($events as $key => $value) {
        if($key == $name && is_callable($value)){
            $value($data);
        }
    }
}
function id($obj){
    if($obj instanceof IID){
        return $obj->id();
    }
    if(is_array($obj)){
        return @$obj['id'];
    }
}