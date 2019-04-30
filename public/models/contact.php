<?php
class Contact{

    // database connection
    public $conn;
    public $table_name = "contacts";

    // object attributes
    public $id;
    public $firstname;
    public $surnames;
    public $registration_date;

    public function __construct($db){
        $this->conn = $db;
    }
}