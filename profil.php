<?php
$page_title = "Mon Profil";
include 'config/config.php';
include 'includes/functions.php';
requireAuth();
require_once 'includes/header.php';
 $pdo = getConnection();
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.*, m.* FROM utilisateurs u 
    LEFT JOIN membres m ON u.id_membre = m.id_membre 
    WHERE u.id_utilisateur = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $adresse = $_POST['adresse'];
    
    try {
         $pdo = getConnection();
        $stmt = $pdo->prepare("
            UPDATE membres SET 
            nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?
            WHERE id_membre = ?
        ");
        $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $user['id_membre']]);
        
        setMessage("Profil mis à jour avec succès", "success");
        redirect($_SERVER['PHP_SELF']);
    } catch (Exception $e) {
        setMessage("Erreur lors de la mise à jour", "danger");
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3>Mon Profil</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Code Membre</label>
                                    <input type="text" class="form-control" value="<?php echo escape($user['code_membre']); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Type de Membre</label>
                                    <input type="text" class="form-control" value="<?php echo escape($user['type_membre']); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Nom *</label>
                                    <input type="text" name="nom" class="form-control" value="<?php echo escape($user['nom']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Prénom *</label>
                                    <input type="text" name="prenom" class="form-control" value="<?php echo escape($user['prenom']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo escape($user['email']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label>Téléphone</label>
                            <input type="tel" name="telephone" class="form-control" value="<?php echo escape($user['telephone']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label>Adresse</label>
                            <textarea name="adresse" class="form-control" rows="3"><?php echo escape($user['adresse']); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
