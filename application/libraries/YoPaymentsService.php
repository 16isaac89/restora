<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/YoPayments.php';

class YoPaymentsService {

    private $ci;
    private $yopay;
    private $is_enabled = false;
    private $is_configured = false;

    public function __construct() {
        $this->ci =& get_instance();
        $this->ci->load->model('Common_model');

        $company_id = $this->ci->session->userdata('company_id');
        $company = $this->ci->Common_model->getDataById($company_id, 'tbl_companies');
        $settings = isset($company->payment_settings) ? json_decode($company->payment_settings) : null;

        $this->is_enabled = true;
        $username = '90009372017';
        $password = '7775856071';
        $this->is_configured = true;
        $mode = 'sandbox';

        $params = array(
            'username' => $username,
            'password' => $password,
            'mode' => $mode
        );

        $this->yopay = new YoPayments($params);
    }

    public function initiate_payment($phone, $amount, $narrative = 'POS Order Payment') {
        if (!$this->is_enabled) {
            return array('Status' => 'ERROR', 'StatusMessage' => 'YoPayments is disabled in payment settings');
        }
        if (!$this->is_configured) {
            return array('Status' => 'ERROR', 'StatusMessage' => 'YoPayments API credentials are missing');
        }
        $phone = $this->format_phone($phone);
        return $this->yopay->ac_deposit_funds($phone, $amount, $narrative);
    }

    public function check_status($transaction_reference) {
        if (!$this->is_enabled || !$this->is_configured) {
            return array('TransactionStatus' => 'FAILED', 'StatusMessage' => 'YoPayments is not configured');
        }
        return $this->yopay->ac_transaction_check_status($transaction_reference);
    }

    private function format_phone($phone) {
        $phone = preg_replace('/[^0-9]/', '', (string)$phone);

        if (strpos($phone, '256') === 0 && strlen($phone) === 12) {
            return $phone;
        }

        if (strpos($phone, '2560') === 0 && strlen($phone) === 13) {
            return '256' . substr($phone, 4);
        }

        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '256' . substr($phone, 1);
        }

        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            return '256' . $phone;
        }

        return $phone;
    }
}
