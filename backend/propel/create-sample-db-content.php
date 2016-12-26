<?php
/**
 * This file is part of bw-post-digital.
 * Please check the file LICENSE.md for information about the license.
 *
 * @copyright Markus Riegert 2016
 * @author Markus Riegert <desmodul@drow-land.de>
 */

 /**
 * Creates a random sample database for bw-post-digital.
 */

// source loading stuff
$bootDir = __DIR__.'/../';
require_once realpath($bootDir."boot.php");

// create a user 
$user = new User();
$user->setPersonalId("U-123-100");
$user->setLoginName("bhecken");
$user->save();
$numUser = 1;

// create some receiver addresses
$receiverList = array("1. SAN Kompanie", "5. Inst. 310", "S6", "Ausbildung1", "Ausbildung");
foreach ($receiverList as $receiverName)
{
	$receiver = new Receiver();
	$receiver->setName($receiverName);
	$receiver->save();
}
$numReceiver = count($receiverList);

// create some deliveries in different states
$deliveries = array("10001"=>"Bremen", "10002"=>"Herborn", "10003"=>"Kön", "10004"=>"Mannheim", "10005"=>"Essen",
					"10006"=>"Karlsruhe", "10007"=>"England, London", "10008"=>"Hamburh", "10009"=>"München");
foreach ($deliveries as $deliveryId=>$sender)
{
	$delivery = new Delivery();
	$delivery->setNumber($deliveryId);
	$delivery->setSenderId($sender);
	$delivery->save();
	// create some corresponding operations
}
$numDeliveries = count($deliveries);

