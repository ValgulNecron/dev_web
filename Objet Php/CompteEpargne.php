<?php
require_once 'CompteBancaire.php';
class CompteEpargne extends CompteBancaire
{
    private $tauxInteret;

    public function __construct($devise, $solde, $titulaire, $tauxInteret)
    {
        parent::__construct($devise, $solde, $titulaire);
        $this->tauxInteret = $tauxInteret;
    }

    public function getTauxInteret()
    {
        return $this->tauxInteret;
    }

    public function setTauxInteret($tauxInteret)
    {
        $this->tauxInteret = $tauxInteret;
    }

    public function __toString()
    {
        return parent::__toString() . " Taux d'interet : " . $this->tauxInteret . "%";
    }

    public function calculInteret()
    {
        return $this->getSolde() * $this->tauxInteret;
    }
}

$compte = new CompteBancaire("euros", 1000, "Dupont");
$compteEpargne = new CompteEpargne("euros", 1000, "Dupont", 0.5);
echo $compteEpargne;
echo $compteEpargne->calculInteret();
echo "_____________________";
echo $compte;
