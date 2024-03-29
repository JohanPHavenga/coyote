<?php

class Result extends Frontend_Controller
{

    public function __construct()
    {
        parent::__construct();
        if (empty($this->logged_in_user)) {
            $this->session->set_flashdata([
                'alert' => "You are not currently logged in, or your session has expired. Please log in or register",
                'status' => "warning",
                'icon' => "info-circle",
            ]);
            redirect(base_url("login"));
        } else {
            $this->load->model('user_model');
            $this->load->model('race_model');
            $this->load->model('edition_model');
            $this->load->library('table');
            $this->data_to_views['page_menu'] = $this->get_user_menu();
        }
    }

    // SEARCH FOR RESULTS
    public function search()
    {
        if ($this->input->post("result_search") !== null) {

            $this->data_to_views['page_title'] = "Search for results";
            $this->data_to_views['meta_description'] = "Search for results";
            $this->data_to_views['crumbs_arr'] = [
                "Home" => base_url(),
                "User" => base_url("user"),
                "My Results" => base_url("user/my-results"),
                "Search" => "",
            ];

            // set searhc paramaters
            $search_params['where_in']["edition_status"] = [1, 17];
            $search_params['group_start'] = "";
            $search_params['like']["edition_name"] = $this->input->post_get("result_search");
            $search_params['or_like']["event_name"] = $this->input->post_get("result_search");
            $search_params['or_like']["race_name"] = $this->input->post_get("result_search");
            $search_params['group_end'] = "";
            $search_params['where']["edition_date >= "] = date("Y-m-d 00:00:00", strtotime("-6 years"));
            $search_params['where']["edition_date <= "] = date("Y-m-d 23:59:59");
            $search_params['where']["has_local_results"] = 1;
            $search_params['order_by']["edition_date"] = "DESC";
            $search_params['limit'] = 50;

            $this->data_to_views['race_list'] = $this->edition_model->main_search($search_params, 0);
            $search_params['where']["has_local_results"] = 0;
            $this->data_to_views['race_list_no_results'] = $this->edition_model->main_search($search_params, 0);
            // $this->data_to_views['race_list'] = $this->race_model->get_race_list_with_results($this->input->post("result_search"));
            //    echo $this->input->post("result_search");
            // wts($this->data_to_views['race_list'], 1);

            // load view
            $this->load->view($this->header_url, $this->data_to_views);
            $this->load->view($this->notice_url, $this->data_to_views);
            $this->load->view('templates/page_menu', $this->data_to_views);
            $this->load->view('result/search', $this->data_to_views);
            $this->load->view($this->footer_url, $this->data_to_views);
        } else {
            $this->session->set_flashdata([
                'alert' => "Use the form below to find race results",
                'status' => "warning",
                'icon' => "info-circle",
            ]);
            redirect(base_url("user/my-results"));
        }
    }

    public function list($race_id, $load = "summary")
    {
        if (is_numeric($race_id)) {
            // set basics for the view
            $this->data_to_views['page_title'] = "List of results";
            $this->data_to_views['meta_description'] = "List of results for race";
            $this->data_to_views['load'] = $load;
            $this->data_to_views['crumbs_arr'] = [
                "Home" => base_url(),
                "User" => base_url("user"),
                "My Results" => base_url("user/my-results"),
                "Search" => "",
            ];

            $params['race_id'] = $race_id;
            if ($load == "summary") {
                $params['name'] = $this->logged_in_user['user_name'];
                $params['surname'] = $this->logged_in_user['user_surname'];
            }
            $this->data_to_views['result_list'] = $this->race_model->get_race_detail_with_results($params);
            if (!$this->data_to_views['result_list']) {
                redirect(base_url("result/list/" . $race_id . "/full"));
            }

            $firstKey = array_key_first($this->data_to_views['result_list']);
            $result = $this->data_to_views['result_list'][$firstKey];
            foreach ($result as $key => $value) {
                if (strpos($key, "result_") === false) {
                    $this->data_to_views['race_info'][$key] = $value;
                }
            }

            $this->data_to_views['css_to_load'] = [base_url("assets/js/plugins/components/datatables/datatables.min.css")];
            $this->data_to_views['scripts_to_load'] = [
                base_url("assets/js/plugins/components/datatables/datatables.min.js"),
                base_url("assets/js/data-tables.js"),
            ];

            // load view
            $this->load->view($this->header_url, $this->data_to_views);
            $this->load->view($this->notice_url, $this->data_to_views);
            $this->load->view('templates/page_menu', $this->data_to_views);
            $this->load->view('result/list', $this->data_to_views);
            $this->load->view($this->footer_url, $this->data_to_views);
        } else {
            $this->session->set_flashdata([
                'alert' => "That race does not exist. Use the form below to find race results",
                'status' => "danger",
                'icon' => "times-circle",
            ]);
            redirect(base_url("user/my-results"));
        }
    }

