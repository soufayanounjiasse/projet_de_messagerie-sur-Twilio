<?php
require_once "vendor/autoload.php";
use Twilio\TwiML\MessagingResponse;

// Set the content-type to XML to send back TwiML from the PHP SDK
header("content-type: text/xml");

$response = new MessagingResponse();
$response->message(
    "The Robots are coming! Head for the hills!"
);

echo $response;

//php -S localhost:3000  démarrez le serveur de développement PHP sur le port 3000
//ngrok http 3000 exécutez la commande suivante pour démarrer ngrok
//et créez un tunne