<?php

Class dbConnect{
    //database info
    var $host = "127.0.0.1";
    var $user = "root";
    var $password = "root";
    var $database = "phone_book";
    var $conn;
    function connect() {
        $this->conn = null;

        try{
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->database.";charset=utf8", $this->user, $this->password);
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

