<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class User extends ModelBase{
    
    public function __construct(){
        parent::__construct(get_class());
    }
    
    public function get($field)
    {
        $func = "get" . ucfirst($field);
        $ret = $this->$func();
        return $ret;
    }

    function existsCheck($username) {
        if ($username == null) {
            return false;
        }

        $con = Database::initialize();

        $stmt = $con->prepare("SELECT * FROM USER WHERE USERNAME = :username");
        $stmt->bindParam(':username', $this->username, PDO::PARAM_STR);
        if ($stmt->execute()) {
            $userCount = 0;

            while ($row = $stmt->fetch()) {
                $userCount++;
                //print "<br> user print" . print_r($row);
            }
            if ($userCount == 0) {
                // echo "no user found";
                return false;
            } else if ($userCount == 1) {
                //echo "one user found";
                return true;
            } else if ($userCount < 0 || $userCount > 1) {
                // log an error 
            }
        }
        unset($con);
    }

    function register($data) {
        // should validate all the fields are correct for the DB table here.
        try {
            $con = Database::initialize();
            $stmt = $con->prepare("INSERT INTO USER (username, password, email, phone, verificationToken) VALUES ( :username, :password, :email , :phone, :verificationToken)");
            $stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
            $stmt->bindParam(':password', $data['password'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
            $stmt->bindParam(':verificationToken', $data['verificationToken'], PDO::PARAM_STR);

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

    function getIDByEmail($email) {
        try {
            $con = Database::initialize();

            $stmt = $con->prepare("SELECT id FROM USER WHERE EMAIL = :email LIMIT 1");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            if ($stmt->execute()) {

                $row = $stmt->fetch();
                return $row['id'];
            }
        } catch (PDOException $e) {
            // should log this error
            echo "<br>" . $e->getMessage();
            return false;
        }
        unset($con);
    }

}