function cnx_usages(rne, dateDebut, dateFin){
    var optionCnxUsages ={};
    optionCnxUsages.chart = {
        renderTo: 'graph_cnx_usages', backgroundColor: '#E6F8E0',
        borderColor: '#BDBDBD', borderWidth: 3};
    optionCnxUsages.title = {text: 'Répartition des visites par catégorie d\'usages', style: {fontWeight: 'bold}'}};
    $.getJSON('report_switch.php?action=EtabReportUsages&rne='+ rne + '&dateDebut=' + dateDebut + '&dateFin=' + dateFin, function(dataCnxUsages) {
        optionCnxUsages.data = {total: 0, stats : []}
        $.each(dataCnxUsages, function(i, e) {
            optionCnxUsages.data.total += e.nb;
            optionCnxUsages.data.stats.push({usage: e.usage, nb_cnx : e.nb_cnx});
        });
        chartCnxUsages = new Highcharts.Chart(optionCnxUsages);
    });
}

function cnx_usages_horsVS(rne, dateDebut, dateFin){
    var optionCnxUsages ={};
    optionCnxUsages.chart = {
        renderTo: 'graph_cnx_usage_horsVS', backgroundColor: '#E6F8E0',
        borderColor: '#BDBDBD', borderWidth: 3};
    optionCnxUsages.title = {text: 'Répartition des visites par catégorie d\'usages hors VS', style: {fontWeight: 'bold}'}};
    $.getJSON('report_switch.php?action=EtabReportUsagesHorsVS&rne='+ rne + '&dateDebut=' + dateDebut + '&dateFin=' + dateFin, function(dataCnxUsages) {
        optionCnxUsages.data = {total: 0, stats : []}
        $.each(dataCnxUsages, function(i, e) {
            optionCnxUsages.data.total += e.nb;
            optionCnxUsages.data.stats.push({usage: e.usage, nb_cnx : e.nb_cnx});
        });
        chartCnxUsages = new Highcharts.Chart(optionCnxUsages);
    });
}






































public void syncEtablissement()
{
    foreach ($line in $lesEtablissement)
    {
        if ($line.key == $bdd.rne || $line.key == $csv.rne)
        {
            audiencesbdd::insertEtablissement();
        }
    }
}