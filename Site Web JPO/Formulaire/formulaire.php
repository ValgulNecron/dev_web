<?php
include_once("../bibliotheque.php");

//Valeur obligatoire pour le formulaire
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$etabliOrig = $_POST['etabliOrig'];
$formaActu = $_POST['formaActu'];
$intereForma = $_POST['intereForma'];
$postulAutreEta = $_POST['postulAutreEta'];

//Valeur additionnels pour le formulaire
$numRue = $_POST['numRue'];
$nomRue = $_POST['nomRue'];
$nomVille = $_POST['nomVille'];
$codePost = $_POST['codePost'];
$mail = $_POST['mail'];
$numTel = $_POST['numTel'];

$codeForma = recuperationVariableSessionCodeForma();
$lienBdd = connexionBdd();
traitementFormulaire($codeForma,$lienBdd,$nom,$prenom,$etabliOrig,$formaActu,$intereForma,$postulAutreEta,$numRue,$nomRue,$nomVille,$codePost,$mail,$numTel);
fermetureBDD($lienBdd);
?>