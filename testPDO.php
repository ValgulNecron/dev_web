<?php
	function connexionBdd()
	{
		//Annexes : quelques commandes PDO
		// On capture les erreurs avec le boc try et le bloc catch permet d'afficher le message correspondant ? l'erreur si elle survient
		try {
			$db = new PDO('mysql:host=localhost;dbname=test;charset=utf8','root','root'); // Connexion BDD MySQL
			return $db;
		}
		catch(Exception $e){
			die ('Erreur :'. $e->getMessage()); // Va mettre fin au programme et afficher l'erreur
		}
	}

	function requete($bdd)
	{
		// Notation objet PDO
		// PREPARE LA REQUETE
		$sql = $bdd->prepare('SELECT COUNT(*) as nombre FROM demo WHERE nom=:nom AND age=:age;');

		// RELIER LES VALEURS A LA REQUETE
		$nom = "durant";
		$age = 30;
		//
		$sql->bindValue(':nom', $nom, PDO::PARAM_STR); // type ENTIER : PARAM_INT
		$sql->bindValue(':age', $age, PDO::PARAM_INT); // type CHAINE DE CARACTEREE


		$sql->execute(); // EXECUTE LA REQUETE
		$result = $sql->fetch(); // VA RECUPERER LE RESULTAT, fetchAll PEUT AUSSI ETRE UTILISE voir php.net 
		echo $result['nombre'];
	}
	
	$bdd = connexionBdd();
	requete($bdd);
	
	// EQUIVALENT EN MYSQLi
	// $query = sprintf("SELECT COUNT(*) as 'nombre' FROM test.demo WHERE nom='%s' AND mdp='%s';",
    // mysqli_real_escape_string($uneBaseDeDonnees, $nom),mysqli_real_escape_string($uneBaseDeDonnees,$motPasse));
	// $result = $uneBaseDeDonnees->query($query);
	// $resultat=mysqli_fetch_array($result);
	// mysqli_free_result($result);
	// $valeur=$resultat['nombre'];
?>