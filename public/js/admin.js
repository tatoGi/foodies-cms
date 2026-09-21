(() => {
  const body = document.body;
  if (!body) {
    return;
  }

  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarBackdrop = document.getElementById('adminSidebarBackdrop');
  const themeToggle = document.getElementById('themeToggle');
  const searchToggle = document.getElementById('headerSearchToggle');
  const searchForm = document.getElementById('adminSearchForm');

  const isMobileMedia = window.matchMedia('(max-width: 767.98px)');
  const isTabletMedia = window.matchMedia('(min-width: 768px) and (max-width: 1279.98px)');

  const applySidebarState = (collapsed) => {
    body.classList.toggle('admin-sidebar-collapsed', collapsed);
  };

  const applyTheme = (theme) => {
    body.classList.toggle('theme-dark', theme === 'dark');
  };

  const setMobileSidebarOpen = (open) => {
    body.classList.toggle('admin-sidebar-open', open);
    body.classList.toggle('admin-sidebar-locked', open);
  };

  const setMobileSearchOpen = (open) => {
    body.classList.toggle('admin-header-search-open', open);
    if (searchToggle) {
      searchToggle.setAttribute('aria-expanded', String(open));
    }

    if (open && searchForm) {
      const searchInput = searchForm.querySelector('input[type="search"]');
      searchInput?.focus();
    }
  };

  const syncSidebarMode = () => {
    const isMobile = isMobileMedia.matches;
    const isTablet = isTabletMedia.matches;

    if (isMobile) {
      body.classList.remove('admin-sidebar-tablet');
      body.classList.remove('admin-sidebar-tablet-expanded');
      body.classList.remove('admin-sidebar-collapsed');
      return;
    }

    setMobileSidebarOpen(false);
    body.classList.remove('admin-sidebar-tablet-expanded');

    if (isTablet) {
      body.classList.add('admin-sidebar-tablet');
      body.classList.remove('admin-sidebar-collapsed');
    } else {
      body.classList.remove('admin-sidebar-tablet');
      const savedSidebar = localStorage.getItem('adminSidebarCollapsed');
      if (savedSidebar !== null) {
        applySidebarState(savedSidebar === 'true');
      }
    }
  };

  const savedTheme = localStorage.getItem('adminTheme');
  if (savedTheme) {
    applyTheme(savedTheme);
  }

  syncSidebarMode();

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      if (isMobileMedia.matches) {
        setMobileSidebarOpen(!body.classList.contains('admin-sidebar-open'));
        return;
      }

      if (isTabletMedia.matches) {
        body.classList.toggle('admin-sidebar-tablet-expanded');
        return;
      }

      const collapsed = !body.classList.contains('admin-sidebar-collapsed');
      applySidebarState(collapsed);
      localStorage.setItem('adminSidebarCollapsed', String(collapsed));
    });
  }

  if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener('click', () => {
      setMobileSidebarOpen(false);
    });
  }

  document.querySelectorAll('.admin-sidebar a').forEach((link) => {
    link.addEventListener('click', () => {
      if (isMobileMedia.matches) {
        setMobileSidebarOpen(false);
      }
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      setMobileSidebarOpen(false);
      setMobileSearchOpen(false);
    }
  });

  if (searchToggle) {
    searchToggle.addEventListener('click', () => {
      setMobileSearchOpen(!body.classList.contains('admin-header-search-open'));
    });
  }

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const isDark = !body.classList.contains('theme-dark');
      applyTheme(isDark ? 'dark' : 'light');
      localStorage.setItem('adminTheme', isDark ? 'dark' : 'light');
    });
  }

  window.addEventListener('resize', () => {
    syncSidebarMode();

    if (!isMobileMedia.matches) {
      setMobileSearchOpen(false);
    }
  });

  document.querySelectorAll('.table-responsive[data-mobile-columns]').forEach((wrapper) => {
    if (!wrapper.querySelector('.table-col-secondary')) {
      return;
    }

    const toggleButton = document.createElement('button');
    toggleButton.type = 'button';
    toggleButton.className = 'btn btn-sm btn-light-soft table-mobile-toggle';
    toggleButton.innerHTML = '<i class="bi bi-layout-three-columns me-1"></i>Show More';

    toggleButton.addEventListener('click', () => {
      const isExpanded = wrapper.classList.toggle('table-mobile-expanded');
      toggleButton.innerHTML = isExpanded
        ? '<i class="bi bi-layout-three-columns me-1"></i>Show Less'
        : '<i class="bi bi-layout-three-columns me-1"></i>Show More';
    });

    wrapper.parentElement?.insertBefore(toggleButton, wrapper);
  });
})();

