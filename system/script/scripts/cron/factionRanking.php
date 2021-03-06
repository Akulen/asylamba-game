<?php
# daily cron
# call at x am. every day

use Asylamba\Classes\Library\Utils;
use Asylamba\Modules\Atlas\Model\FactionRanking;
use Asylamba\Modules\Athena\Model\CommercialRoute;

$factionRankingManager = $this->getContainer()->get('atlas.faction_ranking_manager');
$playerRankingManager = $this->getContainer()->get('atlas.player_ranking_manager');
$playerManager = $this->getContainer()->get('zeus.player_manager');
$colorManager = $this->getContainer()->get('demeter.color_manager');
$conversationManager = $this->getContainer()->get('hermes.conversation_manager');
$database = $this->getContainer()->get('database');

$S_FRM1 = $factionRankingManager->getCurrentSession();
$factionRankingManager->newSession();
$factionRankingManager->loadLastContext();

$S_PRM1 = $playerRankingManager->getCurrentSession();
$playerRankingManager->newSession();
$playerRankingManager->loadLastContext();

# create a new ranking
$qr = $database->prepare('INSERT INTO ranking(dRanking, player, faction) VALUES (?, 0, 1)');
$qr->execute(array(Utils::now()));

$rRanking = $database->lastInsertId();

echo 'Numéro du ranking : ' . $rRanking . '<br /><br />';

require_once ('fr_functions.php');

# load the factions (colors)
$inGameFactions = $colorManager->getInGameFactions();

# create an array with all the factions
$gameover = FALSE;
$list = [];
foreach ($inGameFactions as $faction) {
	$list[$faction->getId()] = array(
		'general' => 0, 
		'wealth' => 0, 
		'territorial' => 0,
		'points' => $faction->rankingPoints);
	if ($faction->isWinner == 1) {
		$gameover = TRUE;
	}
}

#-------------------------------- GENERAL RANKING --------------------------------#
# sum of general player ranking
# load all the players

for ($i = 0; $i < $playerRankingManager->size(); $i++) {
	$playerRank = $playerRankingManager->get($i);

	$player = $playerManager->get($playerRank->rPlayer);

	if (isset($player->rColor)) {
		$list[$player->rColor]['general'] += $playerRank->general;
	}
}

