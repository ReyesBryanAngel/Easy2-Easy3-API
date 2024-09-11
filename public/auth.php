<?php 
require '../config/settings.php';
 
 

/* 
----------------------------
CAPTURE INPUT
----------------------------
*/
//USE THIS IS POSTED AS JSON
$input = @json_decode(@file_get_contents('php://input'), true);

//USE THIS IF POSTED AS FORM POST
//$input = $_POST;

 
$secret_key = "Ns8AUCuLKd78lNBvwgM7m0PwsIJZ8bnl";
function generateJWT($data, $secret_key) {
    // Set expiration time to 15 minutes
    $expiration_time = time() + (15 * 60);

    // Create token header as a JSON string
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

    // Create token payload as a JSON string
    $payload = json_encode(['exp' => $expiration_time] + $data);

    // Encode Header and Payload to Base64Url String
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // Combine all segments to create JWT
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    return $jwt;
}

//-------------------------------------
//START INPUT
//------------------------------------- 
$username = @$input["username"];
$password = md5(@$input["password"]);
  
 
$sql_login = " SELECT *  FROM admin_users  
	WHERE 
	`username` = :uName AND `password` = :uPass 
	 ";

$sthAth = $conn->prepare($sql_login);
$sthAth->bindValue(':uName', $username);
$sthAth->bindValue(':uPass', $password);
 

$sthAth->execute(); 
$rows = $sthAth->fetchAll(PDO::FETCH_ASSOC);
$queryString1 = $sthAth->queryString;

/* 
--------------------------------
TODO: create entry in token table
--------------------------------
*/
//TEMPORARY CODE
// $token = md5(time());
 
$status="success";   
if($rows[0]['id']!=NULL){

	//SUCCESS
	$result = array(
		"status"=>'success',
		"message"=>"Login Successfully!",
		"token"=> generateJWT($rows, $secret_key)
	);  

	$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0'); 		  
	header($protocol . ' 200 ' . 'OK'); 
	header('Content-Type: application/json; charset=utf-8'); 
	header('Content-Type: application/json');
	echo  json_encode($result); 

}else{
	//FAILED
	$transactions = array();
	$result = array(
		"status"=>'failed',
		"message"=>"Login Failed!"
	);  

	$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0'); 		  
	header($protocol . ' 200 ' . 'OK'); 
	header('Content-Type: application/json; charset=utf-8'); 
	header('Content-Type: application/json');
	echo  json_encode($result); 
}

 //CLOSE DATABASE CONNECTION
 $conn = null;
?>
