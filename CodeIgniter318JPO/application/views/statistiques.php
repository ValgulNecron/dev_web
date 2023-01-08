<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="<?php echo base_url()."css/statistiques.css";?>">
  <title>Statistiques des formations</title>
</head>
<body>
	<h1>Statistiques pour la formation <?php echo $statistiquesIntituleForma  ?></h1>
	<div>
		<table>
			<tr>
				<td>Nombre de visiteurs pour cette formation</td>
				<td><?php echo $statistiqueNbrVisiteur; ?></td>
			</tr>
			<tr>
				<td>Nombre de visiteurs intéressés par cette formation</td>
				<td><?php echo $statistiqueNbrVisiteurIntere; ?></td>
			</tr>
			<tr>
				<td>Nombre de visiteurs intéressés qui pensent également déposer un dossier dans un autre établissement</td>
				<td><?php echo $statistiqueNbrVisiteurIntereDepoAutreDossier; ?></td>
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
	<a href="<?php echo site_url('Welcome/contenu/connexionPageStatistiques')?>">Retour à la page de connexion des statistiques</a>
</body>
</html>

