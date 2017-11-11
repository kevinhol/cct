<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Image extends ModelBase {

    public function __construct() {
        parent::__construct(get_class());
    }

    public function get($field) {
        $func = "get" . ucfirst($field);
        $ret = $this->$func();
        return $ret;
    }

    function add($image) {
        // should validate all the fields are correct for the DB table here.
        try {
            $con = Database::initialize();
            $stmt = $con->prepare("INSERT INTO IMAGE (userid, file_name_hash, file_data_hash, location, extension) VALUES ( :userid, :file_name_hash, :file_data_hash, :location , :extension)");
            $stmt->bindParam(':userid', $data['userid'], PDO::PARAM_INT);
            $stmt->bindParam(':file_name_hash', $data['file_name_hash'], PDO::PARAM_STR);
            $stmt->bindParam(':file_data_hash', $data['file_data_hash'], PDO::PARAM_STR);
            $stmt->bindParam(':location', $data['location'], PDO::PARAM_STR);
            $stmt->bindParam(':extension', $data['extension'], PDO::PARAM_STR);

            $success = $stmt->execute();
            unset($con);
            if ($success) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            // should log this error
            echo "<br>" . $e->getMessage();
            return false;
        }
    }

}
