<?php
// Configuration Twilio
define('TWILIO_SID', '---------------');
define('TWILIO_AUTH_TOKEN', '----------------'); // Renommé
define('TWILIO_PHONE_NUMBER', '-----------'); // Renommé
define('TWILIO_WHATSAPP', 'whatsapp:------------'); // Format WhatsApp corrigé
define('TWILIO_MESSAGING_SERVICE_SID', '----------------------------');

// Ne pas modifier en dessous
require_once __DIR__ . '/../vendor/autoload.php';

use Twilio\Rest\Client;

function getTwilioClient() {
    return new Client(TWILIO_SID, TWILIO_AUTH_TOKEN);
}
?>