<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->model('Newbooks_model');
		$this->load->helper('url_helper');
		$this->load->library('session');
		$this->load->helper('form');
		$this->load->library('form_validation');
		$this->load->library('NewBooksConfig');
	}

	public function index()															//Default/homepage, I am not utilizing so just load a bare page.
	{
		$this->load->view('header');
		$this->load->view('footer');
	}
	
	public function login(){
		$data=$this->newbooksconfig->getScriptBrandingURLs();
		$this->load->view('templates/header',$data);
		$this->load->view('templates/login');
		$this->load->view('templates/footer');
	}
	
	public function auth(){
		$username=$this->input->post('username');
		$password=$this->input->post('password');
		//echo $username.$password;
		$this->session->set_userdata('ok','no');
		$unpw=$this->newbooksconfig->getUNPW();
		if($username==$unpw['UN']&&$password==$unpw['PW']){
			$newdata = array('ok'=>'yes','username'=>$unpw['UN']);
			$this->session->set_userdata($newdata);
		}
		$userok=$this->session->all_userdata();
		if($userok['ok']=='yes'){
			$brandData=$this->newbooksconfig->getScriptBrandingURLs();
			$baseURL['baseURL']=$this->newbooksconfig->getBaseURL();
			$this->load->view('templates/header',$brandData);
			$this->load->view('templates/backmenu',$baseURL);
			$this->load->view('templates/footer');
		}
		else{
			echo "You have entered invalid login credentials or been timed out. Please hit your browser's back button and try again.";
		}

	}
}
