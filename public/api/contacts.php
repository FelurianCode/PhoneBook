<?php

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");

    include_once '../config/connection.php';
    include_once '../models/contact.php';
    include_once '../models/phone.php';
    include_once '../models/email.php';

    $request_method=$_SERVER["REQUEST_METHOD"];

    //actions depending on request method:
    switch($request_method){
        case 'GET':

            if(!empty($_GET["id"]))
            {
                $id=intval($_GET["id"]);
                get_contact_by_id($id);
            }
            else
            {
                get_contacts();
            }
            break;

        case 'POST':

            store_contact();
            break;

        case 'PUT':

            $id=intval($_GET["id"]);
            update_contact($id);
            break;

        case 'DELETE':

            $id=intval($_GET["id"]);
            delete_contact($id);
            break;

        default:
            // Invalid Request Method
            header("HTTP/1.0 405 Method Not Allowed");
            break;
    }

    //Lists all contacts

    function get_contacts(){

        // database connection
        $database = new dbConnect();
        $db = $database->connect();
        $contact = new Contact($db);
        $phone = new Phone($db);
        $email = new Email($db);

        // get contacts data
        $result = findAll($contact);
        $num = $result->rowCount();

        // if there are results
        if ($num > 0) {

            $contacts_arr = array();
            $contacts_arr["results"] = array();

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

                extract($row);
                $phones_arr = array();
                $emails_arr = array();

                //checking if the contact has registered phones
                $contact_phones = $phone->conn->prepare("Select * from ".$phone->table_name." where id_contact=".$id);
                $contact_phones->execute();

                if ($contact_phones){

                    while ($row_p = $contact_phones->fetch()) {
                        extract($row_p);

                        $phone_elem = array(
                            "id" => $id_phone,
                            "phone_number" => $phone_number
                        );

                        array_push($phones_arr, $phone_elem);
                    }

                }

                //checking if the contact has registered emails
                $contact_mails = $email->conn->prepare("Select * from ".$email->table_name." where id_contact=".$id);
                $contact_mails->execute();

                if ($contact_mails){

                    while ($row_m = $contact_mails->fetch()) {
                        extract($row_m);

                        $mail_elem = array(
                            "id" => $id_mail,
                            "email_address" => $email_address
                        );

                        array_push($emails_arr, $mail_elem);
                    }

                }

                $contact_elem = array(
                    "id" => $id,
                    "name" => $firstname,
                    "surnames" => $surnames,
                    "phones" => $phones_arr,
                    "emails" => $emails_arr,
                    "created_at" => $registration_date
                );

                array_push($contacts_arr["results"], $contact_elem);
            }

            // set response code - 200 OK
            http_response_code(200);
            echo json_encode($contacts_arr);
        }
        else{

            // set response code - 404 Not found
            http_response_code(404);
            echo json_encode(
                array("message" => "No contacts found.")
            );
        }
    }

    //get contact by id

    function get_contact_by_id($id){

        // database connection
        $database = new dbConnect();
        $db = $database->connect();
        $contact = new Contact($db);
        $phone = new Phone($db);
        $email = new Email($db);

        $contact->id = $id;

        // get contact by id
        $result = find($contact);
        $num = $result->rowCount();

        // if there are results
        if ($num > 0) {

            $contacts_arr = array();
            $contacts_arr["result"] = array();

            $row = $result->fetch(PDO::FETCH_ASSOC);
            extract($row);

            $phones_arr = array();
            $emails_arr = array();

                //checking if the contact has registered phones
                $contact_phones = $phone->conn->prepare("Select * from ".$phone->table_name." where id_contact=".$id);
                $contact_phones->execute();

                if ($contact_phones){

                    while ($row_p = $contact_phones->fetch()) {
                        extract($row_p);

                        $phone_elem = array(
                            "id" => $id_phone,
                            "phone_number" => $phone_number
                        );

                        array_push($phones_arr, $phone_elem);
                    }

                }

                //checking if the contact has registered emails
                $contact_mails = $email->conn->prepare("Select * from ".$email->table_name." where id_contact=".$id);
                $contact_mails->execute();

                if ($contact_mails){

                    while ($row_m = $contact_mails->fetch()) {
                        extract($row_m);

                        $mail_elem = array(
                            "id" => $id_mail,
                            "email_address" => $email_address
                        );

                        array_push($emails_arr, $mail_elem);
                    }

                }

                $contacts_arr = array(
                    "id" => $id,
                    "name" => $firstname,
                    "surnames" => $surnames,
                    "phones" => $phones_arr,
                    "emails" => $emails_arr,
                    "created_at" => $registration_date
                );


            // set response code - 200 OK
            http_response_code(200);
            echo json_encode($contacts_arr);
        }
        else{

            // set response code - 404 Not found
            http_response_code(404);
            echo json_encode(
                array("message" => "No contact found.")
            );
        }

    }

    //create new contact

    function store_contact(){
        $database = new dbConnect();
        $db = $database->connect();

        $contact = new Contact($db);
        $phone = new Phone($db);
        $email = new Email($db);


        // get posted data
        $data = json_decode(file_get_contents("php://input"));

        // make sure data is not empty
        if(
            !empty($data->firstname) &&
            !empty($data->surnames) &&
            !empty($data->phones) &&
            !empty($data->emails)
        ){

            // set contact values
            $contact->firstname = $data->firstname;
            $contact->surnames = $data->surnames;

            //save contact
            $saved_contact = save($contact);

            //phones and emails
            $phones = $data->phones;
            $emails = $data->emails;

            if($saved_contact) {

                //save contact phones
                foreach ($phones as $number) {
                    $phone->phone_number = $number;
                    $phone->id_contact = (int)$saved_contact;
                    savePhone($phone);
                }

                //save contact emails
                foreach ($emails as $address) {
                    $email->email_address = $address;
                    $email->id_contact = (int)$saved_contact;
                    saveEmail($email);
                }


                // 201 created
                http_response_code(201);
                echo json_encode(array("message" => "Contact stored."));
            }
            else{

                http_response_code(503);
                echo json_encode(array("message" => "Unavailable service."));
            }
        }

        else{

            http_response_code(400);
            echo json_encode(array("message" => "Error. Data is incomplete."));
        }
    }

    //update contact info

    function update_contact($id){
        $database = new dbConnect();
        $db = $database->connect();
        $contact = new Contact($db);
        $phone = new Phone($db);
        $email = new Email($db);

        $data = json_decode(file_get_contents("php://input"));

        // contact id to be edited
        $contact->id = $id;

        // set contact values
        $contact->firstname = $data->firstname;
        $contact->surnames = $data->surnames;

        //update contact
        $updated_contact = update($contact);
        $num = $updated_contact->rowCount();

        //phones and emails
        $phones = $data->phones;
        $emails = $data->emails;
        $temp_arr = array();


        if($num > 0){

            //get contact phones
            foreach ($phones as $number) {
                array_push($temp_arr, $number);
            }

            $num = count($temp_arr);

            //update contact phones
            for ($x = 0; $x<$num; $x++){
                $phone->phone_number = $temp_arr[$x]->phone_number;
                $phone->id_phone = $temp_arr[$x]->id;
                updatePhone($phone);
            }

            $temp_arr = array();

            //get contact mails
            foreach ($emails as $mail) {
                array_push($temp_arr, $mail);
            }

            $num = count($temp_arr);

            //update contact mails
            for ($x = 0; $x<$num; $x++){
                $email->email_address = $temp_arr[$x]->email_address;
                $email->id_mail = $temp_arr[$x]->id;
                updateMail($email);
            }


            http_response_code(200);
            echo json_encode(array("message" => "Contact updated."));
        }

        else{

            http_response_code(503);
            echo json_encode(array("message" => "Contact not found."));
        }
    }

    //delete contact register

    function delete_contact($id){
        $database = new dbConnect();
        $db = $database->connect();
        $contact = new Contact($db);
        $phone = new Phone($db);
        $email = new Email($db);

        // set contact to delete
        $contact->id = $id;

        if(delete($contact, $phone, $email)){
            http_response_code(200);
            echo json_encode(array("message" => "Contact deleted."));

        }

        else{

            http_response_code(503);
            echo json_encode(array("message" => "Delete error, Contact not found or Unavailable service"));
        }
    }

    /********** Queries **********/

    //read by id query//

    function find($object){
        $query = "SELECT * FROM ".$object->table_name." WHERE id=".$object->id;

        $result = $object->conn->prepare($query);

        // execute query
        $result->execute();

        return $result;
    }

    //read query

    function findAll($object){
        $query = "SELECT * FROM ".$object->table_name;

        $result = $object->conn->prepare($query);

        // execute query
        $result->execute();

        return $result;
    }

    //insert queries//

    function save($object){

        $query = "INSERT INTO " . $object->table_name . "(firstname, surnames) VALUES ('".$object->firstname."', '".$object->surnames."')";

        $result = $object->conn->prepare($query);

        // execute query
        if($result->execute()){
            $id = $object->conn->lastInsertId();
            return $id;
        }

        return false;

    }

    function savePhone($object){

        $query = "INSERT INTO " . $object->table_name . "(phone_number, id_contact) VALUES ('".$object->phone_number."', '".$object->id_contact."')";

        $result = $object->conn->prepare($query);

        // execute query
        if($result->execute()){
            return true;
        }

        return false;

    }

    function saveEmail($object){

        $query = "INSERT INTO " . $object->table_name . "(email_address, id_contact) VALUES ('".$object->email_address."', '".$object->id_contact."')";

        $result = $object->conn->prepare($query);

        // execute query
        if($result->execute()){
            return true;
        }

        return false;

    }

    //update queries//

    function update($object){

        $query = "UPDATE " . $object->table_name . "
                SET
                    firstname = '".$object->firstname."',
                    surnames = '".$object->surnames."'
                WHERE
                    id = ".$object->id;

        $result = $object->conn->prepare($query);

        if($result->execute()){
            return $result;
        }

        return $result;
    }

    function updatePhone($object){

        $query = "UPDATE " . $object->table_name . "
                    SET
                        phone_number = '".$object->phone_number."'
                    WHERE
                        id_phone = ".$object->id_phone;

        $result = $object->conn->prepare($query);

        if($result->execute()){
            return true;
        }

        return false;
    }

    function updateMail($object){

        $query = "UPDATE " . $object->table_name . "
                        SET
                            email_address = '".$object->email_address."'
                        WHERE
                            id_mail = ".$object->id_mail;

        $result = $object->conn->prepare($query);

        if($result->execute()){
            return true;
        }

        return false;
    }

    function delete($contact, $phone, $email){

        //Check if contact exists
        $queryFind = "SELECT * FROM " . $contact->table_name . " WHERE id = " . $contact->id;
        $resultFind = $contact->conn->prepare($queryFind);
        $resultFind->execute();

        if ($resultFind->rowCount()>0){

            // delete phones an mails first
            $queryP = "DELETE FROM " . $phone->table_name . " WHERE id_contact = " . $contact->id;
            $queryE = "DELETE FROM " . $email->table_name . " WHERE id_contact = " . $contact->id;

            $resultP = $phone->conn->prepare($queryP);
            $resultE = $email->conn->prepare($queryE);

            $resultP->execute();
            $resultE->execute();

            // finally delete contact
            $queryC = "DELETE FROM " . $contact->table_name . " WHERE id = " . $contact->id;
            $resultC = $contact->conn->prepare($queryC);
            $resultC->execute();

            if ($resultC->rowCount()>0) {
                return true;
            }
            return false;

        }else{
            return false;
        }


    }
