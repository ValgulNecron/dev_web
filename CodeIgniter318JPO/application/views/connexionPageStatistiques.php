<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Connexion à la page des statistiques</title>
</head>
<body>
	<img id="headerLogo" src="<?php echo base_url().'img/logoNomLycee.png';?>">
	<?php echo form_open('Welcome/statistiques'); ?>
		<label>Code formation :</label>
		<select name="CodeFormation">
			<option value="">Selectionnez un élément</option>
		<?php
			foreach ($codeFormation as $ligne)
			{
		?>
			<option value="<?php echo $ligne['codeForma']; ?>"><?php echo $ligne['codeForma']; ?></option>
			<?php
			}
		?>
		</select>
		<label>Mot de passe :</label>
		<input type="password" name="mdpStat" required>
		<input type="submit" value="Envoyer"/>
	<?php echo form_close(); ?>
</body>
</html>