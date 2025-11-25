<?php
// Démarrer la session EN PREMIER
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger les fichiers
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/twilio.php';
require_once __DIR__ . '/includes/fonctions.php';

// Récupérer $pdo depuis $GLOBALS
$pdo = $GLOBALS['pdo'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        // Vérifier les identifiants
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Générer le code 2FA
            $code = generateVerificationCode();
            
            if (createVerificationCode($user['id'], $code)) {
                // Envoyer le code par SMS
                $message = "Votre code de vérification 2FA est : " . $code . ". Valide pendant 10 minutes.";
                
                if (sendSMS($user['phone'], $message)) {
                    $_SESSION['temp_user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['verified'] = false;
                    
                    header('Location: verify.php');
                    exit();
                } else {
                    $error = "Erreur lors de l'envoi du code SMS. Veuillez réessayer.";
                }
            } else {
                $error = "Erreur lors de la création du code de vérification.";
            }
        } else {
            $error = "Identifiants incorrects";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Auth 2FA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>🔐 Connexion</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="info-box">
                <p><strong>ℹ️ Authentification 2FA activée</strong></p>
                <p>Après connexion, vous recevrez un code de vérification par SMS sur votre téléphone enregistré.</p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur ou Email</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Entrez votre nom d'utilisateur ou email"
                           value="<?= isset($_POST['username']) ? clean($_POST['username']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Entrez votre mot de passe">
                </div>
                
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </form>
            
            <p class="text-center">
                Pas encore de compte ? <a href="register.php">S'inscrire</a>
            </p>
        </div>
    </div>
</body>
</html>