<?php

function admin_render_password_generate_button(string $targetInputId): void
{
    ?>
    <button type="button" class="btn btn-outline-secondary admin-generate-password"
            data-target="<?= htmlspecialchars($targetInputId) ?>">
        Сгенерировать
    </button>
    <?php
}

function admin_append_password_generate_script(): void
{
    static $appended = false;
    if ($appended) {
        return;
    }
    $appended = true;

    admin_append_footer(<<<'HTML'
<script>
document.querySelectorAll('.admin-generate-password').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = btn.getAttribute('data-target');
        const input = id ? document.getElementById(id) : null;
        if (!input) {
            return;
        }
        const chars = 'abcdefghjkmnpqrstuvwxyz23456789';
        let password = '';
        for (let i = 0; i < 8; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        input.value = password;
        input.focus();
        input.select();
    });
});
</script>
HTML);
}
