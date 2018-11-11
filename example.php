<?php
require_once __DIR__ . '/vendor/autoload.php';

use Jungwild\Crawler;

$crawler = new Crawler();

if($domcrawler = $crawler->get('https://www.google.de/')) {

    echo $domcrawler->html();

}
