document.addEventListener('DOMContentLoaded', () => {
    const apiPost = async (path, data) => {
        const response = await fetch(path, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Erro ao comunicar com o backend.');
        }

        return payload;
    };

    const showMessage = (form, message, type = 'success') => {
        const box = form.parentElement.querySelector('[data-form-message]');
        if (!box) return;
        box.hidden = false;
        box.textContent = message;
        box.className = `alert alert-${type}`;
    };

    const sidebar = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-sidebar-toggle]');

    if (sidebar && toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
        });
    }

    document.querySelectorAll('[data-donut]').forEach((donut) => {
        const value = Number(donut.getAttribute('data-donut') || 0);
        donut.style.setProperty('--value', value);
    });

    document.querySelectorAll('[data-bars]').forEach((barChart) => {
        const values = (barChart.getAttribute('data-bars') || '').split(',').map(Number).filter(Boolean);
        barChart.innerHTML = '';
        values.forEach((value) => {
            const bar = document.createElement('span');
            bar.style.setProperty('--bar', value);
            barChart.appendChild(bar);
        });
    });

    document.querySelectorAll('[data-chart-line]').forEach((chart) => {
        const values = (chart.getAttribute('data-chart-line') || '').split(',').map(Number).filter(Boolean);
        if (!values.length) return;
        const max = Math.max(...values);
        const points = values.map((value, index) => {
            const x = (index / Math.max(values.length - 1, 1)) * 100;
            const y = 100 - (value / max) * 100;
            return `${x},${y}`;
        }).join(' ');
        chart.innerHTML = `<svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><polyline fill="none" stroke="#123d7a" stroke-width="2.5" points="${points}" /></svg>`;
    });

    // Password show/hide toggles
    document.querySelectorAll('[data-show-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const wrap = btn.closest('.password-wrap');
            if (!wrap) return;
            const input = wrap.querySelector('input[type="password"], input[type="text"]');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                btn.setAttribute('aria-label', 'Ocultar senha');
                btn.textContent = 'Ocultar';
            } else {
                input.type = 'password';
                btn.setAttribute('aria-label', 'Mostrar senha');
                btn.textContent = 'Mostrar';
            }
        });
    });

    const loginForm = document.querySelector('[data-login-form]');
    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            try {
                await apiPost('/api/login', Object.fromEntries(new FormData(loginForm)));
                window.location.href = 'index.html';
            } catch (error) {
                showMessage(loginForm, error.message, 'error');
            }
        });
    }

    const registerForm = document.querySelector('[data-register-form]');
    if (registerForm) {
        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = Object.fromEntries(new FormData(registerForm));

            const strongPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

            if (!strongPassword.test(formData.password || '')) {
                showMessage(registerForm, 'A palavra-passe deve ter no mínimo 8 caracteres, uma letra maiúscula, uma minúscula, um número e um caractere especial.', 'error');
                return;
            }

            if (formData.password !== formData.password_confirmation) {
                showMessage(registerForm, 'A confirmação da palavra-passe não corresponde.', 'error');
                return;
            }

            try {
                const payload = await apiPost('/api/register', formData);
                showMessage(registerForm, payload.message, 'success');
                registerForm.reset();
            } catch (error) {
                showMessage(registerForm, error.message, 'error');
            }
        });
    }

    const studentForm = document.querySelector('[data-student-form]');
    if (studentForm) {
        studentForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            try {
                const payload = await apiPost('/api/students', Object.fromEntries(new FormData(studentForm)));
                showMessage(studentForm, `${payload.message} ID: ${payload.id}`, 'success');
                studentForm.reset();
            } catch (error) {
                showMessage(studentForm, error.message, 'error');
            }
        });
    }

    const forgotForm = document.querySelector('[data-forgot-form]');
    if (forgotForm) {
        forgotForm.addEventListener('submit', (event) => {
            event.preventDefault();
            showMessage(forgotForm, 'Pedido recebido. O administrador deverá validar a recuperação de acesso.', 'success');
            forgotForm.reset();
        });
    }
});
