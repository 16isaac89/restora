<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class YoPayments {

    /**
     * The Yo! Payments API Username
     * Required.
     * You may obtain the API Username from the web interface of your Payment Account.
     * @var string
     */
    private $username;

    /**
     * The Yo! Payments API Password
     * Required.
     * You may obtain the API Password from the web interface of your Payment Account.
     * @var string
     */
    private $password;

    /**
     * The Non Blocking Request variable
     * Optional.
     * Whether the connection to the Yo! Payments Gateway is maintained until your request is 
     * fulfilled. "FALSE" maintains the connection till the request is complete.
     * Default: "FALSE"
     * Options: "FALSE", "TRUE".
     * @var string
     */
    private $NonBlocking = "FALSE";

    /**
     * The External Reference variable
     * Optional.
     * An External Reference is something which yourself and the beneficiary agree upon
     * e.g. an invoice number
     * Default: NULL
     * @var string
     */
    private $external_reference = NULL;

    /**
     * The Internal Reference variable
     * Optional.
     * An Internal Reference is a reference code related to another Yo! Payments system transaction
     * If you are unsure about the meaning of this field, leave it as NULL
     * Default: NULL
     * @var string
     */
    private $internal_reference = NULL;

    /**
     * The Provider Reference Text variable
     * Optional.
     * A text you wish to be present in any confirmation message which the mobile money provider
     * network sends to the subscriber upon successful completion of the transaction.
     * Some mobile money providers automatically send a confirmatory text message to the subscriber
     * upon completion of transactions. This parameter allows you to provide some text which will 
     * be appended to any such confirmatory message sent to the subscriber.
     * Default: NULL
     * @var string
     */
    private $provider_reference_text = NULL;

    /**
     * The Instant Notification URL variable
     * Optional.
     * A valid URL which is notified as soon as funds are successfully deposited into your account
     * A payment notification will be sent to this URL. 
     * It must be properly URL encoded.
     * e.g. http://ipnurl?key1=This+value+has+encoded+white+spaces&key2=value
     * Any special XML Characters must be escaped or your request will fail
     * e.g. http://ipnurl?key1=This+value+has+encoded+white+spaces&amp;key2=value
     * Default: NULL
     * @var string
     */
    private $instant_notification_url = NULL;

    /**
     * The Failure Notification URL variable
     * Optional.
     * A valid URL which is notified as soon as your deposit request fails
     * A failure notification will be sent to this URL. 
     * It must be properly URL encoded.
     * e.g. http://failureurl?key1=This+value+has+encoded+white+spaces&key2=value
     * Any special XML Characters must be escaped or your request will fail
     * e.g. http://failureurl?key1=This+value+has+encoded+white+spaces&amp;key2=value
     * Default: NULL
     * @var string
     */
    private $failure_notification_url = NULL;

    /**
     * The Authentication Signature Base64 variable
     * Optional.
     * It may be required to authenticate certain deposit requests.
     * Contact Yo! Payments support services for clarification on the cases where this parameter
     * is required. 
     * Default: NULL
     * @var string
     */
    private $authentication_signature_base64 = NULL;

    /**
     * The Deposit Transaction Type variable
     * Optional.
     * Set to "PUSH" if following up on the status of a push deposit funds transaction
     * Set to "PULL" if following up on the status of a pull deposit funds transaction 
     * Default: "PULL"
     * Options: "PULL", "PUSH"
     * @var string
     */
    private $deposit_transaction_type='PULL';

    /**
     * The Yo Payments API URL
     * Required.
     * Default: "https://paymentsapi1.yo.co.ug/ybs/task.php"
     * Options: 
     * * "https://paymentsapi1.yo.co.ug/ybs/task.php", 
     * * "https://paymentsapi2.yo.co.ug/ybs/task.php",
     * * "https://sandbox.yo.co.ug/services/yopaymentsdev/task.php" For Sandbox tests
     * @var string
     */
    private $YOURL = "https://paymentsapi1.yo.co.ug/ybs/task.php";

    /*
    * This is the sandbox API URL
    */
    private $sandbox_url = "https://sandbox.yo.co.ug/services/yopaymentsdev/task.php";

    /*
    * This is the production URL
    */
    private $production_url = "https://paymentsapi1.yo.co.ug/ybs/task.php";

    /*
    * This is the certificate file. Should be in the same 
    * directory as this Lib file.
    */
    private $public_key_file = "Yo_Uganda_Public_Certificate.crt";


    /*
    * This is the certificate file to use in verifying the signature from Sandbox IPNs
    */
    private $public_key_file_for_sandbox = "Yo_Uganda_Public_Sandbox_Certificate.crt";

    /*
    * This is the certificate file to use in verifying the signature from Production IPNs
    */
    private $public_key_file_for_production = "Yo_Uganda_Public_Certificate.crt";


    
    private $transaction_limit_account_identifier = NULL;

    /**
     * The Public Key Authentication Nonce
     * Required if public key authentication is enabled.
     * Contact Yo! Payments support services for clarification on the cases where this parameter
     * is required. 
     * Max Length: 255 charcaters
     * Reg Expression: [a-zA-Z0-9,-+]
     * Default: NULL
     * It must be unique for each API request made
     * @var string
     */
    private $public_key_authentication_nonce = NULL;

    /**
     * The Public Key Authentication Signature
     * Required if public key authentication is enabled.
     * Contact Yo! Payments support services for clarification on the cases where this parameter
     * is required. 
     * Max Length: 4096 charcaters
     * Reg Expression: [a-zA-Z0-9,-+]
     * Default: NULL
     * 1. It must be a concatenation of the parameters below in the indicated order:
     * * API Username
     * * Amount
     * * Account
     * * Narrative
     * * External Reference
     * * PublicKeyAuthenticationNonce
     * 2. The above concatenated string in 1 should be SHA1 hashed
     * 3. The SHA1 hash should be RSA signed using the private key associated with your public key
     * 4. Base64-encode the RSA signature calculated in 3 above
     * @var string
     */
    private $public_key_authentication_signature_base64 = NULL;

    /**
     * The location of the private key used to sign the public auth key
     * Required if public key authentication is enabled.
     * Contact Yo! Payments support services for clarification on the cases where this parameter
     * is required. 
     * Max Length: 255 charcaters
     * Reg Expression: [a-zA-Z0-9,-+]
     * Default: NULL
     * It must be unique for each API request made
     * @var string
     */
    private $private_key_file_location = NULL;

    /*
    * Set this to sandbox if you are working with sandbox.
    */
    private $mode = "production";


    /**
     * YoAPI constructor.
     * @param array $params
     */
    public function __construct($params = array())
    {
        $this->username = isset($params['username']) ? $params['username'] : '';
        $this->password = isset($params['password']) ? $params['password'] : '';
        $mode = isset($params['mode']) ? $params['mode'] : 'production';

        if (strcmp($mode, "sandbox")==0) {
            $this->YOURL = $this->sandbox_url;
            $this->public_key_file = __DIR__.DIRECTORY_SEPARATOR.$this->public_key_file_for_sandbox;
        } else {
            $this->YOURL = $this->production_url;
            $this->public_key_file = __DIR__.DIRECTORY_SEPARATOR.$this->public_key_file_for_production;
        }
     }

    /**
    * Set the API Username
    * @param string $username The Yo Payments API username to use
    * @return void
    */
    public function set_username($username){
        $this->username = $username;
    }

    /**
    * Set the API Password
    * @param string $password The Yo Payments API Password to use
    * @return void
    */
    public function set_password($password){
        $this->password = $password;
    }

    /**
    * Request Mobile Money User to deposit funds into your account
    * Shortly after you submit this request, the mobile money user receives an on-screen
    * notification on their mobile phone. The notification informs the mobile money user about
    * your request to transfer funds out of their account and requests them to authorize the
    * request to complete the transaction.
    * This request is not supported by all mobile money operator networks
    * @param string $msisdn the mobile money phone number in the format 256772123456
    * @param double $amount the amount of money to deposit into your account (floats are supported)
    * @param string $narrative the reason for the mobile money user to deposit funds 
    * @return array
    */
    public function ac_deposit_funds($msisdn, $amount, $narrative)
    {
        $xml = '';
        $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AutoCreate>';
        $xml .= '<Request>';
        $xml .= '<APIUsername>'.$this->username.'</APIUsername>';
        $xml .= '<APIPassword>'.$this->password.'</APIPassword>';
        $xml .= '<Method>acdepositfunds</Method>';
        $xml .= '<NonBlocking>'.$this->NonBlocking.'</NonBlocking>';
        $xml .= '<Account>'.$msisdn.'</Account>';
        $xml .= '<Amount>'.$amount.'</Amount>';
        $xml .= '<Narrative>'.$narrative.'</Narrative>';
        if( $this->external_reference != NULL ){ $xml .= '<ExternalReference>'.$this->external_reference.'</ExternalReference>'; }
        if( $this->internal_reference != NULL ) { $xml .= '<InternalReference>'.$this->internal_reference.'</InternalReference>'; }
        if( $this->provider_reference_text != NULL ){ $xml .= '<ProviderReferenceText>'.$this->provider_reference_text.'</ProviderReferenceText>'; }
        if( $this->instant_notification_url != NULL ){ $xml .= '<InstantNotificationUrl>'.$this->instant_notification_url.'</InstantNotificationUrl>'; }
        if( $this->failure_notification_url != NULL ){ $xml .= '<FailureNotificationUrl>'.$this->failure_notification_url.'</FailureNotificationUrl>'; }
        if( $this->authentication_signature_base64 != NULL ){ $xml .= '<AuthenticationSignatureBase64>'.$this->authentication_signature_base64.'</AuthenticationSignatureBase64>'; }
        $xml .= '</Request>';
        $xml .= '</AutoCreate>';

        $xml_response = $this->get_xml_response($xml);

        $simpleXMLObject =  new SimpleXMLElement($xml_response);
        $response = $simpleXMLObject->Response;

        $result = array();
        $result['Status'] = (string) $response->Status;
        $result['StatusCode'] = (string) $response->StatusCode;
        $result['StatusMessage'] = (string) $response->StatusMessage;
        $result['TransactionStatus'] = (string) $response->TransactionStatus;
        if (!empty($response->ErrorMessageCode)) {
            $result['ErrorMessageCode'] = (string) $response->ErrorMessageCode;
        }
        if (!empty($response->ErrorMessage)) {
            $result['ErrorMessage'] = (string) $response->ErrorMessage;
        }
        if (!empty($response->TransactionReference)) {
            $result['TransactionReference'] = (string) $response->TransactionReference;
        }
        if (!empty($response->MNOTransactionReferenceId)) {
            $result['MNOTransactionReferenceId'] = (string) $response->MNOTransactionReferenceId;
        }
        if (!empty($response->IssuedReceiptNumber)) {
            $result['IssuedReceiptNumber'] = (string) $response->IssuedReceiptNumber;
        }

        return $result;
        
    }

    /**
    * Check the status of a transaction that was earlier submitted for processing.
    * Its particularly useful where the NonBlocking is set to TRUE.
    * It can also be used to check on any other transaction on the system.
    * @param string $transaction_reference the response from the Yo! Payments Gateway that uniquely identifies the transaction whose status you are checking
    * @param string $private_transaction_reference The External Reference that was used to carry out a transaction
    * @return array
    */
    public function ac_transaction_check_status($transaction_reference, $private_transaction_reference=NULL)
    {
        $xml = '';
        $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AutoCreate>';
        $xml .= '<Request>';
        $xml .= '<APIUsername>'.$this->username.'</APIUsername>';
        $xml .= '<APIPassword>'.$this->password.'</APIPassword>';
        $xml .= '<Method>actransactioncheckstatus</Method>';
        if($transaction_reference!=NULL){ $xml .= '<TransactionReference>'.$transaction_reference.'</TransactionReference>'; }
        if( $private_transaction_reference != NULL ) { $xml .= '<PrivateTransactionReference>'.$private_transaction_reference.'</PrivateTransactionReference>'; }
        $xml .= '<DepositTransactionType>'.$this->deposit_transaction_type.'</DepositTransactionType>';
        $xml .= '</Request>';
        $xml .= '</AutoCreate>';

        $xml_response = $this->get_xml_response($xml);

        $simpleXMLObject =  new SimpleXMLElement($xml_response);
        $response = $simpleXMLObject->Response;

        $result = array();
        $result['Status'] = (string) $response->Status;
        $result['StatusCode'] = (string) $response->StatusCode;
        $result['StatusMessage'] = (string) $response->StatusMessage;
        $result['TransactionStatus'] = (string) $response->TransactionStatus;
        if (!empty($response->ErrorMessageCode)) {
            $result['ErrorMessageCode'] = (string) $response->ErrorMessageCode;
        }
        if (!empty($response->ErrorMessage)) {
            $result['ErrorMessage'] = (string) $response->ErrorMessage;
        }
        if (!empty($response->TransactionReference)) {
            $result['TransactionReference'] = (string) $response->TransactionReference;
        }
        if (!empty($response->MNOTransactionReferenceId)) {
            $result['MNOTransactionReferenceId'] = (string) $response->MNOTransactionReferenceId;
        }
        if (!empty($response->Amount)) {
            $result['Amount'] = (string) $response->Amount;
        }
        if (!empty($response->AmountFormatted)) {
            $result['AmountFormatted'] = (string) $response->AmountFormatted;
        }
        if (!empty($response->CurrencyCode)) {
            $result['CurrencyCode'] = (string) $response->CurrencyCode;
        }
        if (!empty($response->TransactionInitiationDate)) {
            $result['TransactionInitiationDate'] = (string) $response->TransactionInitiationDate;
        }
        if (!empty($response->TransactionCompletionDate)) {
            $result['TransactionCompletionDate'] = (string) $response->TransactionCompletionDate;
        }
        if (!empty($response->IssuedReceiptNumber)) {
            $result['IssuedReceiptNumber'] = (string) $response->IssuedReceiptNumber;
        }

        return $result;
    }

    protected function get_xml_response($xml)
    {
        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL, $this->YOURL);
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($soap_do, CURLOPT_TIMEOUT, 120);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($soap_do, CURLOPT_POST, true);
        curl_setopt($soap_do, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($soap_do, CURLOPT_VERBOSE, false);
        curl_setopt($soap_do, CURLOPT_HTTPHEADER, array('Content-Type: text/xml','Content-transfer-encoding: text','Content-Length: '.strlen($xml)));

        $xml_response = curl_exec($soap_do);
        curl_close($soap_do);

        return $xml_response;
    }
}
