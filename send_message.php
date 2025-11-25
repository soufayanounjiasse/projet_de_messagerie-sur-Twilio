<?php

// Update the path below to your autoload.php,
// see https://getcomposer.org/doc/01-basic-usage.md
require_once "/vendor/autoload.php";

use Twilio\Rest\Client;

// Find your Account SID and Auth Token at twilio.com/console
// and set the environment variables. See http://twil.io/secure
$sid = getenv("TWILIO_ACCOUNT_SID");
$token = getenv("TWILIO_AUTH_TOKEN");
$twilio = new Client($sid, $token);

$message = $twilio->messages->create(
    "+15558675310", // To
    [
        "body" =>
            "This is the ship that made the Kessel Run in fourteen parsecs?",
        "from" => "+15017122661",
    ]
);

print $message->body;