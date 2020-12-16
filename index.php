<?php
require 'vendor/autoload.php';
require 'config.php';
require 'function.php';
use Medoo\Medoo;

if($must_login){
    $pin = require_auth()."@";
}else{
    $pin = "";
}

$db = new Medoo([
	// required
	'database_type' => 'mysql',
	'database_name' => $dbname,
	'server' => $dbhost,
	'username' => $dbuser,
    'password' => $dbpass
]);

header("Content-Type: application/json");

$_host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$pin$_SERVER[HTTP_HOST]/";
//Parsing URL
$_path = array_values(array_filter(explode("/", parse_url($_SERVER['REQUEST_URI'])['path'])));

if(!empty($_path[0])){
    $folder = alphanumeric($_path[0]);
    if($db->has("t_games",['folder'=>$folder])){
        header('Content-Disposition: filename="'.$folder.'.json"');
        if(file_exists("./cache/$folder.json")){
            readfile("./cache/$folder.json");
        }else{
            $json = array();
            $games = $db->select('t_games',['id', 'title','titleid', 'fileSize'],['folder'=>$folder,'ORDER'=>['title'=>'ASC']]);
            foreach($games as $game){
                if(!empty($game['title']) && !empty($game['titleid'])){
                    if($game['fileSize']>0){
                        $json[] = [
                            'url'=>$_host.'dl/'.$game['id'].'/'.urlencode(str_replace('#','',trim($game['title']))),
                            'size'=>$game['fileSize']
                        ];
                    }else{
                        $json[] = $_host.'dl/'.$game['id'].'/'.urlencode(str_replace('#','',trim($game['title'])));
                    }
                }
            }
            file_put_contents("./cache/$folder.json",json_encode(['files'=>$json]));
            echo json_encode(['files'=>$json]);
        }
        die();
    }else if($folder=='dl'){
        $folder = alphanumeric($_path[1]);
        $db->update('t_games', ['hit'=>Medoo::raw('hit+1')], ['id'=>$folder]);
        header("Location: https://docs.google.com/uc?export=download&id=".$folder);
        die();
    }
}

$folders = $db->select('t_games', ['folder'=>Medoo::raw('DISTINCT folder')], $where);
$json = [
    'success' => 'Pakai Seperlunya, Download hanya yang mau dimainkan, agar tidak cepat kena limit. punya google drive mau dishare juga? mention @ibnux | Donasi Biaya Server trakteer.id/ibnux karyakarsa.com/ibnux'
];

foreach($folders as $folder){
    $json['locations'][] = $_host.$folder['folder'];
}

echo json_encode($json);
