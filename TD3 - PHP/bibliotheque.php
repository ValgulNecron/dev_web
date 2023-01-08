<?php
function conversionInches($valeurUnit)
{
	$cm = $valeurUnit * 2.54;
	$m = $valeurUnit / 39.37;
	$km = $valeurUnit / 39370;
	affichage($cm,$m,$km);
}
function conversionFeet($valeurUnit)
{
	$cm = $valeurUnit * 30.48;
	$m = $valeurUnit / 3.281;
	$km = $valeurUnit / 3281;
	affichage($cm,$m,$km);
}
function conversionYards($valeurUnit)
{
	$cm = $valeurUnit * 91.44;
	$m = $valeurUnit / 1.094;
	$km = $valeurUnit / 1094;
	affichage($cm,$m,$km);
}
function conversionMiles($valeurUnit)
{
	$cm = $valeurUnit * 160934;
	$m = $valeurUnit * 1609;
	$km = $valeurUnit * 1.609;
	affichage($cm,$m,$km);
}
function conversionNauticalMiles($valeurUnit)
{
	$cm = $valeurUnit * 185200;
	$m = $valeurUnit * 1852;
	$km = $valeurUnit * 1.852;
	affichage($cm,$m,$km);
}
function conversionLightYears($valeurUnit)
{
	$cm = $valeurUnit * 9.461e+17;
	$m = $valeurUnit * 9.461e+15;
	$km = $valeurUnit * 9.461e+12;
	affichage($cm,$m,$km);
}
function affichage($cm,$m,$km)
{
	include_once("index.php")
?>
	<table>
		<thead>
			<tr>
				<th colspan="2">Resultat de la conversion</th>
			</tr>
		</thead>
		<tr>
            <td>En centimètres : </td>
            <td><?php echo $cm; ?></td>
        </tr>
		<tr>
            <td>En mètres : </td>
            <td><?php echo $m; ?></td>
        </tr>
		<tr>
            <td>En kilomètres : </td>
            <td><?php echo $km; ?></td>
        </tr>
	</table>
<?php
}
?>