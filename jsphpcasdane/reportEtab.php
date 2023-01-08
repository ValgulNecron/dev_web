<?php
class reportEtab
{
    public function jsonReportUsages(){
        $sql = 'SELECT libelle AS usage, SUM(nbCnx) AS nb_cnx'.
            'FROM HistoUsageEtablissement INNER JOIN CategorieUsages'.
            'ON id = idCategorieUsage'.
            'WHERE rneEtablissement = :rne'.
            'AND dateObeservation BETWEEN :dateDebut and :dateFin'.
            'GROUP BY libelle';
        $res = DbCnx::getCnx->prepare($sql);

        $res->bindValue(':rne', $this->rne, PDO::PARAM_STR);
        $res->bindValue(':dateDebut', $this->dateDebObs, PDO::PARAM_STR);
        $res->bindValue(':dateFin', $this->dateFinObs, PDO::PARAM_STR);
        $res->execute();
        $line = $res->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($line);
    }

    public function jsonReportUsagesHorsVS(){
        $sql = 'SELECT libelle AS usage, SUM(nbCnx) AS nb_cnx'.
            'FROM HistoUsageEtablissement INNER JOIN CategorieUsages'.
            'ON id = idCategorieUsage'.
            'WHERE rneEtablissement = :rne'.
            'AND dateObeservation BETWEEN :dateDebut and :dateFin'.
            'AND idCategorieUsage <> 108'.
            'GROUP BY libelle';
        $res = DbCnx::getCnx->prepare($sql);

        $res->bindValue(':rne', $this->rne, PDO::PARAM_STR);
        $res->bindValue(':dateDebut', $this->dateDebObs, PDO::PARAM_STR);
        $res->bindValue(':dateFin', $this->dateFinObs, PDO::PARAM_STR);
        $res->execute();
        $line = $res->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($line);
    }
}