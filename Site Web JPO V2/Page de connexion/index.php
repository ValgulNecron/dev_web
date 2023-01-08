<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Connexion Formulaire JPO 2022</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
	<div>
		<img src="logoLycee.jpg">
		<form method="POST" action="pageDeConnexion.php">
			<label>Code formation :</label>
			<select name="codeFormation">
				<option value="">Selectionnez un élément</option>
				<?php
					//Permet d'inclure le fichier servant de bibliotheque pour la connexion à la Bdd
					include_once("../bibliotheque.php");
					$lienBdd = connexionBdd();
					$codeForma = listeDeroulante($lienBdd);
					
					foreach ($codeForma as $ligne)
					{
						?>
						<option value="<?php echo $ligne['codeForma']; ?>"><?php echo $ligne['codeForma']; ?></option>
						<?php
					}
					fermetureBDD($lienBdd);
				?>
			</select>
			<br>
			<label>Mot de passe :</label>
			<input type="password" name="mdp" required>
			<br>
			<a href="../Page statistiques/connexionPageStatistiques.php">Connexion à la page des statistiques</a>
			<input type="submit" value="Envoyer" class="bouton"/>
		</form>
	</div>
</body>
</html>