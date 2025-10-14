// Données d'événements (format YYYY-MM-DD)
const EVENTS = {
    "2025-10-02": ["Consultation"],
    "2025-10-04": ["Premières complications"]
};

const monthEl = document.getElementById('month');
const yearEl = document.getElementById('year');
const daysEl  = document.getElementById('days');
const btnPrev = document.getElementById('prev');
const btnNext = document.getElementById('next');

const locale = "fr-FR";
const startOnMonday = true;

function pad(n){ return String(n).padStart(2,'0'); }
function keyOf(d){ return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; }
function capitalizedMonthName(d){
    const m = d.toLocaleString('fr-FR', { month: 'long' });
    return m.replace(/^./, c => c.toUpperCase());
}

function renderCalendar(year, monthIndex){
    monthEl.textContent = `${capitalizedMonthName(new Date(year, monthIndex, 1))}`; // Titre du mois
    yearEl.textContent = year;

    const first = new Date(year, monthIndex, 1);
    const jsDay = first.getDay();
    const shift = startOnMonday ? (jsDay === 0 ? 6 : jsDay - 1) : jsDay;
    const startDate = new Date(year, monthIndex, 1 - shift);

    daysEl.innerHTML = "";
    const todayKey = keyOf(new Date());

    // 5 lignes x 7 colonnes (35 jours)
    for(let i=0;i<35;i++){
        const d = new Date(startDate);
        d.setDate(startDate.getDate()+i);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'day';
        btn.dataset.date = keyOf(d);
        btn.innerHTML = `<span class="num">${d.getDate()}</span>`;

        if(d.getMonth() !== monthIndex) btn.classList.add('other-month');
        if(keyOf(d) === todayKey) btn.classList.add('today');
        if(EVENTS[keyOf(d)]) btn.classList.add('has-event');

        daysEl.appendChild(btn);
    }
}

const now = new Date();
let currentYear  = now.getFullYear();
let currentMonth = now.getMonth();


renderCalendar(currentYear, currentMonth);


btnPrev.addEventListener('click', () => {
    currentMonth--;
    if (currentMonth < 0) { currentMonth = 11; currentYear--; }
    renderCalendar(currentYear, currentMonth);
});

btnNext.addEventListener('click', () => {
    currentMonth++;
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    renderCalendar(currentYear, currentMonth);
});

const aside = document.getElementById('aside')
const showAsideBtn = document.getElementById('aside-show-btn')
function toggleAside() {
    aside.classList.toggle('active-aside');
}