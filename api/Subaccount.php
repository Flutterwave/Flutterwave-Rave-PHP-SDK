<?php

namespace Payments;
session_start() ;
// session_destroy();
// Prevent direct access to this class
define("BASEPATH", 1);

require('../lib/rave.php');
require('../lib/raveEventHandlerInterface.php');

use Flutterwave\Rave;
use Flutterwave\Rave\EventHandlerInterface;

if($_POST["submit"]){
    $postData = $_POST;
    $publicKey = $postData['publicKey'];
    $secretKey = $postData['secretKey'];
    $env = $postData['env']; // Remember to change this to 'live' when you are going live
    $accountbank = $postData['account_bank'];
    $accountnumber = $postData['account_number'];
    $bizphone = $postData['biz_phone'];
    $bizname = $postData['biz_name'];
    $contactphone = $postData['contact_phone'];
    $contact =  $postData['biz_contact'];
    $split_type = $postData['split_type'];
    $split_value =  $postData['split_value'];
    $email = $postData['biz_email'];
    $payment_type = $postData['payment_type'];
    echo "Hi there";
    echo $accountbank;
   
    $_SESSION['publicKey'] = $publicKey;
    $_SESSION['secretKey'] = $secretKey;
    $_SESSION['env'] = $env;
    $_SESSION['accountbank'] = $accountbank;
    $_SESSION['accountnumber'] = $accountnumber;
    $_SESSION['contact'] = $contact;
    $_SESSION['bizphone'] = $bizphone;
    $_SESSION['bizname'] = $bizname;
    $_SESSION['contactphone'] = $contactphone;
    $_SESSION['split_type'] = $split_type;
    $_SESSION['split_value'] = $split_value;
    $_SESSION['email'] = $email;
    $_SESSION['payment_type'] = $payment_type;
    
}
$prefix = 'RV'; // Change this to the name of your business or app
$overrideRef = false;



// Uncomment here to enforce the useage of your own ref else a ref will be generated for you automatically
if(isset($postData['ref'])){
    $prefix = $postData['ref'];
    $overrideRef = true;
}

$payment = new Rave($_SESSION['publicKey'], $_SESSION['secretKey'], $prefix, $_SESSION['env'], $overrideRef);


class myEventHandler implements EventHandlerInterface{
    /**
     * This is called only when a transaction is successful
     * */
    function onSuccessful($transactionData){
        // Get the transaction from your DB using the transaction reference (txref)
        // Check if you have previously given value for the transaction. If you have, redirect to your successpage else, continue
        // Comfirm that the transaction is successful
        // Confirm that the chargecode is 00 or 0
        // Confirm that the currency on your db transaction is equal to the returned currency
        // Confirm that the db transaction amount is equal to the returned amount
        // Update the db transaction record (includeing parameters that didn't exist before the transaction is completed. for audit purpose)
        // Give value for the transaction
        // Update the transaction to note that you have given value for the transaction
        // You can also redirect to your success page from here
        if($transactionData["data"]["chargecode"] === '00' || $transactionData["data"]["chargecode"] === '0'){
          echo "Transaction Completed";
      }else{
          $this->onFailure($transactionData);
      }
    }
    
    /**
     * This is called only when a transaction failed
     * */
    function onFailure($transactionData){
        // Get the transaction from your DB using the transaction reference (txref)
        // Update the db transaction record (includeing parameters that didn't exist before the transaction is completed. for audit purpose)
        // You can also redirect to your failure page from here
       
    }
    
    /**
     * This is called when a transaction is requeryed from the payment gateway
     * */
    function onRequery($transactionReference){
        // Do something, anything!
    }
    
    /**
     * This is called a transaction requery returns with an error
     * */
    function onRequeryError($requeryResponse){
        // Do something, anything!
    }
    
    /**
     * This is called when a transaction is canceled by the user
     * */
    function onCancel($transactionReference){
        // Do something, anything!
        // Note: Somethings a payment can be successful, before a user clicks the cancel button so proceed with caution
       
    }
    
    /**
     * This is called when a transaction doesn't return with a success or a failure response. This can be a timedout transaction on the Rave server or an abandoned transaction by the customer.
     * */
    function onTimeout($transactionReference, $data){
        // Get the transaction from your DB using the transaction reference (txref)
        // Queue it for requery. Preferably using a queue system. The requery should be about 15 minutes after.
        // Ask the customer to contact your support and you should escalate this issue to the flutterwave support team. Send this as an email and as a notification on the page. just incase the page timesout or disconnects
      
    }
}
echo "<pre>";
if($postData['payment_type'] === "subaccount"){
    $post_data = array(
            "account_bank"=> $_SESSION['accountbank'],
            "account_number"=> $_SESSION['accountnumber'],
            "business_name"=> $_SESSION['bizname'],
            "business_email" =>  $_SESSION['email'],
            "business_contact" => $_SESSION['contact'],
            "business_contact_mobile"=> $_SESSION['contactphone'],
            "business_mobile"=>  $_SESSION['bizphone'],
            "meta" => ["metaname"=>"MarketplaceID", "metavalue"=> "ggs-920900"],
            "seckey" => $_SESSION['secretKey'],
            "split_type"=> $_SESSION['split_type'],
            "split_value"=> $_SESSION['split_value']
    );

    $payment
    ->eventHandler(new myEventHandler)
    ->setPaymentMethod($postData['payment_type'])
    ->setEndPoint("v2/gpx/subaccounts/create")
    ->subaccount($post_data);
}
?>