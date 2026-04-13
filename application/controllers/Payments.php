<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('YoPaymentsService');
    }

    public function initiate_mobile_money() {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }
        if (!$this->session->userdata('user_id')) {
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized request'));
            return;
        }

        $order_id = trim((string)$this->input->post('order_id'));
        $amount = trim((string)$this->input->post('amount'));
        $phone = trim((string)$this->input->post('phone_number'));

        if ($phone === '' || $amount === '') {
            echo json_encode(array('status' => 'error', 'message' => 'Phone and amount are required'));
            return;
        }

        $response = $this->yopaymentsservice->initiate_payment($phone, $amount, 'POS Order #' . ($order_id ? $order_id : time()));

        if (isset($response['Status']) && strtoupper((string)$response['Status']) === 'OK') {
            echo json_encode(array(
                'status' => 'success',
                'transaction_reference' => isset($response['TransactionReference']) ? $response['TransactionReference'] : '',
                'yo_response' => $response
            ));
            return;
        }

        $message = 'Payment initiation failed';
        if (isset($response['StatusMessage']) && $response['StatusMessage']) {
            $message = (string)$response['StatusMessage'];
        } elseif (isset($response['ErrorMessage']) && $response['ErrorMessage']) {
            $message = (string)$response['ErrorMessage'];
        }

        echo json_encode(array('status' => 'error', 'message' => $message, 'yo_response' => $response));
    }

    public function check_status($ref = '') {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }
        if (!$this->session->userdata('user_id')) {
            echo json_encode(array('status' => 'failed', 'message' => 'Unauthorized request'));
            return;
        }

        $ref = trim((string)$ref);
        if ($ref === '') {
            echo json_encode(array('status' => 'failed', 'message' => 'Missing transaction reference'));
            return;
        }

        $response = $this->yopaymentsservice->check_status($ref);
        $status = isset($response['TransactionStatus']) ? strtoupper(trim((string)$response['TransactionStatus'])) : '';
        $message = isset($response['StatusMessage']) ? (string)$response['StatusMessage'] : '';
        $status_message = strtoupper($message);

        $success_statuses = array('SUCCEEDED', 'SUCCESSFUL', 'COMPLETE', 'COMPLETED', 'PAID');
        if (in_array($status, $success_statuses, true) || strpos($status_message, 'SUCCESS') !== false) {
            echo json_encode(array(
                'status' => 'success',
                'message' => $message,
                'yo_status' => $status,
                'yo_response' => $response
            ));
            return;
        }

        $is_failed = false;
        $failed_tokens = array('FAIL', 'ERROR', 'REJECT', 'DECLIN', 'CANCEL', 'EXPIRE', 'TIMEOUT', 'REVERS');
        foreach ($failed_tokens as $token) {
            if ((strpos($status, $token) !== false) || (strpos($status_message, $token) !== false)) {
                $is_failed = true;
                break;
            }
        }
        if ($is_failed) {
            echo json_encode(array(
                'status' => 'failed',
                'message' => $message,
                'yo_status' => $status,
                'yo_response' => $response
            ));
            return;
        }

        echo json_encode(array(
            'status' => 'pending',
            'message' => $message,
            'yo_status' => $status,
            'yo_response' => $response
        ));
    }
}
