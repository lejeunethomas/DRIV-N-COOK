document.querySelector('#login-form form').onsubmit = async function(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    const alert = document.getElementById('login-alert');
    alert.style.display = 'none';

    try {
        const res = await fetch('api/users/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        alert.style.display = 'block';
        alert.className = 'alert ' + (data.success ? 'alert-success' : 'alert-error');
        alert.textContent = data.message;
        if (data.success) {
            if (data.role === 'admin') {
                setTimeout(() => window.location.href = 'dashboard/index.html', 1000);
            } else if (data.role === 'franchise') {
                setTimeout(() => window.location.href = 'franchise/compte.html', 1000);
            } else if (data.role === 'client') {
                setTimeout(() => window.location.href = 'index.html', 1000);
            }
        }
    } catch (err) {
        alert.style.display = 'block';
        alert.className = 'alert alert-error';
        alert.textContent = "Erreur réseau ou serveur. Veuillez réessayer.";
        console.error(err);
    }
};