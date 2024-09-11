<?php

        // Sample for selecting records ... 
        require '../config/settings.php';        

        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET'){
            http_response_code(403);
            echo "Forbidden: GET request are allowed";
            $conn = null;
            die();
        }

        $sql = "SELECT * FROM admin_users";
        $statement= $conn->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!empty(@$rows)){
            $result = array(
                "status"=>"success",
                "response_code"=>200,
                "message" => "Retrieved the requested records.",
                "data" => $rows
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