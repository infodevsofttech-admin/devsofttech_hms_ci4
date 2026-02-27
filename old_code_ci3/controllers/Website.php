<?php


defined('BASEPATH') OR exit('No direct script access allowed');

    class Website extends MY_Controller {
        public function __construct ()
        {
            parent::__construct();
        }

        public function index()
        {
            $this->load->view('form');
        }

        public function submission()
        {

            if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

            $FormRules = array(
                array(
                    'field' => 'inputEmail3',
                    'label' => 'Email Address',
                    'rules' => 'required|min_length[5]|max_length[30]|valid_email'
                )
            );

            $this->form_validation->set_rules($FormRules);

            if ($this->form_validation->run() == TRUE)
            {
                echo '<div class="success">Your website is submitted</div>';
            }
            else
            {
                echo '<div class="errors">'.validation_errors().'</div>';
            }
        }

    }
