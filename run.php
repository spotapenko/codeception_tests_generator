<?php

require_once 'config.php';
require_once 'functions.php';

$siteTree = [];
$all_urls = [];

processRecursivelyUrlsWithMaxLevel($siteTree, 'index.html', 3, 1, '' , $all_urls);

$testData = getTestDataSeeText($all_urls);

generateCodeceptionAcceptanceTests($testData);

