<?php
function connexionBdd()
{
	//Permet la connexion à MySQL et affiche un message si il y a une erreur de connexion
	$serveurMySQL="localhost";
	$loginMySQL="jpomunsch";
	$motDePasseMySQL="jpoz985";
	$baseDeDonneesMySQL="jpomunsch";
	
	$connexionBdd = new mysqli($serveurMySQL,$loginMySQL,$motDePasseMySQL,$baseDeDonneesMySQL);
	if ($connexionBdd->connect_errno)
	{
        echo "Échec lors de la connexion à MySQL : (" . $connexionBdd->connect_errno . ") " . $connexionBdd->connect_error;
    }
    else
	{
		return($connexionBdd);
    }
	return($connexionBdd);
}

function authentificationPageDeConnexion($lienBdd,$codeFormation,$mdp)
{
	//Permet d'authentifier l'utilisateur avec le mot de passe et le code forma
	$testCodeFormation = $lienBdd->query("SELECT etablissement.codeForma FROM etablissement WHERE etablissement.codeForma='"."$codeFormation"."'LIMIT 1;");
	foreach ($testCodeFormation as $ligne)
	{
		$testCodeFormation = $ligne['codeForma'];
	}
	if ($testCodeFormation == $codeFormation)
	{
		$comparMdp = $lienBdd->query("SELECT etablissement.motDePasseForma FROM etablissement WHERE etablissement.codeForma='"."$codeFormation"."'LIMIT 1;");
		foreach ($comparMdp as $ligne)
		{
			$comparMdp = $ligne['motDePasseForma'];
		}
		if ($comparMdp == $mdp)
		{
			definitionVariableSessionCodeForma($codeFormation);
			//Redirection vers la page formulaire si le mot de passe correspond à l'indentifiant
			header("Location:../Formulaire/formulaire.html");
		}
		else
		{
			//J'utilise include pour me permettre d'inclure le echo sinon le message s'affiche sur une page blanche
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
	$testCodeFormation = $lienBdd->query("SELECT etablissement.codeForma FROM etablissement WHERE etablissement.codeForma='"."$codeFormation"."'LIMIT 1;");
	foreach ($testCodeFormation as $ligne)
	{
		$testCodeFormation = $ligne['codeForma'];
	}
	if ($testCodeFormation == $codeFormation)
	{
		$comparMdpStat = $lienBdd->query("SELECT etablissement.motDePasseStatForma FROM etablissement WHERE etablissement.codeForma='"."$codeFormation"."'LIMIT 1;");
		foreach ($comparMdpStat as $ligne)
		{
			$comparMdpStat = $ligne['motDePasseStatForma'];
		}
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
	$codeVisit = $lienBdd->query("SELECT visiteur.codeVisit FROM visiteur WHERE visiteur.nomVisit='".$nom."' AND visiteur.prenomVisit='".$prenom."' AND visiteur.etabliOrigVisit='".$etabliOrig."' AND visiteur.formaActuVisit='".$formaActu."' ORDER BY visiteur.codeVisit DESC LIMIT 1;");
	foreach ($codeVisit as $ligne)
	{
		$codeVisite = $ligne['codeVisit'];
	}
	return($codeVisite);
}

//Permet d'inserer la saisie dans la Bdd puis redirige vers la page saisie reussie
function traitementFormulaire($codeForma,$lienBdd,$nom,$prenom,$etabliOrig,$formaActu,$intereForma,$postulAutreEta,$numRue,$nomRue,$nomVille,$codePost,$mail,$numTel)
{
	$lienBdd->query("INSERT INTO visiteur(nomVisit,prenomVisit,locRueVisit,rueVisit,villeVisit,cpVisit,telVisit,mailVisit,formaActuVisit,etabliOrigVisit)VALUES('".$nom."','".$prenom."','".$numRue."','".$nomRue."','".$nomVille."','".$codePost."','".$numTel."','".$mail."','".$formaActu."','".$etabliOrig."');");
	$codeVisit = recuperationCodeVisit($lienBdd,$nom,$prenom,$etabliOrig,$formaActu);
	$lienBdd->query("INSERT INTO formulaire(postulAutEtaVisit,intereVisit,codeVisit,codeForma)VALUES('".$postulAutreEta."', '".$intereForma."', ".$codeVisit.", '".$codeForma."');");
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
	$lienBdd->close();
}
?>