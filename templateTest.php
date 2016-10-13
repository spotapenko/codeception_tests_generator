<?php

$I = new AcceptanceTester($scenario);
$I->wantTo('ensure that #PAGE_URL works');
$I->amOnPage('/#PAGE_URL');
