<?php
require("../../../vendor/autoload.php");

$opts = [
	"plugins_path" => "../../../plugins/"
];
$machine = new \Machine\Machine($opts);
$machine->setTemplate("zSinger");
$machine->addPlugin("Link");

$machine->addPage("/", function() {
	return [
		"template" => "home.php",
		"data" => []
	];
});

$machine->addPage("/about/", function() {
	return [
		"template" => "single.php",
		"data" => []
	];
});

$machine->addPage("/blog/", function() {
	return [
		"template" => "archive.php",
		"data" => []
	];
});

$machine->addPage("/contacts/", function() {
	return [
		"template" => "contact.php",
		"data" => []
	];
});

$machine->run();	