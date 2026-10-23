const toggle = document.querySelector('.nav-toggle');
const drawer = document.querySelector('.nav-drawer');

if (toggle && drawer) {
  toggle.addEventListener('click', () => {
    const expanded = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!expanded));
    drawer.setAttribute('data-open', String(!expanded));
  });
}

document.documentElement.classList.add('js');
