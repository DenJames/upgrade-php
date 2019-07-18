<?php
require_once "../RestfulApi.php";
// require_once '../../smarty-3.1.33/libs/Smarty.class.php';
/**
* use this in a htaccess file to make the restful api work
* <IfModule mod_rewrite.c>
*    RewriteEngine On
*    RewriteBase /
*    #RewriteCond %{REQUEST_FILENAME} !-d
*    #RewriteCond %{REQUEST_FILENAME} !-f
*    RewriteRule ^(.*)$ server-api.php
* </IfModule>
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$api=new RestfulApi();
// $smarty = new Smarty_build();
// $smarty->assign("user","app");
$api->addPlugin("path",'Path');
$api->addPlugin("buildroute",'BuildRoute');
// $api->addPlugin("smarty",$smarty);
$api->runPlugin("buildroute",function($plugin){
    $plugin->route('/api/<string:controller>/<string:action>/<path:ffff>',["GET"])->run(function($data,$plugins){
        echo "float is called";
        $path=$plugins->path;
        var_dump($data);
        //echo $path->currentPath();
    });
    $plugin->route('/<string:name>/<float:php>',["GET"])->run(function($data,$plugins){
        echo "float is called";
        $path=$plugins->path;
        var_dump($data);
        //echo $path->currentPath();
    });
    $plugin->route('/<string:name>/<int:php>',["GET"])->run(function($data,$plugins){
        echo "int is called";
        $path=$plugins->path;
        var_dump($data);
        //echo $path->currentPath();
    });
});
foreach([["url"=>"/home","name"=>"home"],["url"=>"/about","name"=>"about"],["url"=>"/contact","name"=>"contact"]] as $vals){
    $api->route($vals['url'],["GET"])->run(function($plugins){
        $path=$plugins->path;
        $url = $path->currentPath();     
    });
}
$api->preg_route("@(.*)@",["GET"])->run(function($plugins){
    print_r($plugins->data);
    $path=$plugins->path;
    echo $path->get_file($path->staticPath().$path->currentPath());
});
// print_r($GLOBALS['RestfulApi']);
?>