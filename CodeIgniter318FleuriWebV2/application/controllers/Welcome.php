<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url_helper');// Charger des foncons pour gérer les URL
		$this->load->database();
		$this->load->model('model', 'requetes');
	}
	public function index()
	{
		$this->load->view('entete'); // créer entete.php dans le dossier views
		$this->load->view('menu'); // créer menu.php dans le dossier views
		$this->load->view('affichage'); // créer affichage.php dans le dossier views
		$this->load->view('piedpage');
	}
	public function contenu($id)
	{
		$this->load->helper('url_helper');
		$this->load->view('entete');
		if($id=="Accueil")
		{
			$this->load->view('menu');
			$this->load->view('affichage');
		}
		elseif($id=="Bulbes")
		{
			$this->load->view('menu');
			$data['bulbes']=$this->requetes->getBulbes();
			$this->load->view('Bulbes',$data);
		}
		elseif($id=="PlantesAMassifs")
		{
			$this->load->view('menu');
			$data['plantesMassifs']=$this->requetes->getMassifs();
			$this->load->view('PlantesAMassif',$data);
		}
		elseif($id=="Rosiers")
		{
			$this->load->view('menu');
			$data['rosiers']=$this->requetes->getRosiers();
			$this->load->view('Rosiers',$data);
		}
		elseif($id=="mentionsLegales")
		{
			$this->load->view('mentionsLegales');
		}
		elseif($id=="cgu")
		{
			$this->load->view('cgu');
		}
		else
		{
			$this->load->view('menu');
			$this->load->view('affichage');
		}
		$this->load->view('piedpage');
	}
}