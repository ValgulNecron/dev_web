<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Connexion Formulaire JPO 2022</title>
  <link rel="stylesheet" href="<?php echo base_url().'css/pageDeConnexion.css';?>"/>
</head>
<body>
	<div>
		<img src="<?php echo base_url().'img/logoLycee.jpg';?>">
		<?php echo form_open('Welcome/pageDeConnexion'); ?>
            <label>Code formation :</label>
            <select name="codeFormation">
                <option value="erreur">Selectionnez un élément</option>
                <?php
                    foreach ($codeForma as $ligne)
                    {
                        ?>
                        <option value="<?php echo $ligne['codeForma']; ?>"><?php echo $ligne['codeForma']; ?></option>
                        <?php
                    }
                ?>
            </select>
            <br>
            <a href="<?php echo site_url('Welcome/contenu/connexionPageStatistiques') ?>">Connexion à la page des statistiques</a>
            <input type="submit" value="Envoyer" class="bouton"/>
        <?php echo form_close(); ?>
	</div>
</body>
</html>