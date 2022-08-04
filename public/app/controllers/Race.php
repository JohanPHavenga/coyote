<?php

// Race Calendar
class Race extends Frontend_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('edition_model');
        $this->load->model('race_model');
    }

    //    public function _remap($method, $params = array()) {
    //        if (method_exists($this, $method)) {
    //            return call_user_func_array(array($this, $method), $params);
    //        } else {
    //            $this->list($method, $params);
    //        }
    //    }

    public function list($year = null, $month = null, $day = null)
    {
        $query_params = [
            "where_in" => [
                "region_id" => $this->session->region_selection,
                "edition_status" => [1, 17],
            ],
            "where" => [
                "edition_date >= " => date("Y-m-d 00:00:00"),
                "edition_date <= " => date("Y-m-d H:i:s", strtotime("3 months")),
            ],
            "order_by" => ["edition_date" => "ASC"],
        ];

        //        wts($this->uri->segment(1));
        //        wts($this->router->fetch_class(),1);

        if ($year !== null) {
            if (is_numeric($year)) {
                $this->data_to_views['page_title'] = "Running Races in " . $year;
                $this->data_to_views['meta_description'] = "List of upcoming running races in " . $year;
                $query_params = [
                    "where" => ["edition_date >= " => "$year-1-1 00:00:00", "edition_date <= " => "$year-12-31 23:59:59",],
                ];

                if ($month !== null) {
                    if (is_numeric($month)) {
                        $month_name = date('F', mktime(0, 0, 0, $month, 1));
                        $this->data_to_views['page_title'] = "Running Races in " . $month_name . " " . date($year);
                        $this->data_to_views['meta_description'] = "List of upcoming running races in " . $month_name . " " . date($year);
                        $query_params = [
                            "where" => ["edition_date >= " => "$year-$month-1 00:00:00", "edition_date <= " => date("$year-$month-t 23:59:59")],
                        ];
                        $this->data_to_views['crumbs_arr'] = replace_key($this->data_to_views['crumbs_arr'], $month, $month_name);

                        if ($day !== null) {
                            if (is_numeric($day)) {
                                $this->data_to_views['page_title'] = "Running Races on " . $day . " " . $month_name . " " . date($year);
                                $this->data_to_views['meta_description'] = "List of running races on " . $day . " " . $month_name . " " . date($year);
                                $query_params = [
                                    "where" => ["edition_date >= " => "$year-$month-$day 00:00:00", "edition_date <= " => "$year-$month-$day 23:59:59"],
                                ];
                            } else {
                                redirect("404");
                            }
                        }
                    } else {
                        redirect("404");
                    }
                    // add status and region to query params for all
                    $query_params["where_in"] = [
                        "region_id" => $this->session->region_selection,
                        "edition_status" => [1, 17],
                    ];
                }
            } else {
                redirect("404");
            }
        } else {
            $this->data_to_views['page_title'] = "Upcoming Race Calendar";
            $this->data_to_views['meta_description'] = "List of upcoming running races in your selected regions";
        }

        // $this->data_to_views['edition_list'] = $this->race_model->add_race_info($this->edition_model->get_edition_list($query_params));
        $search_table_result = $this->edition_model->main_search($query_params, 0);
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

        // check cookie vir listing preference.
        if (get_cookie("listing_pref") == "grid") {
            $view_to_load = 'race_grid';
        } else {
            $view_to_load = 'race_list';
        }
        $this->data_to_views['banner_img'] = "run_16";
        $this->data_to_views['banner_pos'] = "55%";

        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->banner_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        // if (($this->uri->segment(1) == "race") || (!($this->data_to_views['edition_list']))) {
        $this->load->view('templates/search_form');
        // }
        $this->load->view('templates/' . $view_to_load, $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function virtual()
    {
        $query_params = [
            "where_in" => ["edition_status" => [17]],
            "where" => ["edition_date >= " => date("Y-m-d H:i:s")],
            "order_by" => ["edition_date" => "ASC"],
        ];

        // $this->data_to_views['edition_list'] = $this->race_model->add_race_info($this->edition_model->get_edition_list($query_params));
        // if ($this->data_to_views['edition_list']) {
        //     foreach ($this->data_to_views['edition_list'] as $edition_id => $edition_data) {
        //         $this->data_to_views['edition_list'][$edition_id]['status_info'] = $this->formulate_status_notice($edition_data);
        //     }
        // }
        $search_table_result = $this->edition_model->main_search($query_params, 0);
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


        // check cookie vir listing preference.
        if (get_cookie("listing_pref") == "grid") {
            $view_to_load = 'race_grid';
        } else {
            $view_to_load = 'race_list';
        }

        $this->data_to_views['banner_img'] = "run_09";
        $this->data_to_views['banner_pos'] = "60%";
        $this->data_to_views['page_title'] = "Virtual Races in South Africa";
        $this->data_to_views['meta_description'] = "List of upcoming virtual races in South Africa";
        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->banner_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        //        $this->load->view('templates/search_form');
        $this->load->view('templates/' . $view_to_load, $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function favourite()
    {
        // wts("2",1);
        $this->load->model('favourite_model');
        if ($this->logged_in_user) {
            $fav_edition_arr = $this->favourite_model->get_favourite_list($this->logged_in_user['user_id']);
            $query_params = [
                "where_in" => ["edition_id" => $fav_edition_arr, "edition_status" => [1, 3, 9, 17]],
                "where" => ["edition_date >= " => date("Y-m-d H:i:s", strtotime("-1 month")),],
                "order_by" => ["edition_date" => "ASC"],
            ];
            $search_table_result = $this->edition_model->main_search($query_params, 0);
            // wts($search_table_result, 1);


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

            // check cookie vir listing preference.
            if (get_cookie("listing_pref") == "grid") {
                $view_to_load = 'race_grid';
            } else {
                $view_to_load = 'race_list';
            }
            if (empty($search_table_result)) {
                $view_to_load = 'favourite_msg';
            }
        } else {
            $view_to_load = 'favourite_msg';
        }

        $this->data_to_views['banner_img'] = "run_11";
        $this->data_to_views['banner_pos'] = "40%";
        $this->data_to_views['page_title'] = "Favourite Races";
        $this->data_to_views['meta_description'] = "List of your favourite events";

        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->banner_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        if ($this->logged_in_user) {
            $this->data_to_views['page_menu'] = $this->get_user_menu();
            $this->load->view('templates/page_menu', $this->data_to_views);
        }
        $this->load->view('templates/' . $view_to_load, $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function featured()
    {
        $query_params = [
            "where_in" => ["region_id" => $this->session->region_selection, "edition_status" => [1, 3, 9, 17]],
            "where" => ["edition_date >= " => date("Y-m-d H:i:s"), "edition_isfeatured " => 1,],
            "order_by" => ["edition_date" => "ASC"],
        ];

        // $this->data_to_views['edition_list'] = $this->race_model->add_race_info($this->edition_model->get_edition_list($query_params));
        // if ($this->data_to_views['edition_list']) {
        //     foreach ($this->data_to_views['edition_list'] as $edition_id => $edition_data) {
        //         $this->data_to_views['edition_list'][$edition_id]['status_info'] = $this->formulate_status_notice($edition_data);
        //     }
        // }
        $search_table_result = $this->edition_model->main_search($query_params, 0);
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

        // check cookie vir listing preference.
        if (get_cookie("listing_pref") == "grid") {
            $view_to_load = 'race_grid';
        } else {
            $view_to_load = 'race_list';
        }

        $this->data_to_views['banner_img'] = "run_01";
        $this->data_to_views['banner_pos'] = "40%";
        $this->data_to_views['page_title'] = "Featured Races";
        $this->data_to_views['meta_description'] = "List of featured running races in your selected regions";
        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->banner_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        $this->load->view('templates/search_form');
        $this->load->view('templates/' . $view_to_load, $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function most_viewed()
    {
        $this->load->model('history_model');
        $this->data_to_views['edition_list'] = [];
        $query_params = [
            // "where_in" => ["region_id" => $this->session->region_selection,],
            "order_by" => ["historysum_countmonth" => "DESC"],
            "limit" => "10",
        ];
        $most_viewed = $this->history_model->get_history_summary($query_params);

        if ($most_viewed) {
            $query_params = [
                "where_in" => ["edition_id" => array_keys($most_viewed),],
                "order_by" => ["edition_date" => "ASC"],
            ];
            $this->data_to_views['edition_list'] = $this->race_model->add_race_info($this->edition_model->get_edition_list($query_params));
            if ($this->data_to_views['edition_list']) {
                foreach ($this->data_to_views['edition_list'] as $edition_id => $edition_data) {
                    $this->data_to_views['edition_list'][$edition_id]['status_info'] = $this->formulate_status_notice($edition_data);
                }
            }
        }

        $this->data_to_views['banner_img'] = "run_05";
        $this->data_to_views['banner_pos'] = "50%";
        $this->data_to_views['page_title'] = "Top 10 most views races";
        $this->data_to_views['meta_description'] = "List of the Top 10 most viewed running races in your selected regions";
        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->banner_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        $this->load->view('templates/race_grid', $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function history($year = null)
    {

        if ($year) {
            $query_params = [
                //            "where" => ["edition_date >= " => date("Y-m-d H:i:s"),"edition_date < " => date("Y-m-d H:i:s", strtotime($time_span)),],
                "where_in" => ["region_id" => $this->session->region_selection, "edition_status" => [1, 3, 9, 17]],
                "order_by" => ["edition_date" => "DESC"],
            ];
            if ($year == date("Y")) {
                $query_params["where"] = ["edition_date < " => date("Y-m-d H:i:s"), "edition_date >= " => date("$year-01-01 00:00:00"),];
            } else {
                $query_params["where"] = ["edition_date <= " => date("$year-12-31 23:59:59"), "edition_date >= " => date("$year-01-01 00:00:00"),];
            }
            $edition_list = $this->race_model->add_race_info($this->edition_model->get_edition_list($query_params));
            $this->data_to_views['edition_arr'] = $this->chronologise_data($edition_list, "edition_date");
            $this->data_to_views['year'] = $year;
            $view = 'templates/race_accordian';
            $this->data_to_views['meta_description'] = "List of running races that ran in $year";
        } else {
            $view = 'race/history';
            $this->data_to_views['meta_description'] = "List of running races stretching back to when the website began in 2016";
        }


        $this->data_to_views['banner_img'] = "run_04";
        $this->data_to_views['banner_pos'] = "50%";
        $this->data_to_views['page_title'] = "Races History";
        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->banner_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        $this->load->view($view, $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function results()
    {
        $query_params = [
            "where_in" => ["region_id" => $this->session->region_selection, "edition_status" => [1, 17]],
            "where" => ["edition_date >= " => date("Y-m-d H:i:s", strtotime("-2 months")), "edition_date <= " => date("Y-m-d H:i:s"),],
            "order_by" => ["edition_date" => "DESC"],
        ];

        $this->data_to_views['edition_list'] = $this->race_model->add_race_info($this->edition_model->get_edition_list($query_params));
        if ($this->data_to_views['edition_list']) {
            foreach ($this->data_to_views['edition_list'] as $edition_id => $edition_data) {
                $this->data_to_views['edition_list'][$edition_id]['status_info'] = $this->formulate_status_notice($edition_data);
            }
        }
        //        wts($this->data_to_views['edition_list'],true);

        $this->data_to_views['banner_img'] = "run_14";
        $this->data_to_views['banner_pos'] = "50%";
        $this->data_to_views['page_title'] = "Race Results";
        $this->data_to_views['meta_description'] = "List of results for running races over the past 2 months in your selected regions";
        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->banner_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        //        $this->load->view('templates/search_form');
        $this->load->view('templates/race_list', $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function parkrun()
    {
        $this->data_to_views['banner_img'] = "run_04";
        $this->data_to_views['banner_pos'] = "15%";
        $this->data_to_views['page_title'] = "parkrun";
        $this->data_to_views['meta_description'] = "Description of what parkrun is and a link through to the parkrun.co.za site";
        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->banner_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        $this->load->view('race/parkrun', $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }
}
