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

 


//-------------------------------------
//START INPUT
//------------------------------------- 
$from = @$input["fromDate"];
$to = @$input["toDate"];
$order = @$input["order"];
$rows = @$input["rows"];
$to = @$input["toDate"];
$page = (@$input["page"] > 1 )?@$input["page"]:1;
 
$signature = @$input["signature"];
$salt = 'J3Z9Sjh6mTywuujMK7C2D55tZgbdAW';	
	 
 
$page_rows = $rows;
$page_start = $page;
 
$sql_count = " SELECT COUNT(id) AS total_rows FROM transactions  
	WHERE 
	filter = 0 
	AND ( settlement_time BETWEEN :fromDate AND :toDate )
	 ";

$sthCount = $conn->prepare($sql_count);
$sthCount->bindValue(':fromDate', $from);
$sthCount->bindValue(':toDate', $to);
 

$sthCount->execute(); 
$rowsCount = $sthCount->fetchAll();
$queryString1 = $sthCount->queryString;
 
$status="success";   
$total_rows = intval($rowsCount[0]['total_rows']);
if($total_rows>0){
	$total_page = @ceil(@$total_rows/@$page_rows);
}else{
	$total_page=10;
}
 
$transactions = array();
$result = array(
	"status"=>$status,
	"message"=>"History",
	"totalRows"=>$total_rows,
	"records"=>array(),
	"summaryRows"=>array()
);  

$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0'); 		  
header($protocol . ' 200 ' . 'OK'); 
header('Content-Type: application/json; charset=utf-8'); 
header('Content-Type: application/json');
echo  json_encode($result); 

 //CLOSE DATABASE CONNECTION
 $conn = null;


?>
