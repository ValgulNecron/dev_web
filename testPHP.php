<?php
// Partie 0 Mettre en place de données test
// Partie 1 compléter les procédures et fonctions et tester la connexion et l'affichage
// Partie 2 Mettre en place ces fonctions / procédures pour le projet jpo


// Bonnes pratiques : programmation modulaire
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$serveurMySQL="localhost";
$loginMySQL="root";
$motDePasseMySQL="root";
$baseDeDonneesMySQL="test";

/*
// Autre annexe 
$connexionBdd=new mysqli($serveur,$login,$motPasse,$Bdd) // Cette variable doit être testée
// pour savoir si la connexion s'est bien passée

$tableauResulat=$connexionBdd->query("SELECT num from demo;")

$connexionBdd->close();

foreach($resultat as $ligne)
{
	echo $ligne['num'];
}

*/

function connexionBDD($serveurMySQL,$loginMySQL,$motDePasseMySQL,$baseDeDonneesMySQL)
{
	$connexionBdd = new mysqli($serveurMySQL,$loginMySQL,$motDePasseMySQL,$baseDeDonneesMySQL);
	// test connexion
	if ($connexionBdd -> connect_errno) 
		{
		echo "Problème de connexion à la base de données: " . $mysqli -> connect_error;
		}
	else
		{
		return($connexionBdd);
		}
}

function executeRequete($lienBdd)
{
	// on va exécuter la requête qui affiche toutes les données de la table démo
	// et on va renvoyer le tableau contenant le résultat de la requête exécutée
	$tableauResultat=$lienBdd->query("SELECT * from demo;");
	return($tableauResultat);
}

function fermetureBDD($uneBaseDeDonnees)
{
	// On va détruire ou fermer la connexion à la base de données
	$uneBaseDeDonnees->close();
}

function procedureAfficheResultat($tableau)
{
	// On afficher chaque ligne issue de la requête
	foreach($tableau as $ligne)
	{
	echo '<p>'.$ligne['num'].$ligne['nom'].$ligne['age'].'</p>';
	}
}

 
// Pour les appels
$lienBdd=connexionBDD($serveurMySQL,$loginMySQL,$motDePasseMySQL,$baseDeDonneesMySQL);
$tableau=executeRequete($lienBdd);
procedureAfficheResultat($tableau);
fermetureBDD($lienBdd);
?>