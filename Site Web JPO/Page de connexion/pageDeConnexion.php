<?php
//Permet d'inclure le fichier servant de bibliotheque pour la connexion à la Bdd
include_once("../bibliotheque.php");

//Récuperation des valeurs des champs code forma et mot de passe
$codeFormation = $_POST['codeFormation']; 
$mdp = $_POST['mdp'];

//Appels des fonctions et procédures depuis le fichier bibliotheque
$lienBdd = connexionBdd();
//Permet de verifier si le mot de passe correcpond à l'indentifiant et donc autoriser la connexion
authentificationPageDeConnexion($lienBdd,$codeFormation,$mdp);
fermetureBDD($lienBdd);
?>