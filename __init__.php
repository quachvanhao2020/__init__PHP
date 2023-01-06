<?php
require_once __DIR__."/BaseStdArray.php";
require_once __DIR__."/StdArray.php";
require_once __DIR__."/AbstractContainer.php";
require_once __DIR__."/FsContainer.php";

global $hosts;
global $host_metas;
global $events;
$events = [];

factory(!define('_HOST_',"_HOST_")?:_HOST_,$host,$hosts,$null,new FsContainer(__DIR__."/data/_HOST_"));

function factory(string $name,BaseStdArray &$entity = null,BaseStdArray &$entitys = null,BaseStdArray &$metas = null,AbstractContainer $container = null,AbstractContainer $metaContainer = null,array $relationship = []){
    global $hosts;
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
    $default = function(array $entity_default = [],array $entitys_default = [],array $metas_default = []) use ($hosts,$name,&$entity,&$entitys,&$metas){
        if(isset($hosts[$name])){
            $config = $hosts[$name];
            if(!@$config['init_entity']){
                $entity->merge($entity_default);
            }
            if(!@$config['init_entitys']){
                $entitys->merge($entitys_default);
            }
            if(!@$config['init_metas']){
                $metas->merge($metas_default);
            }
            return;
            $hosts[$name] = [
                'id' => $name,
                'init_entity' => true,
                'init_entitys' => true,
                'init_metas' => true,
            ];
        }else{
            $hosts[$name] = [
                'id' => $name,
                'init_entity' => false,
                'init_entitys' => false,
                'init_metas' => false,
            ];
        }
    };
    unset($entitys->id);
    unset($metas->id);
    if(isset($hosts)){
        //$default($entity_default,$entitys_default,$metas_default);
    }
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
    if(!empty($relationship)){
        foreach ($relationship as $key => $relation) {
            foreach ($entitys as $_key => $_value) {
                $fu = function($entity) use ($relation,$_value){
                    foreach ($relation as $_key => $value) {
                        $define = $value;
                        if($one = $define['one']){
                            if($entity->{$_key} == $_value->{$one}){
                                $_value->{$one} = $entity;
                            }
                        };
                        if($many = $define['many']){
                            foreach ($_value->{$many} as $key => $value) {
                                if($entity->{$_key} == $value){
                                    $_value->{$many}[$key] = $entity;
                                }
                            }
                        }
                    }
                };
                run_event($key,$fu);
            }
        }
    }
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

function run_event(string $name,$data){
    global $events;
    foreach ($events as $key => $value) {
        if($key == $name && is_callable($value)){
            $value($data);
        }
    }
}