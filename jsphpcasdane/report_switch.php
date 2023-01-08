<?php
require_once 'reportetab.class.php';

$action = $_GET['action'];
$rne = $_GET['rne'];
$dateDebut = $_GET['dateDebut'];
$dateFin = $_GET['dateFin'];

$report = new reportEtab($rne, $dateDebut, $dateFin);
switch ($action){
    case 'EtabEvolCnx': echo  $report->jsonEvolCnx();break;
    case 'EtabReportUsages': echo $report->jsonReportUsages();break;
    case 'EtabReportUsagesHorsVS': echo $report->jsonReportUsagesHorsVS();break;
}