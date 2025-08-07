<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement - Client</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <main class="main-content client">
            <h1>Paiement</h1>

            <div class="stats-grid" id="stats-client">
                <div class="stat-item info">
                    <div class="stat-number" id="total-commandes" style="color: #1976d2;">0</div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            
            <div class="section-card client">
                <h3>Récapitulatif de votre commande</h3>
                <p>Veuillez vérifier les détails de votre commande avant de procéder au paiement.</p>
                <table class="order-summary">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Prix Unitaire</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>

            <div class="section-card client">
                <h2>Informations de Paiement</h2>
                <p>Veuillez entrer vos informations de paiement pour finaliser votre commande.</p>
                <form action="../api/paiement/process.php" method="post">
                    <input type="text" name="card_number" placeholder="Numéro de carte" required>
                    <input type="text" name="card_holder" placeholder="Nom du titulaire" required>
                    <input type="text" name="expiry_date" placeholder="Date d'expiration (MM/AA)" required>
                    <input type="text" name="cvv" placeholder="CVV" required>
                    <button type="submit" class="btn-nav">Payer</button>
                    <button type="reset" class="btn-nav" style="background-color: #f44336;">Annuler</button>
                </form>
            </div>
        </main>
    </div>

    <script>

    </script>
</body>
</html>