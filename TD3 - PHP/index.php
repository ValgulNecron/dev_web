<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Titre de la page</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
	<h1>Convertisseur d'unité</h1>
	<form method="POST" action="traitement.php">
		<label>Valeur :</label>
		<input type="text" name="valeurUnit" required>
		<select name="unit">
			<option>Inches</option>
			<option>Feet</option>
			<option>Yards</option>
			<option>Miles</option>
			<option>Nautical Miles</option>
			<option>Light-years (LY)</option>
		</select>
		<button class="bouton" type="submit">Envoyer</button>
	</form>
</body>
</html>