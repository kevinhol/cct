<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Mailer
 *
 * @author User
 */
require_once 'PhpMailer/class.phpmailer.php';

class Mailer {

    public static $mailer;
    
    public function __construct() {
        
    }
    
    public static function init() {
        if (self::$mailer == NULL)
            self::$mailer = new self();

        return self::$mailer;
    }


    /**
     * Send a system notification that the contact form has been posted
     * 
     * @param String $to      Send to the system setting email address
     * @param String $from    The posted and validated email address
     * @param String $subject The contact form subject
     * @param String $message The contact form message
     * @param Array  $headers Any additional headers
     * 
     * @return Boolean success of the function
     */
    public static function sendContactFormMail($to, $from, $subject, $message, $headers = null) {
        // send mail notification
        $headers .= "From: " . strip_tags($from) . "\r\n";
        $headers .= "Reply-To: " . strip_tags($from) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        return mail(
            $to
            , $subject
            , $message
            , $headers
        );
    }

}

?>
