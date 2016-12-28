<?php

use Asylamba\Classes\Exception\FormException;
use Asylamba\Classes\Library\Http\Response;

$factionNewsManager = $this->getContainer()->get('demeter.faction_news_manager');
$request = $this->getContainer()->get('app.request');

$id = $request->query->get('id');

if ($id !== FALSE) {	
	$S_FNM_1 = $factionNewsManager->getCurrentSession();
	$factionNewsManager->newSession();
	$factionNewsManager->load(array('id' => $id));

	if ($factionNewsManager->size() == 1) {
		$factionNewsManager->deleteById($id);

		$this->getContainer()->get('app.response')->flashbag->add('L\'annonce a bien été supprimée.', Response::FLASHBAG_SUCCESS);
	} else {
		throw new FormException('Cette annonce n\'existe pas.');
	}

	$factionNewsManager->changeSession($S_FNM_1);
} else {
	throw new FormException('Manque d\'information.');
}