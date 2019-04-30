<?php
class Phone{

    // database connection
    public $conn;
    public $table_name = "phones";

    // object attributes
    public $id_phone;
    public $phone_number;
    public $id_contact;

    public function __construct($db){
        $this->conn = $db;
    }
}