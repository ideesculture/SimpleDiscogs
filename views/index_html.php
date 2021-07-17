<?php
$va_var 	= $this->getVar('var');
$va_servers = $this->getVar('servers');
?>

<h1>Import Discogs</h1>

<form action="<?php print __CA_URL_ROOT__."/index.php/SimpleDiscogs/SimpleDiscogs/Search"; ?>" method="post">
	<h2>Recherche</h2>
	<p>La recherche porte sur <b><span id='searchtarget'>...</span></b></p>
	<input type="text" style="width:100%;" name="search">
	<button type="submit">Chercher</button>
</form>

