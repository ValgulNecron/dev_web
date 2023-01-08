<?php
include_once("../bibliotheque.php");
$codeFormation = $_POST['CodeFormation']; 
$mdpStat = $_POST['mdpStat'];

$lienBdd = connexionBdd();
$authentificationPageDeStat = authentificationPageDeStat($lienBdd,$codeFormation,$mdpStat);
if ($authentificationPageDeStat == true)
{
	$intituleForma = $lienBdd->query("SELECT etablissement.intituleForma FROM etablissement WHERE etablissement.codeForma='".$codeFormation."';");
	foreach ($intituleForma as $ligne)
	{
		$intituleForma = $ligne['intituleForma'];
	}
	$nbrVisiteur = $lienBdd->query("SELECT COUNT(*) AS 'Nombre de visiteur pour cette formation' FROM formulaire WHERE formulaire.codeForma='".$codeFormation."';");
	foreach ($nbrVisiteur as $ligne)
	{
		$nbrVisiteur = $ligne['Nombre de visiteur pour cette formation'];
	}
	$nbrVisiteurIntere = $lienBdd->query("SELECT COUNT(*) AS 'Nombre de visiteur interesse par cette formation' FROM formulaire WHERE formulaire.codeForma='".$codeFormation."' AND formulaire.intereVisit = 1;");
	foreach ($nbrVisiteurIntere as $ligne)
	{
		$nbrVisiteurIntere = $ligne['Nombre de visiteur interesse par cette formation'];
	}
	$nbrVisiteurIntereDepoAutreDossier = $lienBdd->query("SELECT COUNT(*) AS 'nbrVisiteurIntereDepoAutreDossier' FROM formulaire  WHERE formulaire.codeForma='".$codeFormation."' AND formulaire.intereVisit = 1 AND formulaire.postulAutEtaVisit = 1;");
	foreach ($nbrVisiteurIntereDepoAutreDossier as $ligne)
	{
		$nbrVisiteurIntereDepoAutreDossier = $ligne['nbrVisiteurIntereDepoAutreDossier'];
	}
	?>
	<html lang="fr">
	<head>
	  <meta charset="utf-8">
	  <link rel="stylesheet" href="style.css">
	  <title>Statistiques des formations</title>
	</head>
	<body>
		<h1>Statistiques pour la formation <?php echo $intituleForma ?></h1>
		<div>
			<table>
				<tr>
					<td>Nombre de visiteurs pour cette formation</td>
					<td><?php echo $nbrVisiteur; ?></td>
				</tr>
				<tr>
					<td>Nombre de visiteurs intéressés par cette formation</td>
					<td><?php echo $nbrVisiteurIntere; ?></td>
				</tr>
				<tr>
					<td>Nombre de visiteurs intéressés qui pensent également déposer un dossier dans un autre établissement</td>
					<td><?php echo $nbrVisiteurIntereDepoAutreDossier; ?></td>
				</tr>
			</table>
		</div>
		<div>
			<table>
				<tr>
					<th>Heure</th>
					<th>Nombre de visiteurs</th>
				</tr>
			<?php
				$visitHeure = $lienBdd->query("SELECT COUNT(formulaire.codeVisit) AS 'Nombre de visiteur', HOUR(formulaire.datePass) AS 'Heure' FROM formulaire WHERE formulaire.codeForma='".$codeFormation."' GROUP BY HOUR(formulaire.datePass) ORDER BY HOUR(formulaire.datePass);");
				foreach ($visitHeure as $ligne)
				{
			?>
					<tr>
						<td><?php echo $ligne['Heure']; ?>H</td>
						<td><?php echo $ligne['Nombre de visiteur']; ?></td>
					</tr>
			<?php
				}
			?>
			</table>
		</div>
		<div>
			<table>
				<tr>
					<th>Nom</th>
					<th>Prénom</th>
					<th>Numéro de rue</th>
					<th>Rue</th>
					<th>Ville</th>
					<th>Code postale</th>
					<th>Téléphone</th>
					<th>Adresse mail</th>
					<th>Formation actuelle</th>
					<th>Etablissement d'origine</th>
					<th>Souhaite postuler dans un autre établissement</th>
					<th>Interet pour la formation</th>
				</tr>
			<?php
				$infoVisit = $lienBdd->query("SELECT * FROM visiteur, formulaire WHERE visiteur.codeVisit=formulaire.codeVisit AND formulaire.codeForma='".$codeFormation."';");
				foreach ($infoVisit as $ligne)
				{
			?>
					<tr>
						<td><?php echo $ligne['nomVisit']; ?></td>
						<td><?php echo $ligne['prenomVisit']; ?></td>
						<td><?php echo $ligne['locRueVisit']; ?></td>
						<td><?php echo $ligne['rueVisit']; ?></td>
						<td><?php echo $ligne['villeVisit']; ?></td>
						<td><?php echo $ligne['cpVisit']; ?></td>
						<td><?php echo $ligne['telVisit']; ?></td>
						<td><?php echo $ligne['mailVisit']; ?></td>
						<td><?php echo $ligne['formaActuVisit']; ?></td>
						<td><?php echo $ligne['etabliOrigVisit']; ?></td>
						<td><?php echo $ligne['postulAutEtaVisit']; ?></td>
						<td><?php echo $ligne['intereVisit']; ?></td>
					</tr>
			<?php
				}
			?>
			</table>
		</div>
		<a href="connexionPageStatistiques.php">Retour à la page de connexion des statistiques</a>
	</body>
	</html>
	<?php
}
fermetureBDD($lienBdd);
?>
