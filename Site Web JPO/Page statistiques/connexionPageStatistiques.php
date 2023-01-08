<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Connexion à la page des statistiques</title>
</head>
<body>
	<img id="headerLogo" src="logoNomLycee.png">
	<form method="POST" action="statistiques.php">
		<?php
			include_once("../bibliotheque.php");
			$lienBdd = connexionBdd();
		?>
		<label>Code formation :</label>
		<select name="CodeFormation">
			<option value="">Selectionnez un élément</option>
		<?php
			$codeForma = $lienBdd->query("SELECT etablissement.codeForma FROM etablissement;");
			foreach ($codeForma as $ligne)
			{
		?>
			<option value="<?php echo $ligne['codeForma']; ?>"><?php echo $ligne['codeForma']; ?></option>
			<?php
			}
			fermetureBDD($lienBdd);
		?>
		</select>
		<label>Mot de passe :</label>
		<input type="password" name="mdpStat" required>
		<input type="submit" value="Envoyer"/>
	</form>
</body>
</html>