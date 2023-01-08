<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<h3>Plantes à massif</h3>
<table>
	<tr>
		<th>Référence</th>
		<th>Désignation</th>
		<th>Photo</th>
		<th>Prix</th>
	</tr>
	<?php
	foreach($plantesMassifs as $row)
	{
		echo "<tr>";
			echo "<td>".$row['pdtRef']."</td>";
			echo "<td>".$row['pdtDesignation']."</td>";
			echo "<td><img src='".base_url()."img/".$row['pdtImage']."'/></td>";
			echo "<td>".$row['pdtPrix']."</td>";
		echo "</tr>";
	}
	?>
</table>