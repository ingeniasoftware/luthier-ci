<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class DashboardController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Controller index
     *
     * @return void
     *
     * @access public
     */
    public function index()
    {
        $this->load->view('UserArea/dashboard');
    }
}