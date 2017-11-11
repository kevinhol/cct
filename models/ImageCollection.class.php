<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ImageCollection extends ModelBase {

    public function __construct() {
        parent::__construct(get_class());
    }

    function getAllImages($siteConfigs, $userid) {

        //$server = $siteConfigs['website_url'] . '/' . $siteConfigs['base_image_directory'];
        $images = array();
        // should validate all the fields are correct for the DB table here.
        try {
            $con = Database::initialize();
            $stmt = $con->prepare("SELECT * FROM IMAGE WHERE USERID = :userid");
            $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            while ($row = $stmt->fetch()) {
                $image = ModelFactory::create("Image");
                $image->loadFromDB($row['id']);

                $images[] = $image;
            }

            foreach ($images as $image) {
//                var_dump($image); 
                $image = decryptImage($siteConfigs, $image);
//                var_dump($image); exit;
//echo $image->file_name_hash ; exit;
//                $image->url = $server . $_SESSION['userspace'] . '/' . $image->file_name_hash . $image->extension;
//                $image->data_hash_verified = verifyDataHash($image->file_data_hash, $image->url);
            }

            unset($con);
            return $images;
        } catch (PDOException $e) {
            // should log this error
            echo "<br>" . $e->getMessage();
            return false;
        }
    }

}
