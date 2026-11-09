/*
 * Column-heading filters (<x-th-filter>): each heading's dropdown holds an input that
 * belongs to the page's GET form. Apply / Enter / choosing from a list submits the form;
 * Clear empties that column's filter and submits.
 */
(function () {
    function formFor(el) {
        const id = el.getAttribute('form');
        return id ? document.getElementById(id) : el.closest('form');
    }

    function submit(form) {
        if (!form) return;
        // drop the page number so a new filter always starts at page 1
        const page = form.querySelector('input[name="page"]');
        if (page) page.remove();
        form.requestSubmit ? form.requestSubmit() : form.submit();
    }

    document.addEventListener('click', function (e) {
        const apply = e.target.closest('.thf-apply');
        const clear = e.target.closest('.thf-clear');
        if (!apply && !clear) return;

        const menu = e.target.closest('.thf-menu');
        const inputs = menu ? menu.querySelectorAll('.thf-input') : [];
        if (!inputs.length) return;

        if (clear) inputs.forEach(i => { i.value = ''; });
        submit(formFor(inputs[0]));
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || !e.target.classList.contains('thf-input')) return;
        e.preventDefault();
        submit(formFor(e.target));
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('thf-autosubmit')) submit(formFor(e.target));
    });

    // Place an open heading menu just under its icon. Tables scroll sideways inside a clipped
    // container, so the menu is pinned to the screen (position: fixed) and kept inside the window.
    function placeMenu(button, menu) {
        const r = button.getBoundingClientRect();
        // 'important' because the theme forces `.dropdown-menu.show { top: 100% !important }`
        const set = (prop, value) => menu.style.setProperty(prop, value, 'important');
        set('position', 'fixed');
        set('right', 'auto');
        set('bottom', 'auto');
        set('transform', 'none');
        set('margin', '0');
        const width = menu.offsetWidth || 230;
        const height = menu.offsetHeight || 140;
        const left = Math.min(r.left, window.innerWidth - width - 10);
        let top = r.bottom + 6;
        if (top + height > window.innerHeight - 10) top = Math.max(10, r.top - height - 6); // open upwards near the bottom
        set('left', Math.max(10, left) + 'px');
        set('top', top + 'px');
    }

    document.addEventListener('shown.bs.dropdown', function (e) {
        const button = e.target;
        const menu = button.closest('.thf-dd')?.querySelector('.thf-menu');
        if (!menu) return;
        placeMenu(button, menu);
        const input = menu.querySelector('input.thf-input');
        if (input) setTimeout(() => input.focus(), 30);
    });

    // close an open heading menu when the page or the table scrolls (it would float away otherwise)
    function closeOpenMenus() {
        document.querySelectorAll('.thf-btn[aria-expanded="true"]').forEach(btn => {
            if (window.bootstrap) bootstrap.Dropdown.getOrCreateInstance(btn).hide();
        });
    }
    window.addEventListener('resize', closeOpenMenus);
    document.addEventListener('scroll', function (e) {
        if (e.target.closest && e.target.closest('.thf-menu')) return; // scrolling inside a long list
        closeOpenMenus();
    }, true);
})();
