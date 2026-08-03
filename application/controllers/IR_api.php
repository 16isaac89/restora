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
        $users = $this->db->select('id,full_name,phone,email_address,designation,will_login,role,outlet_id,outlets,kitchens,company_id,active_status,del_status,login_pin,role_id,password')
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

    private function desktop_config_payload($decoded_payload)
    {
        if (isset($decoded_payload['payload']) && is_array($decoded_payload['payload'])) {
            return $decoded_payload['payload'];
        }
        return is_array($decoded_payload) ? $decoded_payload : array();
    }

    private function apply_desktop_config_record($entity_type, $decoded_payload, $company_id, $outlet_id)
    {
        $payload = $this->desktop_config_payload($decoded_payload);
        if ($entity_type == 'printer') {
            return $this->apply_desktop_printer_config($payload, $company_id, $outlet_id);
        }
        if ($entity_type == 'kitchen') {
            return $this->apply_desktop_kitchen_config($payload, $company_id, $outlet_id);
        }
        return array('ok' => true, 'status' => 'received');
    }

    private function apply_desktop_printer_config($payload, $company_id, $outlet_id)
    {
        $title = isset($payload['title']) ? trim($payload['title']) : '';
        if ($title == '') {
            return array('ok' => false, 'message' => 'Printer title is required.');
        }

        $printing_choice = isset($payload['printing_choice']) ? $payload['printing_choice'] : 'direct_print';
        $type = isset($payload['type']) ? $payload['type'] : 'network';
        $data = array(
            'outlet_id' => $outlet_id,
            'path' => $printing_choice == 'web_browser_popup' ? '' : (isset($payload['path']) ? $payload['path'] : ''),
            'title' => $title,
            'type' => $printing_choice == 'web_browser_popup' ? '' : $type,
            'profile_' => $printing_choice == 'web_browser_popup' ? '' : 'default',
            'printer_ip_address' => $printing_choice == 'web_browser_popup' ? '' : (isset($payload['printer_ip_address']) ? $payload['printer_ip_address'] : ''),
            'printer_port' => $printing_choice == 'web_browser_popup' ? '' : (isset($payload['printer_port']) ? $payload['printer_port'] : ''),
            'characters_per_line' => $printing_choice == 'web_browser_popup' ? '' : (isset($payload['characters_per_line']) ? $payload['characters_per_line'] : ''),
            'printing_choice' => $printing_choice,
            'ipvfour_address' => $printing_choice == 'web_browser_popup' ? '' : (isset($payload['ipvfour_address']) ? $payload['ipvfour_address'] : ''),
            'print_format' => $printing_choice == 'web_browser_popup' ? (isset($payload['print_format']) ? $payload['print_format'] : '80mm') : '',
            'inv_qr_code_enable_status' => isset($payload['inv_qr_code_status']) ? $payload['inv_qr_code_status'] : 'Disable',
            'open_cash_drawer_when_printing_invoice' => isset($payload['open_cash_drawer_when_printing_invoice']) ? $payload['open_cash_drawer_when_printing_invoice'] : 'No',
            'company_id' => $company_id,
            'del_status' => isset($payload['is_active']) && !$payload['is_active'] ? 'Deleted' : 'Live'
        );

        $printer_id = isset($payload['restora_backend_id']) ? (int)$payload['restora_backend_id'] : 0;
        if ($printer_id) {
            $this->db->where('id', $printer_id)->where('company_id', $company_id)->update('tbl_printers', $data);
        } else {
            $existing = $this->db->select('id')->from('tbl_printers')->where('company_id', $company_id)->where('outlet_id', $outlet_id)->where('title', $title)->get()->row();
            if ($existing) {
                $printer_id = (int)$existing->id;
                $this->db->where('id', $printer_id)->update('tbl_printers', $data);
            } else {
                $this->db->insert('tbl_printers', $data);
                $printer_id = (int)$this->db->insert_id();
            }
        }

        return array('ok' => true, 'status' => 'applied', 'restora_backend_id' => $printer_id);
    }

    private function apply_desktop_kitchen_config($payload, $company_id, $outlet_id)
    {
        $name = isset($payload['name']) ? trim($payload['name']) : '';
        if ($name == '') {
            return array('ok' => false, 'message' => 'Kitchen name is required.');
        }

        $printer_id = isset($payload['printer_id']) ? (int)$payload['printer_id'] : 0;
        $data = array(
            'name' => $name,
            'printer_id' => $printer_id ? $printer_id : '',
            'outlet_id' => $outlet_id,
            'company_id' => $company_id,
            'del_status' => isset($payload['is_active']) && !$payload['is_active'] ? 'Deleted' : 'Live'
        );

        $kitchen_id = isset($payload['restora_backend_id']) ? (int)$payload['restora_backend_id'] : 0;
        if ($kitchen_id) {
            $this->db->where('id', $kitchen_id)->where('company_id', $company_id)->update('tbl_kitchens', $data);
        } else {
            $existing = $this->db->select('id')->from('tbl_kitchens')->where('company_id', $company_id)->where('outlet_id', $outlet_id)->where('name', $name)->get()->row();
            if ($existing) {
                $kitchen_id = (int)$existing->id;
                $this->db->where('id', $kitchen_id)->update('tbl_kitchens', $data);
            } else {
                $this->db->insert('tbl_kitchens', $data);
                $kitchen_id = (int)$this->db->insert_id();
            }
        }

        $category_ids = isset($payload['category_ids']) && is_array($payload['category_ids']) ? $payload['category_ids'] : array();
        if (!$category_ids && isset($payload['item_check']) && is_array($payload['item_check'])) {
            foreach ($payload['item_check'] as $category_uuid) {
                if (preg_match('/restora-category-([0-9]+)/', $category_uuid, $matches)) {
                    $category_ids[] = (int)$matches[1];
                }
            }
        }

        $this->db->delete('tbl_kitchen_categories', array('kitchen_id' => $kitchen_id));
        foreach ($category_ids as $category_id) {
            $category_id = (int)$category_id;
            if (!$category_id) {
                continue;
            }
            $this->db->insert('tbl_kitchen_categories', array(
                'kitchen_id' => $kitchen_id,
                'cat_id' => $category_id,
                'outlet_id' => $outlet_id
            ));
        }

        return array('ok' => true, 'status' => 'applied', 'restora_backend_id' => $kitchen_id);
    }

    /**
     * Creates a genuine unpaid kitchen order (tbl_kitchen_sales / tbl_kitchen_sales_details),
     * the same "sent to kitchen, not yet paid" record the browser POS creates via
     * Sale::add_kitchen_sale_by_ajax(). Deliberately does NOT touch tbl_sales or trigger
     * e-invoicing -- this is a pre-payment order only; a cashier still finalizes payment
     * on the POS afterwards, exactly like an order a waiter sends from the floor today.
     */
    public function desktop_submit_order_post()
    {
        if (!$this->require_desktop_authorized()) {
            return;
        }

        $input = $this->desktop_input();

        $company_id = isset($input['company_id']) ? (int)$input['company_id'] : 0;
        $outlet_id = isset($input['outlet_id']) ? (int)$input['outlet_id'] : 0;
        if (!$company_id || !$outlet_id) {
            $this->response(array('status' => false, 'message' => 'company_id and outlet_id are required.'), 400);
            return;
        }
        $outlet = $this->db->select('id')->from('tbl_outlets')->where('id', $outlet_id)->where('company_id', $company_id)->where('del_status', 'Live')->get()->row();
        if (!$outlet) {
            $this->response(array('status' => false, 'message' => 'Outlet does not belong to that company, or is not active.'), 404);
            return;
        }

        $waiter_id = isset($input['waiter_id']) ? (int)$input['waiter_id'] : 0;
        if (!$waiter_id) {
            $this->response(array('status' => false, 'message' => 'waiter_id is required.'), 400);
            return;
        }
        $waiter = $this->db->select('id')->from('tbl_users')->where('id', $waiter_id)->where('company_id', $company_id)->where('del_status', 'Live')->get()->row();
        if (!$waiter) {
            $this->response(array('status' => false, 'message' => 'Waiter was not found for that company.'), 404);
            return;
        }

        $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : array();
        if (empty($items)) {
            $this->response(array('status' => false, 'message' => 'At least one order item is required.'), 400);
            return;
        }

        $idempotency_key = isset($input['idempotency_key']) ? trim((string)$input['idempotency_key']) : '';
        if ($idempotency_key) {
            $existing = $this->db->select('id,sale_no,total_payable')
                ->from('tbl_kitchen_sales')
                ->where('random_code', $idempotency_key)
                ->where('outlet_id', $outlet_id)
                ->where('del_status', 'Live')
                ->get()->row();
            if ($existing) {
                $this->response(array(
                    'status' => true,
                    'sale_id' => (int)$existing->id,
                    'sale_no' => $existing->sale_no,
                    'total_payable' => (float)$existing->total_payable,
                    'message' => 'Order was already received (idempotent replay).'
                ));
                return;
            }
        }

        $table_id = isset($input['table_id']) && $input['table_id'] ? (int)$input['table_id'] : 0;
        $table = null;
        if ($table_id) {
            $table = $this->db->select('id,name')->from('tbl_tables')->where('id', $table_id)->where('outlet_id', $outlet_id)->where('del_status', 'Live')->get()->row();
            if (!$table) {
                $this->response(array('status' => false, 'message' => 'Table does not belong to that outlet.'), 404);
                return;
            }
        }
        $customer_id = isset($input['customer_id']) && $input['customer_id'] ? (int)$input['customer_id'] : null;
        $guests = isset($input['guests']) ? max(1, (int)$input['guests']) : 1;
        $order_type = isset($input['order_type']) ? (int)$input['order_type'] : 1;
        if (!in_array($order_type, array(1, 2, 3), true)) {
            $order_type = 1;
        }

        // Server computes totals from the line items it is about to insert, rather than
        // trusting a separately supplied header total that could drift out of sync with them.
        $sub_total = 0.0;
        $total_vat = 0.0;
        $total_discount = 0.0;
        $clean_items = array();
        foreach ($items as $item) {
            $food_menu_id = isset($item['food_menu_id']) ? (int)$item['food_menu_id'] : 0;
            $qty = isset($item['qty']) ? (int)$item['qty'] : 0;
            if (!$food_menu_id || $qty < 1) {
                $this->response(array('status' => false, 'message' => 'Each item requires a valid food_menu_id and qty.'), 400);
                return;
            }
            $food = $this->db->select('id,name,parent_id')->from('tbl_food_menus')->where('id', $food_menu_id)->where('company_id', $company_id)->where('del_status', 'Live')->get()->row();
            if (!$food) {
                $this->response(array('status' => false, 'message' => "food_menu_id $food_menu_id was not found for that company."), 404);
                return;
            }
            $unit_price = isset($item['menu_unit_price']) ? (float)$item['menu_unit_price'] : 0;
            $price_without_discount = isset($item['menu_price_without_discount']) ? (float)$item['menu_price_without_discount'] : ($unit_price * $qty);
            $discount_amount = isset($item['discount_amount']) ? (float)$item['discount_amount'] : 0;
            $price_with_discount = isset($item['menu_price_with_discount']) ? (float)$item['menu_price_with_discount'] : ($price_without_discount - $discount_amount);
            $item_vat = isset($item['menu_taxes']) && is_array($item['menu_taxes']) ? $item['menu_taxes'] : array();
            $item_vat_total = 0.0;
            foreach ($item_vat as $tax) {
                $item_vat_total += isset($tax['item_vat_amount_for_all_quantity']) ? (float)$tax['item_vat_amount_for_all_quantity'] : 0;
            }
            $modifiers = isset($item['modifiers']) && is_array($item['modifiers']) ? $item['modifiers'] : array();

            $sub_total += $price_without_discount;
            $total_vat += $item_vat_total;
            $total_discount += $discount_amount;

            $clean_items[] = array(
                'food_menu_id' => $food_menu_id,
                'menu_name' => isset($item['menu_name']) ? substr((string)$item['menu_name'], 0, 250) : $food->name,
                'qty' => $qty,
                'menu_unit_price' => $unit_price,
                'menu_price_without_discount' => $price_without_discount,
                'menu_price_with_discount' => $price_with_discount,
                'menu_taxes' => $item_vat,
                'discount_amount' => $discount_amount,
                'discount_type' => isset($item['discount_type']) ? (string)$item['discount_type'] : '',
                'menu_note' => isset($item['kitchen_note']) ? substr((string)$item['kitchen_note'], 0, 150) : '',
                'modifiers' => $modifiers,
            );
        }
        $total_payable = $sub_total - $total_discount + $total_vat;

        $this->db->trans_begin();

        $now = date('Y-m-d H:i:s');
        $sale_data = array(
            'customer_id' => $customer_id,
            'sale_no' => 'PENDING',
            'total_items' => count($clean_items),
            'sub_total' => $sub_total,
            'paid_amount' => 0,
            'due_amount' => $total_payable,
            'vat' => $total_vat,
            'total_payable' => $total_payable,
            'close_time' => date('H:i:s'),
            'table_id' => $table_id ?: null,
            'total_item_discount_amount' => $total_discount,
            'sub_total_with_discount' => $sub_total - $total_discount,
            'sub_total_discount_amount' => 0,
            'total_discount_amount' => $total_discount,
            'sub_total_discount_value' => '0',
            'sub_total_discount_type' => 'fixed',
            'sale_date' => date('Y-m-d'),
            'date_time' => $now,
            'order_time' => date('H:i:s'),
            'modified' => 'No',
            'user_id' => $waiter_id,
            'waiter_id' => $waiter_id,
            'outlet_id' => $outlet_id,
            'company_id' => $company_id,
            'order_status' => 1,
            'order_type' => $order_type,
            'del_status' => 'Live',
            'orders_table_text' => $table ? $table->name : '',
            'self_order_content' => json_encode($input),
            'random_code' => $idempotency_key,
        );
        if (!$this->db->insert('tbl_kitchen_sales', $sale_data)) {
            $this->db->trans_rollback();
            $this->response(array('status' => false, 'message' => 'Could not create the order.'), 500);
            return;
        }
        $sale_id = $this->db->insert_id();
        $sale_no = 'APP-' . $outlet_id . '-' . $sale_id;
        $this->db->where('id', $sale_id)->update('tbl_kitchen_sales', array('sale_no' => $sale_no));

        if ($table_id) {
            $this->db->insert('tbl_orders_table', array(
                'persons' => $guests,
                'booking_time' => $now,
                'sale_id' => $sale_id,
                'sale_no' => $sale_no,
                'outlet_id' => $outlet_id,
                'table_id' => $table_id,
            ));
        }

        foreach ($clean_items as $item) {
            $detail_data = array(
                'food_menu_id' => $item['food_menu_id'],
                'menu_name' => $item['menu_name'],
                'qty' => $item['qty'],
                'tmp_qty' => $item['qty'],
                'menu_price_without_discount' => $item['menu_price_without_discount'],
                'menu_price_with_discount' => $item['menu_price_with_discount'],
                'menu_unit_price' => $item['menu_unit_price'],
                'menu_vat_percentage' => 0,
                'menu_taxes' => json_encode($item['menu_taxes']),
                'discount_type' => $item['discount_type'],
                'menu_note' => $item['menu_note'],
                'discount_amount' => $item['discount_amount'],
                'item_type' => 'Kitchen Item',
                'previous_id' => 0,
                'sales_id' => $sale_id,
                'order_status' => 0,
                'user_id' => $waiter_id,
                'outlet_id' => $outlet_id,
                'is_free_item' => 0,
                'del_status' => 'Live',
                'cooking_status' => 'New',
            );
            $this->db->insert('tbl_kitchen_sales_details', $detail_data);
            $detail_id = $this->db->insert_id();
            $this->db->where('id', $detail_id)->update('tbl_kitchen_sales_details', array('previous_id' => $detail_id));

            foreach ($item['modifiers'] as $modifier) {
                $modifier_id = isset($modifier['modifier_id']) ? (int)$modifier['modifier_id'] : 0;
                if (!$modifier_id) {
                    continue;
                }
                $this->db->insert('tbl_kitchen_sales_details_modifiers', array(
                    'modifier_id' => $modifier_id,
                    'modifier_price' => isset($modifier['modifier_price']) ? (float)$modifier['modifier_price'] : 0,
                    'food_menu_id' => $item['food_menu_id'],
                    'sales_id' => $sale_id,
                    'order_status' => 0,
                    'sales_details_id' => $detail_id,
                    'menu_taxes' => '',
                    'user_id' => $waiter_id,
                    'outlet_id' => $outlet_id,
                    'customer_id' => $customer_id ?: 0,
                    'del_status' => 'Live',
                ));
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->response(array('status' => false, 'message' => 'Order could not be saved.'), 500);
            return;
        }
        $this->db->trans_commit();

        $this->response(array(
            'status' => true,
            'sale_id' => $sale_id,
            'sale_no' => $sale_no,
            'total_payable' => $total_payable,
            'message' => 'Order received by Restora kitchen.'
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
            $entity_type = isset($record['entity_type']) ? $record['entity_type'] : 'order';
            $device_uuid = isset($decoded_payload['device']['uuid']) ? $decoded_payload['device']['uuid'] : (isset($decoded_payload['device_uuid']) ? $decoded_payload['device_uuid'] : null);
            $terminal_uuid = isset($decoded_payload['terminal']['uuid']) ? $decoded_payload['terminal']['uuid'] : (isset($decoded_payload['terminal_uuid']) ? $decoded_payload['terminal_uuid'] : null);
            $apply_result = $this->apply_desktop_config_record($entity_type, $decoded_payload, $company_id, $outlet_id);
            if (!$apply_result['ok']) {
                $rejected[] = array('uuid' => $uuid, 'message' => $apply_result['message']);
                continue;
            }

            $data = array(
                'uuid' => $uuid,
                'entity_type' => $entity_type,
                'entity_uuid' => isset($record['entity_uuid']) ? $record['entity_uuid'] : '',
                'action' => isset($record['action']) ? $record['action'] : 'upsert',
                'company_id' => $company_id,
                'outlet_id' => $outlet_id,
                'device_uuid' => $device_uuid,
                'terminal_uuid' => $terminal_uuid,
                'payload' => $payload,
                'status' => isset($apply_result['status']) ? $apply_result['status'] : 'received',
                'updated_at' => date('Y-m-d H:i:s')
            );
            $this->db->where('uuid', $uuid);
            $exists = $this->db->get('tbl_desktop_sync_orders')->row();
            if ($exists) {
                $this->db->where('uuid', $uuid)->update('tbl_desktop_sync_orders', $data);
            } else {
                $this->db->insert('tbl_desktop_sync_orders', $data);
            }
            $accepted[] = array('uuid' => $uuid, 'status' => $data['status']);
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

