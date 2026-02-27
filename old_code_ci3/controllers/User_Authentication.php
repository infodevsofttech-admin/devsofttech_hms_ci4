<?php

Class User_Authentication extends CI_Controller {

public function __construct() {
	parent::__construct();

	// Load form helper library
	$this->load->helper('form');

	// Load form validation library
	$this->load->library('form_validation');

	// Load session library
	$this->load->library('session');

	$this->load->helper('security');
}

// Show login page
public function index() {
	$this->load->view('Mobile_app/login');
}

// Check for user login process
public function user_login_process() {

	// Retrieve session data
	$session_set_value = $this->session->all_userdata();

	// Check for remember_me data in retrieved session data
	if (isset($session_set_value['remember_me']) && $session_set_value['remember_me'] == "1") {
		//$this->load->view('admin_page');
		$data['user'] = 1;
		$this->load->view('Mobile_app/index',$data);
	} else {

		// Check for validation
		$this->form_validation->set_rules('username', 'Username', 'trim|required|xss_clean');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');

		if ($this->form_validation->run() == FALSE) {
			//$this->load->view('login_form');
			$this->load->view('Mobile_app/login');
		} else {
			$username = $this->input->post('username');
			$password = $this->input->post('password');
			if ($username == "fugo" && $password == "formget") {
				$remember = $this->input->post('remember_me');
				if ($remember) {
					// Set remember me value in session
					$this->session->set_userdata('remember_me', TRUE);
				}
				$sess_data = array(
				'username' => $username,
				'password' => $password
				);
		
				$this->session->set_userdata('logged_in', $sess_data);
				//$this->load->view('admin_page');
				
				$data['user'] = 1;
				$this->load->view('Mobile_app/index',$data);

			} else {
				$data = array(
				'error_message' => 'Invalid Username or Password'
				);
				$this->load->view('Mobile_app/login', $data);
			}
		}
	}
}

// Logout from admin page
public function logout() {

	// Destroy session data
	$this->session->sess_destroy();
	$data['message_display'] = 'Successfully Logout';
	$this->load->view('Mobile_app/login', $data);
}

}?>