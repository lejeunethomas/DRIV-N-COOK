<?php
require_once '../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des camions - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar admin">
            <h2>Admin</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="franchisés.php">Gérer les franchisés</a>
            <a href="camions.php" class="active">Gérer les camions</a>
            <a href="produits.php">Gérer les produits</a>
            <a href="entrepots.php">Gérer les entrepôts</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        
        <main class="main-content admin">
            <h1 class="admin">Gestion des camions</h1>
            <div class="tabs">
                <button class="tab-btn active" id="tab-camions-btn" onclick="switchTab('camions')">
                    Parc de camions
                </button>
                <button class="tab-btn" id="tab-demandes-btn" onclick="switchTab('demandes')">
                    Demandes à traiter <span class="badge" id="badge-demandes" style="display:none;">0</span>
                </button>
            </div>

            <div id="tab-camions" class="tab-content">
                <table>
                    <thead>
                        <tr>
                            <th>Camion #</th>
                            <th>Franchisé</th>
                            <th>État</th>
                            <th>Emplacement</th>
                            <th>Prochaine maintenance</th>
                            <th>Demande de maintenance</th>
                            <th>Date de livraison</th>
                        </tr>
                    </thead>
                    <tbody id="liste-camions">
                    </tbody>
                </table>
            </div>

            <div id="tab-demandes" class="tab-content" style="display:none;">
                <table>
                    <thead>
                        <tr>
                            <th>Franchisé</th>
                            <th>Nom du camion</th>
                            <th>N° Permis</th>
                            <th>Emplacement</th>
                            <th>Menu</th>
                            <th>Jours d'ouverture</th>
                            <th>Date de demande</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="liste-demandes-camion">
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
    function switchTab(tab) {
        document.getElementById('tab-camions').style.display = tab === 'camions' ? '' : 'none';
        document.getElementById('tab-demandes').style.display = tab === 'demandes' ? '' : 'none';
        document.getElementById('tab-camions-btn').classList.toggle('active', tab === 'camions');
        document.getElementById('tab-demandes-btn').classList.toggle('active', tab === 'demandes');
    }

    document.addEventListener('DOMContentLoaded', async function() {
        const demandesRes = await fetch('../api/camions/demandes.php');
        demandes = await demandesRes.json();
        
        document.getElementById('badge-demandes').textContent = demandes.length;
        document.getElementById('badge-demandes').style.display = demandes.length ? '' : 'none';
        
        const tbodyDem = document.getElementById('liste-demandes-camion');
        demandes.forEach(demande => {
            tbodyDem.innerHTML += `
                <tr>
                    <td>${demande.franchise_nom} ${demande.franchise_prenom}</td>
                    <td><strong>${demande.nom_camion}</strong></td>
                    <td>${demande.numero_permis}</td>
                    <td>${demande.emplacement}</td>
                    <td>${demande.menu}</td>
                    <td>${demande.jours}</td>
                    <td>${new Date(demande.date_demande).toLocaleDateString('fr-FR')}</td>
                    <td>
                        <button class="btn-action" onclick="validerDemande(${demande.id})" style="background: #4caf50;">Valider</button>
                        <button class="btn-action" onclick="refuserDemande(${demande.id})" style="background: #f44336;">Refuser</button>
                    </td>
                </tr>
            `;
        });
        
        const camionsRes = await fetch('../api/camions/list.php');
        let camions = await camionsRes.json();

        camions.sort((a, b) => {
            if ((b.demande_maintenance ? 1 : 0) - (a.demande_maintenance ? 1 : 0) !== 0)
                return (b.demande_maintenance ? 1 : 0) - (a.demande_maintenance ? 1 : 0);
            return a.franchise_nom.localeCompare(b.franchise_nom);
        });

        const tbody = document.getElementById('liste-camions');
        camions.forEach(camion => {
            const urgence = camion.demande_maintenance ? 'camion-urgence' : '';
            tbody.innerHTML += `
                <tr class="accordion-row ${urgence}" onclick="toggleDetails(this)">
                    <td>${camion.id}</td>
                    <td>${camion.franchise_nom}</td>
                    <td>${camion.etat}</td>
                    <td>${camion.emplacement}</td>
                    <td>${camion.prochaine_maintenance ?? '-'}</td>
                    <td>
                        ${camion.demande_maintenance 
                            ? '<span class="camion-maintenance">À traiter</span>' 
                            : '<span class="camion-ok">OK</span>'}
                    </td>
                    <td>
                        ${camion.date_livraison 
                            ? '<span class="date-livraison">'+camion.date_livraison+'</span>' 
                            : '-'}
                    </td>
                </tr>
                <tr class="accordion-details">
                    <td colspan="7">
                        <strong>Détails du camion #${camion.id}</strong><br>
                        Menu : ${camion.menu ?? '-'}<br>
                        Jours d'ouverture : ${camion.jours ?? '-'}<br>
                        Historique entretiens : ${camion.historique_entretiens ?? '-'}<br>
                        <button class="btn-action">Modifier</button>
                        <button class="btn-action">Maintenance</button>
                    </td>
                </tr>
            `;
        });
    });

    function toggleDetails(row) {
        const next = row.nextElementSibling;
        if (next && next.classList.contains('accordion-details')) {
            next.classList.toggle('open');
        }
    }

    async function validerDemande(demandeId) {
        // Récupérer les détails de la demande
        const demande = demandes.find(d => d.id == demandeId);
        if (!demande) {
            alert('Demande non trouvée');
            return;
        }
        
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h3>Valider la demande de camion</h3>
                
                <div class="demande-details">
                    <h4>Détails de la demande :</h4>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                        <strong>Franchisé :</strong> ${demande.franchise_nom} ${demande.franchise_prenom}<br>
                        <strong>Nom du camion :</strong> ${demande.nom_camion}<br>
                        <strong>Emplacement :</strong> ${demande.emplacement}<br>
                        <strong>Menu :</strong> ${demande.menu}<br>
                        <strong>Jours d'ouverture :</strong> ${demande.jours}<br>
                        <strong>N° Permis :</strong> ${demande.numero_permis}
                    </div>
                </div>
                
                <form id="validation-form">
                    <div class="form-group">
                        <label for="date_livraison">Date de livraison prévue *</label>
                        <input type="date" id="date_livraison" name="date_livraison" required 
                               min="${new Date().toISOString().split('T')[0]}"
                               value="${new Date(Date.now() + 14*24*60*60*1000).toISOString().split('T')[0]}">
                        <small style="color: #666;">La livraison est fixée par défaut à 2 semaines</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="commentaire_validation">Commentaire (optionnel)</label>
                        <textarea id="commentaire_validation" name="commentaire" 
                                  placeholder="Instructions particulières, notes..."></textarea>
                    </div>
                    
                    <div style="background: #e8f5e8; border: 1px solid #4caf50; border-radius: 6px; padding: 1rem; margin: 1rem 0;">
                        <strong>Validation de la demande :</strong><br>
                        <small>
                            • Un camion sera automatiquement créé<br>
                            • Une immatriculation unique sera générée<br>
                            • Le franchisé sera notifié de la validation
                        </small>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                        <button type="submit" class="btn-primary" style="background: #4caf50;">Valider la demande</button>
                    </div>
                </form>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        document.getElementById('validation-form').onsubmit = async function(e) {
            e.preventDefault();
            await submitValidationDemande(demandeId, 'valider');
        };
    }

    async function refuserDemande(demandeId) {
        const demande = demandes.find(d => d.id == demandeId);
        if (!demande) {
            alert('Demande non trouvée');
            return;
        }
        
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h3>Refuser la demande de camion</h3>
                
                <div class="demande-details">
                    <h4>Demande de :</h4>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                        <strong>${demande.franchise_nom} ${demande.franchise_prenom}</strong><br>
                        <small>Camion : ${demande.nom_camion} | Emplacement : ${demande.emplacement}</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="raison_refus">Raison du refus *</label>
                    <textarea id="raison_refus" required 
                              placeholder="Expliquez pourquoi cette demande est refusée (documents manquants, emplacement non autorisé, etc.)"></textarea>
                </div>
                
                <div style="background: #ffebee; border: 1px solid #f44336; border-radius: 6px; padding: 1rem; margin: 1rem 0;">
                    <strong>Attention :</strong><br>
                    <small>Le franchisé recevra une notification de refus avec votre commentaire.</small>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                    <button type="button" class="btn-danger" onclick="confirmRefus(${demandeId})">Confirmer le refus</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }

    async function confirmRefus(demandeId) {
        const raison = document.getElementById('raison_refus').value;
        if (!raison.trim()) {
            alert('Veuillez indiquer une raison pour le refus');
            return;
        }
        
        await submitValidationDemande(demandeId, 'refuser', { commentaire: raison });
    }

    async function submitValidationDemande(demandeId, action, extraData = {}) {
        try {
            const formData = action === 'valider' ? {
                demande_id: demandeId,
                action: action,
                date_livraison: document.getElementById('date_livraison')?.value,
                commentaire: document.getElementById('commentaire_validation')?.value,
                ...extraData
            } : {
                demande_id: demandeId,
                action: action,
                ...extraData
            };
            
            const response = await fetch('../api/camions/validate_demande.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert(result.message, 'success');
                closeModal();
                location.reload();
            } else {
                showAlert('Erreur: ' + result.message, 'error');
            }
        } catch (error) {
            showAlert('Erreur réseau lors de la validation', 'error');
            console.error('Erreur:', error);
        }
    }

    function closeModal() {
        const modal = document.querySelector('.modal');
        if (modal) modal.remove();
    }

    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.style.cssText = `
            position: fixed; top: 20px; right: 20px; z-index: 1001;
            padding: 1rem 1.5rem; border-radius: 6px; font-weight: bold;
            max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            ${type === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 
              'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'}
        `;
        alertDiv.textContent = message;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    let demandes = [];

    document.addEventListener('DOMContentLoaded', async function() {
        const demandesRes = await fetch('../api/camions/demandes.php');
        demandes = await demandesRes.json();

        document.getElementById('badge-demandes').textContent = demandes.length;
        document.getElementById('badge-demandes').style.display = demandes.length ? '' : 'none';
        
        const tbodyDem = document.getElementById('liste-demandes-camion');
        demandes.forEach(demande => {
            tbodyDem.innerHTML += `
                <tr>
                    <td>${demande.franchise_nom} ${demande.franchise_prenom}</td>
                    <td><strong>${demande.nom_camion}</strong></td>
                    <td>${demande.numero_permis}</td>
                    <td>${demande.emplacement}</td>
                    <td>${demande.menu}</td>
                    <td>${demande.jours}</td>
                    <td>${new Date(demande.date_demande).toLocaleDateString('fr-FR')}</td>
                    <td>
                        <button class="btn-action" onclick="validerDemande(${demande.id})" style="background: #4caf50;">Valider</button>
                        <button class="btn-action" onclick="refuserDemande(${demande.id})" style="background: #f44336;">Refuser</button>
                    </td>
                </tr>
            `;
        });
        
        const camionsRes = await fetch('../api/camions/list.php');
        let camions = await camionsRes.json();

        camions.sort((a, b) => {
            if ((b.demande_maintenance ? 1 : 0) - (a.demande_maintenance ? 1 : 0) !== 0)
                return (b.demande_maintenance ? 1 : 0) - (a.demande_maintenance ? 1 : 0);
            return a.franchise_nom.localeCompare(b.franchise_nom);
        });

        const tbody = document.getElementById('liste-camions');
        camions.forEach(camion => {
            const urgence = camion.demande_maintenance ? 'camion-urgence' : '';
            tbody.innerHTML += `
                <tr class="accordion-row ${urgence}" onclick="toggleDetails(this)">
                    <td>${camion.id}</td>
                    <td>${camion.franchise_nom}</td>
                    <td>${camion.etat}</td>
                    <td>${camion.emplacement}</td>
                    <td>${camion.prochaine_maintenance ?? '-'}</td>
                    <td>
                        ${camion.demande_maintenance 
                            ? '<span class="camion-maintenance">À traiter</span>' 
                            : '<span class="camion-ok">OK</span>'}
                    </td>
                    <td>
                        ${camion.date_livraison 
                            ? '<span class="date-livraison">'+camion.date_livraison+'</span>' 
                            : '-'}
                    </td>
                </tr>
                <tr class="accordion-details">
                    <td colspan="7">
                        <strong>Détails du camion #${camion.id}</strong><br>
                        Menu : ${camion.menu ?? '-'}<br>
                        Jours d'ouverture : ${camion.jours ?? '-'}<br>
                        Historique entretiens : ${camion.historique_entretiens ?? '-'}<br>
                        <button class="btn-action">Modifier</button>
                        <button class="btn-action">Maintenance</button>
                    </td>
                </tr>
            `;
        });
    });
    </script>
</body>
</html>