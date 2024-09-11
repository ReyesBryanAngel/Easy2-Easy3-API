<?php

        // Sample for deleting records ... 
        require '../config/settings.php';        

        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST'){
            http_response_code(403);
            echo "Forbidden: GET request are allowed";
            $conn = null;
            die();
        }

        $input = @json_decode(@file_get_contents('php://input'), true);


        $sql ="DELETE FROM admin_users WHERE id = :id";
        $statement= $conn->prepare($sql);
        $statement->bindValue(':id', @$input['id']);

        if ( $statement->execute()){
            $result = array(
                "status"=>"success",
                "response_code"=>201,
                "message" => "Updated successfully"
            );  
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0'); 		  
            header($protocol . ' 200 ' . 'OK'); 
            header('Content-Type: application/json; charset=utf-8'); 
            header('Content-Type: application/json');
              
            echo  json_encode($result, JSON_PRETTY_PRINT);
            $conn = null;
            die();
        }

?>