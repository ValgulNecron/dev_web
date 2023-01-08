<?php if(!defined('BASEPATH'))exit('No direct scrip access allowed');
class Model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	
	function listeDeroulante()
    {
        $result = $this->db->conn_id->prepare("SELECT etablissement.codeForma FROM etablissement;");
        $result->execute();
        return ($result->fetchall());
    }
	
	function authentificationPageDeConnexion($codeFormation)
	{
		$testCodeFormation = $this->db->conn_id->prepare("SELECT etablissement.codeForma FROM etablissement WHERE etablissement.codeForma=:codeFormation LIMIT 1;");
		$testCodeFormation->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
		$testCodeFormation->execute();
		$testCodeFormation = $testCodeFormation->fetch();
		$testCodeFormation = $testCodeFormation['codeForma'];
		if ($testCodeFormation == $codeFormation)
		{
			return(true);
			
		}
		else
		{
			return(false);
		}
	}
	
	function authentificationPageDeStat($codeFormation,$mdpStat)
	{
		$testCodeFormation = $this->db->conn_id->prepare("SELECT etablissement.codeForma FROM etablissement WHERE etablissement.codeForma=:codeFormation LIMIT 1;");
		$testCodeFormation->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
		$testCodeFormation->execute();
		$testCodeFormation = $testCodeFormation->fetch();
		$testCodeFormation = $testCodeFormation['codeForma'];
		if ($testCodeFormation == $codeFormation)
		{
			$comparMdpStat = $this->db->conn_id->prepare("SELECT etablissement.motDePasseStatForma FROM etablissement WHERE etablissement.codeForma=:codeFormation LIMIT 1;");
			$comparMdpStat->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
			$comparMdpStat->execute();
			$comparMdpStat = $comparMdpStat->fetch();
			$comparMdpStat = $comparMdpStat['motDePasseStatForma'];
			if ($comparMdpStat == $mdpStat)
			{
				return(true);		
			}
			else
			{
				return(false);
			}
		}
		else
		{
			return(false);
		}
	}
	
	function recuperationCodeVisit($nom,$prenom,$etabliOrig,$formaActu)
    {
        $search = "SELECT visiteur.codeVisit FROM visiteur WHERE visiteur.nomVisit=:nom AND visiteur.prenomVisit=:prenom AND visiteur.etabliOrigVisit=:etabliOrig AND visiteur.formaActuVisit=:formaActu ORDER BY visiteur.codeVisit DESC LIMIT 1;";
        $codeVisit = $this->db->conn_id->prepare($search);
        $codeVisit->bindValue(':nom', $nom, PDO::PARAM_STR);
        $codeVisit->bindValue(':prenom', $prenom, PDO::PARAM_STR);
        $codeVisit->bindValue(':etabliOrig', $etabliOrig, PDO::PARAM_STR);
        $codeVisit->bindValue(':formaActu', $formaActu, PDO::PARAM_STR);
        $codeVisit->execute();
        $codeVisit = $codeVisit->fetch();
        $codeVisit = $codeVisit['codeVisit'];
        return($codeVisit);
    }
    
    function traitementFormulaire($codeForma,$nom,$prenom,$etabliOrig,$formaActu,$intereForma,$postulAutreEta,$numRue,$nomRue,$nomVille,$codePost,$mail,$numTel)
    {
        $search = "INSERT INTO visiteur(nomVisit,prenomVisit,locRueVisit,rueVisit,villeVisit,cpVisit,telVisit,mailVisit,formaActuVisit,etabliOrigVisit)VALUES(:nom,:prenom,:numRue,:nomRue,:nomVille,:codePost,:numTel,:mail,:formaActu,:etabliOrig);";
		$insertVisit = $this->db->conn_id->prepare($search);
        $insertVisit->bindValue(':nom', $nom, PDO::PARAM_STR);
        $insertVisit->bindValue(':prenom', $prenom, PDO::PARAM_STR);
        $insertVisit->bindValue(':numRue', $numRue, PDO::PARAM_STR);
        $insertVisit->bindValue(':nomRue', $nomRue, PDO::PARAM_STR);
        $insertVisit->bindValue(':nomVille', $nomVille, PDO::PARAM_STR);
        $insertVisit->bindValue(':codePost', $codePost, PDO::PARAM_STR);
        $insertVisit->bindValue(':numTel', $numTel, PDO::PARAM_STR);
        $insertVisit->bindValue(':mail', $mail, PDO::PARAM_STR);
        $insertVisit->bindValue(':formaActu', $formaActu, PDO::PARAM_STR);
        $insertVisit->bindValue(':etabliOrig', $etabliOrig, PDO::PARAM_STR);
        $insertVisit->execute();
        $search = "INSERT INTO formulaire(postulAutEtaVisit,intereVisit,codeVisit,codeForma)VALUES(:postulAutreEta, :intereForma, :codeVisit, :codeForma);";
        $codeVisit = $this->Model->recuperationCodeVisit($nom,$prenom,$etabliOrig,$formaActu);
        $insertFormulaire = $this->db->conn_id->prepare($search);
        $insertFormulaire->bindValue(':postulAutreEta', $postulAutreEta, PDO::PARAM_INT);
        $insertFormulaire->bindValue(':intereForma', $intereForma, PDO::PARAM_INT);
        $insertFormulaire->bindValue(':codeVisit', $codeVisit, PDO::PARAM_STR);
        $insertFormulaire->bindValue(':codeForma', $codeForma, PDO::PARAM_STR);
        $insertFormulaire->execute();
    }
	
	function statistiquesIntituleForma($codeFormation)
	{
		$intituleForma = $this->db->conn_id->prepare("SELECT etablissement.intituleForma FROM etablissement WHERE etablissement.codeForma=:codeFormation;");
		$intituleForma->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
		$intituleForma->execute();
		$intituleForma = $intituleForma->fetch();
		$intituleForma = $intituleForma['intituleForma'];
		
		return($intituleForma);
	}
	
	function statistiqueNbrVisiteur($codeFormation)
	{
		$nbrVisiteur = $this->db->conn_id->prepare("SELECT COUNT(*) AS 'Nombre de visiteur pour cette formation' FROM formulaire WHERE formulaire.codeForma=:codeFormation;");
		$nbrVisiteur->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
		$nbrVisiteur->execute();
		$nbrVisiteur = $nbrVisiteur->fetch();
		$nbrVisiteur = $nbrVisiteur['Nombre de visiteur pour cette formation'];
		
		return($nbrVisiteur);
	}
	
	function statistiqueNbrVisiteurIntere($codeFormation)
	{
		$nbrVisiteurIntere = $this->db->conn_id->prepare("SELECT COUNT(*) AS 'Nombre de visiteur interesse par cette formation' FROM formulaire WHERE formulaire.codeForma=:codeFormation AND formulaire.intereVisit = 1;");
		$nbrVisiteurIntere->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
		$nbrVisiteurIntere->execute();
		$nbrVisiteurIntere = $nbrVisiteurIntere->fetch();
		$nbrVisiteurIntere = $nbrVisiteurIntere['Nombre de visiteur interesse par cette formation'];
		
		return($nbrVisiteurIntere);
	}
	
	function statistiqueNbrVisiteurIntereDepoAutreDossier($codeFormation)
	{	
		$nbrVisiteurIntereDepoAutreDossier = $this->db->conn_id->prepare("SELECT COUNT(*) AS 'nbrVisiteurIntereDepoAutreDossier' FROM formulaire  WHERE formulaire.codeForma=:codeFormation AND formulaire.intereVisit = 1 AND formulaire.postulAutEtaVisit = 1;");
		$nbrVisiteurIntereDepoAutreDossier->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
		$nbrVisiteurIntereDepoAutreDossier->execute();
		$nbrVisiteurIntereDepoAutreDossier = $nbrVisiteurIntereDepoAutreDossier->fetch();
		$nbrVisiteurIntereDepoAutreDossier = $nbrVisiteurIntereDepoAutreDossier['nbrVisiteurIntereDepoAutreDossier'];
		
		return($nbrVisiteurIntereDepoAutreDossier);
	}
	
	function visitHeure($codeFormation)
	{
		$visitHeure = $this->db->conn_id->prepare("SELECT COUNT(formulaire.codeVisit) AS 'Nombre de visiteur', HOUR(formulaire.datePass) AS 'Heure' FROM formulaire WHERE formulaire.codeForma=:codeFormation GROUP BY HOUR(formulaire.datePass) ORDER BY HOUR(formulaire.datePass);");
		$visitHeure->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
		$visitHeure->execute();
		$visitHeure = $visitHeure->fetchall();
		
		return($visitHeure);
	}
	
	function infoVisit($codeFormation)
	{
		$infoVisit = $this->db->conn_id->prepare("SELECT * FROM visiteur, formulaire WHERE visiteur.codeVisit=formulaire.codeVisit AND formulaire.codeForma=:codeFormation;");
		$infoVisit->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
		$infoVisit->execute();
		$infoVisit = $infoVisit->fetchall();
		
		return($infoVisit);
	}
	
}
?>