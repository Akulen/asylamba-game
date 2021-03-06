<?php

use Asylamba\Classes\Library\Utils;
use Asylamba\Modules\Demeter\Model\Law\Law;
use Asylamba\Modules\Demeter\Resource\ForumResources;
use Asylamba\Modules\Demeter\Resource\LawResources;
use Asylamba\Modules\Zeus\Model\Player;
use Asylamba\Modules\Demeter\Model\Color;

$session = $this->getContainer()->get('app.session');
$request = $this->getContainer()->get('app.request');
$response = $this->getContainer()->get('app.response');
$playerManager = $this->getContainer()->get('zeus.player_manager');
$colorManager = $this->getContainer()->get('demeter.color_manager');
$factionNewsManager = $this->getContainer()->get('demeter.faction_news_manager');
$forumMessageManager = $this->getContainer()->get('demeter.forum_message_manager');
$lawManager = $this->getContainer()->get('demeter.law_manager');
$voteManager = $this->getContainer()->get('demeter.vote_manager');
$electionManager = $this->getContainer()->get('demeter.election_manager');
$candidateManager = $this->getContainer()->get('demeter.candidate_manager');
$forumTopicManager = $this->getContainer()->get('demeter.forum_topic_manager');
$sectorManager = $this->getContainer()->get('gaia.sector_manager');

if (($faction = $colorManager->get($session->get('playerInfo')->get('color'))) === null) {
	$response->redirect('profil');
}

# background paralax
echo '<div id="background-paralax" class="profil"></div>';

# inclusion des elements
include 'factionElement/subnav.php';
include 'defaultElement/movers.php';

