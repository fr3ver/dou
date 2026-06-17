(function () {
    function stripEmptyOption(select) {
        var empty = select.querySelector('option[value=""]');
        if (!empty) {
            return '';
        }
        var label = empty.textContent.trim();
        empty.remove();
        return label;
    }

    function wrapSelect(el, className) {
        var parent = el.parentElement;
        if (parent && parent.classList.contains(className)) {
            return parent;
        }
        var wrap = document.createElement('div');
        wrap.className = className;
        el.parentNode.insertBefore(wrap, el);
        wrap.appendChild(el);
        return wrap;
    }

    function initTomSelect(el, config) {
        if (el.tomselect) {
            return;
        }

        var placeholder = config.placeholder || stripEmptyOption(el);
        config.placeholder = placeholder;
        config.allowEmptyOption = false;

        wrapSelect(el, config.wrapClass || 'admin-tomselect-wrap');

        config.copyClassesToDropdown = false;

        var instance = new TomSelect(el, config);

        var wrap = el.parentElement;
        if (wrap && instance.dropdown) {
            var syncWidth = function () {
                var w = wrap.getBoundingClientRect().width;
                if (w > 0) {
                    instance.dropdown.style.width = w + 'px';
                }
            };
            instance.on('dropdown_open', syncWidth);
            window.addEventListener('resize', syncWidth);
        }

        return instance;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof TomSelect === 'undefined') {
            return;
        }

        document.querySelectorAll('select.form-select-parent').forEach(function (el) {
            initTomSelect(el, {
                wrapClass: 'parent-select-wrap',
                create: false,
                maxOptions: 500,
                placeholder: 'Начните вводить ФИО…',
                searchField: ['text'],
                sortField: { field: 'text', direction: 'asc' },
                openOnFocus: true,
                plugins: ['dropdown_input'],
                render: {
                    no_results: function () {
                        return '<div class="no-results px-3 py-2 text-muted">Никого не найдено</div>';
                    },
                },
            });
        });

        document.querySelectorAll('select.form-select-club-child').forEach(function (el) {
            initTomSelect(el, {
                wrapClass: 'parent-select-wrap',
                create: false,
                maxOptions: 500,
                placeholder: 'Начните вводить ФИО…',
                searchField: ['text'],
                sortField: { field: 'text', direction: 'asc' },
                openOnFocus: true,
                plugins: ['dropdown_input'],
                render: {
                    optgroup_header: function (data, escape) {
                        return '<div class="ts-optgroup-header">' + escape(data.label) + '</div>';
                    },
                    no_results: function () {
                        return '<div class="no-results px-3 py-2 text-muted">Никого не найдено</div>';
                    },
                },
            });
        });

        document.querySelectorAll('select.form-select-groups').forEach(function (el) {
            var placeholder = stripEmptyOption(el) || 'Выберите группу';

            var ts = initTomSelect(el, {
                wrapClass: 'group-select-wrap',
                create: false,
                maxOptions: 500,
                placeholder: placeholder,
                closeAfterSelect: true,
                render: {
                    optgroup_header: function (data, escape) {
                        return '<div class="ts-optgroup-header">' + escape(data.label) + '</div>';
                    },
                    option: function (data, escape) {
                        return '<div class="option ps-2">' + escape(data.text) + '</div>';
                    },
                },
            });

        });
    });
})();
