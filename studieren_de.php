<?php
require_once __DIR__ . '/vendor/autoload.php';

use Jungwild\Crawler\StudierenDe;

$std = new StudierenDe();

$std->run();