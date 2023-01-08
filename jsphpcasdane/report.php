<?php
?>
<html>
<head>
    <title>Report</title>
</head>
    <body>
        <div data-role="collapsible" data-theme="c" data-content-theme="d" data-corner="false" data-collapsed-icon="arrow-d">
            <h3>Vue d'ensemble des visites</h3>
            <div id="graphiques">
                <div id="graph_cnx_etab" style="max-width: 700px"></div>
                <div id="graph_cnx_usage" style="max-width: 700px"></div>
                <div id="graph_cnx_usage_horsVS" style="max-width: 700px"></div>
            </div>d
        </div>
        <script>
            $(document).redy(function(){
                rne = '<?php echo $rne ?>'
                libType = '<?php echo $libType ?>'
                dateDebut = '<?php echo $dateDebut ?>'
                dateFin = '<?php echo $dateFin ?>'
                cnx_etab(rne, libType, dateDebut, dateFin);
                cnx_usage(rne, dateDebut, dateFin);
                cnx_usage_horsVS(rne, dateDebut, dateFin);
            });
        </script>
    </body>
</html>
