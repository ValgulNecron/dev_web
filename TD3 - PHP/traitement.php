<?php
$valeurUnit = $_POST['valeurUnit'];
$unit = $_POST['unit'];
include_once("bibliotheque.php");
if ($unit == "Inches")
{
	conversionInches($valeurUnit);
}
elseif ($unit == "Feet")
{
	conversionFeet($valeurUnit);
}
elseif ($unit == "Yards")
{
	conversionYards($valeurUnit);
}
elseif ($unit == "Miles")
{
	conversionMiles($valeurUnit);
}
elseif ($unit == "Nautical Miles")
{
	conversionNauticalMiles($valeurUnit);
}
elseif ($unit == "Light-years (LY)")
{
	conversionLightYears($valeurUnit);
}
?>