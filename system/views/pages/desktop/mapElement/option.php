<?php
echo '<div id="map-option">';
	echo '<a ';
		echo 'href="#" ';
		echo 'class="sh switch-class hb lb" ';
		echo 'data-class="active" ';
		echo 'data-target="commercial-routes" ';
		echo 'title="afficher/cacher vos routes commerciales"';
	echo '>';
		echo '<img src="' . MEDIA . 'orbitalbase/commercialplateforme.png" alt="" />';
	echo '</a>';

	echo '<a ';
		echo 'href="#" ';
		echo 'class="sh switch-class hb lb active" ';
		echo 'data-class="active" ';
		echo 'data-target="spying" ';
		echo 'title="afficher/cacher vos cercles de contre-espionnage"';
	echo '>';
		echo '<img src="' . MEDIA . 'orbitalbase/antispy.png" alt="" />';
	echo '</a>';

	echo '<a ';
		echo 'href="#" ';
		echo 'class="sh switch-class hb lb active" ';
		echo 'data-class="active" ';
		echo 'data-target="fleet-movements" ';
		echo 'title="afficher/cacher vos mouvements de flotte"';
	echo '>';
		echo '<img src="' . MEDIA . 'fleet/general-quarter.png" alt="" />';
	echo '</a>';

	echo '<a ';
		echo 'href="#" ';
		echo 'class="sh switch-class hb lb active" ';
		echo 'data-class="active" ';
		echo 'data-target="attacks" ';
		echo 'title="afficher/cacher les attaques entrantes""';
	echo '>';
		echo '<img src="' . MEDIA . 'fleet/movement.png" alt="" />';
	echo '</a>';
echo '</div>';
?>