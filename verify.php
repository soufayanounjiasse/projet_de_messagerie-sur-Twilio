<?php
require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'config/twilio.php';
require_once 'includes/fonctions.php';

// Démarrer la session
session_start();

// Vérifier si l'utilisateur a commencé la connexion
//login.php → Vérification identifiants → Envoi code SMS → verify.php → Validation code → dashboard.php

if (!isset($_SESSION['temp_user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['temp_user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $code = clean($_POST['code']);
        
        if (empty($code)) {
            $error = "Veuillez entrer le code de vérification";
        } elseif (!preg_match('/^[0-9]{6}$/', $code)) {
            $error = "Le code doit contenir exactement 6 chiffres";
        } elseif (verifyCode($user['id'], $code)) {
            // Code correct - connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['verified'] = true;
            unset($_SESSION['temp_user_id']);
            
            header('Location: dashboad.php');
            exit();
        } else {
            $error = "Code incorrect ou expiré. Le code est valide pendant 10 minutes.";
        }
    } elseif (isset($_POST['resend_code'])) {
        // Vérifier qu'on ne spam pas (limite de 1 minute entre chaque renvoi)
        $lastSent = isset($_SESSION['last_code_sent']) ? $_SESSION['last_code_sent'] : 0;
        $timeDiff = time() - $lastSent;
        
        if ($timeDiff < 60) {
            $remaining = 60 - $timeDiff;
            $error = "Veuillez attendre encore {$remaining} secondes avant de renvoyer un code";
        } else {
            // Renvoyer un nouveau code
            $code = generateVerificationCode();
            
            if (createVerificationCode($user['id'], $code)) {
                $message = "Votre nouveau code de vérification 2FA est : " . $code . ". Valide pendant 10 minutes.";
                
                if (sendSMS($user['phone'], $message)) {
                    $_SESSION['last_code_sent'] = time();
                    $success = "✅ Un nouveau code a été envoyé à votre téléphone";
                } else {
                    $error = "Erreur lors de l'envoi du code. Veuillez réessayer.";
                }
            } else {
                $error = "Erreur lors de la création du code de vérification.";
            }
        }
    }
}

// Masquer partiellement le numéro de téléphone
function maskPhone($phone) {
    if (strlen($phone) > 8) {
        return substr($phone, 0, -6) . '******' . substr($phone, -2);
    }
    return 'XXXX';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification 2FA - Auth 2FA</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .code-input {
            font-size: 24px;
            text-align: center;
            letter-spacing: 10px;
            font-weight: bold;
        }
        .timer {
            text-align: center;
            color: #666;
            margin: 10px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>🔐 Vérification en deux étapes</h1>
            <p class="text-muted">Un code de vérification à 6 chiffres a été envoyé au numéro : 
                <strong><?= maskPhone($user['phone']) ?></strong>
            </p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <div class="info-box">
                <p><strong>ℹ️ Instructions :</strong></p>
                <ul style="text-align: left; margin: 10px 0;">
                    <li>Vérifiez vos SMS pour le code à 6 chiffres</li>
                    <li>Le code expire après 10 minutes</li>
                    <li>Vous pouvez renvoyer un nouveau code si nécessaire</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="code">Code de vérification (6 chiffres)</label>
                    <input type="text" id="code" name="code" 
                           class="code-input"
                           maxlength="6" 
                           pattern="[0-9]{6}" 
                           inputmode="numeric"
                           required 
                           autofocus
                           placeholder="••••••"
                           autocomplete="one-time-code">
                </div>
                
                <div class="timer" id="timer">
                    Code valide pendant : <span id="countdown">10:00</span>
                </div>
                
                <button type="submit" name="verify_code" class="btn btn-primary">
                    ✓ Vérifier le code
                </button>
            </form>
            
            <form method="POST" action="" style="margin-top: 15px;">
                <button type="submit" name="resend_code" class="btn btn-secondary" id="resendBtn">
                    📲 Renvoyer le code
                </button>
            </form>
            
            <p class="text-center" style="margin-top: 20px;">
                <a href="logout.php">← Annuler et retourner à la connexion</a>
            </p>
        </div>
    </div>
    
    <script>
        // Auto-focus et formatage du code
        const codeInput = document.getElementById('code');
        
        codeInput.addEventListener('input', function(e) {
            // Garder seulement les chiffres
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto-submit si 6 chiffres sont entrés
            if (this.value.length === 6) {
                this.form.querySelector('button[name="verify_code"]').focus();
            }
        });
        
        // Compte à rebours de 10 minutes
        let timeLeft = 600; // 10 minutes en secondes
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft > 0) {
                timeLeft--;
                setTimeout(updateCountdown, 1000);
            } else {
                countdownElement.textContent = 'Expiré';
                countdownElement.style.color = 'red';
            }
        }
        
        updateCountdown();
        
        // Cooldown pour le bouton "Renvoyer"
        const resendBtn = document.getElementById('resendBtn');
        <?php if (isset($_SESSION['last_code_sent'])): ?>
            let cooldown = <?= max(0, 60 - (time() - $_SESSION['last_code_sent'])) ?>;
            if (cooldown > 0) {
                resendBtn.disabled = true;
                resendBtn.textContent = `Attendre ${cooldown}s...`;
                
                const cooldownInterval = setInterval(() => {
                    cooldown--;
                    if (cooldown > 0) {
                        resendBtn.textContent = `Attendre ${cooldown}s...`;
                    } else {
                        resendBtn.disabled = false;
                        resendBtn.textContent = '📲 Renvoyer le code';
                        clearInterval(cooldownInterval);
                    }
                }, 1000);
            }
        <?php endif; ?>
    </script>
</body>
</html>