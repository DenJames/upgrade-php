<?php
class BuildRoute extends RestfulApi
{
    public $data=[];
    protected $datacap=[];
    public function __construct(&$RestfulApi){
        if (is_object($RestfulApi)) {
            foreach($RestfulApi as $key=>$val){
                $this->{$key}=$val;
            }
        }
    }
    public function run($callback=null,$call=null){
        $this->updatePlugin();
        $this->bond_data();
        if($this->continue==true && $GLOBALS["RestfulApi"]->found==false){
            $this->continue=false;
            $GLOBALS["RestfulApi"]->path=$this->path;
            $GLOBALS["RestfulApi"]->found=true;
            if(is_callable($callback)){
                $callback($this->data,$this->plugins);   
            }else if(property_exists($this->plugins,$callback) && is_callable($call)){
                $call($this->data,$this->plugins->{$callback});
            }
        }
    }
    public function escape_url($url){
        return "/^".addcslashes($url,"/")."$/";
    }
    protected function bond_data(){
        static $a,$is_patch,$name;
        $a=explode("/",$this->path);
        $is_patch=false;
        $name="";
        foreach ($a as $key=>$val) {
            if($is_patch){
                $this->data[$name].="/".$val;
            }
            else if(array_key_exists($key,$this->datacap) && $this->datacap[$key]!=null){
                $find=$this->datacap[$key]["variable"];
                $name=$this->datacap[$key]["name"];
                if($find=="string" || $find=="str"){
                    $this->data[$name] = (string) $val;
                }else if($find=="int" || $find =="integer"){
                    $this->data[$name] = (integer) $val;
                }
                else if($find=="float"){
                    $this->data[$name] = (float) $val;
                }
                else if($find=="double"){
                    $this->data[$name] = (double) $val;
                }else if($find=="path"){
                    $this->data[$name]=$val;
                    $is_patch=true;
                }else{
                    $this->data[$name] = $val;
                }
            }
        }
    }
    protected function convert($data=null){
        if(preg_match("/<([a-z]+):([a-z0-9]+)>/i",$data,$mathes)){
            static $find,$name;
            $find=strtolower($mathes[1]);
            $name=$mathes[2];
            $this->datacap[]=["variable"=>$find,"name"=>$name];
            if($find=="string" || $find=="str"){
                return "([^/]+)";
            }else if($find=="int" || $find =="integer"){
                return "([0-9]+)";
            }
            else if($find=="float"){
                return "([0-9]+\.[0-9]+)";

            }
            else if($find=="double"){
                return "([0-9]+\.[0-9]{2})";
            }
            else if($find=="path"){
                return "(.+)";
            }
            else {
                return "([^/]+)";
            }
        }else{
            $this->datacap[]=null;
            return $data;
        }
    }
    protected function build_url($url=null){
        if(!is_null($url)){
            $this->data=[];
            $this->datacap=[];
            $a=explode("/",$url);
            foreach($a as $key=>$val){
                $a[$key]=$this->convert($val);
            }
            return implode("/",$a);
        }
        return $url;
    }
    public function route($url=null,$use=[]){
        return $this->build_route($url,$use);
    }
    public function build_route($url=null,$use=[]){
        return $this->when($url,$use);
    }
    public function when($url=null,$use=[]){
        static $paths;
        if($GLOBALS["RestfulApi"]->found==true){
            return $this;
        }
        $paths=$this->req_path;
        $this->path=$paths;
        if(is_array ($url) || is_object($url)){
            foreach ($url as $value) {
                # code...
                $value=$this->escape_url($this->build_url($value));
                if(preg_match($value,$paths)){
                    return $this->use($value,$use);
                }
            }
            return $this;
        }else{ 
            $url=$this->escape_url($this->build_url($url));
            if(preg_match($url,$paths)){
                return $this->use($url,$use);
            }
            else{
                return $this;
            }
        }
    }
}
class Path extends RestfulApi
{
    public function __construct($RestfulApi){
        if (is_object($RestfulApi)) {
            foreach($RestfulApi as $key=>$val){
                $this->{$key}=$val;
            }
        }
    }
    public function requestedPath(){
        return $this->req_path;
    }
    public function staticPath(){
        return $this->static_path;
    }
    public function currentPath (){
        return $this->path;
    }
    static public function mimetype($niddle=null){
        static $ar;
        $niddle=strtolower($niddle);
        $ar=["woff"=>"font/woff","woff2"=>"font/woff2","css"=>"text/css"];
        if(array_key_exists($niddle,$ar)){
            return $ar[$niddle];
        }
        return null;
    }
    static public function pathinfo($url=null){
        return self::parse_url($url,true);
    }
    static public function parse_url($url=null,$path_info=false){
        $url=parse_url($url);
        if ($path_info ) {
            if(array_key_exists("path", $url)){
                return pathinfo($url['path']);
            }else{
                return [];
            }
            
        }
        return $url;
    }
    
