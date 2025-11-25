<?php
require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'config/twilio.php'; // Ajouter le fichier de config Twilio
require_once 'includes/fonctions.php'; // Ajouter les fonctions

if (!isset($_SESSION)) {
    session_start();
}
// Vérification de la session utilisateur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupération des informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérification si l'utilisateur existe
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_whatsapp'])) {
    $phoneNumber = formatPhoneNumber(clean($_POST['phone_number']));
    $messageText = clean($_POST['message']);
    
    if (empty($phoneNumber) || empty($messageText)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        // Envoyer le message WhatsApp
        if (sendWhatsApp($phoneNumber, $messageText)) {
            $message = "Message WhatsApp envoyé avec succès !";
        } else {
            $error = "Erreur lors de l'envoi du message WhatsApp. Assurez-vous que le numéro est enregistré dans le Sandbox WhatsApp de Twilio.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Auth 2FA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="header">
                <h1>Bienvenue, <?= clean($user['username']) ?> !</h1>
                <a href="lagout.php" class="btn btn-danger">Se déconnecter</a>
            </div>
            
            <div class="user-info">
                <h2>Informations du compte utilisateur</h2>
                <p><strong>Email :</strong> <?= clean($user['email']) ?></p>
                <p><strong>Téléphone :</strong> <?= clean($user['phone']) ?></p>
                <p><strong>Membre depuis :</strong> <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
            </div>
            
            <div class="whatsapp-section">
                <h2>📱 Envoyer un message WhatsApp</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>
                
                <div class="info-box">
                    <p><strong>⚠️ Important a savoir :</strong></p>
                    <ul>
                        <li>Le numéro doit être au format international : +237XXXXXXXXX</li>
                    </ul>
                </div>
                
                <form method="POST" action="" class="whatsapp-form">
                    <div class="form-group">
                        <label for="phone_number">Numéro de téléphone</label>
                        <input type="tel" id="phone_number" name="phone_number" 
                               placeholder="+237690000000" 
                               value="<?= isset($_POST['phone_number']) ? clean($_POST['phone_number']) : '' ?>"
                               required>
                        <small>Format international : +[code pays][numéro] (ex: +237690000000)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" 
                                  placeholder="Votre message ici..." 
                                  required><?= isset($_POST['message']) ? clean($_POST['message']) : '' ?></textarea>
                        <small>Maximum 1600 caractères</small>
                    </div>
                    
                    <button type="submit" name="send_whatsapp" class="btn btn-success">
                        📤 Envoyer via WhatsApp
                    </button>
                </form>
            </div>
            
            <div class="features">
                <h2>✨ Fonctionnalités</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h3>🔐 Authentification 2FA</h3>
                        <p>Sécurité renforcée avec double facteur via SMS</p>
                    </div>
                    <div class="feature-card">
                        <h3>📱 WhatsApp API</h3>
                        <p>Envoyez des messages via l'API WhatsApp de Twilio</p>
                    </div>
                    <div class="feature-card">
                        <h3>💬 SMS Integration</h3>
                        <p>Codes de vérification envoyés par SMS</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



