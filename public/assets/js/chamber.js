// Exemple de chambres simulées (plus tard, ce sera remplacé par la requête SQL)
const chambres = [
    { id: 101, nom: '101' },
    { id: 102, nom: '102' },
    { id: 103, nom: '103' },
    { id: 104, nom: '104' }
];

const select = document.getElementById('chamber-select');

select.innerHTML = '';

const defaultOption = document.createElement('option');
defaultOption.textContent = 'Sélectionnez une chambre';
defaultOption.disabled = true;
select.appendChild(defaultOption);

chambres.forEach(ch => {
    const opt = document.createElement('option');
    opt.value = ch.id;
    opt.textContent = ch.nom;
    select.appendChild(opt);
});

const saved = localStorage.getItem('selectedChamber');
select.value = saved || '101';

select.addEventListener('change', e => {
    localStorage.setItem('selectedChamber', e.target.value);
    console.log('Chambre sélectionnée :', e.target.value);
});
