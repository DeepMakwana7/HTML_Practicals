const eventManager = document.querySelector('[data-event-manager]');

if (eventManager) {
  const apiUrl = eventManager.dataset.api;
  const csrfToken = eventManager.dataset.csrfToken;
  const form = eventManager.querySelector('[data-event-form]');
  const eventList = eventManager.querySelector('[data-event-list]');
  const listStatus = eventManager.querySelector('[data-event-list-status]');
  const status = eventManager.querySelector('[data-event-status]');
  const heading = eventManager.querySelector('[data-form-heading]');
  const eyebrow = eventManager.querySelector('[data-form-eyebrow]');
  const submitButton = eventManager.querySelector('[data-submit-event]');
  const cancelButton = eventManager.querySelector('[data-cancel-edit]');
  const posterPreview = eventManager.querySelector('[data-current-poster]');
  let events = [];

  const readResponse = async (response) => {
    let payload;
    try {
      payload = await response.json();
    } catch (error) {
      throw new Error('The server returned an invalid response.');
    }

    if (!response.ok || !payload.success) {
      const message = payload.errors?.join(' ') || payload.message || 'The request could not be completed.';
      throw new Error(message);
    }
    return payload;
  };

  const showStatus = (message, type) => {
    status.textContent = message;
    status.className = `alert alert--${type}`;
    status.hidden = false;
  };

  const renderEvents = () => {
    eventList.replaceChildren();
    if (events.length === 0) {
      listStatus.textContent = 'No events have been added yet.';
      return;
    }

    listStatus.textContent = `${events.length} event${events.length === 1 ? '' : 's'} available.`;
    events.forEach((event) => {
      const card = document.createElement('article');
      card.className = 'card event-admin-card';

      if (event.poster) {
        const poster = document.createElement('img');
        poster.className = 'event-admin-poster';
        poster.src = event.poster;
        poster.alt = `Poster for ${event.title}`;
        card.append(poster);
      }

      const badge = document.createElement('span');
      badge.className = 'badge badge--brand';
      badge.textContent = event.category;
      const title = document.createElement('h3');
      title.className = 'card__title';
      title.textContent = event.title;
      const meta = document.createElement('p');
      meta.className = 'card__meta';
      meta.textContent = `${event.date} · ${event.location}`;
      const description = document.createElement('p');
      description.textContent = event.description;

      const actions = document.createElement('div');
      actions.className = 'form__actions';
      const editButton = document.createElement('button');
      editButton.className = 'btn btn--soft btn--sm';
      editButton.type = 'button';
      editButton.textContent = 'Edit';
      editButton.addEventListener('click', () => beginEdit(event));
      const deleteButton = document.createElement('button');
      deleteButton.className = 'btn btn--danger btn--sm';
      deleteButton.type = 'button';
      deleteButton.textContent = 'Delete';
      deleteButton.addEventListener('click', () => deleteEvent(event.id));
      actions.append(editButton, deleteButton);
      card.append(badge, title, meta, description, actions);
      eventList.append(card);
    });
  };

  const loadEvents = async () => {
    listStatus.textContent = 'Loading events…';
    const response = await fetch(apiUrl, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    const payload = await readResponse(response);
    events = payload.events;
    renderEvents();
  };

  const resetForm = () => {
    form.reset();
    form.elements.namedItem('id').value = '';
    heading.textContent = 'Add an event';
    eyebrow.textContent = 'New event';
    submitButton.textContent = 'Add event';
    cancelButton.hidden = true;
    posterPreview.hidden = true;
    posterPreview.removeAttribute('src');
  };

  const beginEdit = (event) => {
    ['id', 'title', 'category', 'date', 'description', 'location', 'time', 'note', 'link', 'linkText']
      .forEach((field) => {
        form.elements.namedItem(field).value = event[field] || '';
      });
    heading.textContent = 'Update event';
    eyebrow.textContent = 'Edit event';
    submitButton.textContent = 'Save changes';
    cancelButton.hidden = false;
    posterPreview.hidden = !event.poster;
    if (event.poster) posterPreview.src = event.poster;
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const deleteEvent = async (id) => {
    if (!window.confirm('Delete this event?')) return;
    const body = new FormData();
    body.set('action', 'delete');
    body.set('id', id);

    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
        credentials: 'same-origin',
        body,
      });
      const payload = await readResponse(response);
      events = payload.events;
      renderEvents();
      if (form.elements.namedItem('id').value === id) resetForm();
      showStatus(payload.message, 'success');
    } catch (error) {
      showStatus(error.message, 'danger');
    }
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;

    const body = new FormData(form);
    const isEditing = body.get('id') !== '';
    body.set('action', isEditing ? 'update' : 'add');
    submitButton.disabled = true;

    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
        credentials: 'same-origin',
        body,
      });
      const payload = await readResponse(response);
      events = payload.events;
      renderEvents();
      resetForm();
      showStatus(payload.message, 'success');
    } catch (error) {
      showStatus(error.message, 'danger');
    } finally {
      submitButton.disabled = false;
    }
  });

  cancelButton.addEventListener('click', resetForm);
  loadEvents().catch((error) => {
    listStatus.textContent = 'Unable to load events.';
    showStatus(error.message, 'danger');
  });
}
