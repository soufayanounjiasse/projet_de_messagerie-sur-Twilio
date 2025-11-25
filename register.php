<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';
$pdo = require_once __DIR__ . '/config/database.php'; // MODIFIER
require_once __DIR__ . '/config/twilio.php';
require_once __DIR__ . '/includes/fonctions.php';

// Récupérer $pdo depuis $GLOBALS
$pdo = $GLOBALS['pdo'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $phone = formatPhoneNumber($_POST['phone']); // Utilise la fonction du fichier functions.php
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($phone) || empty($password)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères";
    } elseif (!preg_match('/^\+237[0-9]{9}$/', $phone)) {
        $error = "Format de téléphone invalide. Utilisez +237XXXXXXXXX";
    } else {
        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? OR phone = ?");
        $stmt->execute([$username, $email, $phone]);
        
        if ($stmt->fetch()) {
            $error = "Nom d'utilisateur, email ou téléphone déjà utilisé";
        } else {
            // Créer l'utilisateur
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $phone, $hashedPassword])) {
                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                // Réinitialiser les champs après succès
                $_POST = [];
            } else {
                $error = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Auth 2FA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>📝 Inscription</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required 
                           minlength="3" maxlength="50"
                           value="<?= isset($_POST['username']) ? clean($_POST['username']) : '' ?>"
                           placeholder="Entrez votre nom d'utilisateur">
                    <small>3-50 caractères</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>"
                           placeholder="exemple@email.com">
                </div>
                
                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input type="tel" id="phone" name="phone" required 
                           pattern="^\+237[0-9]{9}$"
                           placeholder="+237690000000"
                           value="<?= isset($_POST['phone']) ? clean($_POST['phone']) : '' ?>">
                    <small>Format : +237XXXXXXXXX (9 chiffres après +237)</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required 
                           minlength="6"
                           placeholder="Au moins 6 caractères">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Retapez votre mot de passe">
                </div>
                
                <button type="submit" class="btn btn-primary">S'inscrire</button>
            </form>
            
            <p class="text-center">
                Vous avez déjà un compte ? <a href="login.php">Se connecter</a>
            </p>
        </div>
    </div>
    
    <script>
        // Vérification côté client pour les mots de passe
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas !');
            }
        });
    </script>
</body>
</html>