#-------------------------------- WEALTH RANKING ----------------------------------#
foreach ($inGameFactions as $faction) { 
	$color = $faction->id;
	$qr = $database->prepare('SELECT
		COUNT(cr.id) AS nb,
		SUM(cr.income) AS income
		FROM commercialRoute AS cr
		LEFT JOIN orbitalBase AS ob1
			ON cr.rOrbitalBase = ob1.rPlace
			LEFT JOIN player AS pl1
				ON ob1.rPlayer = pl1.id
		LEFT JOIN orbitalBase AS ob2
			ON cr.rOrbitalBaseLinked = ob2.rPlace
			LEFT JOIN player AS pl2
				ON ob2.rPlayer = pl2.id
		WHERE (pl1.rColor = ? OR pl2.rColor = ?) AND cr.statement = ?
	');
	# hint : en fait ça compte qu'une fois une route interfaction, mais chut
	$qr->execute([$color, $color, CommercialRoute::ACTIVE]);
	$aw = $qr->fetch();
	if ($aw['income'] == NULL) {
		$income = 0;
	} else {
		$income = $aw['income'];
	}
	$list[$color]['wealth'] = $income;
}

#-------------------------------- TERRITORIAL RANKING -----------------------------#

$sectorManager = $this->getContainer()->get('gaia.sector_manager');
$sectors = $sectorManager->getAll();
foreach ($sectors as $sector) {
	if ($sector->rColor != 0) {
		$list[$sector->rColor]['territorial'] += $sector->points;
	}
}

#---------------- COMPUTING -------------------#

# copy the arrays
$listG = $list;
$listW = $list;
$listT = $list;

# sort all the copies
uasort($listG, 'cmpFactionGeneral');
uasort($listW, 'cmpWealth');
uasort($listT, 'cmpTerritorial');

/*foreach ($list as $key => $value) {
	echo $key . ' => ' . $value['general'] . '<br/>';
}*/

# put the position in each array
$listG = setPositions($listG, 'general');
$listW = setPositions($listW, 'wealth');
$listT = setPositions($listT, 'territorial');

#-------------------------------- POINTS RANKING -----------------------------#

# faire ce classement uniquement après x jours de jeu
if (Utils::interval(SERVER_START_TIME, Utils::now(), 'h') > HOURS_BEFORE_START_OF_RANKING) {
	# points qu'on gagne en fonction de sa place dans le classement
	$pointsToEarn = [40, 30, 20, 10, 0, 0, 0, 0, 0, 0, 0];
	$coefG = 0.1; # 4 3 2 1 0 ...
	$coefW = 0.4; # 16 12 8 4 0 ...
	$coefT = 0.5; # 20 15 10 5 0 ...

	foreach ($inGameFactions as $faction) {
		$factionId = $faction->id;
		$additionalPoints = 0;

		# general
		$additionalPoints += intval($pointsToEarn[$listG[$factionId]['position'] - 1] * $coefG);

		# wealth
		$additionalPoints += intval($pointsToEarn[$listW[$factionId]['position'] - 1] * $coefW);

		# territorial
		$additionalPoints += intval($pointsToEarn[$listT[$factionId]['position'] - 1] * $coefT);

		$list[$factionId]['points'] += $additionalPoints;
	}
}


#---------------- LAST COMPUTING -------------------#

$listP = $list;
uasort($listP, 'cmpPoints');

$position = 1;
foreach ($listP as $key => $value) { $listP[$key]['position'] = $position++;}

#---------------- SAVING -------------------#

$rankings = [];

foreach ($list as $faction => $value) {
	$fr = new FactionRanking();
	$fr->rRanking = $rRanking;
	$fr->rFaction = $faction; 

	$firstRanking = true;
	for ($i = 0; $i < $factionRankingManager->size(); $i++) {
		if ($factionRankingManager->get($i)->rFaction == $faction) {
			$oldRanking = $factionRankingManager->get($i);
			$firstRanking = false;
			break;
		}
	}

	$fr->general = $listG[$faction]['general'];
	$fr->generalPosition = $listG[$faction]['position'];
	$fr->generalVariation = $firstRanking ? 0 : $oldRanking->generalPosition - $fr->generalPosition;

	$fr->wealth = $listW[$faction]['wealth'];
	$fr->wealthPosition = $listW[$faction]['position'];
	$fr->wealthVariation = $firstRanking ? 0 : $oldRanking->wealthPosition - $fr->wealthPosition;

	$fr->territorial = $listT[$faction]['territorial'];
	$fr->territorialPosition = $listT[$faction]['position'];
	$fr->territorialVariation = $firstRanking ? 0 : $oldRanking->territorialPosition - $fr->territorialPosition;

	if ($gameover) {
		$fr->points = $oldRanking->points;
		$fr->pointsPosition = $oldRanking->pointsPosition;
		$fr->pointsVariation = 0;
		$fr->newPoints = 0;
	} else {
		$fr->points = $listP[$faction]['points'];
		$fr->pointsPosition = $listP[$faction]['position'];
		$fr->pointsVariation = $firstRanking ? 0 : $oldRanking->pointsPosition - $fr->pointsPosition;
		$fr->newPoints = $firstRanking ? $fr->points : $fr->points - $oldRanking->points;
	}

	# update faction infos
	$f = $colorManager->get($faction);
	$f->rankingPoints = $listP[$faction]['points'];
	$f->points = $listG[$faction]['general'];
	$f->sectors = $listT[$faction]['territorial'];
	$colorManager->updateInfos($f);
	$this->getContainer()->get('entity_manager')->flush($f);

	$rankings[] = $fr;
	$factionRankingManager->add($fr);
}

$playerRankingManager->changeSession($S_PRM1);
$factionRankingManager->changeSession($S_FRM1);

if ($gameover == FALSE) {
	# check if a faction wins the game
	$winRanking = NULL;
	foreach ($rankings as $ranking) {
		if ($ranking->points >= POINTS_TO_WIN) {
			if ($winRanking !== NULL) {
				if ($winRanking->points < $ranking->points) {
					$winRanking = $ranking;
				}
			} else {
				$winRanking = $ranking;
			}
		}
	}
	if ($winRanking !== NULL) {
		# there is a winner !!!
		$faction = $colorManager->get($winRanking->rFaction);

		$faction->isWinner = Color::WIN;

		# envoyer un message de Jean-Mi
		$winnerName = ColorResource::getInfo($faction->id, 'officialName');
		$content = 'Salut,<br /><br />La victoire vient d\'être remportée par : <br /><strong>' . $winnerName . '</strong><br />';
		$content .= 'Cette faction a atteint les ' . POINTS_TO_WIN . ' points, la partie est donc terminée.<br /><br />Bravo et un grand merci à tous les participants !';

		$S_CVM1 = $conversationManager->getCurrentSession();
		$conversationManager->newSession();
		$conversationManager->load(
			['cu.rPlayer' => ID_JEANMI]
		);

		if ($conversationManager->size() == 1) {
			$conv = $conversationManager->get();

			$conv->messages++;
			$conv->dLastMessage = Utils::now();

			# désarchiver tous les users
			$users = $conv->players;
			foreach ($users as $user) {
				$user->convStatement = ConversationUser::CS_DISPLAY;
			}

			# création du message
			$message = new ConversationMessage();

			$message->rConversation = $conv->id;
			$message->rPlayer = ID_JEANMI;
			$message->type = ConversationMessage::TY_STD;
			$message->content = $content;
			$message->dCreation = Utils::now();
			$message->dLastModification = NULL;

			$this->getContainer()->get('hermes.conversation_message_manager')->add($message);
		} else {
			throw new ErrorException('La conversation n\'existe pas ou ne vous appartient pas.');
		}
		$conversationManager->changeSession($S_CVM1);
	}
}
$this->getContainer()->get('entity_manager')->flush();