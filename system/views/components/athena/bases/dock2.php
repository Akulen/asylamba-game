<?php
# dock2 component
# in athena.bases package

# affichage du chantier de ligne

# require
	# {orbitalBase}		ob_dock2

# work
$S_SQM1 = ASM::$sqm->getCurrentSession();
ASM::$sqm->changeSession($ob_dock2->dock2Manager);
$s = array('', '', '', '', '', '');
$technology = new Technology(CTR::$data->get('playerId'));

#place dans le hangar
$totalSpace = OrbitalBaseResource::getBuildingInfo(3, 'level', $ob_dock2->getLevelDock2(), 'storageSpace');
$storage = $ob_dock2->getShipStorage();
$inStorage = 0;
for ($m = 6; $m < 12; $m++) {
	$inStorage += ShipResource::getInfo($m, 'pev') * $storage[$m];
}
$inQueue = 0;
if (ASM::$sqm->size() > 0) {
	for ($j = 0; $j < ASM::$sqm->size(); $j++) {
		$inQueue += ShipResource::getInfo(ASM::$sqm->get($j)->getShipNumber(), 'pev') * ASM::$sqm->get($j)->getQuantity();
	}
}

for ($i = 6; $i < 12; $i++) {
	# calcul du nombre de vaisseaux max
	$maxShipResource = floor($ob_dock2->getResourcesStorage() / ShipResource::getInfo($i, 'resourcePrice'));
	$maxShipResource = ($maxShipResource < 100) ? $maxShipResource : 99;
	$maxShipPev = $totalSpace - $inStorage - $inQueue;
	$maxShipPev = floor($maxShipPev /ShipResource::getInfo($i, 'pev'));
	$maxShipPev = ($maxShipPev < 100) ? $maxShipPev : 99;
	$maxShip    = ($maxShipResource <= $maxShipPev) ? $maxShipResource : $maxShipPev;

	# display
	$name = ShipResource::getInfo($i, 'codeName');
	$picto = MEDIA . 'ship/picto/' . ShipResource::getInfo($i, 'imageLink') . '.png';
	$disability = 'disable';

	if (($answer = ShipResource::haveRights($i, 'techno', $technology)) !== TRUE) {
		# technology
		$but  = '<span class="button disable">';
			$but .= $answer;
		$but .= '</span>';
	} elseif (!ShipResource::haveRights($i, 'shipTree', $ob_dock2)) {
		# ship tree
		$but  = '<span class="button disable">';
			$but .= 'il vous faut augmenter votre chantier de ligne au niveau ' .  ShipResource::dockLevelNeededFor($i);
		$but .= '</span>';
	} else {
		# usable ship
		$disability = '';

		if (!ShipResource::haveRights($i, 'queue', ASM::$sqm->size())) {
			# queue size
			$but = '<span class="button disable">';
				$but .= 'file de construction pleine<br />';
				$but .= '<span class="final-cost">' . Format::numberFormat(ShipResource::getInfo($i, 'resourcePrice')) . '</span> ';
				$but .= '<img class="icon-color" alt="ressources" src="' . MEDIA . 'resources/resource.png"> et ';
				$but .= '<span class="final-time">' . Chronos::secondToFormat(ShipResource::getInfo($i, 'time'), 'lite') . '</span> ';
				$but .= '<img class="icon-color" alt="relèves" src="' . MEDIA . 'resources/time.png">';
			$but .= '</span>';
		} elseif ($maxShip < 1) {
			$but = '<span class="button disable">';
				$but .= 'pas assez de ressource / hangar plein<br />';
				$but .= '<span class="final-cost">' . Format::numberFormat(ShipResource::getInfo($i, 'resourcePrice')) . '</span> ';
				$but .= '<img class="icon-color" alt="ressources" src="' . MEDIA . 'resources/resource.png"> et ';
				$but .= '<span class="final-time">' . Chronos::secondToFormat(ShipResource::getInfo($i, 'time'), 'lite') . '</span> ';
				$but .= '<img class="icon-color" alt="relèves" src="' . MEDIA . 'resources/time.png">';
			$but .= '</span>';
		} else {
			$but  = '<a href="' . APP_ROOT . 'action/a-buildship/baseid-' . $ob_dock2->getId() . '/ship-' . $i . '/quantity-1" class="button">';
				$but .= 'construire 1 ' . ShipResource::getInfo($i, 'codeName') . ' pour<br />';
				$but .= '<span class="final-cost">' . Format::numberFormat(ShipResource::getInfo($i, 'resourcePrice')) . '</span> ';
				$but .= '<img class="icon-color" alt="ressources" src="' . MEDIA . 'resources/resource.png"> et ';
				$but .= '<span class="final-time">' . Chronos::secondToFormat(ShipResource::getInfo($i, 'time'), 'lite') . '</span> ';
				$but .= '<img class="icon-color" alt="relèves" src="' . MEDIA . 'resources/time.png">';
			$but .= '</a>';
		}
	}

	$s[$i - 6] .= '<div class="build-item ' . $disability . '">';
		$s[$i - 6] .= '<div class="name">';
			$s[$i - 6] .= '<img src="' . $picto . '" alt="" />';
			$s[$i - 6] .= '<strong>' . $name . '</strong>';
			$s[$i - 6] .= '<em>' . ShipResource::getInfo($i, 'name') . '</em>'; 
			$s[$i - 6] .= '<a href="#" class="addInfoPanel info hb lt" title="plus d\'info" data-ship-id="' . $i . '" data-info-type="ship">+</a>';
		$s[$i - 6] .= '</div>';
		$s[$i - 6] .= $but;
	$s[$i - 6] .= '</div>';
}

