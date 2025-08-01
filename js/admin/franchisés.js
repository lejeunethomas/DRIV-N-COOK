fetch('../api/users/get_all.php')
    .then(res => res.json())
    .then(data => {
        const tbody = document.querySelector('#franchise-table tbody');
        tbody.innerHTML = '';
        data.forEach(fr => {
            tbody.innerHTML += `
                <tr>
                    <td>${fr.nom}</td>
                    <td>${fr.email}</td>
                    <td>${fr.date_inscription}</td>
                    <td>
                        <button class="btn">Modifier</button>
                        <button class="btn">Supprimer</button>
                    </td>
                </tr>
            `;
        });
    });

// Ajouter la fonction pour le bouton "Ajouter un franchisé"
document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('add-franchise-btn');
    if (addBtn) {
        addBtn.onclick = function() {
            window.location.href = '../login.html?register=1';
        };
    }
});