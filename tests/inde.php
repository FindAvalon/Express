<?php

include_once(__DIR__.'/../src/Express.php');

use Longway\Express\Express;

$express = new Express();
var_dump($express->select('圆通速递', '1232131'));

$express->getSeller();

