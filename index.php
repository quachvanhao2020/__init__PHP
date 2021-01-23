<?php
require_once __DIR__."/vendor/autoload.php";
global $user;
global $users;
global $user_metas;
$user_metas = new StdArray;
factory(
    !define("_USER_","_USER_")?:_USER_,
    $user,
    $users,
    $user_metas,
    new FsContainer(__DIR__."/data/"._USER_),
    new FsContainer(__DIR__."/data/"._USER_."_META"),
    [

    ]
);

global $product;
global $products;
global $product_metas;
$product_metas = new StdArray;
factory(
    !define("_PRODUCT_","_PRODUCT_")?:_PRODUCT_,
    $product,
    $products,
    $product_metas,
    new FsContainer(__DIR__."/data/"._PRODUCT_),
    new FsContainer(__DIR__."/data/"._PRODUCT_."_META"),
    [
        _USER_ => [
            "id" => [
                "one" => "owner",
                "many" => "owners",
            ],
        ]
    ]
);

function __(){
    global $user;
    global $users;
    global $user_metas;
    global $product;
    global $products;
    global $product_metas;
    //var_dump($user,$users,$user_metas);
    $product = new StdArray;
    $product->id = "432423";   
    var_dump($product,$products,$product_metas);
    $product = new StdArray;
    $product->id = "99999";
    $product->owner = "4324";    
    $product->owners = [
        "4324",
        "4324",
    ];
    $product_metas['type'] = 333;
}

__();