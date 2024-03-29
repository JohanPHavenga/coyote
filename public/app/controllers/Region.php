<?php

// MAIN Region controller
class Region extends Frontend_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('region_model');
    }

    // check if method exists, if not calls "view" method
    public function _remap($method, $params = array())
    {
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $params);
        } else {
            $this->calendar($method, $params = array());
        }
    }

    function add_edition_count($region_list)
    {
        foreach ($region_list as $province_id => $province) {
            $province_count = $this->edition_model->edition_count($province_id);
            $return_list[$province_id] = $province;
            $return_list[$province_id]['province_count'] = $province_count;
            foreach ($province['region_list'] as $region_id => $region) {
                $region_count = $this->edition_model->edition_count($province_id, $region_id);
                $return_list[$province_id]['region_list'][$region_id] = $region;
                $return_list[$province_id]['region_list'][$region_id]['region_count'] = $region_count;
            }
        }

        return $return_list;
    }

    public function list()
    {
        $this->load->model('edition_model');

        $this->data_to_views['banner_img'] = "run_04";
        $this->data_to_views['banner_pos'] = "20%";
        $this->data_to_views['page_title'] = "Region List";
        $this->data_to_views['meta_description'] = "List of all available regions and running races in them";

        $region_list = $this->region_model->get_region_list(true);
        unset($region_list["No Province"]);
        //        wts($region_list, 1);
        $this->data_to_views['region_list'] = $this->add_edition_count($region_list);

        //        wts($this->data_to_views['region_list'], 1);

        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->banner_url, $this->data_to_views);
        $this->load->view('region/list', $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function calendar($slug)
    {

        $slug = strtolower($slug);

        $this->load->model('edition_model');
        $this->load->model('race_model');
        // as daar nie 'n region naam deurgestuur word nie
        if ($slug == "index") {
            redirect("/region/list");
        }

        // default banner image
        $this->data_to_views['banner_img'] = "run_04";
        $this->data_to_views['banner_pos'] = "45%";

        $query_params["where"] = ["edition_date >= " => date("Y-m-d H:i:s")];
        $query_params["order_by"] = ["edition_date" => "ASC"];

        // setup array for special regions
        $special_arr = ["capetown", "cape-town", "gauteng", "kzn-coast", "kzncoast", "gardenroute", "garden-route"];
        if (in_array(strtolower($slug), $special_arr)) {
            switch ($slug) {
                case "capetown":
                case "cape-town":
                    $region_id_arr = [2, 3, 4, 5, 6, 63];
                    $region_name = "Cape Town";
                    $this->data_to_views['crumbs_arr'] = replace_key($this->data_to_views['crumbs_arr'], ucwords(str_replace("-", " ", $slug)), $region_name);
                    $province_name = "Western Cape";
                    $this->data_to_views['banner_img'] = "cpt_02";
                    $this->data_to_views['banner_pos'] = "50%";
                    break;
                case "gauteng":
                    $region_id_arr = [26, 27, 28, 29, 30];
                    $region_name = "greater";
                    $province_name = "Gauteng";
                    break;
                case "kzncoast":
                case "kzn-coast":
                    $region_id_arr = [35, 32];
                    $region_name = "the Coastal";
                    $this->data_to_views['crumbs_arr'] = replace_key($this->data_to_views['crumbs_arr'], ucwords(str_replace("-", " ", $slug)), $region_name);
                    $province_name = "KwaZulu-Natal";
                    break;
                case "garden-route":
                case "gardenroute":
                    $region_id_arr = [62];
                    $region_name = "Garden Route";
                    $province_name = "Western Cape";
                    break;
            }
        } else {
            $region_id = $this->region_model->get_region_id_from_slug($slug);
            $region = $this->region_model->get_region_detail($region_id);
            $region_id_arr = [$region_id];
            $region_name = $region['region_name'];
            $province_name = $region['province_name'];
            // set search form
            $this->data_to_views['where'] = "reg_" . $region_id;
        }

        //        wts($region_id_arr, 1);
        // kry al die editions vir die region 
        $query_params["where_in"] = ["region_id" => $region_id_arr, "edition_status" => [1, 17]];

        // $this->data_to_views['edition_list'] = $this->race_model->add_race_info($this->edition_model->get_edition_list($query_params));

        $search_table_result = $this->edition_model->main_search($query_params, 0);



        if ($search_table_result) {
            // foreach ($this->data_to_views['edition_list'] as $edition_id => $edition_data) {
            //     $this->data_to_views['edition_list'][$edition_id]['status_info'] = $this->formulate_status_notice($edition_data);
            // }
            foreach ($search_table_result as $result) {
                if (!isset($this->data_to_views['edition_list'][$result['edition_id']])) {
                    $this->data_to_views['edition_list'][$result['edition_id']] = $result;
                    // status msg
                    $this->data_to_views['edition_list'][$result['edition_id']]['status_info'] = $this->formulate_status_notice($result);
                }
                // race stuffs
                $this->data_to_views['edition_list'][$result['edition_id']]['race_list'][$result['race_id']] = $result;
                $this->data_to_views['edition_list'][$result['edition_id']]['race_list'][$result['race_id']]['race_color'] = $this->edition_model->get_race_color($result['race_distance']);
                $this->data_to_views['edition_list'][$result['edition_id']]['race_distance_arr'][] = fraceDistance($result['race_distance']);
            }
            $region_pages = $this->session->region_pages;
            $this->data_to_views['page_title'] = "Running Races in " . $region_name . " region of " . $province_name;
        } else {
            if (!isset($region_name)) {
                $region_name = ucwords(str_replace("-", " ", $slug));
            }
            $this->data_to_views['page_title'] = "Running Races in " . $region_name . " region of " . $province_name;
        }
        $this->data_to_views['meta_description'] = "A list of road running races in the " . $region_name . " region with in the " . $province_name . " province of South Africa";

        // GET REGION LIST FOR FOOTER
        $region_list = $this->region_model->get_region_list(true);
        $this->data_to_views['region_by_province_list'] = $this->add_edition_count($region_list);

        // check cookie vir listing preference.
        if (get_cookie("listing_pref") == "grid") {
            $view_to_load = 'race_grid';
        } else {
            $view_to_load = 'race_list';
        }


        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->banner_url, $this->data_to_views);
        //        $this->load->view('region/calendar', $this->data_to_views);
        if (!$this->data_to_views['edition_list']) {
            $this->load->view('templates/search_form');
        }
        $this->load->view('templates/' . $view_to_load, $this->data_to_views);
        $this->load->view('templates/region_list', $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function switch()
    {
        $this->data_to_views['page_title'] = "Region Selection";
        $this->data_to_views['meta_description'] = "Select the regions for which you would like to limit your view to";
        $this->load->model('region_model');
        $this->data_to_views['region_dropdown'] = $this->region_model->get_region_dropdown();
        $this->data_to_views['form_url'] = base_url("region/switch");

        if ($this->logged_in_user) {
            $this->data_to_views['page_menu'] = $this->get_user_menu();
            $this->data_to_views['crumbs_arr'] = [
                "Home" => base_url(),
                "User" => base_url("user"),
                "Region Selection" => "",
            ];
        }
        $this->form_validation->set_rules('site_version[]', 'Region', 'required');


        // load correct view
        if ($this->form_validation->run() === FALSE) {
            $this->load->view($this->header_url, $this->data_to_views);
            $this->load->view($this->notice_url, $this->data_to_views);
            if ($this->logged_in_user) {
                $this->load->view('templates/page_menu', $this->data_to_views);
            }
            $this->load->view('region/switch', $this->data_to_views);
            $this->load->view($this->footer_url, $this->data_to_views);
        } else {
            $this->session->set_flashdata([
                'alert' => "<b>SUCCESS!</b> Selected regions updated",
                'status' => "success",
            ]);
            if ($this->input->post("site_version") == [0]) {
                $region_list = $this->region_model->get_all_region_ids();
            } else {
                $region_list = $this->input->post("site_version");
            }

            if ($this->session->user['logged_in'] == true) {
                $this->region_model->set_user_region($this->session->user['user_id'], $region_list);
            }
            $this->session->set_userdata("region_selection", $region_list);
            redirect(base_url("region/switch"));
        }
    }
}
