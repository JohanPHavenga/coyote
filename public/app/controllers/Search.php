<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Search extends Frontend_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('edition_model');
        $this->load->model('race_model');
    }

    public function index()
    {
        // wts($_POST);

        $this->load->model('admin/result_model');

        // SET BAIC STATUS CHECK 
        $search_params['where_in']["edition_status"] = [1, 3, 9, 17];

        // Get correct search paramaters from post
        // QUERY
        if ($this->input->post_get("query")) {
            $search_params['group_start'] = "";
            $search_params['like']["edition_name"] = $this->input->post_get("query");
            $search_params['or_like']["event_name"] = $this->input->post_get("query");
            $search_params['or_like']["town_name"] = $this->input->post_get("query");
            $search_params['or_like']["town_name_alt"] = $this->input->post_get("query");
            $search_params['or_like']["province_name"] = $this->input->post_get("query");
            $search_params['or_like']["race_name"] = $this->input->post_get("query");
            $search_params['or_where']["province_abbr"] = $this->input->post_get("query");
            $search_params['group_end'] = "";

            $this->edition_model->log_search($this->input->post_get("query"));
        }
        // WHERE
        if ($this->input->post("where") !== NULL) {
            switch ($this->input->post("where")) {
                case "my":
                    if (isset($this->session->region_selection)) {
                        $search_params['where_in']["region_id"] = $this->session->region_selection;
                    }
                    break;
                case "all":
                    break;
                default:
                    $field_parts = explode("_", $this->input->post("where"));
                    // REGION
                    if ($field_parts[0] == "reg") {
                        $search_params['where']["region_id"] = $field_parts[1];
                    }
                    // PROVINCE
                    if ($field_parts[0] == "pro") {
                        $search_params['where']["province_id"] = $field_parts[1];
                    }
                    break;
            }
        } else {
            $search_params['where_in']["region_id"] = $this->session->region_selection;
        }

        // DISTANCE
        // search dalk eers races, en pass dan 'n lys van edition IDs
        switch ($this->input->post("distance")) {
            case 'fun':
                $from_dist = 0;
                $to_dist = 10;
                break;
            case '10':
                $from_dist = 10;
                $to_dist = 15;
                break;
            case '15':
                $from_dist = 15;
                $to_dist = 21.1;
                break;
            case '21':
                $from_dist = 21.1;
                $to_dist = 30;
                break;
            case '30':
                $from_dist = 30;
                $to_dist = 42.2;
                break;
            case '42':
                $from_dist = 42.2;
                $to_dist = 42.3;
                break;
            case 'ultra':
                $from_dist = 42.3;
                $to_dist = 1000;
                break;
            default:
                $from_dist = 0;
                $to_dist = 1000;
                break;
        }
        $search_params['where']['race_distance >='] = $from_dist;
        $search_params['where']['race_distance <'] = $to_dist;


        // STATUS
        set_cookie("search_status_pref", $this->input->post("status"), 172800);
        switch ($this->input->post("status")) {
            case 'active':
                $search_params['where_in']['edition_status'] = [1, 17];
                break;
            case 'confirmed':
                $search_params['where_in']['edition_status'] = [1, 17];
                $search_params['where_in']['edition_info_status'] = [14, 15, 16];
                break;
            case 'verified':
                $search_params['where_in']['edition_status'] = [1, 17];
                $search_params['where']['edition_info_status'] = 16;
                break;

            default:

                break;
        }


        // WHEN
        set_cookie("search_when_pref", $this->input->post("when"), 172800);
        if ($this->input->post_get("query")) {
            $from_date = date("Y-m-d 00:00:00", strtotime("-2 weeks"));
        } else {
            $from_date = date("Y-m-d 00:00:00");
        }
        switch ($this->input->post("when")) {
            case "any":
                $from_date = date("2016-10-01 00:00:00");
                $to_date = date("Y-m-d H:i:s", strtotime("2 years"));
                $search_params['limit'] = 500;
                break;
            case "weekend":
                $to_date = date("Y-m-d 23:59:59", strtotime("next sunday"));
                break;
            case "plus_30d":
                $to_date = date("Y-m-d 23:59:59", strtotime("30 days"));
                break;
            case "plus_3m":
                $to_date = date("Y-m-d 23:59:59", strtotime("3 months"));
                break;
            case "plus_6m":
                $to_date = date("Y-m-d 23:59:59", strtotime("6 months"));
                $search_params['limit'] = 500;
                break;
            case "plus_1y":
                $to_date = date("Y-m-d 23:59:59", strtotime("1 year"));
                $search_params['limit'] = 500;
                break;
            case "minus_6m":
                $from_date = date("Y-m-d 00:00:00", strtotime("-6 months"));
                $to_date = date("Y-m-d 23:59:59");
                break;
            default:
                $to_date = date("Y-m-d 23:59:59", strtotime("1 year"));
                $search_params['limit'] = 500;
                break;
        }
        $search_params['where']["edition_date >= "] = $from_date;
        $search_params['where']["edition_date <= "] = $to_date;


        // SHOW AS
        set_cookie("listing_pref", $this->input->post("show"), 172800);
        if ($this->input->post("show") == "grid") {
            $view_to_load = 'race_grid';
        } else {
            $view_to_load = 'race_list';
        }



        // SORT       
        switch ($this->input->post("when")) {
            case "any":
            case "minus_6m":
                $sort = "DESC";
                break;
            default:
                $sort = "ASC";
                break;
        }
        $search_params['order_by']["edition_date"] = $sort;

        // wts($search_params,true);
        // DO THE SEARCH
        // OLD
        // $this->data_to_views['edition_list'] = $this->race_model->add_race_info($this->edition_model->get_edition_list($search_params, NULL, false), $race_search_params);

        // NEW
        $search_table_result = $this->edition_model->main_search($search_params, 0);
        // wts($search_table_result);
        foreach ($search_table_result as $result) {
            if (!isset($this->data_to_views['edition_list'][$result['edition_id']])) {
                $this->data_to_views['edition_list'][$result['edition_id']] = $result;
                // status msg
                $this->data_to_views['edition_list'][$result['edition_id']]['status_info'] = $this->formulate_status_notice($result);
            }
            // race stuffs
            $this->data_to_views['edition_list'][$result['edition_id']]['race_list'][$result['race_distance_int']] = $result;
            $this->data_to_views['edition_list'][$result['edition_id']]['race_list'][$result['race_distance_int']]['race_color'] = $this->edition_model->get_race_color($result['race_distance']);
            $this->data_to_views['edition_list'][$result['edition_id']]['race_distance_arr'][] = fraceDistance($result['race_distance']);
            // results
            $this->data_to_views['edition_list'][$result['edition_id']]['has_results'] = false;
            $has_result = $this->result_model->result_exist_for_race($result['race_distance_int']);
            $this->data_to_views['edition_list'][$result['edition_id']]['race_list'][$result['race_distance_int']]['has_results'] = $has_result;
            if ($has_result) {
                $this->data_to_views['edition_list'][$result['edition_id']]['has_results'] = $has_result;
            }

            // sort array according to the distance (key) decending
            krsort($this->data_to_views['edition_list'][$result['edition_id']]['race_list']);
        }

        // wts($this->input->post());
        // wts($search_params, true);
        // wts($this->data_to_views['edition_list'], true);

        $this->data_to_views['page_title'] = "Search";
        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        $this->load->view('templates/search_form');
        // $this->load->view('templates/' . $view_to_load, $this->data_to_views);
        $this->load->view('templates/race_list', $this->data_to_views);
        
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function tag($tag_type, $query)
    {
        //STATUS
        $search_params['where_in']["edition_status"] = [1, 17];

        $search_params['where']["edition_date >= "] = date("Y-m-d 00:00:00");
        $search_params['where']["edition_date <= "] = date("Y-m-d 23:59:59", strtotime("1 year"));
        $race_search_params = [];

        $query = urldecode($query);

        switch ($tag_type) {
            case "race_name":
                $race_search_params['where']["race_name"] = $query;
                break;
            case "race_distance":
                $race_search_params['where']["race_distance"] = floatval(str_replace("km", "", $query));
                break;
            case "region_name":
                $search_params['where']["region_name"] = $query;
                break;
            case "province_name":
                $search_params['where']["province_name"] = $query;
                break;
            case "club_name":
                $search_params['where']["club_name"] = $query;
                break;
            case "town_name":
                $search_params['where']["town_name"] = $query;
                break;
            case "event_name":
                $search_params['where']["edition_date >= "] = date("2016-01-01 00:00:00");
                $search_params['where']["event_name"] = $query;
                break;
            case "edition_year":
                redirect(base_url("calendar/" . $query));
                break;
            case "edition_month":
                $query_part = explode(" ", $query);
                $month_num = date("m", strtotime("$query_part[0]-$query_part[1]"));
                redirect(base_url("calendar/" . $query_part[1] . "/" . $month_num));
                break;
            case "asa_member_abbr":
                $search_params['where']["asa_member_abbr"] = $query;
                break;
            default:
                redirect("404");
                break;
        }

        // DO THE SEARCH
        $this->data_to_views['edition_list'] = $this->race_model->add_race_info($this->edition_model->get_edition_list($search_params), $race_search_params);
        if (!empty($this->data_to_views['edition_list'])) {
            foreach ($this->data_to_views['edition_list'] as $edition_id => $edition_data) {
                $this->data_to_views['edition_list'][$edition_id]['status_info'] = $this->formulate_status_notice($edition_data);
            }
        }

        //        echo $view_to_load;
        //        wts($this->input->post());
        //        wts($this->data_to_views['edition_list']);
        //        wts($race_search_params);
        //        wts($search_params, true);
        // SHOW AS 
        set_cookie("listing_pref", $this->input->post("show"), 172800);
        if ($this->input->post("show") == "grid") {
            $view_to_load = 'race_grid';
        } else {
            $view_to_load = 'race_list';
        }
        $this->data_to_views['page_title'] = "Search";
        $this->data_to_views['tag'] = $query;

        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        $this->load->view('templates/search_form');
        $this->load->view('templates/' . $view_to_load, $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }
}
