<?php
        require '../config/settings.php';        

        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST'){
            http_response_code(403);
            echo "Forbidden: GET request are allowed";
            $conn = null;
            die();
        }

        $input = @json_decode(@file_get_contents('php://input'), true);
            
        if($_SERVER['HTTP_HOST']=='crazywinapiv2.gamewizard.online'){
            $salt = 'J3Z9Sjh6mTywuujMK7C2D55tZgbdAW';
            $table = "crazywin";
        } else if ($_SERVER['HTTP_HOST']=='crazywinapi.gametime.solutions'){
            $salt = '@Z9S#h6$TywuujMK7C2%55*Zgb&AW';
            $table = "crazywin";
        } else {
            http_response_code(403);
            echo "Unauthorized";
            $conn = null;
            die();
        }

        $sql = "INSERT INTO admin_users (username, password) VALUES :username, :password";
        $statement= $conn->prepare($sql);
        $statement->bindValue(':username', @$input['username']);
        $statement->bindValue(':password', @$input['password']);
        $statement->execute();
        if (!empty(@$rows)){
            $result = array(
                "status"=>"success",
                "response_code"=>201,
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