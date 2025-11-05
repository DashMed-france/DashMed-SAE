// Exemple de chambres simulées (plus tard, ce sera remplacé par la requête SQL)
const chambres = [
    { id: 1, nom: '1' },
    { id: 2, nom: '2' },
    { id: 3, nom: '3' },
    { id: 4, nom: '4' },
    { id: 5, nom: '5' },
    { id: 6, nom: '6' },
    { id: 7, nom: '7' },
    { id: 8, nom: '8' },
    { id: 9, nom: '9' },
    { id: 10, nom: '10' },
    { id: 11, nom: '11' },
    { id: 12, nom: '12' },
    { id: 13, nom: '13' },
    { id: 14, nom: '14' },
    { id: 15, nom: '15' },
    { id: 16, nom: '16' },
    { id: 17, nom: '17' },
    { id: 18, nom: '18' },
    { id: 19, nom: '19' },
    { id: 20, nom: '20' },
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
select.value = saved || '1';

select.addEventListener('change', e => {
    localStorage.setItem('selectedChamber', e.target.value);
    console.log('Chambre sélectionnée :', e.target.value);
});