# contenu spécifique
echo '<div id="content">';
	include COMPONENT . 'publicity.php';

	if (!$request->query->has('view') OR $request->query->get('view') == 'overview') {
		if ($request->query->get('news') === 'list') {
			$factionNews = $factionNewsManager->getFactionBasicNews($faction->id);
			$mode = 'all';
		} else {
			$factionNews = $factionNewsManager->getFactionPinnedNew($faction->id);
			$mode = 'pin';
		}

		include COMPONENT . 'faction/overview/news.php';

		$governmentMembers = $playerManager->getGovernmentMembers($session->get('playerInfo')->get('color'));

		include COMPONENT . 'faction/overview/stat.php';

		$effectiveLaws = $lawManager->getByFactionAndStatements($faction->id, [Law::EFFECTIVE]);
		$votingLaws = $lawManager->getByFactionAndStatements($faction->id, [Law::VOTATION]);

		include COMPONENT . 'faction/overview/laws.php';
	} elseif ($request->query->get('view') == 'forum') {
		if (!$request->query->has('forum')) {
			# page d'accueil des forums
			# charge les x premiers sujets de chaque forum

			$S_TOM1 = $forumTopicManager->getCurrentSession();

			$archivedMode = FALSE;

			for ($i = 1; $i <= ForumResources::size(); $i++) {
				$forum_topics = ForumResources::getInfo($i, 'id');
				
				if ($forum_topics < 10 || ($forum_topics >= 10 && $forum_topics < 20 && $session->get('playerInfo')->get('status') > 2) || ($forum_topics >= 20 && $forum_topics < 30 && $session->get('playerInfo')->get('status') == Player::CHIEF)) {
					
					$where = [
						'rForum' => $forum_topics,
						'isArchived' => $archivedMode
					];

					if ($forum_topics < 20) {
						$where['rColor'] = $session->get('playerInfo')->get('color');
					}

					$forumTopicManager->newSession();
					$forumTopicManager->load(
						$where,
						['isUp', 'DESC', 'dLastMessage', 'DESC'],
						[0, 10],
						$session->get('playerId')
					);

					$topic_topics = [];

					for ($j = 0; $j < $forumTopicManager->size(); $j++) { 
						$topic_topics[$j] = $forumTopicManager->get($j);
					}

					$isStandard_topics = FALSE;
					$idColum_topics = $i;
					
					include COMPONENT . 'faction/forum/topics.php';
				}
			}

			$forumTopicManager->changeSession($S_TOM1);
		} else {
			$forumId = !$request->query->has('forum') ? 1 : $request->query->get('forum');
			$archivedMode = $request->query->get('mode') === 'archived' && in_array($session->get('playerInfo')->get('status'), [Player::CHIEF, Player::WARLORD, Player::TREASURER, Player::MINISTER])
				? TRUE : FALSE;
				
			if ($forumId < 10 || ($forumId >= 10 && $forumId < 20 && $session->get('playerInfo')->get('status') > 2) || ($forumId >= 20 && $forumId < 30 && $session->get('playerInfo')->get('status') == Player::CHIEF)) {
				# forum component
				include COMPONENT . 'faction/forum/forum.php';

				$where = [
					'rForum' => $forumId,
					'isArchived' => $archivedMode
				];

				if ($forumId < 20) {
					$where['rColor'] = $session->get('playerInfo')->get('color');
				}

				$S_TOM1 = $forumTopicManager->getCurrentSession();
				$forumTopicManager->newSession();
				$forumTopicManager->load(
					$where,
					['isUp', 'DESC', 'dLastMessage', 'DESC'],
					[0, 10],
					$session->get('playerId')
				);

				$topic_topics = array();
				for ($i = 0; $i < $forumTopicManager->size(); $i++) { 
					$topic_topics[$i] = $forumTopicManager->get($i);
				}
				
				$isStandard_topics = TRUE;
				$forum_topics = $forumId;

				include COMPONENT . 'faction/forum/topics.php';

				$forumTopicManager->changeSession($S_TOM1);
			} else {
				$response->redirect('faction/view-forum');
			}

			if ($request->query->has('topic')) {
				# topic component
				$S_TOM2 = $forumTopicManager->getCurrentSession();
				$forumTopicManager->newSession();

				if ($forumId < 10 || ($forumId >= 10 && $forumId < 20 && $session->get('playerInfo')->get('status') > 2)) {
					$forumTopicManager->load(
						['id' => $request->query->get('topic'), 'rColor' => $session->get('playerInfo')->get('color'), 'rForum' => $forumId],
						[], [],
						$session->get('playerId')
					);
				} else if ($forumId >= 20 && $forumId < 30 && $session->get('playerInfo')->get('status') == Player::CHIEF) {
					$forumTopicManager->load(
						['id' => $request->query->get('topic'), 'rForum' => $forumId],
						[], [],
						$session->get('playerId')
					);
				}

				if ($forumTopicManager->size() == 0) {
					throw new ErrorException('Les données sont illisibles, les messages doivent sûrement être cryptés !');
				} else {
					$topic_topic = $forumTopicManager->get(0);
					$forumTopicManager->updateLastView($topic_topic, $session->get('playerId'));

					$S_FMM1 = $forumMessageManager->getCurrentSession();
					$forumMessageManager->newSession();
					$forumMessageManager->load(array('rTopic' => $topic_topic->id), array('dCreation', 'DESC', 'id', 'DESC'));

					$message_topic = array();
					for ($i = 0; $i < $forumMessageManager->size(); $i++) { 
						$message_topic[$i] = $forumMessageManager->get($i);
					}

					include COMPONENT . 'faction/forum/topic.php';

					if (in_array($session->get('playerInfo')->get('status'), [Player::CHIEF, Player::WARLORD, Player::TREASURER, Player::MINISTER])) {
						include COMPONENT . 'faction/forum/manage-topic.php';
					}
					$forumMessageManager->changeSession($S_FMM1);
				}
				$forumTopicManager->changeSession($S_TOM2);
			} elseif ($request->query->has('mode') && $request->query->get('mode') == 'create') {
				# créer un topic
				include COMPONENT . 'faction/forum/createTopic.php';
			} else {
				include COMPONENT . 'default.php';
			}
		}
	} elseif ($request->query->get('view') == 'data') {
		include COMPONENT . 'faction/data/nav.php';

		if (!$request->query->has('mode') OR $request->query->get('mode') == 'financial') {
			include COMPONENT . 'faction/data/financial/stats.php';
			include COMPONENT . 'faction/data/financial/sectors-tax.php';
			include COMPONENT . 'faction/data/financial/donations.php';
		} elseif ($request->query->get('mode') == 'trade') {
			include COMPONENT . 'faction/data/trade/rc-stats.php';
			include COMPONENT . 'faction/data/trade/tax-out.php';
			include COMPONENT . 'faction/data/trade/tax-in.php';
		} elseif ($request->query->get('mode') == 'war') {
			include COMPONENT . 'faction/data/war/stats.php';
			include COMPONENT . 'faction/data/war/reports-attack.php';
			include COMPONENT . 'faction/data/war/reports-defend.php';
			include COMPONENT . 'faction/data/war/levels.php';
		} elseif ($request->query->get('mode') == 'tactical') {
			include COMPONENT . 'faction/data/tactical/map.php';
			include COMPONENT . 'faction/data/tactical/sectors.php';
			include COMPONENT . 'faction/data/tactical/targets.php';
		} elseif ($request->query->get('mode') == 'diplomacy') {
			include COMPONENT . 'faction/data/diplomacy/main.php';
			include COMPONENT . 'faction/data/diplomacy/about.php';
			include COMPONENT . 'default.php';
		} elseif ($request->query->get('mode') == 'law') {
			$listlaw_status = 6;
			include COMPONENT . 'faction/data/law/list.php';
			$listlaw_status = 3;
			include COMPONENT . 'faction/data/law/list.php';
			$listlaw_status = 4;
			include COMPONENT . 'faction/data/law/list.php';
			$listlaw_status = 5;
			include COMPONENT . 'faction/data/law/list.php';
		}
	} elseif ($request->query->get('view') == 'government') {
		if (in_array($session->get('playerInfo')->get('status'), [Player::CHIEF, Player::WARLORD, Player::TREASURER, Player::MINISTER])) {
			$senators = $playerManager->getParliamentMembers($faction->id);

			include COMPONENT . 'faction/government/nav.php';

			if (!$request->query->has('mode') OR $request->query->get('mode') == 'law') {
				$factionSectors = $sectorManager->getFactionSectors($faction->id);

				$nbLaws = 0;
				$nbPlayer = $playerManager->countByFactionAndStatements($faction->id, [Player::ACTIVE]);
				
				for ($i = 1; $i <= LawResources::size(); $i++) {
					if (LawResources::getInfo($i, 'department') == $session->get('playerInfo')->get('status') AND LawResources::getInfo($i, 'isImplemented')) {
						$governmentLaw_id = $i;
						$nbLaws++;
						include COMPONENT . 'faction/government/law.php';
					}
				}

				if (2 - $nbLaws > 0) {
					for ($i = 0; $i < 2 - $nbLaws; $i++) { 
						include COMPONENT . 'default.php';
					}
				}
			} elseif ($request->query->get('mode') == 'news') {
				$factionNews = $factionNewsManager->getFactionNews($faction->id);
				
				include COMPONENT . 'faction/news/list.php';

				if ($request->query->has('news')) {
					if ($factionNewsManager->get($request->query->get('news')) !== null) {
						include COMPONENT . 'faction/news/edit.php';
					} else {
						$response->redirect('faction/view-government/mode-news');
					}
				} else {
					include COMPONENT . 'faction/news/create.php';
				}
			} elseif ($request->query->get('mode') == 'message') {
				include COMPONENT . 'faction/government/message.php';
			} elseif ($request->query->get('mode') == 'description') {
				include COMPONENT . 'faction/government/description.php';
			} elseif ($request->query->get('mode') == 'credit') {
				include COMPONENT . 'faction/government/credit.php';
			} elseif ($request->query->get('mode') == 'manage') {
				$governmentMembers = $playerManager->getGovernmentMembers($faction->id);

				include COMPONENT . 'faction/government/manage/main.php';
				include COMPONENT . 'default.php';

			} else {
				$response->redirect('faction');
			}
		} else {
			$response->redirect('faction');
		}
	} elseif ($request->query->get('view') == 'senate') {
		if (in_array($session->get('playerInfo')->get('status'), [Player::CHIEF, Player::WARLORD, Player::TREASURER, Player::MINISTER, Player::PARLIAMENT])) {
			$laws = $lawManager->getByFactionAndStatements($faction->id, [Law::VOTATION]);
			include COMPONENT . 'faction/senate/stats.php';

			foreach ($laws as $law) {
				include COMPONENT . 'faction/senate/law.php';
			}
			if (count($laws) < 1) {
				include COMPONENT . 'default.php';
			}

			$laws = $lawManager->getByFactionAndStatements($faction->id, [Law::EFFECTIVE, Law::OBSOLETE, Law::REFUSED]);

			include COMPONENT . 'faction/senate/historic.php';
		} else {
			$response->redirect('faction');
		}
	} elseif ($request->query->get('view') == 'election' && in_array($faction->electionStatement, array(Color::CAMPAIGN, Color::ELECTION))) {
		if ($faction->electionStatement == Color::CAMPAIGN) {
			if (($election = $electionManager->getFactionLastElection($faction->id)) !== null) {
				$candidates = $candidateManager->getByElection($election);
				$nbCandidate = count($candidates);
				include COMPONENT . 'faction/election/campaign.php';
				include COMPONENT . 'faction/election/list.php';

				if ($request->query->get('candidate') === 'create') {
					include COMPONENT . 'faction/election/postulate.php';
				} elseif ($request->query->has('candidate') AND ($candidat = $candidateManager->get($request->query->get('candidate'))) !== null) {
					include COMPONENT . 'faction/election/candidate.php';

					$forumTopicManager->load(
						array(
							'rForum' => 30, 
							'rPlayer' => $candidat->rPlayer
						),
						array('id', 'DESC'),
						array(0, 1),
						$session->get('playerId')
					);

					if ($forumTopicManager->size() == 1) {
						$topic_topic = $forumTopicManager->get(0);
						$forumTopicManager->updateLastView($topic_topic, $session->get('playerId'));

						$S_FMM1 = $forumMessageManager->getCurrentSession();
						$forumMessageManager->newSession();
						$forumMessageManager->load(array('rTopic' => $topic_topic->id), array('dCreation', 'DESC', 'id', 'DESC'));

						$message_topic = array();
						for ($i = 0; $i < $forumMessageManager->size(); $i++) { 
							$message_topic[$i] = $forumMessageManager->get($i);
						}

						include COMPONENT . 'faction/forum/topic.php';

						$forumMessageManager->changeSession($S_FMM1);
					}
				} else {
					include COMPONENT . 'default.php';
				}
			} else {
				$response->redirect('faction');
			}
		} elseif ($faction->electionStatement == Color::ELECTION) {
			$election = $electionManager->getFactionLastElection($faction->id);

			$candidates = $candidateManager->getByElection($election);

			$playerVote = $voteManager->getPlayerVote($playerManager->get($session->get('playerId')), $election);

			$votes = $voteManager->getElectionVotes($election);

			$factionPlayers = $playerManager->getFactionPlayers($session->get('playerInfo')->get('color'));

			if ($faction->regime == Color::DEMOCRATIC) {
				$nbCandidate = count($candidates);
				include COMPONENT . 'faction/election/election.php';

				$rElection = $election->id;
				include COMPONENT . 'faction/election/list.php';

				if ($request->query->has('candidate') AND ($candidat = $candidateManager->get($request->query->get('candidate'))) !== null) {
					include COMPONENT . 'faction/election/candidate.php';

					$forumTopicManager->load(
						array(
							'rForum' => 30, 
							'rPlayer' => $candidat->rPlayer
						),
						array('id', 'DESC'),
						array(0, 1),
						$session->get('playerId')
					);

					if ($forumTopicManager->size() == 1) {
						$topic_topic = $forumTopicManager->get(0);
						$forumTopicManager->updateLastView($topic_topic, $session->get('playerId'));

						$S_FMM1 = $forumMessageManager->getCurrentSession();
						$forumMessageManager->newSession();
						$forumMessageManager->load(array('rTopic' => $topic_topic->id), array('dCreation', 'DESC', 'id', 'DESC'));

						$message_topic = array();
						for ($i = 0; $i < $forumMessageManager->size(); $i++) { 
							$message_topic[$i] = $forumMessageManager->get($i);
						}

						$election_topic = TRUE;
						include COMPONENT . 'faction/forum/topic.php';

						$forumMessageManager->changeSession($S_FMM1);
					}
				} else {
					include COMPONENT . 'default.php';
				}
			} elseif ($faction->regime == Color::ROYALISTIC) {
				$candidat  = $candidates[0];
				$rElection = $election->id;

				include COMPONENT . 'faction/election/putsch.php';
				include COMPONENT . 'faction/election/candidate.php';

				$forumTopicManager->load(
					array(
						'rForum' => 30, 
						'rPlayer' => $candidat->rPlayer
					),
					array('id', 'DESC'),
					array(0, 1),
					$session->get('playerId')
				);

				if ($forumTopicManager->size() == 1) {
					$topic_topic = $forumTopicManager->get(0);
					$forumTopicManager->updateLastView($topic_topic, $session->get('playerId'));

					$S_FMM1 = $forumMessageManager->getCurrentSession();
					$forumMessageManager->newSession();
					$forumMessageManager->load(array('rTopic' => $topic_topic->id), array('dCreation', 'DESC', 'id', 'DESC'));

					$message_topic = array();
					for ($i = 0; $i < $forumMessageManager->size(); $i++) { 
						$message_topic[$i] = $forumMessageManager->get($i);
					}
					$election_topic = TRUE;
					include COMPONENT . 'faction/forum/topic.php';

					$forumMessageManager->changeSession($S_FMM1);
				}
			} else {
				include COMPONENT . 'default.php';
			}
		}
	} elseif ($request->query->get('view') == 'player') {

		# statPlayer component
		$nbPlayer_statPlayer = $faction->activePlayers;

		$nbOnlinePlayer_statPlayer = 0;
		$nbOfflinePlayer_statPlayer = 0;

		$avgVictoryPlayer_statPlayer = 0;
		$avgDefeatPlayer_statPlayer = 0;
		$avgPointsPlayer_statPlayer = 0;

		# listPlayer component
		$players_listPlayer = array();

		$factionPlayers = $playerManager->getFactionPlayersByRanking($session->get('playerInfo')->get('color'));
		# worker
		foreach ($factionPlayers as $factionPlayer) {
			if (Utils::interval(Utils::now(), $factionPlayer->getDLastActivity(), 's') < 600) {
				$nbOnlinePlayer_statPlayer++;
			} else {
				$nbOfflinePlayer_statPlayer++;
			}

			$avgVictoryPlayer_statPlayer += $factionPlayer->getVictory();
			$avgDefeatPlayer_statPlayer += $factionPlayer->getDefeat();
			$avgPointsPlayer_statPlayer += $factionPlayer->getExperience();

			$players_listPlayer[] = $factionPlayer;
		}

		include COMPONENT . 'faction/player/statPlayer.php';
		include COMPONENT . 'faction/player/listPlayer.php';
	} else {
		$response->redirect('faction');
	}
echo '</div>';