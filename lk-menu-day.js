(function () {

    function isoWeekdayFromDate(dateStr) {

        var parts = dateStr.split('-');

        if (parts.length !== 3) {

            return 1;

        }

        var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));

        var day = d.getDay();

        return day === 0 ? 7 : day;

    }



    document.querySelectorAll('[data-lk-menu-card]').forEach(function (card) {

        var select = card.querySelector('.lk-menu-day-select');

        var tbody = card.querySelector('tbody');

        var bjuWrap = card.querySelector('[data-lk-menu-bju-wrap]');

        if (!select || !tbody) {

            return;

        }



        function showDate(date) {

            tbody.querySelectorAll('tr[data-menu-date]').forEach(function (row) {

                row.classList.toggle('d-none', row.getAttribute('data-menu-date') !== date);

            });



            if (!bjuWrap) {

                return;

            }



            var weekday = String(isoWeekdayFromDate(date));

            bjuWrap.querySelectorAll('[data-menu-weekday]').forEach(function (block) {

                block.classList.toggle('d-none', block.getAttribute('data-menu-weekday') !== weekday);

            });

        }



        select.addEventListener('change', function () {

            showDate(select.value);

        });

        showDate(select.value);

    });

})();

