<?php

//for events selection of boards after validating of jwt
require('../../../../config/config-colorgame.php');   

header('Content-Type: application/json; charset=utf-8'); 
header('Content-Type: application/json');  

$input = @json_decode(@file_get_contents('php://input'), true);


                 
                    $get_chips = "SELECT * FROM chips WHERE 
                    `chip_status` = 'active'";
    
                    $chipstatement= $conn->prepare($get_chips);
                    $chipstatement->execute();
                    $chip_details = $chipstatement->fetchAll(PDO::FETCH_ASSOC);

                        $result = array(
                            "status"=>"success",
                            "response_code"=>200,
                            "message" => "Dashboard",
                            "data" => $chip_details
                                                // 'chips' => $chip_details,
                        );  



                    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0'); 		  
                 
                    http_response_code(200);
                    $code = 200;
               



$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0'); 		  
header($protocol . " $code " . 'OK'); 
echo  json_encode($result); 

//CLOSE DATABASE CONNECTION
$conn = null;  
?>