(() => {
  const quillEditors = new WeakMap();

  const normalizeHtml = (value) => {
    if (typeof value !== 'string') {
      return '';
    }

    const trimmed = value.trim();
    if (trimmed === '') {
      return '';
    }

    const hasEmbeddedMedia = /<(img|video|iframe)\b/i.test(trimmed);
    const withoutEmptyBlocks = trimmed
      .replace(/<p><br\s*\/?><\/p>/gi, '')
      .replace(/<br\s*\/?>/gi, '')
      .replace(/&nbsp;/gi, ' ');
    const textOnly = withoutEmptyBlocks.replace(/<[^>]*>/g, '').trim();

    if (!hasEmbeddedMedia && textOnly === '' && withoutEmptyBlocks.replace(/\s/g, '') === '') {
      return '';
    }

    return trimmed;
  };

  const syncTextareaValue = (textarea, quill) => {
    if (!textarea || !quill) {
      return;
    }

    textarea.value = normalizeHtml(quill.root.innerHTML);
  };

  const initializeQuillEditors = (root = document) => {
    if (!window.Quill) {
      return;
    }

    root.querySelectorAll('textarea.editor-field, textarea[data-editor="quill"]').forEach((textarea) => {
      if (textarea.closest('template')) {
        return;
      }

      if (textarea.dataset.quillInitialized === 'true') {
        return;
      }

      const container = document.createElement('div');
      container.className = 'admin-quill-editor';
      textarea.insertAdjacentElement('afterend', container);
      textarea.classList.add('d-none');
      const mediaButton = document.createElement('button');
      mediaButton.type = 'button';
      mediaButton.className = 'btn btn-outline-secondary btn-sm admin-quill-media-button';
      mediaButton.innerHTML = '<i class="bi bi-image me-1"></i>Choose from Media';
      container.insertAdjacentElement('afterend', mediaButton);

      const toolbarOptions = [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link', 'image', 'blockquote'],
        [{ align: [] }],
        ['clean'],
      ];

      const resolveMediaImageUrl = (item) => {
        if (!item) {
          return '';
        }

        if (item.url) {
          return item.url;
        }

        if (item.full_url) {
          return item.full_url;
        }

        return item.path ? `/storage/${String(item.path).replace(/^\/+/, '')}` : '';
      };

      let lastRange = null;

      const insertImageFromMedia = () => {
        if (!window.AdminMediaPicker?.open) {
          return;
        }

        lastRange = quill.getSelection() || lastRange;

        window.AdminMediaPicker.open({
          mode: 'image',
          trigger: mediaButton,
          onSelect(item) {
            const imageUrl = resolveMediaImageUrl(item);
            if (imageUrl === '') {
              return;
            }

            const range = lastRange || quill.getSelection() || { index: quill.getLength(), length: 0 };
            quill.focus();
            quill.insertEmbed(range.index, 'image', imageUrl, 'user');
            quill.setSelection(range.index + 1, 0, 'silent');
            syncTextareaValue(textarea, quill);
          },
        });
      };

      const quill = new window.Quill(container, {
        theme: 'snow',
        modules: {
          toolbar: {
            container: toolbarOptions,
            handlers: {
              image: insertImageFromMedia,
            },
          },
        },
        placeholder: textarea.getAttribute('placeholder') || '',
      });

      quill.root.innerHTML = normalizeHtml(textarea.value);
      quill.on('text-change', () => syncTextareaValue(textarea, quill));
      quill.on('selection-change', (range) => {
        if (range) {
          lastRange = range;
        }
      });
      mediaButton.addEventListener('click', insertImageFromMedia);

      textarea.dataset.quillInitialized = 'true';
      quillEditors.set(textarea, quill);
    });
  };

  const setQuillValue = (textarea, value) => {
    if (!textarea) {
      return;
    }

    const normalized = normalizeHtml(value);
    textarea.value = normalized;
    const quill = quillEditors.get(textarea);
    if (quill) {
      quill.root.innerHTML = normalized;
    }
  };

  window.AdminQuill = {
    initialize: initializeQuillEditors,
    setValue: setQuillValue,
    sync(textarea) {
      syncTextareaValue(textarea, quillEditors.get(textarea));
    },
  };

  document.addEventListener('DOMContentLoaded', () => initializeQuillEditors(document));

  document.addEventListener(
    'submit',
    () => {
      document
        .querySelectorAll('textarea.editor-field, textarea[data-editor="quill"]')
        .forEach((textarea) => window.AdminQuill?.sync(textarea));
    },
    true
  );
})();
