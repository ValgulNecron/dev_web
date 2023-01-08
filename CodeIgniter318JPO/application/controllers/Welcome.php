<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller
{
	public function __construct()
    {
        parent:: __construct();
        $this->load->helper('url_helper');
        $this->load->database();
        $this->load->model('model', 'Model');
		$this->load->helper('form');
		$this->load->library('session');
    }
    public function index()
    {
        $data['codeForma']=$this->Model->listeDeroulante();
        $this->load->view('pageDeConnexion', $data);
    }
	public function pageDeConnexion()
    {
        $test = false;
        $codeForma = $this->input->post('codeFormation');
        $test = $this->Model->authentificationPageDeConnexion($codeForma);
        if ($test == true)
        {
            $_SESSION['codeForma'] = $codeForma;
			$this->load->view('formulaire');
        }
        else
        {
            $data['codeForma']=$this->Model->listeDeroulante();
            $this->load->view('pageDeConnexion', $data);
        }
    }
	
	public function formulaire()
    {
        $nom = $this->input->post('nom');
        $prenom = $this->input->post('prenom');
        $etabliOrig = $this->input->post('etabliOrig');
        $formaActu = $this->input->post('formaActu');
        $intereForma = $this->input->post('intereForma');
        $postulAutreEta = $this->input->post('postulAutreEta');
        $numRue = $this->input->post('numRue');
        $nomRue = $this->input->post('nomRue');
        $nomVille = $this->input->post('nomVille');
        $codePostal = $this->input->post('codePostal');
        $codePost = $this->input->post('codePost');
        $mail = $this->input->post('mail');
        $numTel = $this->input->post('numTel');
        $codeForma = $_SESSION['codeForma'];
        $this->Model->traitementFormulaire($codeForma,$nom,$prenom,$etabliOrig,$formaActu,$intereForma,$postulAutreEta,$numRue,$nomRue,$nomVille,$codePost,$mail,$numTel);
        $this->load->view('saisieReussie');
    }
	
	
	public function statistiques()
	{
		$codeFormation = $this->input->post('CodeFormation');
        $mdpStat = $this->input->post('mdpStat');
		
		if ($this->Model->authentificationPageDeStat($codeFormation,$mdpStat))
		{
			$data['statistiquesIntituleForma']= $this->Model->statistiquesIntituleForma($codeFormation);
			$data['statistiqueNbrVisiteur']= $this->Model->statistiqueNbrVisiteur($codeFormation);
			$data['statistiqueNbrVisiteurIntere']= $this->Model->statistiqueNbrVisiteurIntere($codeFormation);
			$data['statistiqueNbrVisiteurIntereDepoAutreDossier']= $this->Model->statistiqueNbrVisiteurIntereDepoAutreDossier($codeFormation);
			$data['visitHeure']= $this->Model->visitHeure($codeFormation);
			$data['infoVisit']= $this->Model->infoVisit($codeFormation);
			
			$this->load->view('statistiques',$data);
		}
		else
		{
			$data['codeFormation']=$this->Model->listeDeroulante();
			$this->load->view('connexionPageStatistiques', $data);
		}
		
		
	}
	
	public function contenu($id)
	{
		if ($id == "mentionsLegales")
		{
			$this->load->view('mentionsLegales');
		}
		elseif ($id == "formulaire")
		{
			$this->load->view('formulaire');
		}
		elseif ($id == "connexionPageStatistiques")
		{
			$data['codeFormation']=$this->Model->listeDeroulante();
			$this->load->view('connexionPageStatistiques', $data);
		}
	}
}