    public function auto()
    {
        $this->load->model('admin/result_model');
        $this->load->model('admin/userresult_model');
        // set basics for the view
        $this->data_to_views['page_title'] = "Auto search for results";
        $this->data_to_views['meta_description'] = "Auto suggest results using your name and surname";
        $this->data_to_views['crumbs_arr'] = [
            "Home" => base_url(),
            "User" => base_url("user"),
            "My Results" => base_url("user/my-results"),
            "Auto Search" => "",
        ];

        $params['name'] = $this->logged_in_user['user_name'];
        $params['surname'] = $this->logged_in_user['user_surname'];



        // auto search result list
        $this->data_to_views['result_list'] = $this->result_model->auto_search($params);
        // get already claimed results
        $claimed_results = $this->userresult_model->get_userresult_summary($this->logged_in_user['user_id']);
        // remove results already claimed from the result_list
        $already_claimed = array_intersect_key($this->data_to_views['result_list'], $claimed_results);
        foreach ($already_claimed as $result_id => $result) {
            unset($this->data_to_views['result_list'][$result_id]);
        }

        $this->data_to_views['css_to_load'] = [base_url("assets/js/plugins/components/datatables/datatables.min.css")];
        $this->data_to_views['scripts_to_load'] = [
            base_url("assets/js/plugins/components/datatables/datatables.min.js"),
            base_url("assets/js/data-tables.js"),
        ];

        // load view
        $this->load->view($this->header_url, $this->data_to_views);
        $this->load->view($this->notice_url, $this->data_to_views);
        $this->load->view('templates/page_menu', $this->data_to_views);
        $this->load->view('result/auto', $this->data_to_views);
        $this->load->view($this->footer_url, $this->data_to_views);
    }

    public function view($result_id)
    {
        $this->load->model('admin/userresult_model');

        if ((is_numeric($result_id)) && ($this->userresult_model->exists($this->logged_in_user['user_id'], $result_id))) {

            $result = $this->race_model->get_race_detail_with_results(["result_id" => $result_id]);
            $this->data_to_views['result_detail'] = $result[$result_id];

            if ($this->data_to_views['result_detail']['file_id']) {
                $this->data_to_views['result_detail']['official_result']=1;
            } else {
                $this->data_to_views['result_detail']['official_result']=0;
                if ($this->data_to_views['result_detail']['result_pos']==0) {
                    $this->data_to_views['result_detail']['result_pos']="Unknown";
                }
            }

            $this->data_to_views['page_title'] = "Detail for result #" . $result_id;
            $this->data_to_views['meta_description'] = "Result details for " . $result[$result_id]['result_name'] . " " . $result[$result_id]['result_surname'] . " in the " . $result[$result_id]['edition_name'] . " race.";

            $this->data_to_views['crumbs_arr'] = [
                "Home" => base_url(),
                "User" => base_url("user"),
                "My Results" => base_url("user/my-results"),
                "View" => "",
            ];

            // load view
            $this->load->view($this->header_url, $this->data_to_views);
            $this->load->view($this->notice_url, $this->data_to_views);
            $this->load->view('result/view', $this->data_to_views);
            $this->load->view($this->footer_url, $this->data_to_views);
        } else {
            $this->session->set_flashdata([
                'alert' => "You are not linked to that result.",
                'status' => "danger",
                'icon' => "times-circle",
            ]);
            redirect(base_url("user/my-results"));
        }
    }

