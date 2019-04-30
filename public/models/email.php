<?php
class Email{

    // database connection
    public $conn;
    public $table_name = "emails";

    // object attributes
    public $id_mail;
    public $email_address;
    public $id_contact;

    public function __construct($db){
        $this->conn = $db;
    }
}