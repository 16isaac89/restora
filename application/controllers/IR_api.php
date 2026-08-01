<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH.'libraries/REST_Controller.php');
class IR_api extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Waiter_api_model');
        $this->load->model('Api_model');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, X-Restora-Desktop-Token');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }
    }

    private function desktop_input()
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        return is_array($json) ? $json : $_POST;
    }

    private function desktop_authorized()
    {
        $configured_token = getenv('RESTORA_DESKTOP_API_KEY');
        if (!$configured_token) {
            return true;
        }
        $headers = function_exists('getallheaders') ? getallheaders() : array();
        $provided = isset($headers['X-Restora-Desktop-Token']) ? $headers['X-Restora-Desktop-Token'] : '';
        if (!$provided && isset($headers['X-RESTORA-DESKTOP-TOKEN'])) {
            $provided = $headers['X-RESTORA-DESKTOP-TOKEN'];
        }
        return hash_equals($configured_token, $provided);
    }

    private function require_desktop_authorized()
    {
        if (!$this->desktop_authorized()) {
            $this->response(array('status' => false, 'message' => 'Desktop API token is invalid.'), 401);
            return false;
        }
        return true;
    }

    private function resolve_desktop_scope($input)
    {
        $company_id = isset($input['company_id']) ? (int)$input['company_id'] : 0;
        $outlet_id = isset($input['outlet_id']) ? (int)$input['outlet_id'] : 0;

        if (!$company_id && $outlet_id) {
            $outlet = $this->db->select('company_id')->from('tbl_outlets')->where('id', $outlet_id)->where('del_status', 'Live')->get()->row();
            $company_id = $outlet ? (int)$outlet->company_id : 0;
        }
        if (!$company_id) {
            $company = $this->db->select('id')->from('tbl_companies')->where('del_status', 'Live')->order_by('id', 'ASC')->get()->row();
            $company_id = $company ? (int)$company->id : 0;
        }
        if (!$outlet_id && $company_id) {
            $outlet = $this->db->select('id')->from('tbl_outlets')->where('company_id', $company_id)->where('del_status', 'Live')->order_by('id', 'ASC')->get()->row();
            $outlet_id = $outlet ? (int)$outlet->id : 0;
        }

        return array($company_id, $outlet_id);
    }

    private function desktop_table_exists($table)
    {
        return $this->db->table_exists($table);
    }

    private function ensure_desktop_sync_schema()
    {
        if (!$this->desktop_table_exists('tbl_desktop_sync_orders')) {
            $this->db->query("CREATE TABLE `tbl_desktop_sync_orders` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `uuid` varchar(80) NOT NULL,
                `entity_type` varchar(80) NOT NULL,
                `entity_uuid` varchar(80) NOT NULL,
                `action` varchar(80) NOT NULL,
                `company_id` int(11) DEFAULT NULL,
                `outlet_id` int(11) DEFAULT NULL,
                `device_uuid` varchar(80) DEFAULT NULL,
                `terminal_uuid` varchar(80) DEFAULT NULL,
                `payload` longtext NOT NULL,
                `status` varchar(30) NOT NULL DEFAULT 'received',
                `error_message` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uuid` (`uuid`),
                KEY `entity_uuid` (`entity_uuid`),
                KEY `scope_status` (`company_id`,`outlet_id`,`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
    }

    public function desktop_bootstrap_post()
    {
        if (!$this->require_desktop_authorized()) {
            return;
        }

        $input = $this->desktop_input();
        list($company_id, $outlet_id) = $this->resolve_desktop_scope($input);
        if (!$company_id || !$outlet_id) {
            $this->response(array('status' => false, 'message' => 'No active Restora company or outlet was found.'), 404);
            return;
        }

        $company = $this->db->select('id,business_name,short_name,address,phone,currency,precision,default_customer,default_waiter,default_payment,print_format_invoice,split_bill,printing_invoice,receipt_printer_invoice,printing_bill,receipt_printer_bill,printing_kot,receipt_printer_kot,print_format_kot,print_kot_when_placing_order,del_status')
            ->from('tbl_companies')->where('id', $company_id)->get()->row();
        $outlet = $this->db->select('id,outlet_name,outlet_code,address,phone,email,default_waiter,company_id,food_menus,food_menu_prices,delivery_price,has_kitchen,active_status,del_status')
            ->from('tbl_outlets')->where('id', $outlet_id)->where('company_id', $company_id)->get()->row();
        if (!$outlet) {
            $outlet = $this->db->select('id,outlet_name,outlet_code,address,phone,email,default_waiter,company_id,food_menus,food_menu_prices,delivery_price,has_kitchen,active_status,del_status')
                ->from('tbl_outlets')->where('company_id', $company_id)->where('del_status', 'Live')->order_by('id', 'ASC')->get()->row();
            $outlet_id = $outlet ? (int)$outlet->id : $outlet_id;
        }

        $categories = $this->db->select('id,category_name,description,company_id,order_by,category_image,del_status')
            ->from('tbl_food_menu_categories')->where('company_id', $company_id)->where('del_status', 'Live')->order_by('order_by', 'ASC')->get()->result();
        $food_menu_ids = array_filter(explode(',', getApiFMIds($outlet_id, $company_id)));
        $products_query = $this->db->select('id,code,name,alternative_name,category_id,description,sale_price,tax_information,tax_string,company_id,photo,calories,veg_item,beverage_item,bar_item,parent_id,sale_price_take_away,sale_price_delivery,delivery_price,product_type,combo_ids,is_variation,show_online,del_status')
            ->from('tbl_food_menus')
            ->where('company_id', $company_id)
            ->where('del_status', 'Live');
        if (!empty($food_menu_ids)) {
            $products_query->group_start()->where_in('id', $food_menu_ids)->or_where_in('parent_id', $food_menu_ids)->group_end();
        }
        $products = $products_query->order_by('name', 'ASC')->get()->result();

        $modifiers = $this->db->select('id,name,price,description,company_id,tax_information,tax_string,del_status')
            ->from('tbl_modifiers')->where('company_id', $company_id)->where('del_status', 'Live')->order_by('name', 'ASC')->get()->result();
        $food_menu_modifiers = $this->db->select('id,modifier_id,food_menu_id,outlet_id,company_id,del_status')
            ->from('tbl_food_menus_modifiers')->where('company_id', $company_id)->where('del_status', 'Live')->get()->result();
        $customers = $this->db->select('id,name,phone,email,address,company_id,del_status,added_date')
            ->from('tbl_customers')->where('company_id', $company_id)->where('del_status', 'Live')->order_by('name', 'ASC')->limit(1000)->get()->result();
        $tables = $this->db->select('id,area,table_type,name,sit_capacity,position,description,outlet_id,company_id,del_status,is_setting')
            ->from('tbl_tables')->where('outlet_id', $outlet_id)->where('company_id', $company_id)->where('del_status', 'Live')->order_by('name', 'ASC')->get()->result();
        $users = $this->db->select('id,full_name,phone,email_address,designation,will_login,role,outlet_id,outlets,kitchens,company_id,active_status,del_status,login_pin,role_id')
            ->from('tbl_users')->where('company_id', $company_id)->where('del_status', 'Live')->order_by('full_name', 'ASC')->get()->result();
        $payment_methods = $this->db->select('id,name,description,company_id,order_by,del_status')
            ->from('tbl_payment_methods')->where('company_id', $company_id)->where('del_status', 'Live')->order_by('order_by', 'ASC')->get()->result();
        $printers = $this->db->select('id,path,title,type,profile_,characters_per_line,printer_ip_address,printer_port,company_id,outlet_id,printing_choice,ipvfour_address,print_format,inv_qr_code_enable_status,open_cash_drawer_when_printing_invoice,del_status')
            ->from('tbl_printers')->where('company_id', $company_id)->where('outlet_id', $outlet_id)->where('del_status', 'Live')->order_by('title', 'ASC')->get()->result();
        $kitchens = $this->db->select('id,name,printer_id,print_server_url,company_id,del_status,outlet_id')
            ->from('tbl_kitchens')->where('company_id', $company_id)->where('outlet_id', $outlet_id)->where('del_status', 'Live')->order_by('name', 'ASC')->get()->result();
        $kitchen_categories = $this->db->select('id,kitchen_id,cat_id,via_printer,del_status,outlet_id')
            ->from('tbl_kitchen_categories')->where('outlet_id', $outlet_id)->where('del_status', 'Live')->get()->result();

        $this->response(array(
            'status' => true,
            'generated_at' => date('c'),
            'cursor' => date('c'),
            'full_refresh_required' => false,
            'scope' => array('company_id' => $company_id, 'outlet_id' => $outlet_id),
            'company' => $company,
            'outlet' => $outlet,
            'categories' => $categories,
            'products' => $products,
            'modifiers' => $modifiers,
            'food_menu_modifiers' => $food_menu_modifiers,
            'customers' => $customers,
            'tables' => $tables,
            'users' => $users,
            'payment_methods' => $payment_methods,
            'printers' => $printers,
            'kitchens' => $kitchens,
            'kitchen_categories' => $kitchen_categories
        ));
    }

    public function desktop_sync_orders_post()
    {
        if (!$this->require_desktop_authorized()) {
            return;
        }

        $this->ensure_desktop_sync_schema();
        $input = $this->desktop_input();
        list($company_id, $outlet_id) = $this->resolve_desktop_scope($input);
        $records = isset($input['records']) && is_array($input['records']) ? $input['records'] : array();
        $accepted = array();
        $rejected = array();

        foreach ($records as $record) {
            $uuid = isset($record['uuid']) ? trim($record['uuid']) : '';
            $payload = isset($record['payload']) ? $record['payload'] : null;
            if (!$uuid || !$payload) {
                $rejected[] = array('uuid' => $uuid, 'message' => 'Missing sync uuid or payload.');
                continue;
            }
            if (is_array($payload) || is_object($payload)) {
                $payload = json_encode($payload);
            }
            $decoded_payload = json_decode($payload, true);
            $device_uuid = isset($decoded_payload['device']['uuid']) ? $decoded_payload['device']['uuid'] : null;
            $terminal_uuid = isset($decoded_payload['terminal']['uuid']) ? $decoded_payload['terminal']['uuid'] : null;

            $data = array(
                'uuid' => $uuid,
                'entity_type' => isset($record['entity_type']) ? $record['entity_type'] : 'order',
                'entity_uuid' => isset($record['entity_uuid']) ? $record['entity_uuid'] : '',
                'action' => isset($record['action']) ? $record['action'] : 'upsert',
                'company_id' => $company_id,
                'outlet_id' => $outlet_id,
                'device_uuid' => $device_uuid,
                'terminal_uuid' => $terminal_uuid,
                'payload' => $payload,
                'status' => 'received',
                'updated_at' => date('Y-m-d H:i:s')
            );
            $this->db->where('uuid', $uuid);
            $exists = $this->db->get('tbl_desktop_sync_orders')->row();
            if ($exists) {
                $this->db->where('uuid', $uuid)->update('tbl_desktop_sync_orders', $data);
            } else {
                $this->db->insert('tbl_desktop_sync_orders', $data);
            }
            $accepted[] = array('uuid' => $uuid, 'status' => 'received');
        }

        $this->response(array(
            'status' => true,
            'message' => 'Desktop sync records received by Restora.',
            'accepted' => $accepted,
            'rejected' => $rejected
        ));
    }
    /**
     * get new notification
     * @access public
     * @return object
     * @param no
     */
    public function get_notifications_post()
    {
        $waiter_id = isset($_POST['waiter_id']) && $_POST['waiter_id']?$_POST['waiter_id']:'';
        $notifications = $this->Waiter_api_model->getNotificationByOutletId($waiter_id);
        foreach ($notifications as $key=>$value){
            $notifications[$key]->sale_no = returnSaleNo($value->sale_id);
        }
        $this->response($notifications);
    }
    /**
     * waiter login checker
     * @access public
     * @return object
     * @param no
     */
    public function waiter_login_check_post()
    {
        $email = isset($_POST['email']) && $_POST['email']?$_POST['email']:'';
        $password = isset($_POST['password']) && $_POST['password']?$_POST['password']:'';
        $user_information = $this->Waiter_api_model->getUserInformationWater($email, $password);
        $this->response($user_information);
    }
    public function waiter_login_pin_check_post()
    {
        $login_pin = isset($_POST['login_pin']) && $_POST['login_pin']?$_POST['login_pin']:'';
        $user_information = $this->Waiter_api_model->getUserInformationWaterPin($login_pin);
        $this->response($user_information);
    }
    /**
     * collect notification
     * @access public
     * @return object
     * @param no
     */
    public function collect_notification_post()
    {
        $notification_id = isset($_POST['notification_id']) && $_POST['notification_id']?$_POST['notification_id']:'';
        $this->db->delete('tbl_notifications', array('id' => $notification_id));
        $this->response("Success");
    }
    /**
     * waiter login checker
     * @access public
     * @return object
     * @param no
     */
    public function remove_multiple_notification_post()
    {
        $notification_ids = isset($_POST['notification_ids']) && $_POST['notification_ids']?$_POST['notification_ids']:'';
        $notifications_array = explode(",",$notification_ids);
        foreach($notifications_array as $single_notification){
            $this->db->delete('tbl_notifications', array('id' => $single_notification));
        }
        $this->response("Success");
    }

    /**
     * waiter login checker
     * @access public
     * @return object
     * @param no
     */
    public function push_notification_status_change_post()
    {
        $notification_id = isset($_POST['notification_id']) && $_POST['notification_id']?$_POST['notification_id']:'';
        if($notification_id){
            $this->db->set('push_status', "2");
            $this->db->where('id', $notification_id);
            $this->db->update("tbl_notifications");
        }
        $this->response("Success");
    }
    /**
     * waiter login checker
     * @access public
     * @return object
     * @param no
     */
    public function get_outlet_name_post()
    {
        $outlet_id = isset($_POST['outlet_id']) && $_POST['outlet_id']?$_POST['outlet_id']:'';
        $outlet = $this->Waiter_api_model->get_outlet_name($outlet_id);
        $this->response($outlet);
    }
}

