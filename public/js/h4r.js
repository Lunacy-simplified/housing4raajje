/*
 * Housing 4 Raajje (H4R): the only script in the app. Both jobs are for small screens,
 * and every page still works without it (the menu stays open and tables scroll sideways).
 * The matching styles are at the end of css/h4r.css.
 */

// 1. Menu button: shows and hides the main menu on screens narrower than 900px.
const button = document.querySelector('.menu-btn');
const menu = document.getElementById('menu');
if (button && menu) {
    button.addEventListener('click', () => {
        const open = menu.classList.toggle('open');
        button.setAttribute('aria-expanded', open);
    });
}

// 2. Tables: copy each column name onto its cells, so a phone can show a row as a stacked list.
for (const table of document.querySelectorAll('.tbl')) {
    const heads = table.tHead ? [...table.tHead.rows[0].cells] : [];
    const names = heads.map((cell) => (cell.querySelector('.sr') ? '' : cell.textContent.trim()));
    for (const row of table.tBodies[0]?.rows ?? []) {
        if (row.cells.length !== names.length) continue; // "nothing here yet" rows span every column
        [...row.cells].forEach((cell, i) => { cell.dataset.label = names[i]; });
    }
}
