// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {

    document.querySelectorAll('.navbar-nav .nav-link[href^="#"]').forEach(function(link) {
        link.addEventListener('click', function() {
            const targetId = link.getAttribute('href');
            if (!targetId || targetId === '#') {
                return;
            }
            const nav = document.getElementById('navbarNav') || document.getElementById('navbarAdmin');
            if (nav && nav.classList.contains('show') && typeof bootstrap !== 'undefined') {
                const instance = bootstrap.Collapse.getInstance(nav) || new bootstrap.Collapse(nav, { toggle: false });
                instance.hide();
            }
        });
    });

    function showMessage(el, text, isError) {
        if (!el) return;
        el.textContent = text;
        el.classList.remove('d-none', 'alert-success', 'alert-danger');
        el.classList.add(isError ? 'alert-danger' : 'alert-success');
    }

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const messageEl = document.getElementById('loginMessage');

            if (!username || !password) {
                showMessage(messageEl, 'Введите логин и пароль', true);
                return;
            }

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ username, password })
                });

                const data = await response.json();

                if (data.success) {
                    const params = new URLSearchParams(window.location.search);
                    if (params.get('next') === 'change_password.php') {
                        window.location.href = 'change_password.php';
                        return;
                    }
                    if (data.role_id == 3 || data.role_id == 4) {
                        window.location.href = 'admin/dashboard.php';
                    } else if (data.role_id == 2) {
                        window.location.href = 'employee/dashboard.php';
                    } else if (data.role_id == 1) {
                        window.location.href = 'parent/dashboard.php';
                    } else {
                        window.location.href = 'index.php';
                    }
                } else {
                    showMessage(messageEl, data.message || 'Ошибка входа', true);
                }
            } catch (error) {
                showMessage(messageEl, 'Ошибка соединения с сервером', true);
            }
        });
    }

    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const messageEl = document.getElementById('passwordMessage');
            const current = document.getElementById('current_password').value;
            const newPass = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (newPass !== confirm) {
                showMessage(messageEl, 'Новый пароль и подтверждение не совпадают', true);
                return;
            }

            try {
                const response = await fetch('api/change_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        current_password: current,
                        new_password: newPass,
                        confirm_password: confirm
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(messageEl, data.message, false);
                    changePasswordForm.reset();
                } else {
                    showMessage(messageEl, data.message || 'Не удалось сменить пароль', true);
                }
            } catch (error) {
                showMessage(messageEl, 'Ошибка соединения с сервером', true);
            }
        });
    }
});
