<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends CI_Controller {

    private $callback_cache_dir;
    private $debug_log_dir;

    public function __construct() {
        parent::__construct();
        $this->load->library('YoPaymentsService');
        $this->callback_cache_dir = APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'yopayments_callbacks';
        $this->debug_log_dir = APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'yopayments_logs';
    }

    public function initiate_mobile_money() {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }
        if (!$this->session->userdata('user_id')) {
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized request'));
            return;
        }
        $this->release_session_lock();

        $order_id = trim((string)$this->input->post('order_id'));
        $amount = trim((string)$this->input->post('amount'));
        $phone = trim((string)$this->input->post('phone_number'));

        if ($phone === '' || $amount === '') {
            echo json_encode(array('status' => 'error', 'message' => 'Phone and amount are required'));
            return;
        }

        $response = $this->yopaymentsservice->initiate_payment($phone, $amount, 'POS Order #' . ($order_id ? $order_id : time()));
        $this->write_debug_log('initiate', array(
            'order_id' => $order_id,
            'amount' => $amount,
            'phone' => $phone,
            'response' => $response
        ));

        if (isset($response['Status']) && strtoupper((string)$response['Status']) === 'OK') {
            echo json_encode(array(
                'status' => 'success',
                'transaction_reference' => isset($response['TransactionReference']) ? $response['TransactionReference'] : '',
                'private_transaction_reference' => isset($response['ExternalReference']) ? $response['ExternalReference'] : '',
                'yo_response' => $response
            ));
            return;
        }

        $message = $this->extract_yo_message($response, 'Payment initiation failed');

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
        $this->release_session_lock();

        $ref = trim((string)$ref);
        if ($ref === '') {
            echo json_encode(array('status' => 'failed', 'message' => 'Missing transaction reference'));
            return;
        }

        $private_ref = trim((string) $this->input->get('private_ref'));
        $cache_key = $private_ref !== '' ? $private_ref : $ref;
        $response = $this->get_cached_callback_response($cache_key);
        $response_source = $response ? 'callback_cache' : 'status_poll';
        if (!$response) {
            $response = $this->yopaymentsservice->check_status($ref !== '' ? $ref : null, $private_ref !== '' ? $private_ref : null);
        }
        $gateway_status = isset($response['Status']) ? strtoupper(trim((string)$response['Status'])) : '';
        $gateway_status_code = isset($response['StatusCode']) ? trim((string)$response['StatusCode']) : '';
        $gateway_status_code_int = is_numeric($gateway_status_code) ? (int) $gateway_status_code : null;
        $status = isset($response['TransactionStatus']) ? strtoupper(trim((string)$response['TransactionStatus'])) : '';
        $message = $this->extract_yo_message($response, '');
        $status_message = strtoupper($message);
        $error_message = isset($response['ErrorMessage']) ? strtoupper(trim((string)$response['ErrorMessage'])) : '';
        $error_code = isset($response['ErrorMessageCode']) ? trim((string)$response['ErrorMessageCode']) : '';
        $final_status = 'pending';

        $success_statuses = array('SUCCEEDED', 'SUCCESSFUL', 'COMPLETE', 'COMPLETED', 'PAID');
        $has_success_keyword = preg_match('/\b(SUCCEEDED|SUCCESSFUL|COMPLETED|COMPLETE|PAID)\b/', $status_message) === 1;
        $has_failure_keyword = preg_match('/\b(FAILED|FAIL|ERROR|UNSUCCESSFUL|DECLINED|CANCELLED|CANCELED|REJECTED)\b/', $status_message) === 1;
        if ((in_array($status, $success_statuses, true) || $has_success_keyword) && !$has_failure_keyword) {
            $final_status = 'success';
            $this->write_debug_log('check_status', array(
                'ref' => $ref,
                'private_ref' => $private_ref,
                'source' => $response_source,
                'final_status' => $final_status,
                'response' => $response
            ));
            echo json_encode(array(
                'status' => 'success',
                'message' => $message,
                'yo_status' => $status,
                'yo_response' => $response
            ));
            return;
        }

        if ($gateway_status_code === 'TRANSPORT_ERROR') {
            $final_status = 'pending';
            $this->write_debug_log('check_status', array(
                'ref' => $ref,
                'private_ref' => $private_ref,
                'source' => $response_source,
                'final_status' => $final_status,
                'response' => $response
            ));
            echo json_encode(array(
                'status' => 'pending',
                'message' => $message !== '' ? $message : 'Payment status could not be verified yet. Retrying...',
                'yo_status' => 'PENDING',
                'yo_response' => $response
            ));
            return;
        }

        $explicit_failed_status_codes = array(
            2, 3, 7, 8, 10, 11, 14, 15, 16, 18, 19, 22, 24, 26, 28
        );
        if ($gateway_status_code_int !== null && in_array($gateway_status_code_int, $explicit_failed_status_codes, true)) {
            $final_status = 'failed';
            $this->write_debug_log('check_status', array(
                'ref' => $ref,
                'private_ref' => $private_ref,
                'source' => $response_source,
                'final_status' => $final_status,
                'response' => $response
            ));
            echo json_encode(array(
                'status' => 'failed',
                'message' => $message,
                'yo_status' => $status,
                'yo_response' => $response
            ));
            return;
        }

        $explicit_pending_status_codes = array(1, 27);
        if ($gateway_status_code_int !== null && in_array($gateway_status_code_int, $explicit_pending_status_codes, true)) {
            $final_status = 'pending';
            $this->write_debug_log('check_status', array(
                'ref' => $ref,
                'private_ref' => $private_ref,
                'source' => $response_source,
                'final_status' => $final_status,
                'response' => $response
            ));
            echo json_encode(array(
                'status' => 'pending',
                'message' => $message,
                'yo_status' => $status !== '' ? $status : 'PENDING',
                'yo_response' => $response
            ));
            return;
        }

        $explicit_indeterminate_status_codes = array(4, 5, 6, 9, 12, 13, 20, 21, 23, 25);
        if ($gateway_status_code_int !== null && in_array($gateway_status_code_int, $explicit_indeterminate_status_codes, true)) {
            $final_status = 'pending';
            $this->write_debug_log('check_status', array(
                'ref' => $ref,
                'private_ref' => $private_ref,
                'source' => $response_source,
                'final_status' => $final_status,
                'response' => $response
            ));
            echo json_encode(array(
                'status' => 'pending',
                'message' => $message !== '' ? $message : 'Payment status is indeterminate. Please verify before retrying.',
                'yo_status' => $status !== '' ? $status : 'INDETERMINATE',
                'yo_response' => $response
            ));
            return;
        }

        $is_failed = false;
        $failed_tokens = array('FAIL', 'ERROR', 'REJECT', 'DECLIN', 'CANCEL', 'EXPIRE', 'TIMEOUT', 'REVERS', 'UNSUCCESS', 'INVALID', 'INTERRUPT', 'ABORT', 'DENIED', 'NOT APPROVED', 'NOT AUTHORISED', 'NOT AUTHORIZED', 'INSUFFICIENT');
        foreach ($failed_tokens as $token) {
            if (
                (strpos($status, $token) !== false) ||
                (strpos($status_message, $token) !== false) ||
                (strpos($error_message, $token) !== false)
            ) {
                $is_failed = true;
                break;
            }
        }
        if (!$is_failed && $gateway_status === 'ERROR') {
            $is_failed = true;
        }
        if (!$is_failed && ($error_code !== '' || $error_message !== '')) {
            $is_failed = true;
        }
        if (!$is_failed && $gateway_status_code !== '' && $gateway_status_code !== '0' && $status === '') {
            $is_failed = true;
        }
        if ($is_failed) {
            $final_status = 'failed';
            $this->write_debug_log('check_status', array(
                'ref' => $ref,
                'private_ref' => $private_ref,
                'source' => $response_source,
                'final_status' => $final_status,
                'response' => $response
            ));
            echo json_encode(array(
                'status' => 'failed',
                'message' => $message,
                'yo_status' => $status,
                'yo_response' => $response
            ));
            return;
        }

        $this->write_debug_log('check_status', array(
            'ref' => $ref,
            'private_ref' => $private_ref,
            'source' => $response_source,
            'final_status' => $final_status,
            'response' => $response
        ));
        echo json_encode(array(
            'status' => 'pending',
            'message' => $message,
            'yo_status' => $status,
            'yo_response' => $response
        ));
    }

    public function yo_callback($type = 'success') {
        $payload = $this->collect_callback_payload();
        $response = $this->normalize_yo_response($payload, $type);
        $cache_keys = $this->get_callback_cache_keys($response);

        foreach ($cache_keys as $cache_key) {
            $this->store_callback_response($cache_key, $response);
        }
        $this->write_debug_log('callback_' . strtolower(trim((string) $type)), array(
            'payload' => $payload,
            'normalized_response' => $response,
            'cache_keys' => $cache_keys
        ));

        $this->output
            ->set_content_type('text/plain')
            ->set_status_header(200)
            ->set_output('OK');
    }

    private function extract_yo_message($response, $default = '') {
        if (isset($response['ErrorMessage']) && trim((string)$response['ErrorMessage']) !== '') {
            return trim((string)$response['ErrorMessage']);
        }

        if (isset($response['StatusMessage']) && trim((string)$response['StatusMessage']) !== '') {
            return trim((string)$response['StatusMessage']);
        }

        return $default;
    }

    private function collect_callback_payload() {
        $payload = array();
        $raw = file_get_contents('php://input');

        if (is_array($_POST) && !empty($_POST)) {
            $payload = array_merge($payload, $_POST);
        }

        if ($raw) {
            $xml = @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xml !== false) {
                $xml_data = json_decode(json_encode($xml), true);
                if (is_array($xml_data)) {
                    $payload = array_merge($payload, $this->flatten_payload($xml_data));
                }
            } else {
                parse_str($raw, $parsed_raw);
                if (is_array($parsed_raw) && !empty($parsed_raw)) {
                    $payload = array_merge($payload, $parsed_raw);
                }
            }
        }

        if (is_array($_GET) && !empty($_GET)) {
            $payload = array_merge($payload, $_GET);
        }

        return $payload;
    }

    private function normalize_yo_response($payload, $type = '') {
        $type = strtolower(trim((string) $type));
        $external_reference = $this->find_payload_value($payload, array('external_ref', 'ExternalReference', 'external_reference', 'externalreference'), '');
        $failed_transaction_reference = $this->find_payload_value($payload, array('failed_transaction_reference', 'FailedTransactionReference', 'failedtransactionreference'), '');
        $network_reference = $this->find_payload_value($payload, array('network_ref', 'NetworkReference', 'networkreference'), '');
        $normalized = array(
            'Status' => $this->find_payload_value($payload, array('Status', 'status'), $type === 'failure' ? 'ERROR' : ''),
            'StatusCode' => $this->find_payload_value($payload, array('StatusCode', 'status_code', 'statuscode'), ''),
            'StatusMessage' => $this->find_payload_value($payload, array('StatusMessage', 'status_message', 'statusmessage', 'Message', 'message'), ''),
            'TransactionStatus' => $this->find_payload_value($payload, array('TransactionStatus', 'transaction_status', 'transactionstatus'), ''),
            'ErrorMessageCode' => $this->find_payload_value($payload, array('ErrorMessageCode', 'error_message_code', 'errormessagecode', 'ErrorCode', 'error_code'), ''),
            'ErrorMessage' => $this->find_payload_value($payload, array('ErrorMessage', 'error_message', 'errormessage', 'FailureReason', 'failure_reason'), ''),
            'TransactionReference' => $this->find_payload_value($payload, array('TransactionReference', 'transaction_reference', 'transactionreference'), $failed_transaction_reference),
            'MNOTransactionReferenceId' => $this->find_payload_value($payload, array('MNOTransactionReferenceId', 'mno_transaction_reference_id', 'mnotransactionreferenceid'), $network_reference),
            'ExternalReference' => $external_reference,
            'PrivateTransactionReference' => $this->find_payload_value($payload, array('PrivateTransactionReference', 'private_transaction_reference', 'privatetransactionreference'), $external_reference !== '' ? $external_reference : $failed_transaction_reference)
        );

        if ($type === 'success') {
            if ($normalized['Status'] === '') {
                $normalized['Status'] = 'OK';
            }
            if ($normalized['StatusCode'] === '') {
                $normalized['StatusCode'] = '0';
            }
            if ($normalized['TransactionStatus'] === '') {
                $normalized['TransactionStatus'] = 'SUCCEEDED';
            }
        }

        if ($normalized['TransactionStatus'] === '' && $type === 'failure') {
            $normalized['TransactionStatus'] = 'FAILED';
        }

        if ($type === 'failure' && $normalized['StatusMessage'] === '') {
            $normalized['StatusMessage'] = 'Payment request was unsuccessful.';
        }

        if ($normalized['StatusMessage'] === '' && $normalized['ErrorMessage'] !== '') {
            $normalized['StatusMessage'] = $normalized['ErrorMessage'];
        }

        $normalized['CallbackType'] = $type;
        $normalized['CallbackReceivedAt'] = date('c');
        $normalized['RawPayload'] = $payload;

        return $normalized;
    }

    private function get_callback_cache_keys($response) {
        $keys = array();
        $candidate_keys = array(
            isset($response['PrivateTransactionReference']) ? $response['PrivateTransactionReference'] : '',
            isset($response['ExternalReference']) ? $response['ExternalReference'] : '',
            isset($response['TransactionReference']) ? $response['TransactionReference'] : ''
        );

        foreach ($candidate_keys as $candidate_key) {
            $candidate_key = trim((string) $candidate_key);
            if ($candidate_key !== '') {
                $keys[$candidate_key] = $candidate_key;
            }
        }

        return array_values($keys);
    }

    private function flatten_payload($payload) {
        $flattened = array();

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flatten_payload($value));
                continue;
            }

            $flattened[$key] = $value;
        }

        return $flattened;
    }

    private function find_payload_value($payload, $candidate_keys, $default = '') {
        foreach ($candidate_keys as $key) {
            if (isset($payload[$key]) && trim((string) $payload[$key]) !== '') {
                return trim((string) $payload[$key]);
            }
        }

        $lower_payload = array_change_key_case($payload, CASE_LOWER);
        foreach ($candidate_keys as $key) {
            $lower_key = strtolower($key);
            if (isset($lower_payload[$lower_key]) && trim((string) $lower_payload[$lower_key]) !== '') {
                return trim((string) $lower_payload[$lower_key]);
            }
        }

        return $default;
    }

    private function store_callback_response($transaction_reference, $response) {
        $dir = $this->callback_cache_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents($this->get_callback_cache_path($transaction_reference), json_encode($response));
    }

    private function get_cached_callback_response($transaction_reference) {
        $path = $this->get_callback_cache_path($transaction_reference);
        if (!is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);
        if ($contents === false || $contents === '') {
            return null;
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function get_callback_cache_path($transaction_reference) {
        return $this->callback_cache_dir . DIRECTORY_SEPARATOR . sha1($transaction_reference) . '.json';
    }

    private function write_debug_log($type, $data) {
        $dir = $this->debug_log_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = json_encode(array(
            'timestamp' => date('c'),
            'type' => $type,
            'data' => $data
        )) . PHP_EOL;

        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'yopayments.log', $line, FILE_APPEND);
    }

    private function release_session_lock() {
        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
            return;
        }

        if (function_exists('session_id') && session_id() !== '') {
            @session_write_close();
        }
    }
}
