<?php
$db = DataBaseAdmin::getInstance();

echo '<h2>Ajout de points dans sector</h2>';

$db->query("ALTER TABLE `sector` ADD `points` INT NOT NULL DEFAULT 1 AFTER `lifePlanet`;");