<?php
use Psr\Container\ContainerInterface;
interface IID{
    public function id() : string;
}
interface IAdapter{
    public function read(string $path,StdArray $default = null);
    public function write(string $path,StdArray $data);
}
class BinAdapter implements IAdapter{
    public function read(string $path,StdArray $default = null){
        $storage = unserialize(@file_get_contents($path));
        if(!$storage){
            $storage = $default;
        }
        return $storage;
    }
    public function write(string $path,StdArray $data){
        $data = serialize($data);
        file_put_contents($path,$data);
    }
}
class JsonAdapter implements IAdapter{
    public function read(string $path,StdArray $default = null){
        $host = $this->host($path);
        foreach ($host as $key => $value) {
            $v = json_decodes(@file_get_contents($path."/".$key.".json"),true);
            if($value == "object"){
                $class = get_class($default);
                $default[$key] = new $class($v);
            }
        }
        $default->setModifies();
        return [];
    }
    public function write(string $path,StdArray $data){
        if(!is_dir($path)){
            mkdir($path,0755);
        }
        $host = $this->host($path);
        foreach ($data->getModifies() as $k => $v) {
            $value = $data[$k];
            $file = $path."/".$k.".json";
            $this->_write($file,$k,$value,$v,$host);
        }
        foreach ($data as $key => $value) {
            if($value instanceof StdArray){
                if($value->getModified()){
                    $file = $path."/".$key.".json";
                    $this->_write($file,$key,$value,0,$host);
                }
            }
        }
        file_put_contents($path."/host.json",json_encode($host,JSON_PRETTY_PRINT));
    }
    public function _write($file,$k,$value,$v,&$host){
        if($v == -1){
            unset($host[$k]);
            unlink($file);
        }else{
            $type = gettype($value);
            $host[$k] = $type;
            file_put_contents($file,json_encode($value,JSON_PRETTY_PRINT));
        }
    }
    public function host(string $path){
        $host = json_decode(@file_get_contents($path."/host.json"),true);
        if(!$host) $host = [];
        return $host;
    }
}
abstract class AbstractContainer implements ContainerInterface{
    public $namespace;
    public $entity;
    public $storage;
    public $adapter;
    public function __construct(string $namespace = null,IAdapter $adapter = null)
    {
        if($adapter == null) $adapter = new JsonAdapter();
        $this->namespace = $namespace;
        $this->adapter = $adapter;
    }
    public abstract function list(array $query = null);
    public abstract function remove(string $id);
    public abstract function find(string $id);
    public abstract function findBy(string $key,$value = null);
    public abstract function shutdown();
    public abstract function load($default = null);
}
class FsContainer extends AbstractContainer{
    public function ___destruct()
    {
        $this->adapter->write($this->namespace,$this->storage);
    }
    public function load($default = null){
        $new = $this->adapter->read($this->namespace,$default);
        if(empty($new)) return;
        $this->storage->merge($new);
    }
    public function shutdown()
    {
        if($this->storage->getReadonly()) return;
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
    public function id() : string{
        return $this['id'];
    }
    public function __construct($storage = []) {
        $this->merge($storage);
    }
    public function merge($storage = []){
        if(!is_iterable($storage) && !($storage instanceof ArrayAccess)) return;
        foreach ($storage as $key => $value) {
            $this->{$key} = $value;
        }
    }
    public function __call($method, $arguments) {
        if (isset($this->{$method}) && is_callable($this->{$method})) {
            return call_user_func_array($this->{$method}, $arguments);
        } else {
            throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
        }
    }
    public function offsetSet($offset,$value) : void{
        if (is_null($offset)) {
            $this[] = $value;
        } else {
            $this->{$offset} = $value;
        }
    }
    public function offsetExists($offset) : bool{
        return isset($this->{$offset});
    }
    public function offsetUnset($offset) : void{
        unset($this->{$offset});
    }
    public function offsetGet($offset) : mixed {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }
    public function count() : int
    {
        return count((array)$this);
    }
}
class StdArray extends BaseStdArray
{
    private $modifies = [];
    private $readonly = false;
    private $shutdown;
    public function offsetSet($offset,$value) : void{
        if(isset($this[$offset])){
            $this->modifies[$offset] = 0;
        }else{
            $this->modifies[$offset] = 1;
        }
        parent::offsetSet($offset,$value);
    }
    public function offsetUnset($offset) : void{
        $this->modifies[$offset] = -1;
        parent::offsetUnset($offset);
    }
    public function getModifies(){
        return $this->modifies;
    }
    public function setModifies($modifies = []){
        $this->modifies = $modifies;
    }
    public function getModified(){
        if(count($this->modifies) != 0){
            return true;
        };
        foreach ($this as $value) {
            if($value instanceof StdArray){
                if($value->getModified()){
                    return true;
                }
            }
        }
        return false;
    }
    public function getReadonly(){
        return $this->readonly;
    }
    public function setReadonly($readonly){
        $this->readonly = $readonly;
    }
    public function getShutdown(){
        return $this->shutdown;
    }
    public function setShutdown(callable $shutdown){
        return $this->shutdown = $shutdown;
    }
}
class StdArrays extends StdArray
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
                    throw new Exception($key."_max", 1);
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
                    throw new Exception($key."_max", 1);
                }
                $data[$key] = $v;
                break;
            case "float":
                if($value == "0") {$data[$key] = 0; return;}
                $v = floatval($value);
                if($type != "float" && !$v){
                    throw new Exception($key."_float",1);
                }
                if(isset($val['min']) && $v < $val['min']){
                    throw new Exception($key."_min", 1);
                }
                if(isset($val['max']) && $v > $val['max']){
                    throw new Exception($key."_max", 1);
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
function factory(string $name,BaseStdArray &$entitys = null,AbstractContainer $container = null){
    $container->storage = $entitys;
    $container->load($entitys);    
    register_event($name,function($data) use ($entitys){
        foreach ($entitys as $value) {
            if(is_callable($data)){
                $data($value);
            }
        }
    });
    register_shutdown_function(function() use($container,&$entitys) {
        $error = error_get_last();
        if (@$error['type'] === E_ERROR) {
            return;
        }
        $container->storage = $entitys;
        $container->shutdown();
    });
}
function register_event(string $name,callable $callable){
    global $events;
    if(isset($events[$name])){
        array_push($events[$name],$callable);
        return;
    }
    $events[$name] = [$callable];
}
function run_event(string $name,&$data){
    global $events;
    foreach ($events as $key => $value) {
        if($key == $name){
            foreach ($value as $key => $call) {
                $call($data);
            }
        }
    }
}
function _run_event(string $name,$data){
    global $events;
    foreach ($events as $key => $value) {
        if($key == $name){
            foreach ($value as $key => $call) {
                $call($data);
            }
        }
    }
}
function id($obj){
    if($obj instanceof IID){
        return $obj->id();
    }
}
function is_arrays($data){
    if($data instanceof ArrayAccess){
        return true;
    }
    return is_array($data);
}
function json_decodes(string $data){
    $data = json_decode($data,true);
    run_event("json.decode",$data);
    return $data;
}
function arrs(array $data = []){
    return new StdArrays($data);
}