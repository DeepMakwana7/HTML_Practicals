document.documentElement.classList.add('js');

const toggle = document.querySelector('.nav-toggle');
const drawer = document.querySelector('.nav-drawer');

if (toggle && drawer) {
  const closeMenu = () => {
    toggle.setAttribute('aria-expanded', 'false');
    drawer.setAttribute('data-open', 'false');
  };

  toggle.addEventListener('click', () => {
    const expanded = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!expanded));
    drawer.setAttribute('data-open', String(!expanded));
  });

  drawer.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMenu();
  });
}

const navActions = document.querySelector('.nav-actions');
if (navActions) {
  const themeToggle = document.createElement('button');
  themeToggle.className = 'btn btn--ghost btn--sm theme-toggle';
  themeToggle.type = 'button';
  themeToggle.setAttribute('aria-pressed', 'false');
  navActions.append(themeToggle);

  const updateTheme = (isDark) => {
    document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
    themeToggle.textContent = isDark ? 'Light mode' : 'Dark mode';
    themeToggle.setAttribute('aria-pressed', String(isDark));
  };

  let isDark = false;
  try {
    isDark = localStorage.getItem('studenthub-theme') === 'dark';
  } catch (error) {
    console.warn('Could not read the saved theme preference.', error);
  }
  updateTheme(isDark);

  themeToggle.addEventListener('click', () => {
    isDark = !isDark;
    updateTheme(isDark);
    try {
      localStorage.setItem('studenthub-theme', isDark ? 'dark' : 'light');
    } catch (error) {
      console.warn('Could not save the theme preference.', error);
    }
  });
}

const banner = document.querySelector('[data-notice-banner]');
if (banner) {
  banner.querySelector('.notice-banner__close')?.addEventListener('click', () => {
    banner.remove();
  });
}

const slider = document.querySelector('[data-slider]');
if (slider) {
  const slides = Array.from(slider.querySelectorAll('[data-slide]'));
  const count = slider.querySelector('[data-slide-count]');
  let activeSlide = 0;

  const showSlide = (index) => {
    activeSlide = (index + slides.length) % slides.length;
    slides.forEach((slide, slideIndex) => {
      slide.hidden = slideIndex !== activeSlide;
    });
    if (count) count.textContent = `${activeSlide + 1} / ${slides.length}`;
  };

  slider.querySelector('[data-slide-prev]')?.addEventListener('click', () => {
    showSlide(activeSlide - 1);
  });
  slider.querySelector('[data-slide-next]')?.addEventListener('click', () => {
    showSlide(activeSlide + 1);
  });
}

const dialog = document.querySelector('[data-tour-dialog]');
if (dialog && typeof dialog.showModal === 'function') {
  document.querySelectorAll('[data-open-dialog]').forEach((button) => {
    button.addEventListener('click', () => dialog.showModal());
  });

  dialog.querySelector('[data-close-dialog]')?.addEventListener('click', () => {
    dialog.close();
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });
}

const registrationForm = document.querySelector('[data-registration-form]');
if (registrationForm) {
  const namePattern = /^[A-Za-z][A-Za-z .'-]{1,59}$/;
  const emailPattern = /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/;
  const mobilePattern = /^[6-9]\d{9}$/;
  const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d\s])\S{8,}$/;
  const password = registrationForm.elements.namedItem('password');
  const confirmation = registrationForm.elements.namedItem('password-confirmation');
  const message = registrationForm.querySelector('[data-registration-message]');

  const validateField = (field) => {
    let error = '';

    if (field.value) {
      switch (field.name) {
        case 'name':
          if (!namePattern.test(field.value.trim())) {
            error = 'Enter a name using 2–60 letters, spaces, apostrophes, periods, or hyphens.';
          }
          break;
        case 'email':
          if (!emailPattern.test(field.value.trim())) {
            error = 'Enter a valid email address.';
          }
          break;
        case 'mobile':
          if (!mobilePattern.test(field.value)) {
            error = 'Enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.';
          }
          break;
        case 'password':
          if (!passwordPattern.test(field.value)) {
            error = 'Use at least 8 characters, including uppercase, lowercase, a number, and a symbol; do not use spaces.';
          }
          break;
        case 'password-confirmation':
          if (field.value !== password.value) {
            error = 'Passwords do not match.';
          }
          break;
      }
    }

    field.setCustomValidity(error);
  };

  registrationForm.addEventListener('input', (event) => {
    if (event.target instanceof HTMLInputElement) {
      validateField(event.target);
      if (event.target === password) validateField(confirmation);
    }
    message.textContent = '';
  });

  registrationForm.addEventListener('submit', (event) => {
    event.preventDefault();
    registrationForm.querySelectorAll('input').forEach(validateField);

    if (!registrationForm.checkValidity()) {
      registrationForm.reportValidity();
      message.textContent = '';
      return;
    }

    message.textContent = 'Your details passed validation. This demo does not submit data to a server.';
  });
}
