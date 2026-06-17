(function () {
    document.querySelectorAll('.admin-searchable').forEach(function (block) {
        var input = block.querySelector('.admin-search-input');
        var table = block.querySelector('.admin-table');
        if (!input || !table) {
            return;
        }

        var tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        var rows = Array.from(tbody.querySelectorAll('tr'));
        var headerRows = rows.filter(function (row) {
            return row.hasAttribute('data-group-header');
        });
        var emptyRows = rows.filter(function (row) {
            return row.querySelector('td[colspan]') && !row.hasAttribute('data-group-header');
        });
        var dataRows = rows.filter(function (row) {
            return row.classList.contains('admin-table-group-row')
                || (!row.querySelector('td[colspan]') && !row.hasAttribute('data-group-header'));
        });

        var colCount = table.querySelectorAll('thead th').length || 1;
        var noMatch = document.createElement('tr');
        noMatch.className = 'admin-search-no-match d-none';
        noMatch.innerHTML =
            '<td colspan="' + colCount + '" class="text-center text-muted py-4">Ничего не найдено</td>';
        tbody.appendChild(noMatch);

        function applyFilter() {
            var query = input.value.trim().toLowerCase();

            emptyRows.forEach(function (row) {
                row.classList.add('d-none');
            });

            function syncGroupHeaders() {
                headerRows.forEach(function (header) {
                    var key = header.getAttribute('data-group-key');
                    if (!key) {
                        return;
                    }
                    var inGroup = dataRows.filter(function (row) {
                        return row.getAttribute('data-group-key') === key;
                    });
                    var anyVisible = inGroup.some(function (row) {
                        return !row.classList.contains('d-none');
                    });
                    header.classList.toggle('d-none', !anyVisible);
                });
            }

            if (!query) {
                dataRows.forEach(function (row) {
                    row.classList.remove('d-none');
                });
                headerRows.forEach(function (row) {
                    row.classList.remove('d-none');
                });
                if (dataRows.length === 0) {
                    emptyRows.forEach(function (row) {
                        row.classList.remove('d-none');
                    });
                }
                noMatch.classList.add('d-none');
                return;
            }

            var visible = 0;
            dataRows.forEach(function (row) {
                var match = row.textContent.toLowerCase().indexOf(query) !== -1;
                row.classList.toggle('d-none', !match);
                if (match) {
                    visible++;
                }
            });
            syncGroupHeaders();
            noMatch.classList.toggle('d-none', visible > 0);
        }

        input.addEventListener('input', applyFilter);
    });
})();
