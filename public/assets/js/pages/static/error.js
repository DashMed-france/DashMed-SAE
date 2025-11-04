function toggleDetails(){
    const btn = document.getElementById('details-btn');
    const box = document.getElementById('error-details');
    const open = box.style.display === 'block';
    box.style.display = open ? 'none' : 'block';
    btn.setAttribute('aria-expanded', String(!open));
    btn.textContent = open ? 'Afficher les détails techniques' : 'Masquer les détails techniques';
    box.setAttribute('aria-hidden', String(open));
}