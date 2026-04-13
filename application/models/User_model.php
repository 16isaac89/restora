<?php
/*
  ###########################################################
  # PRODUCT NAME: 	iRestora PLUS - Next Gen Restaurant POS
  ###########################################################
  # AUTHER:		Doorsoft
  ###########################################################
  # EMAIL:		info@doorsoft.co
  ###########################################################
  # COPYRIGHTS:		RESERVED BY Door Soft
  ###########################################################
  # WEBSITE:		http://www.doorsoft.co
  ###########################################################
  # This is User_model Model
  ###########################################################
 */
class User_model extends CI_Model {
    /**
     * ensure user schedule and session schema
     * @access public
     * @return void
     */
    public function ensureUserScheduleAndSessionSchema() {
        $work_schedule_column = $this->db->query("SHOW COLUMNS FROM tbl_users LIKE 'work_schedule'")->row();
        if(!$work_schedule_column){
            $this->db->query("ALTER TABLE `tbl_users` ADD `work_schedule` LONGTEXT NULL DEFAULT NULL AFTER `kitchens`");
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `tbl_user_sessions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            `outlet_id` int(11) DEFAULT NULL,
            `login_at` datetime NOT NULL,
            `logout_at` datetime DEFAULT NULL,
            `duration_minutes` int(11) DEFAULT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'Open',
            `ip_address` varchar(100) DEFAULT NULL,
            `user_agent` varchar(255) DEFAULT NULL,
            `del_status` varchar(15) DEFAULT 'Live',
            PRIMARY KEY (`id`),
            KEY `idx_user_company_status` (`user_id`,`company_id`,`status`),
            KEY `idx_company_date` (`company_id`,`login_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    /**
     * get User Menu Access
     * @access public
     * @return object
     * @param int
     */
    public function getUserMenuAccess($user_id) {
        $this->db->select("tbl_user_menu_access.menu_id");
        $this->db->from("tbl_user_menu_access");
        $this->db->where("user_id", $user_id);
        return $this->db->get()->result();
    }
    /**
     * get Users By Company Id
     * @access public
     * @return object
     * @param int
     */
    public function getUsersByCompanyId($company_id) {
        $user_id = $this->session->userdata('user_id');
        $language_manifesto = $this->session->userdata('language_manifesto');

        if(str_rot13($language_manifesto)=="eriutoeri"){
            $this->db->select("tbl_users.*,tbl_outlets.outlet_name");
            $this->db->from("tbl_users");
            $this->db->join('tbl_outlets', 'tbl_outlets.id = tbl_users.outlet_id', 'left');
            $this->db->where("tbl_users.company_id", $company_id);
            $this->db->where("tbl_users.del_status", 'Live');
            $this->db->order_by("id", 'DESC');
            return $this->db->get()->result();
        }else{
            $this->db->select("tbl_users.*,tbl_outlets.outlet_name");
            $this->db->from("tbl_users");
            $this->db->join('tbl_outlets', 'tbl_outlets.id = tbl_users.outlet_id', 'left');
            $this->db->where("tbl_users.company_id", $company_id);
            $this->db->where("tbl_users.del_status", 'Live');
            $this->db->order_by("id", 'DESC');
            return $this->db->get()->result();
        }
    }

    /**
     * get user sessions
     * @access public
     * @return object
     * @param int
     * @param int
     * @param string
     * @param string
     */
    public function getUserSessions($company_id,$user_id='',$start_date='',$end_date=''){
        $this->db->select("tbl_user_sessions.*,tbl_users.full_name,tbl_users.designation,tbl_outlets.outlet_name");
        $this->db->from("tbl_user_sessions");
        $this->db->join("tbl_users","tbl_users.id=tbl_user_sessions.user_id","left");
        $this->db->join("tbl_outlets","tbl_outlets.id=tbl_user_sessions.outlet_id","left");
        $this->db->where("tbl_user_sessions.company_id",$company_id);
        $this->db->where("tbl_user_sessions.del_status",'Live');
        if($user_id!=''){
            $this->db->where("tbl_user_sessions.user_id",$user_id);
        }
        if($start_date!=''){
            $this->db->where("DATE(tbl_user_sessions.login_at)>=",$start_date);
        }
        if($end_date!=''){
            $this->db->where("DATE(tbl_user_sessions.login_at)<=",$end_date);
        }
        $this->db->order_by("tbl_user_sessions.id","DESC");
        return $this->db->get()->result();
    }

}