    static public function  get_file($where,$line=null){
        static $r;
        if(strripos($where,".ph")){
            return "";
        }
        if (is_file($where) && $stream = fopen($where, 'rb')) {
            $extention=self::pathinfo($where);
            $extention=isset($extention['extension'])?$extention['extension']:"";
            header('Content-Type: '.mime_content_type($where));
            $mimetype=self::mimetype($extention);
            if($mimetype){
                header('Content-Type: '.$mimetype);
            }
            $r = stream_get_contents($stream);
            fclose($stream);
            if(is_int($line) and $stream || is_string($line) and $stream){
                // $r=preg_split("/\r\n|\n|\r/", $r);
                // print_r($r);
                if(is_string($line)){
                    if($line=="all"){
                        return $r;
                    }
                    elseif($line=="first"){
                        return isset($r[0])?$r[0]:'';
                    }elseif($line=="middle"){
                        return isset($r[(count($r)-1)/2])?$r[(count($r)-1)/2]:'';
                    }elseif($line=="last"){
                        return isset($r[count($r)-1])?$r[count($r)-1]:'';
                    }else{
                        return "";
                    }
                }else{
                    if(@$r[$line]){
                        return $r[$line];
                    }else{
                        return "";
                    }
                }
            }
            else{
                return $r;
            }
        }
        else{
            return '';
        }
    }
};
abstract class RestfulApiRouter {
    public function get($path="",$callback=null){
        static $handler;
        $handler=$this->route($path,"GET");
        if(is_callable($callback)){
            $handler->run($callback);
        }
        return $handler;
       
    }
    public function post($path="",$callback=null){
        static $handler;
        $handler=$this->route($path,"POST");
        if(is_callable($callback)){
            $handler->run($callback);
        }
        return $handler;
    }
    public function put($path="",$callback=null){
        static $handler;
        $handler=$this->route($path,"PUT");
        if(is_callable($callback)){
            $handler->run($callback);
        }
        return $handler;
    }
    public function delete($path="",$callback=null){
        static $handler;
        $handler=$this->route($path,"DELETE");
        if(is_callable($callback)){
            $handler->run($callback);
        }
        return $handler;
    }
    public function patch($path="",$callback=null){
        static $handler;
        $handler=$this->route($path,"PATCH");
        if(is_callable($callback)){
            $handler->run($callback);
        }
        return $handler;
    }
    public function option($path="",$callback=null){
        static $handler;
        $handler=$this->route($path,"OPTION");
        if(is_callable($callback)){
            $handler->run($callback);
        }
        return $handler;
    }
    public function head($path="",$callback=null){
        static $handler;
        $handler=$this->route($path,"HEAD");
        if(is_callable($callback)){
            $handler->run($callback);
        }
        return $handler;
    }
}
class RestfulApiReq{
    public function __construct()
    {
        static $req_,$body,$ao;
        $oa=explode("?", $_SERVER["REQUEST_URI"]);
        $body=file_get_contents('php://input');
        $req_=(object)[
            "path"=>isset($oa[0])?$oa[0]:"",
            "query"=>isset($oa[1])?$oa[1]:"",
            "body"=>RestfulApi::is_json($body)?json_decode($body,true):$_POST,
            "header"=>getallheaders(),
            "get"=>$_GET,
            "post"=>$_POST,
            "request"=>$_REQUEST,
            "files"=>$_FILES,
            "cookie"=>$_COOKIE,
            "session"=>isset($_SESSION)?$_SESSION:""
        ];
        foreach($req_ as $key=>$value){
            $this->{$key}=$value;
        }   
    }
}
class Pipe{
    protected $filename=false;
    protected $fpoutput=false;
    protected $mode=null;
    public $opened=false;
    public $closed=false;
    public function __construct(){
        define("PIPE_STREAM",1);
        define("PIPE_CLOSECONTENT",2);
    }
    public function open($write=null,$mode=PIPE_CLOSECONTENT){
        
        $this->opened=true;
        $this->mode=$mode;
        if($this->mode==PIPE_STREAM){
            $this->stream($write);
        }else{
            $this->filename=tempnam(sys_get_temp_dir(),"RestfulApi");
            $this->flush();
            $this->push($write);
        }  
    }
    public function push($write=null){
        if($this->mode==PIPE_STREAM){
            return $this->stream($write);
        }else if($this->mode==PIPE_CLOSECONTENT){
            return $this->put($write);
        } 
    }
    protected function put($write=null){
        if(!is_null($write) && $this->filename){ 
            $fp=fopen($this->filename, 'a');
            if($fp){
                if (flock($fp, LOCK_EX)) {
                    fwrite($fp, $write); 
                    flock($fp, LOCK_UN); 
                }else{
                    throw new Exception("{$this->filename} pipe open in another instance\r\n", 1);      
                }
                fclose($fp);
                return true;
            }
        }  
        return false; 
    }
    protected function stream($write=null){
        if(!is_null($write)){
            file_put_contents("php://output",$write);
            flush();
            ob_flush();
            return true;
        }
        return false;
    }
    public function close($write=null){
        $this->push($write);
        if ($this->filename){
            if(ob_get_level()){
                ob_clean();
            }
            file_put_contents("php://output",file_get_contents($this->filename));
            flush();
            ob_flush();
            if (unlink($this->filename)) {
                $this->filename=false;
                $this->closed=true;
                return true;
            } else {
                return false;
            }
        }
        if($this->opened && $this->closed==false){
            if (!ob_get_level()) {
                ob_end_flush();
            }
        }
        return true;
    }
    public function flush(){
        if($this->filename){
            $fp=fopen($this->filename, 'w');
            if ($fp) {
                if (flock($fp, LOCK_EX)) {
                    ftruncate($fp, 0);
                    flock($fp, LOCK_UN);
                } else {
                    throw new Exception("{$this->filename} pipe open in another instance\r\n", 1);
                }
                fclose($fp);
                return true;
            }
        }
        return false;
    }
    public function start($write=null){
        return $this->open($write);
    }
    public function append($write=null){
        return $this->push($write);
    }
    public function stop($write=null){
        return $this->close($write);
    }
    public function begin($write=null){
        return $this->open($write);
    }
    public function continue($write=null){
        return $this->push($write);
    }
    public function end($write=null){
        return $this->close($write);
       
    }
    public function __destruct()
    {   
        $this->close();   
    }

}
class RestfulApiRes{
    public $pipe;
    public $opened=false;
    public $closed=false;
    public function __construct()
    {
        $this->pipe=new Pipe();
        
    }
    public function header($array=[]){
        static $key,$value;
        if(is_array($array) || is_object($array)){
            foreach($array as $key=>$value){
                if($key=="StatusCode"){
                    http_response_code($value);
                }elseif($key=="RawStatusCode"){
                    header($value);
                }
                else{
                    header($key.": ".$value);
                }
            }
        }
        else{
            header($array);
        }  
    }
    public function stream($body=null){
        $this->body($body);
        flush();
        ob_flush();
    }
    public function flush(){
        if (ob_get_level()) {
            ob_clean();
        }
    }
    public function body($body=null){
        if($this->opened==false){
            $this->opened=true;
            $this->flush();
        }
        if(!is_null($body)&& $this->opened && $this->pipe->opened==false){
            
            file_put_contents("php://output" ,$body);
        }
    }
    public function end($body=null){
        $this->body($body);
        $this->closed=true;
    }
}

