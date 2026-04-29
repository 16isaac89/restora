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

        $this->is_enabled = isset($settings->field_yopayments) && (string) $settings->field_yopayments === '1';
        $username = isset($settings->field_yopayments_key_1) ? trim((string) $settings->field_yopayments_key_1) : '';
        $password = isset($settings->field_yopayments_key_2) ? trim((string) $settings->field_yopayments_key_2) : '';
        $this->is_configured = $username !== '' && $password !== '';
        $mode = (isset($settings->field_yopayments_v) && strtolower((string) $settings->field_yopayments_v) === 'sandbox')
            ? 'sandbox'
            : 'production';

        $params = array(
            'username' => $username,
            'password' => $password,
            'mode' => $mode
        );

        $this->yopay = new YoPayments($params);
        $this->yopay->set_non_blocking('TRUE');
        $this->yopay->set_instant_notification_url($this->build_callback_url('Payments/yo_callback/success'));
        $this->yopay->set_failure_notification_url($this->build_callback_url('Payments/yo_callback/failure'));
    }

    public function initiate_payment($phone, $amount, $narrative = 'POS Order Payment') {
        if (!$this->is_enabled) {
            return array('Status' => 'ERROR', 'StatusMessage' => 'YoPayments is disabled in payment settings');
        }
        if (!$this->is_configured) {
            return array('Status' => 'ERROR', 'StatusMessage' => 'YoPayments API credentials are missing');
        }
        $phone = $this->format_phone($phone);
        $provider_code = $this->detect_account_provider_code($phone);
        if ($provider_code !== '') {
            $this->yopay->set_account_provider_code($provider_code);
        }
        $external_reference = $this->generate_external_reference($phone);
        $this->yopay->set_external_reference($external_reference);
        $response = $this->yopay->ac_deposit_funds($phone, $amount, $narrative);
        $response['ExternalReference'] = $external_reference;
        if ($provider_code !== '') {
            $response['AccountProviderCode'] = $provider_code;
        }
        return $response;
    }

    public function check_status($transaction_reference = null, $private_transaction_reference = null) {
        if (!$this->is_enabled || !$this->is_configured) {
            return array('TransactionStatus' => 'FAILED', 'StatusMessage' => 'YoPayments is not configured');
        }
        return $this->yopay->ac_transaction_check_status($transaction_reference, $private_transaction_reference);
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

    private function generate_external_reference($phone) {
        $phone_suffix = substr($phone, -4);
        return 'restora-' . $phone_suffix . '-' . date('YmdHis') . '-' . substr(str_replace('.', '', uniqid('', true)), -6);
    }

    private function detect_account_provider_code($phone) {
        $msisdn = preg_replace('/[^0-9]/', '', (string) $phone);

        if (strpos($msisdn, '256') === 0) {
            $local = '0' . substr($msisdn, 3);
        } else {
            $local = $msisdn;
        }

        $mtn_prefixes = array('077', '078', '076', '039');
        $airtel_prefixes = array('070', '075', '074', '020');

        foreach ($mtn_prefixes as $prefix) {
            if (strpos($local, $prefix) === 0) {
                return 'MTN_UGANDA';
            }
        }

        foreach ($airtel_prefixes as $prefix) {
            if (strpos($local, $prefix) === 0) {
                return 'AIRTEL_UGANDA';
            }
        }

        return '';
    }

    private function build_callback_url($path) {
        $host = isset($_SERVER['HTTP_HOST']) ? trim((string) $_SERVER['HTTP_HOST']) : '';
        if ($host !== '') {
            $is_https = (
                (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ||
                (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443') ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            );
            $scheme = $is_https ? 'https' : 'http';
            return $scheme . '://' . $host . '/' . ltrim($path, '/');
        }

        return $this->ci->config->site_url($path);
    }
}