# display
echo '<div class="component size2 dock1">';
	echo '<div class="head skin-1">';
		echo '<img src="' . MEDIA . 'orbitalbase/dock2.png" alt="" />';
		echo '<h2>' . OrbitalBaseResource::getBuildingInfo(3, 'frenchName') . '</h2>';
		echo '<em>niveau ' . $ob_dock2->getLevelDock2() . '</em>';
	echo '</div>';
	echo '<div class="fix-body">';
		echo '<div class="body">';
			echo '<table>';
				echo '<tr><td colspan="2">';
					echo '<div class="info-building">';
						echo '<h4>Classe Frégate</h4>';
					echo '</div>';
				echo '</td></tr>';
				echo '<tr>';
					echo '<td>' . $s[0] . '</td>';
					echo '<td>' . $s[1] . '</td>';
				echo '</tr>';
				echo '<tr><td colspan="2">';
					echo '<div class="info-building">';
						echo '<h4>Classe Destroyer / Croiseur</h4>';
					echo '</div>';
				echo '</td></tr>';
				echo '<tr>';
					echo '<td>' . $s[2] . '</td>';
					echo '<td>' . $s[3] . '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>' . $s[4] . '</td>';
					echo '<td>' . $s[5] . '</td>';
				echo '</tr>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '<div class="component">';
	echo '<div class="head skin-2">';
		echo '<h2>Gestion des commandes</h2>';
	echo '</div>';
	echo '<div class="fix-body">';
		echo '<div class="body">';
			echo '<div class="number-box ' . ((CTR::$data->get('playerBonus')->get(PlayerBonus::DOCK2_SPEED) == 0) ? 'grey' : '') . '">';
				echo '<span class="label">bonus de vitesse de production</span>';
				echo '<span class="value">';
					echo Format::numberFormat(CTR::$data->get('playerBonus')->get(PlayerBonus::DOCK2_SPEED)) . ' %';
				echo '</span>';
			echo '</div>';
			
			if (ASM::$sqm->size() > 0) {
				echo '<div class="queue">';
				$n = 1; $realSizeQueue = 0;
				for ($i = 0; $i < ASM::$sqm->size(); $i++) {
					$queue = ASM::$sqm->get($i);
					$realSizeQueue++;
					$totalTimeShips = ShipResource::getInfo($queue->getShipNumber(), 'time');

					if ($realSizeQueue > 1) {
						echo '<div class="item">';
					} else { 
						echo '<div class="item active progress" data-progress-output="lite" data-progress-current-time="' . $queue->getRemainingTime() . '" data-progress-total-time="' . $totalTimeShips . '">';
					}
					echo '<a href="' . APP_ROOT . 'action/a-dequeueship/baseid-' . $ob_dock2->getId() . '/dock-2/queue-' . $queue->getId() . '"' . 
						'class="button hb lt" title="annuler la commande">×</a>';
					echo  '<img class="picto" src="' . MEDIA . 'ship/picto/' . ShipResource::getInfo($queue->getShipNumber(), 'imageLink') . '.png" alt="" />';
					echo '<strong>' . ShipResource::getInfo($queue->getShipNumber(), 'codeName') . '</strong>';
					
					if ($realSizeQueue > 1) {
						echo '<em>en attente</em>';

						echo '<span class="progress-container"></span>';
					} else {
						echo '<em><span class="progress-text">' . Chronos::secondToFormat($queue->getRemainingTime(), 'lite') . '</span></em>';

						echo '<span class="progress-container">';
							echo '<span style="width: ' . Format::percent($totalTimeShips - $queue->getRemainingTime(), $totalTimeShips) . '%;" class="progress-bar">';
							echo '</span>';
						echo '</span>';
					}

					echo '</div>';
					$n++;
				}

				if ($realSizeQueue > SQM_SHIPMAXQUEUE) {
					echo '<p><em>file de construction pleine, revenez dans un moment.</em></p>';
				}
				echo '</div>';
			} else {
				echo '<p><em>Aucun vaisseau en construction !</em></p>';
			}
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '<div class="component">';
	echo '<div class="head skin-2">';
		echo '<h2>Hangars</h2>';
	echo '</div>';
	echo '<div class="fix-body">';
		echo '<div class="body">';
			echo '<div class="tool">';
				echo '<span><a href="' . APP_ROOT . 'fleet/view-movement">intégrer à vos armées</a></span>';
				echo '<span><a href="#" class="sh hb lt" title="information" data-target="info-dock2">?</a></span>';
			echo '</div>';

			echo '<p class="info" id="info-dock2" style="display:none;">Le hangar de votre chantier vous sert à stocker des vaisseaux. Les vaisseaux 
			sont stockés directement après leur construction. Si vous ne disposez pas suffisamment de place, vous ne pourrez plus en construire. Faite 
			donc bien attention de vider votre hangar régulièrement.<br />
			Chaque vaisseau prend une place différente dans votre hangar. Cette place correspond au point équivalent vaisseau (PEV). Le hangar dispose 
			d’un certain nombre de PEV qui augmente en fonction du niveau de votre chantier.<br />
			Le bouton intégration vous renverra à l’amirauté, ce qui vous permettra de vider votre hangar et de répartir vos vaisseaux dans vos flottes 
			en orbite autour de la planète sur laquelle vous avez construit votre chantier.</p>';

			echo '<div class="queue">';
			for ($i = 6; $i < 12; $i++) {
				if ($storage[$i] !== 0) {
					echo '<div class="item">';
						echo '<img class="picto" src="' . MEDIA . 'ship/picto/' . ShipResource::getInfo($i, 'imageLink') . '.png" alt="" />';
						echo '<strong><span class="big">' . $storage[$i] . '</span> ' . ShipResource::getInfo($i, 'codeName') . Format::addPlural($storage[$i]) . '</strong>';
						echo '<em>' . ($storage[$i] * ShipResource::getInfo($i, 'pev')) . ' PEV</em>';
					echo '</div>';
				}
			}
			echo '</div>';

			echo '<div class="number-box">';
				echo '<span class="label">capacité du hangar</span>';
				echo '<span class="value">';
					echo $inStorage . ' <img class="icon-color" alt="" src="' . MEDIA . 'resources/pev.png">';
					echo ' / ';
					echo $totalSpace . ' <img class="icon-color" alt="" src="' . MEDIA . 'resources/pev.png">';
				echo '</span>';
				$percent = Format::numberFormat($inStorage / $totalSpace * 100);
				echo '<span class="progress-bar hb bl" title="remplissage : ' . $percent . '%">';
					echo '<span style="width:' . $percent . '%;" class="content"></span>';
				echo '</span>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '<div class="component">';
	echo '<div class="head skin-2">';
		echo '<h2>À propos</h2>';
	echo '</div>';
	echo '<div class="fix-body">';
		echo '<div class="body">';
			echo '<p class="long-info">' . OrbitalBaseResource::getBuildingInfo(3, 'description') . '</p>';
		echo '</div>';
	echo '</div>';
echo '</div>';

ASM::$sqm->changeSession($S_SQM1);
?>