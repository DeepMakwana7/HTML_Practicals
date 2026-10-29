document.documentElement.classList.add('js');

const registrationForm = document.querySelector('[data-registration-form]');
if (registrationForm) {
  const password = registrationForm.querySelector('[name="password"]');
  const confirmation = registrationForm.querySelector('[data-password-confirmation]');
  const validatePasswordConfirmation = () => {
    confirmation.setCustomValidity(
      confirmation.value === password.value ? '' : 'Passwords do not match.'
    );
  };

  password.addEventListener('input', validatePasswordConfirmation);
  confirmation.addEventListener('input', validatePasswordConfirmation);
  registrationForm.addEventListener('submit', validatePasswordConfirmation);
}

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

const dataList = document.querySelector('[data-json-list]');
if (dataList) {
  const listType = dataList.dataset.jsonList;
  const controls = document.querySelector(`[data-list-controls="${listType}"]`);
  const status = document.querySelector('[data-list-status]');
  const pagination = document.querySelector('[data-pagination]');
  const pageInfo = pagination?.querySelector('[data-page-info]');
  const previousButton = pagination?.querySelector('[data-page-prev]');
  const nextButton = pagination?.querySelector('[data-page-next]');
  const search = controls?.querySelector('[data-search]');
  const category = controls?.querySelector('[data-category]');
  const sort = controls?.querySelector('[data-sort]');
  const pageSize = 3;
  let items = [];
  let currentPage = 1;

  const formatDate = (date, options) => new Intl.DateTimeFormat('en', options).format(new Date(`${date}T00:00:00`));

  const makeLink = (item) => {
    if (!item.link || !item.linkText) return null;
    const link = document.createElement('a');
    link.className = 'feature__link';
    link.href = item.link;
    link.textContent = item.linkText;
    return link;
  };

  const renderEvent = (event) => {
    const article = document.createElement('article');
    article.className = 'event';

    const date = document.createElement('div');
    date.className = 'event__date';
    const day = document.createElement('span');
    day.className = 'event__day';
    day.textContent = formatDate(event.date, { day: '2-digit' });
    const month = document.createElement('span');
    month.className = 'event__month';
    month.textContent = formatDate(event.date, { month: 'long', year: 'numeric' });
    date.append(day, month);

    const body = document.createElement('div');
    body.className = 'event__body';
    const badge = document.createElement('span');
    badge.className = 'badge badge--brand';
    badge.textContent = event.category;
    const title = document.createElement('h2');
    title.textContent = event.title;
    const description = document.createElement('p');
    description.textContent = event.description;
    const meta = document.createElement('div');
    meta.className = 'event__meta';
    const location = document.createElement('span');
    location.textContent = `Location: ${event.location}`;
    const time = document.createElement('span');
    time.textContent = `Time: ${event.time}`;
    meta.append(location, time);

    const foot = document.createElement('div');
    foot.className = 'event__foot';
    const note = document.createElement('span');
    note.className = 't-small t-muted';
    note.textContent = event.note;
    foot.append(note);
    const link = makeLink(event);
    if (link) foot.append(link);
    body.append(badge, title, description, meta, foot);
    article.append(date, body);
    return article;
  };

  const renderNotice = (notice) => {
    const article = document.createElement('article');
    const categoryClass = notice.category.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    article.className = `notice notice--${categoryClass}`;

    const heading = document.createElement('div');
    heading.className = 'cluster cluster--between';
    const title = document.createElement('h2');
    title.className = 't-h3';
    title.textContent = notice.title;
    const badge = document.createElement('span');
    badge.className = 'badge badge--brand';
    badge.textContent = notice.category;
    heading.append(title, badge);

    const meta = document.createElement('div');
    meta.className = 'notice__meta';
    const posted = document.createElement('span');
    posted.textContent = `Posted ${formatDate(notice.date, { month: 'long', day: 'numeric', year: 'numeric' })}`;
    meta.append(posted);

    const description = document.createElement('p');
    description.textContent = notice.description;
    article.append(heading, meta, description);
    const link = makeLink(notice);
    if (link) article.append(link);
    return article;
  };

  const render = () => {
    const query = search.value.trim().toLocaleLowerCase();
    const selectedCategory = category.value;
    const [sortBy, direction] = sort.value.split('-');
    const filtered = items
      .filter((item) => (!selectedCategory || item.category === selectedCategory)
        && (!query || Object.values(item).some((value) => String(value).toLocaleLowerCase().includes(query))))
      .sort((first, second) => {
        const comparison = sortBy === 'date'
          ? first.date.localeCompare(second.date)
          : first.title.localeCompare(second.title);
        return direction === 'desc' ? -comparison : comparison;
      });
    const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
    currentPage = Math.min(currentPage, pageCount);
    const pageItems = filtered.slice((currentPage - 1) * pageSize, currentPage * pageSize);

    dataList.replaceChildren(...pageItems.map(listType === 'events' ? renderEvent : renderNotice));
    status.textContent = filtered.length
      ? `Showing ${pageItems.length} of ${filtered.length} ${listType}.`
      : `No ${listType} match your search and filters.`;
    pagination.hidden = filtered.length <= pageSize;
    pageInfo.textContent = `Page ${currentPage} of ${pageCount}`;
    previousButton.disabled = currentPage === 1;
    nextButton.disabled = currentPage === pageCount;
  };

  const loadItems = async () => {
    try {
      const response = await fetch(dataList.dataset.source);
      if (!response.ok) throw new Error(`Request failed with status ${response.status}`);
      const data = await response.json();
      if (!Array.isArray(data) || data.some((item) => !item.title || !item.category || !item.date)) {
        throw new Error('The JSON file does not contain a valid list of items.');
      }
      items = data;
      [...new Set(items.map((item) => item.category))].sort().forEach((value) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        category.append(option);
      });
      render();
    } catch (error) {
      console.error(`Could not load ${listType}.`, error);
      status.textContent = `Unable to load ${listType}. Please try again later.`;
      dataList.replaceChildren();
      pagination.hidden = true;
    }
  };

  [search, category, sort].forEach((control) => {
    control.addEventListener('input', () => {
      currentPage = 1;
      render();
    });
    control.addEventListener('change', () => {
      currentPage = 1;
      render();
    });
  });
  previousButton.addEventListener('click', () => {
    currentPage -= 1;
    render();
  });
  nextButton.addEventListener('click', () => {
    currentPage += 1;
    render();
  });
  loadItems();
}

