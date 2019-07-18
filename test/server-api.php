<?php
require_once "../RestfulApi.php";
require_once '../../smarty-3.1.33/libs/Smarty.class.php';
/**
* use this in a htaccess file to make the restful api work
* <IfModule mod_rewrite.c>
*    RewriteEngine On
*    RewriteBase /anthony/
*    #RewriteCond %{REQUEST_FILENAME} !-d
*    #RewriteCond %{REQUEST_FILENAME} !-f
*    RewriteRule ^api(.*)$ server-api.php?resouces=$1
* </IfModule>
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$api=new RestfulApi();

class Smarty_build extends Smarty {

    function __construct()
    {
 
         // Class Constructor.
         // These automatically get set with each new instance.
 
         parent::__construct();
 
         $this->setTemplateDir('tmpl/templates/');
         $this->setCompileDir('tmpl/templates_c/');
         $this->setConfigDir('tmpl/configs/');
         $this->setCacheDir('tmpl/cache/');
 
         $this->caching = Smarty::CACHING_LIFETIME_CURRENT;
         $this->assign('app_name', 'Guest Book');
    }
 
}


require_once 'inc/app.php';
$smarty = new Smarty_build();
$smarty->assign("user","app");
$api->addPlugin("path",'Path');
$api->addPlugin("buildroute",'BuildRoute');
$api->addPlugin("smarty",$smarty);
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
foreach($webmaster->website("pages") as $vals){
    $api->route($vals['url'],["GET"])->run(function($plugins){
        global $webmaster;
        $path=$plugins->path;
        $url = $path->currentPath();
        $page=$webmaster->page($url,true);
        if($page){
            include_once "theme/{$webmaster->website("theme")}/echo.php";
        }   
    });
}

$api->preg_route("@(.*)@",["GET"])->run(function($plugins){
    print_r($plugins->data);
    $path=$plugins->path;
    echo $path->get_file($path->staticPath().$path->currentPath());
});
// print_r($GLOBALS['RestfulApi']);
?>