<?php
function connexionBdd()
{
	//Permet la connexion à MySQL et affiche un message si il y a une erreur de connexion
	// $serveurMySQL="localhost";
	// $loginMySQL="jpomunsch";
	// $motDePasseMySQL="jpoz985";
	// $baseDeDonneesMySQL="jpomunsch";
	
	try {
			$db = new PDO('mysql:host=localhost;dbname=jpomunsch;charset=utf8','jpomunsch','jpoz985'); // Connexion BDD MySQL
			return $db;
		}
		catch(Exception $e)
		{
			die ('Erreur :'. $e->getMessage()); // Va mettre fin au programme et afficher l'erreur
		}
}

function listeDeroulante($lienBdd)
{
	$codeForma = $lienBdd->prepare("SELECT etablissement.codeForma FROM etablissement;");
	$codeForma->execute();
	return ($codeForma->fetchall());
}

function authentificationPageDeConnexion($lienBdd,$codeFormation,$mdp)
{
	$testCodeFormation = $lienBdd->prepare("SELECT etablissement.codeForma FROM etablissement WHERE etablissement.codeForma=:codeFormation LIMIT 1;");
	$testCodeFormation->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
	$testCodeFormation->execute();
	$testCodeFormation = $testCodeFormation->fetch();
	$testCodeFormation = $testCodeFormation['codeForma'];
	if ($testCodeFormation == $codeFormation)
	{
		$comparMdp = $lienBdd->prepare("SELECT etablissement.motDePasseForma FROM etablissement WHERE etablissement.codeForma=:codeFormation LIMIT 1;");
		$comparMdp->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
		$comparMdp->execute();
		$comparMdp = $comparMdp->fetch();
		$comparMdp = $comparMdp['motDePasseForma'];
		if ($comparMdp == $mdp)
		{
			definitionVariableSessionCodeForma($codeFormation);
			header("Location:../Formulaire/formulaire.html");
		}
		else
		{
			include_once("index.php");
			echo "<p>Mot de passe incorrect</p>";
		}
	}
	else
	{
		include_once("index.php");
		echo "<p>Identifiant incorrect</p>";
	}
}

function authentificationPageDeStat($lienBdd,$codeFormation,$mdpStat)
{
	$testCodeFormation = $lienBdd->prepare("SELECT etablissement.codeForma FROM etablissement WHERE etablissement.codeForma=:codeFormation LIMIT 1;");
	$testCodeFormation->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
	$testCodeFormation->execute();
	$testCodeFormation = $testCodeFormation->fetch();
	$testCodeFormation = $testCodeFormation['codeForma'];
	if ($testCodeFormation == $codeFormation)
	{
		$comparMdpStat = $lienBdd->prepare("SELECT etablissement.motDePasseStatForma FROM etablissement WHERE etablissement.codeForma=:codeFormation LIMIT 1;");
		$comparMdpStat->bindValue(':codeFormation', $codeFormation, PDO::PARAM_STR);
		$comparMdpStat->execute();
		$comparMdpStat = $comparMdpStat->fetch();
		$comparMdpStat = $comparMdp['motDePasseStatForma'];
		if ($comparMdpStat == $mdpStat)
		{
			return(true);		
		}
		else
		{
			include_once("/Page statistiques/connexionPageStatistiques.php");
			echo "<p>Mot de passe incorrect</p>";
		}
	}
	else
	{
		include_once("/Page statistiques/connexionPageStatistiques.php");
		echo "<p>Identifiant incorrect</p>";
	}
}

//Permet de recuperer le code du visiteur lier à la saisie en cours pour la fonction traitement formulaire
function recuperationCodeVisit($lienBdd,$nom,$prenom,$etabliOrig,$formaActu)
{
	$codeVisit = $lienBdd->prepare("SELECT visiteur.codeVisit FROM visiteur WHERE visiteur.nomVisit=:nom AND visiteur.prenomVisit=:prenom AND visiteur.etabliOrigVisit=:etabliOrig AND visiteur.formaActuVisit=:formaActu ORDER BY visiteur.codeVisit DESC LIMIT 1;");
	$codeVisit->bindValue(':nom', $nom, PDO::PARAM_STR);
	$codeVisit->bindValue(':prenom', $prenom, PDO::PARAM_STR);
	$codeVisit->bindValue(':etabliOrig', $etabliOrig, PDO::PARAM_STR);
	$codeVisit->bindValue(':formaActu', $formaActu, PDO::PARAM_STR);
	$codeVisit->execute();
	$codeVisit = $codeVisit->fetch();
	$codeVisit = $codeVisit['codeVisit'];
	return($codeVisite);
}

//Permet d'inserer la saisie dans la Bdd puis redirige vers la page saisie reussie
function traitementFormulaire($codeForma,$lienBdd,$nom,$prenom,$etabliOrig,$formaActu,$intereForma,$postulAutreEta,$numRue,$nomRue,$nomVille,$codePost,$mail,$numTel)
{
	$insertVisit = $lienBdd->prepare("INSERT INTO visiteur(nomVisit,prenomVisit,locRueVisit,rueVisit,villeVisit,cpVisit,telVisit,mailVisit,formaActuVisit,etabliOrigVisit)VALUES(:nom,:prenom,:numRue,:nomRue,:nomVille,:codePost,:numTel,:mail,:formaActu,:etabliOrig);");
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
	$codeVisit = recuperationCodeVisit($lienBdd,$nom,$prenom,$etabliOrig,$formaActu);
	$insertFormulaire = $lienBdd->prepare("INSERT INTO formulaire(postulAutEtaVisit,intereVisit,codeVisit,codeForma)VALUES(:postulAutreEta, :intereForma, :codeVisit, :codeForma);");
	$insertFormulaire->bindValue(':postulAutreEta', $postulAutreEta, PDO::PARAM_INT);
	$insertFormulaire->bindValue(':intereForma', $intereForma, PDO::PARAM_INT);
	$insertFormulaire->bindValue(':codeVisit', $codeVisit, PDO::PARAM_STR);
	$insertFormulaire->bindValue(':codeForma', $codeForma, PDO::PARAM_STR);
	$insertFormulaire->execute();
	header("Location:../Saisie reussie/saisieReussie.html");
}

//Permet d'enregistrer codeFormation dans la variable de session "sessionCodeForma"
/*Une variable de session en PHP est une variable stockée sur le serveur. 
C'est une variable temporaire qui a une durée limitée et est détruite à la déconnexion (fermeture du navigateur). 
Les variables de session sont partagées par toutes les pages PHP d'une session.*/
function definitionVariableSessionCodeForma($codeFormation)
{
	session_start();
	$_SESSION["sessionCodeForma"]=$codeFormation;
}

function recuperationVariableSessionCodeForma()
{
	session_start();
	return($_SESSION["sessionCodeForma"]);
}

//Permet de fermer la connexion à la Bdd
function fermetureBDD($lienBdd)
{
	$lienBdd = null;
}
?>