<?php 

date_default_timezone_set('Asia/Manila');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
 header('Access-Control-Allow-Credentials: true');
 /* 
 THE CACHE IS TO PREVENT THE PREFLIGHT FROM EXECUTING EVERY REQUEST.
 WHEN ADDING CACHE AGE, THE PREFLIGHT ONLY EXECUTE AT FIRST TIME REQUEST
 */ 
 header('Access-Control-Max-Age: -1');

 header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
 header("Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept");
 header('Content-Type: application/json');

 if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])){
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");    
    }

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])){
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
   
    
    header($protocol . ' 200 ' . "OK");  
    die();
    
        
}

// https://adminer.gametime.solutions/
// $prod = 'localhost';
// $config['db_host']=$prod;
// $config['db_username']='root';
// $config['db_password']='8767cfbab7ebf3175cfebc1440d71509';
// $config['db_name']='colorgame';
 
$conn = null;

$config['host'] = '127.0.0.1';
$config['port'] = '3069';
$config['username'] = 'root';
$config['password'] = 'Toshiba_25';
$config['database'] = 'colorgame';


try {
    $conn = new PDO("mysql:host=".$config['host'].";port=".$config['port'].";dbname=".$config['database'], $config['username'], $config['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));    // set the PDO error mode to exception
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
  } catch(PDOException $e) {
 
    $response = array(
        'status'=>'failed',
        'message'=>"Connection failed: " . $e->getMessage()
    );

    header('Content-Type: application/json; charset=utf-8'); 
    header('Content-Type: application/json');
    echo  json_encode($response);
    
  }
