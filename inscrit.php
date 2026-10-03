<?php
// connexion.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM membres WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            
            header('Location: compte.php');
            exit;
        } else {
            echo "<div style='color: red;'>Email ou mot de passe incorrect</div>";
        }
    } catch (PDOException $e) {
        echo "<div style='color: red;'>Erreur de connexion</div>";
    }
}
?>