<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	public function index()
	{
		$this->load->helper('url_helper');// Charger des foncons pour gérer les URL
		$this->load->view('entete'); // créer entete.php dans le dossier views
		$this->load->view('menu'); // créer menu.php dans le dossier views
		$this->load->view('affichage'); // créer affichage.php dans le dossier views
		$this->load->view('piedpage'); // créer piedpage.php dans le dossier views
	}
	public function contenu($id)
	{
		$this->load->helper('url_helper');// Charger des foncons pour gérer les URL
		$this->load->view('entete');
		$this->load->view('menu');
		if($id=="Photos")
		{
			$this->load->view('liste'); // Créer une vue liste.php dans VIEWS
		}
		else
		{
			$this->load->view('affichage');
		}
		$this->load->view('piedpage');
	}
}