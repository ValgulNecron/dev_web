<?php
$moyenne=15; 
switch ($moyenne)
	{
	case ($moyenne < 8):
	echo "Recalé";
	break;
	case ($moyenne >= 8 AND $moyenne < 10):
	echo "Rattrapage";
	break;
	default:echo "Reçu";
	}
?>