class RestfulApi extends RestfulApiRouter{
    protected $continue=false;
    protected $plugins=[];
    protected $pluginslist=[];
    protected $req_path="";
    protected $req_query="";
    protected $current_path="";
    protected $path="";
    protected $static_path="";
    public $data=[];
    function __construct ($plugins=null,$static_path=null){
        $this->plugins=(object)[];
        if (is_array($plugins) || is_object($plugins)) {
            foreach($plugins as $key=>$val){
                $this->addPlugin($key,$val);
            }
        }
        if(!empty($static_path)){
            $this->static_path=$static_path;
        }else{
            $this->static_path=$_SERVER['DOCUMENT_ROOT'];
        }
        if(!array_key_exists("RestfulApi",$GLOBALS)){
            $GLOBALS["RestfulApi"]=(object) Array();
            $GLOBALS["RestfulApi"]->found=false;
        }
        $this->plugins->req=new RestfulApiReq;
        $this->plugins->res=new RestfulApiRes;
        $this->req_path=$this->plugins->req->path;
        $this->req_query=$this->plugins->req->query;
    }
    static public function is_json($string=null){
        if(preg_match("/^\[(.*)\]$|^\{(.*)\}$/",$string)){
            return true;
        }else{
            return false;
        }
    }
    static public function flush_buffer(){
        if (ob_get_level()) {
            ob_clean();
        }
    }
    public function run($callback="",$call=null){
        RestfulApi::flush_buffer();
        $this->updatePlugin();
        if($this->continue==true && $GLOBALS["RestfulApi"]->found==false){
            $GLOBALS["RestfulApi"]->path=$this->path;
            $this->continue=false;
            $GLOBALS["RestfulApi"]->found=true;
            if (is_callable($callback)){
                $callback($this->plugins);   
            }else if(property_exists($this->plugins,$callback) && is_callable($call)){
                $call($this->plugins->{$callback});
            }
        }
    }
    public function end($callback=null){
        RestfulApi::flush_buffer();
        if($GLOBALS["RestfulApi"]->found==false){
            if (is_callable($callback)) {

                $callback($this->plugins);
            }else{
                http_response_code(405);
                die("METHOD NOT ALLOWED");
            }
        }
    }
    protected function use($url="",$use=[]){
        static $callback;
        $method=$_SERVER['REQUEST_METHOD'];
        $this->current_path=$url;
        if(is_array($use) || is_object($use)){
            foreach ($use as $value) {
                if($method==$value){
                    $this->continue = true;
                    break;
                }
            }
        }else{
            if($method==$use){
                $this->continue = true;
            }
        }
        return $this;
        
    }
    public function plugin($callback="",$call=null){
        $this->updatePlugin();
        if (is_callable($callback)){
            $callback($this->plugins);
        }else if(property_exists($this->plugins,$callback) && is_callable($call)){
            $call($this->plugins->{$callback});
        }
    }
    public function addPlugin($id="",$plugin=""){
        if(property_exists($this->plugins,$id) ){
            return false;
        }else if(!empty($id) ){
            if(is_string($plugin) && class_exists($plugin)){         
                $this->plugins->{$id}= new $plugin($this);
                $this->pluginslist[$id]=$plugin;
            }
            else{
                $this->plugins->{$id}= $plugin;
            }
            return true;
        }else{
            return false;
        }

    }
    public function updatePlugin($id="",$plugin=""){
        if(!empty($id) && property_exists($this->plugins,$id) && class_exists($plugin)){
            $this->plugins->{$id}=new $plugin($this);
            return true;
        }else{
            if(count($this->pluginslist)>0){
                foreach ($this->pluginslist as $id=>$plugin){
                    $this->updatePlugin($id,$plugin);
                }
                $this->plugins->data=$this->data;
                return true;
            }
            return false;

        }
    }
    public function removePlugin($id=null){
        if(!empty($id) && !is_null($id)){
            if(property_exists($this->plugins,$id)){
                unset($this->plugins->{$id});
            }
            if (property_exists($this->pluginslist, $id)) {
                unset($this->pluginslist[$id]);
            }
            return true;
        }else{
            return false;
        }
    }
    public function resources_path($path=""){
        if(!empty($path)){
            $this->req_path=$path;
        }else{
            return $this->req_path;
        }
    }
    public function route($path="",$use=[]){
        return $this->when($path,$use);
    }
    public function preg_route($path="",$use=[]){
        return $this->when($path,$use,true);
    }
    public function when($url=[],$use=[],$preg=false){
        static $paths;
        $this->data=[];
        if($GLOBALS["RestfulApi"]->found==true){
            return $this;
        }
        $paths=$this->req_path;
        $this->path=$paths;
        if(is_array ($url) || is_object($url)){
            foreach ($url as $value) {
                # code...
                if($preg && preg_match($value,$paths,$mathes)){
                    $this->data=$mathes;
                    return $this->use($value,$use);
                }
                else if($paths==$value){
                    return $this->use($value,$use);
                }
            }
            return $this;
        }else{
            if($preg && preg_match($url,$paths,$mathes)){
                $this->data=$mathes;
                return $this->use($url,$use);
            }
            else if ($paths==$url) {
                return $this->use($url, $use);
            }
            else{  
                return $this;
            }
        }
    }
}
?>