    public function claim($result_id)
    {
        $this->load->model('admin/userresult_model');
        if (is_numeric($result_id)) {
            $user_id = $this->logged_in_user['user_id'];
            // check if already exists
            if (!$this->userresult_model->exists($user_id, $result_id)) {
                $this->userresult_model->set_userresult("add", ["user_id" => $user_id, "result_id" => $result_id]);
                $this->session->set_flashdata([
                    'alert' => "Result has been added to your profile",
                    'status' => "success",
                    'icon' => "check-circle",
                ]);
            } else {
                $this->session->set_flashdata([
                    'alert' => "That result is already linked to your profile",
                    'status' => "warning",
                    'icon' => "info-circle",
                ]);
            }
        } else {
            $this->session->set_flashdata([
                'alert' => "That result does not exist. Use the form below to find race results",
                'status' => "danger",
                'icon' => "times-circle",
            ]);
        }
        redirect(base_url("user/my-results"));
    }

    public function remove($result_id)
    {
        $this->load->model('admin/userresult_model');
        if ((is_numeric($result_id)) && ($this->userresult_model->exists($this->logged_in_user['user_id'], $result_id))) {
            $user_id = $this->logged_in_user['user_id'];
            // check if already exists
            $this->userresult_model->remove_userresult($user_id, $result_id);
            $this->session->set_flashdata([
                'alert' => "Result has been removed from your profile",
                'status' => "success",
                'icon' => "check-circle",
            ]);
        } else {
            $this->session->set_flashdata([
                'alert' => "You are not linked to that result.",
                'status' => "danger",
                'icon' => "times-circle",
            ]);
        }
        redirect(base_url("user/my-results"));
    }

    public function my_data($dist)
    {
        $this->load->model('admin/userresult_model');
        $result_list = array_reverse($this->userresult_model->get_userresult_list($this->logged_in_user['user_id'], null, null, $dist));

        foreach ($result_list as $key => $result) {
            $data[$key]['date'] = fdateShort($result['edition_date']);
            $data[$key]['time'] = strval(strtotime("1970-01-01 " . $result['result_time'] . " UTC"));
            $data[$key]['race'] = $result['edition_name'];
        }
        echo json_encode($data);
    }

    public function add($race_id)
    {
        $this->load->model('admin/club_model');
        $this->load->model('admin/result_model');
        $this->load->model('admin/userresult_model');
        $this->data_to_views['race_id'] = $race_id;
        // get race info
        $this->data_to_views['race_info'] = $this->race_model->get_race_detail($race_id);

        // Club Dropdown
        $club_dropdown = $this->club_model->get_club_dropdown(true);
        array_shift($club_dropdown);
        $club_dropdown = ["None" => "No Club", "TEMP" => "Temp"] + $club_dropdown;
        $this->data_to_views['club_dropdown'] = $club_dropdown;

        //Category Dropdown
        $this->data_to_views['category_dropdown'] = [
            "" => "",
            "Junior" => "Junior",
            "Senior" => "Senior",
            "40-49" => "40-49",
            "50-59" => "50-59",
            "60-69" => "60-69",
            "70+" => "70+",
        ];

        // wts($this->data_to_views['page_title'], 1);

        //  $this->data_to_views['meta_description'] = "Result details for " . $result[$result_id]['result_name'] . " " . $result[$result_id]['result_surname'] . " in the " . $result[$result_id]['edition_name'] . " race.";

        $this->data_to_views['crumbs_arr'] = [
            "Home" => base_url(),
            "User" => base_url("user"),
            "My Results" => base_url("user/my-results"),
            "Add" => "",
        ];

        // validation rules
        $this->form_validation->set_rules('result_time', 'Your Time', 'trim|required');
        $this->form_validation->set_rules('result_pos', 'Your Position', 'trim|required');
        $this->form_validation->set_rules('result_name', 'Name', 'trim|required');
        $this->form_validation->set_rules('result_surname', 'Surname', 'trim|required');
        $this->form_validation->set_rules('result_sex', 'Gender', 'trim|required');

        // load correct view
        if ($this->form_validation->run() === FALSE) {

            $this->load->view($this->header_url, $this->data_to_views);
            $this->load->view($this->notice_url, $this->data_to_views);
            $this->load->view('result/add', $this->data_to_views);
            $this->load->view($this->footer_url, $this->data_to_views);
        } else {
            // write result to result table
            $result_id=$this->result_model->set_result("add",null,$this->input->post());
            // write to user_result table
            $this->userresult_model->set_userresult("add",["user_id"=>$this->logged_in_user['user_id'],"result_id"=>$result_id]);
            redirect(base_url("user/my-results"));
            // redirect to user/my-results
        }
    }
}
