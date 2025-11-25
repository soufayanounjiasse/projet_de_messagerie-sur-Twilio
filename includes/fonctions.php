<?php
//session_start();

// Générer un code de vérification à 6 chiffres
function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}


function sendSMS($phone, $message) {
    try {
        $client = getTwilioClient();
        
        // Vérifier que le numéro est au format international
        if (!preg_match('/^\+/', $phone)) {
            error_log("Erreur : Le numéro doit commencer par + (format international)");
            return false;
        }
        
        $result = $client->messages->create(
            $phone, // Numéro destinataire
            [
                'from' => TWILIO_PHONE_NUMBER, // Votre numéro Twilio
                'body' => $message
            ]
        );
        
        error_log("SMS envoyé avec succès. SID: " . $result->sid);
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur SMS Twilio : " . $e->getMessage());
        error_log("Code erreur : " . $e->getCode());
        return false;
    }
}

// Envoyer un SMS avec Twilio (via Messaging Service)
/*function sendSMS($to, $message) {
    try {
        $client = getTwilioClient();
        $client->messages->create($to, [
            'messagingServiceSid' => TWILIO_MESSAGING_SERVICE_SID,
            'body' => $message
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Erreur SMS : " . $e->getMessage());
        return false;
    }
}*/

// Envoyer un message WhatsApp avec Twilio
function sendWhatsApp($to, $message) {
    try {
        $client = getTwilioClient();
        // Le numéro doit être au format whatsapp:+1234567890
        if (strpos($to, 'whatsapp:') !== 0) {
            $to = 'whatsapp:' . $to;
        }
        
        $client->messages->create($to, [
            'from' => TWILIO_WHATSAPP,
            'body' => $message
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Erreur WhatsApp : " . $e->getMessage());
        return false;
    }
}

// Créer un code de vérification en base de données
function createVerificationCode($userId, $code) {
    global $pdo;
    
    // Supprimer les anciens codes non utilisés
    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE user_id = ? AND verified = 0");
    $stmt->execute([$userId]);
    
    // Créer le nouveau code (valide 10 minutes)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $stmt = $pdo->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
    return $stmt->execute([$userId, $code, $expiresAt]);
}

// Vérifier le code
function verifyCode($userId, $code) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM verification_codes 
        WHERE user_id = ? AND code = ? AND verified = 0 AND expires_at > NOW()
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$userId, $code]);
    $verification = $stmt->fetch();
    
    if ($verification) {
        // Marquer comme vérifié
        $stmt = $pdo->prepare("UPDATE verification_codes SET verified = 1 WHERE id = ?");
        $stmt->execute([$verification['id']]);
        return true;
    }
    
    return false;
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['verified']) && $_SESSION['verified'] === true;
}

// Rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Nettoyer les entrées
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Formater le numéro de téléphone pour Twilio
function formatPhoneNumber($phone) {
    // Enlever tous les caractères non numériques sauf le +
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Si le numéro ne commence pas par +, ajouter +237 (Cameroun)
    if (substr($phone, 0, 1) !== '+') {
        $phone = '+237' . $phone;
    }
    
    return $phone;
}
?>