const studentProfile = document.querySelector('[data-student-profile]');
if (studentProfile) {
  const status = studentProfile.querySelector('[data-student-status]');
  const details = studentProfile.querySelector('[data-student-details]');

  const loadStudentProfile = async () => {
    try {
      const response = await fetch(studentProfile.dataset.source);
      if (!response.ok) throw new Error(`Request failed with status ${response.status}`);
      const student = await response.json();
      const requiredFields = [
        'firstName', 'fullName', 'studentId', 'contactNumber', 'email',
        'college', 'branch', 'currentAssignments', 'semesterGpa', 'attendance',
        'upcomingDeadline'
      ];
      if (requiredFields.some((field) => student[field] === undefined || student[field] === null || student[field] === '')) {
        throw new Error('The JSON file is missing required student fields.');
      }

      studentProfile.querySelectorAll('[data-student-field]').forEach((element) => {
        element.textContent = student[element.dataset.studentField];
      });
      studentProfile.querySelector('[data-student-initials]').textContent = student.fullName
        .split(/\s+/)
        .map((name) => name[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

      const rows = [
        ['Full name', student.fullName],
        ['Roll number', student.studentId],
        ['Contact number', student.contactNumber],
        ['Email', student.email],
        ['College', student.college],
        ['Branch', student.branch]
      ].map(([label, value]) => {
        const row = document.createElement('div');
        const term = document.createElement('dt');
        term.textContent = label;
        const description = document.createElement('dd');
        description.textContent = value;
        row.append(term, description);
        return row;
      });
      details.replaceChildren(...rows);
      status.remove();
    } catch (error) {
      console.error('Could not load student profile.', error);
      status.textContent = 'Unable to load student data. Please try again later.';
    }
  };

  loadStudentProfile();
}
