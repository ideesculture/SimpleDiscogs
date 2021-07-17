<?php
/* ----------------------------------------------------------------------
 * plugins/statisticsViewer/controllers/StatisticsController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__.'/TaskQueue.php');
require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_MODELS_DIR__.'/ca_lists.php');
require_once(__CA_MODELS_DIR__.'/ca_objects.php');
require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
require_once(__CA_MODELS_DIR__.'/ca_locales.php');

require_once(__CA_BASE_DIR__."/vendor/pear/file_marc/File/MARC.php");

class SimpleDiscogsController extends ActionController {
	# -------------------------------------------------------
	protected $opo_config;		// plugin configuration file
	protected $pa_parameters;

	# -------------------------------------------------------
	# Constructor
	# -------------------------------------------------------

	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);

//		if (!$this->request->user->canDoAction('can_use_simple_z3950_plugin')) {
//			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
//			return;
//		}

		$this->opo_config = Configuration::load(__CA_APP_DIR__.'/plugins/SimpleDiscogs/conf/SimpleDiscogs.conf');
					
		// Note : simple import from conf
		// var_dump($this->opo_config->getAssoc('servers'));

	}

	# -------------------------------------------------------
	# Functions to render views
	# -------------------------------------------------------
	public function Index($type="") {
		// GET : $opa_stat=$this->request->getParameter('stat', pString);
		// SET : $this->view->setVar('queryparameters', $opa_queryparameters);
		if(!function_exists("curl_init")) {
			$this->view->setVar("message","L'extension PHP Curl n'est pas disponible sur ce serveur.");
			$this->render('error_html.php');
		} else {
			$consumer_key = $this->opo_config->get("consumer_key");
			$consumer_secret = $this->opo_config->get("consumer_secret");
			//$this->view->setVar("servers",$servers);
			$this->render('index_html.php');
		}
	}

	# -------------------------------------------------------
	public function Lot($type="") {
		// GET : $opa_stat=$this->request->getParameter('stat', pString);
		// SET : $this->view->setVar('queryparameters', $opa_queryparameters);
		$this->render('lot_html.php');
	}

	public function Search() {
		$ps_search=$this->request->getParameter('search', pString);
		if(!function_exists("curl_init")) {
			$this->view->setVar("message","L'extension PHP Curl n'est pas disponible sur ce serveur.");
			$this->render('error_html.php');
		} else {
			//Sample curl exec cmd
			var_dump($ps_search);
			$personaltoken = $this->opo_config->get("personaltoken");
			$cmd = 'curl -k "https://api.discogs.com/database/search?q='.$ps_search.'" -H "Authorization: Discogs token='.$personaltoken.'" --user-agent "SimpleDiscogsCollectiveAccess/0.1" > '.__CA_APP_DIR__.'/tmp/curl_discogs.json';
			exec($cmd);
			$answer = json_decode(file_get_contents(__CA_APP_DIR__.'/tmp/curl_discogs.json'), true);
			unlink(__CA_APP_DIR__.'/tmp/curl_discogs.json');
			//var_dump($answer);die();
			$this->view->setVar("results", $answer["results"]);
		

			$this->render('search_results_html.php');
		}

	}

	public function Import() {
		$vn_results = $this->request->getParameter('nb_results', pInteger);
		$files=[];
		$commands=[];
		$outputs=[];
		$uris=[];
		for($i=0;$i<$vn_results;$i++) {
			$uri = $this->request->getParameter('file_'.$i, pString);
			if(!$uri) continue;
			$uris[] = $uri;
			//var_dump($uri);die();
			$personaltoken = $this->opo_config->get("personaltoken");

			$cmd = 'curl -k "'.$uri.'" -H "Authorization: Discogs token='.$personaltoken.'" --user-agent "SimpleDiscogsCollectiveAccess/0.1" > '.__CA_APP_DIR__.'/tmp/curl_discogs.json';
			//var_dump($cmd);die();
			exec($cmd);
			$answer = json_decode(file_get_contents(__CA_APP_DIR__.'/tmp/curl_discogs.json'), true);
			unlink(__CA_APP_DIR__.'/tmp/curl_discogs.json');
			var_dump($answer);die();

			// Create the album
			$vt_album = new ca_objects();
			$vt_album->setMode(ACCESS_WRITE);
			$vt_album->set("idno", "");
			// album
			$vt_album->set("type_id", 878);
			$vt_album->set("access", 2);
			$vt_album->set("status", 1);
			$album_id = $vt_album->insert();
			$vt_album->addLabel(["name"=>$answer["title"]], 6, null, true);
			$vt_album->update();

			// Create the tracks
			foreach($answer["tracklist"] as $track) {
				$vt_track = new ca_objects();
				$vt_track->setMode(ACCESS_WRITE);
				$vt_track->set("idno", "");
				$vt_track->set("type_id", 878);
				$vt_track->set("access", 2);
				$vt_track->set("status", 1);
				$vt_track->set("parent_id", $album_id);
				$track_id = $vt_track->insert();
				$vt_track->addLabel(["name"=>$track["title"]], 6, null, true);
				$vt_track->update();
			}

			$this->view->setVar('message',  $answer["title"].' importé ('.$album_id.').');
		}
		if(!sizeof($uris)) $this->view->setVar('message', 'Aucun album coché à importer.');
		// ./bin/caUtils import-data -s /www/www.z3950.local/gestion/app/tmp/z3950_2-02-013706-2_1.pan -m z3950_import_marc -f marc -l . -d DEBUG
		//$this->view->setVar("outputs",$outputs);
		//$this->view->setVar("commands",$commands);
		$this->render('import_html.php');
	}
	# -------------------------------------------------------
}
?>
