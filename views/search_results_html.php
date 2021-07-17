<?php
$results = $this->getVar('results');
//var_dump($results);die();
$nb_results = sizeof($results);
print "<h1>".$nb_results." résultat".($nb_results>1 ? "s" : "")."</h1>";
?>

<form action="<?php print __CA_URL_ROOT__."/index.php/SimpleDiscogs/SimpleDiscogs/Import"; ?>" method="post">
	<h2>Liste des résultats</h2>
	<input type="hidden" name="nb_results" value="<?php print $nb_results;?>" />
	<input type="hidden" name="mapping" value="<?php print $vs_mapping;?>" /><br/>	
	<?php foreach($results as $key=>$result): 
		//var_dump($result);
		?>
	<div style="clear:both;">
		<input type="checkbox" name="file_<?php print $key; ?>" value="<?php print $result["resource_url"]; ?>"> 
		<img src="<?php print $result["thumb"]; ?>" style="height:60px;float:left;margin-right:6px;">
		<?php print $result["format"][0]." ".$result["title"]."<br/><small>".$result["label"][0]." ".$result["year"]; ?></small><br/>
		<a onClick="jQuery('#preview_<?php print $key; ?>').slideToggle();" style="color:gray;font-size:9px;cursor:pointer;">Afficher un aperçu</a>
	</div>
	<pre id='preview_<?php print $key; ?>' style="display:none;font-size:9px;border:1px solid gray;background:darkgray;color:white;padding:12px;"><?php print $previews[$key];?>
	</pre>
	<?php endforeach; ?>
	<div style="clear:both;margin-top:20px;">
	<button type="submit">Importer</button>
	</div>
</form>

<div style="height:120px;"></div>
