document.addEventListener('DOMContentLoaded', () => {
    const apiGet = async (path) => {
        const response = await fetch(path);
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Erro ao comunicar com o backend.');
        }

        return payload.data;
    };

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

    const currentUser = () => JSON.parse(localStorage.getItem('currentUser') || '{}');

    const initials = (name = '') => {
        const parts = name.trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return 'U';
        return parts.slice(0, 2).map((part) => part[0]).join('').toUpperCase();
    };

    const shortName = (full = '') => {
        const parts = String(full).trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return '';
        if (parts.length === 1) return parts[0];
        return parts[0] + ' ' + parts[parts.length - 1];
    };

    const formatNumber = (value) => new Intl.NumberFormat('pt-PT').format(Number(value || 0));

    const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    const statusLabel = (status) => ({
        active: 'Ativo',
        pending: 'Pendente',
        suspended: 'Suspenso',
        graduated: 'Graduado',
        approved: 'Aprovado',
        failed: 'Reprovado',
        available: 'Disponível',
        almost_full: 'Quase cheio',
        closed: 'Encerrado',
    }[status] || 'Sem estado');

    const statusClass = (status) => ['active', 'approved', 'available'].includes(status)
        ? 'status-active'
        : ['failed', 'suspended', 'closed'].includes(status)
            ? 'status-danger'
            : '';

    const setText = (selector, value) => {
        document.querySelectorAll(selector).forEach((element) => {
            element.textContent = value;
        });
    };

    const emptyRow = (colspan, message) => `<tr><td colspan="${colspan}" class="empty-state">${message}</td></tr>`;

    const showMessage = (form, message, type = 'success') => {
        const box = form.parentElement.querySelector('[data-form-message]');
        if (!box) return;
        box.hidden = false;
        box.textContent = message;
        box.className = `alert alert-${type}`;
    };

    const sidebar = document.querySelector('[data-sidebar]') || document.querySelector('.sidebar');
    const toggle = document.querySelector('[data-sidebar-toggle]') || document.querySelector('.mobile-trigger');
    const collapseBtn = document.querySelector('[data-sidebar-collapse]');
    const appLayout = sidebar ? sidebar.closest('.app-layout') : null;
    const navShortLabels = {
        Dashboard: 'D',
        Estudantes: 'E',
        Matrículas: 'M',
        Notas: 'N',
        Relatórios: 'R',
        Perfil: 'P',
        Configurações: 'C',
    };
    const fallbackCourses = [
        { id: 1, name: 'Logística e Gestão Comercial' },
        { id: 2, name: 'Gestão de Recursos Humanos' },
        { id: 3, name: 'Contabilidade e Finanças' },
        { id: 4, name: 'Redes e Telecomunicações' },
        { id: 5, name: 'Informática e Sistemas de Informação' },
    ];

    document.querySelectorAll('.sidebar-link').forEach((link) => {
        const label = link.textContent.trim();
        link.dataset.short = navShortLabels[label] || label.substring(0, 1).toUpperCase();
        link.setAttribute('title', label);
    });

    document.querySelectorAll('.sidebar').forEach((nav) => {
        if (nav.querySelector('[data-logout-button], [data-sidebar-logout]')) return;
        const footer = document.createElement('div');
        footer.className = 'sidebar-footer';
        footer.innerHTML = `
                <button type="button" class="sidebar-logout" data-logout-button aria-label="Sair">
                <span aria-hidden="true">⎋</span>
                <span class="sidebar-logout-label">Sair</span>
            </button>
        `;
        nav.appendChild(footer);
    });

    if (sidebar && toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
        });
    }
    
    if (collapseBtn && sidebar) {
        collapseBtn.textContent = '◀';
        collapseBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            appLayout?.classList.toggle('sidebar-is-collapsed', sidebar.classList.contains('collapsed'));
            collapseBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
        });
    }

    // Robust logout handling via event delegation: works for existing and dynamically added buttons
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-logout-button], [data-sidebar-logout]');
        if (!btn) return;
        try {
            localStorage.removeItem('currentUser');
        } catch (e) {
            // ignore storage errors
        }

        // Redirect to login.html in the same directory as the current page
        try {
            const path = window.location.pathname;
            const parts = path.split('/');
            parts.pop(); // remove last segment (file or empty)
            const base = parts.join('/') || '/';
            const target = (base.endsWith('/') ? base : base + '/') + 'login.html';
            window.location.href = target;
        } catch (e) {
            window.location.href = 'login.html';
        }
    });

    const renderDonut = (donut, value) => {
        const percent = Math.max(0, Math.min(100, Number(value || 0)));
        const radius = 52;
        const circumference = 2 * Math.PI * radius;
        const offset = circumference - (percent / 100) * circumference;

        donut.innerHTML = `
            <svg class="donut-chart" viewBox="0 0 140 140" role="img" aria-label="Taxa de aprovação ${percent.toFixed(1)}%">
                <circle class="donut-track" cx="70" cy="70" r="${radius}" />
                <circle class="donut-value" cx="70" cy="70" r="${radius}" stroke-dasharray="${circumference.toFixed(2)}" stroke-dashoffset="${offset.toFixed(2)}" />
            </svg>
        `;
    };

    document.querySelectorAll('[data-bars]').forEach((barChart) => {
        const values = (barChart.getAttribute('data-bars') || '').split(',').map(Number).filter(Boolean);
        barChart.innerHTML = '';
        values.forEach((value) => {
            const bar = document.createElement('span');
            bar.style.setProperty('--bar', value);
            barChart.appendChild(bar);
        });
    });

    const renderLineChart = (chart, items) => {
        const chartItems = items.map((item, index) => typeof item === 'number'
            ? { label: String(index + 1), total: item }
            : { label: item.label || item.period || String(index + 1), total: Number(item.total || 0) });
        const values = chartItems.map((item) => item.total);

        if (!chartItems.length) {
            chart.innerHTML = '<p class="empty-state">Sem dados de matrículas.</p>';
            return;
        }

        const width = 640;
        const height = 260;
        const left = 54;
        const right = 22;
        const top = 24;
        const bottom = 42;
        const plotWidth = width - left - right;
        const plotHeight = height - top - bottom;
        const max = Math.max(...values, 1);
        const yMax = Math.ceil(max / 5) * 5 || 5;
        const points = values.map((value, index) => {
            const x = left + (index / Math.max(values.length - 1, 1)) * plotWidth;
            const y = top + plotHeight - (value / yMax) * plotHeight;
            return { x, y, value, label: chartItems[index].label };
        });
        const linePoints = points.map((point) => `${point.x.toFixed(1)},${point.y.toFixed(1)}`).join(' ');
        const areaPoints = `${left},${top + plotHeight} ${linePoints} ${left + plotWidth},${top + plotHeight}`;
        const grid = [0, .25, .5, .75, 1].map((ratio) => {
            const y = top + plotHeight - ratio * plotHeight;
            const label = Math.round(yMax * ratio);
            return `<line class="chart-grid" x1="${left}" y1="${y}" x2="${left + plotWidth}" y2="${y}" /><text class="chart-axis-label" x="${left - 12}" y="${y + 4}" text-anchor="end">${label}</text>`;
        }).join(' ');
        const labels = points.map((point) => `<text class="chart-axis-label" x="${point.x}" y="${height - 12}" text-anchor="middle">${escapeHtml(point.label)}</text>`).join(' ');
        const circles = points.map((point) => `<circle class="chart-point" cx="${point.x}" cy="${point.y}" r="4"><title>${escapeHtml(point.label)}: ${formatNumber(point.value)}</title></circle>`).join('');

        chart.innerHTML = `
            <svg viewBox="0 0 ${width} ${height}" role="img" aria-label="Evolução de matrículas">
                <defs>
                    <linearGradient id="lineAreaGradient" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stop-color="#1d57aa" stop-opacity="0.22" />
                        <stop offset="100%" stop-color="#1d57aa" stop-opacity="0.02" />
                    </linearGradient>
                </defs>
                ${grid}
                <line class="chart-axis" x1="${left}" y1="${top + plotHeight}" x2="${left + plotWidth}" y2="${top + plotHeight}" />
                <polygon class="chart-area" points="${areaPoints}" />
                <polyline class="chart-stroke" points="${linePoints}" />
                ${circles}
                ${labels}
            </svg>
        `;
    };

    const renderCourseBars = (bars, courses) => {
        const values = courses.map((item) => Number(item.total));
        const max = Math.max(...values, 1);

        bars.innerHTML = courses.length
            ? courses.map((course) => {
                const total = Number(course.total || 0);
                const percent = max > 0 ? (total / max) * 100 : 0;
                return `
                    <div class="course-bar-row">
                        <div class="course-bar-copy">
                            <strong>${escapeHtml(course.name)}</strong>
                            <span>${formatNumber(total)} estudantes</span>
                        </div>
                        <div class="course-bar-meter">
                            <div class="course-bar-track"><span style="width:${Math.max(percent, total > 0 ? 8 : 0)}%"></span></div>
                            <b>${Math.round(percent)}%</b>
                        </div>
                    </div>
                `;
            }).join('')
            : '<p class="empty-state">Cursos ainda não carregados pelo backend.</p>';
    };

    const renderRecentActivities = (container, activities) => {
        container.innerHTML = activities.length ? activities.map((activity) => `
            <div class="activity-item">
                <span class="activity-dot"></span>
                <div>
                    <strong>${escapeHtml(activity.title || 'Registo')}</strong>
                    <span>${escapeHtml(activity.description || 'Atividade académica')}</span>
                </div>
                <time>${escapeHtml((activity.occurred_at || '').substring(0, 10))}</time>
            </div>
        `).join('') : '<p class="empty-state">Sem atividades recentes.</p>';
    };
    

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
                const payload = await apiPost('/api/login', Object.fromEntries(new FormData(loginForm)));
                if (payload.user) {
                    localStorage.setItem('currentUser', JSON.stringify(payload.user));
                }
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

    const hydrateHeaderProfile = () => {
        const user = currentUser();
        const rawName = user.name || user.full_name || 'Utilizador';
        const name = shortName(rawName) || 'Utilizador';
        const role = user.role_name || user.role || 'Conta ativa';

        document.querySelectorAll('[data-current-user-name]').forEach((element) => {
            element.textContent = name;
        });
        document.querySelectorAll('[data-current-user-role]').forEach((element) => {
            element.textContent = role;
        });
        document.querySelectorAll('[data-current-user-avatar]').forEach((element) => {
            element.textContent = initials(name);
        });
    };

    const loadUserProfile = () => {
        const user = currentUser();
        const userName = document.getElementById('userName');
        const userAvatar = document.getElementById('userAvatar');
        const userEmail = document.getElementById('userEmail');
        const userAcademic = document.getElementById('userAcademic');
        const userCourse = document.getElementById('userCourse');
        const userPhone = document.getElementById('userPhone');
        const userLocation = document.getElementById('userLocation');
        const userYear = document.getElementById('userYear');
        const userAverage = document.getElementById('userAverage');
        const userECTS = document.getElementById('userECTS');
        const progressBar = document.getElementById('progressBar');
        const userProgress = document.getElementById('userProgress');

        if (user && user.id) {
            if (userName) userName.textContent = shortName(user.name || user.full_name || 'Utilizador');
            if (userAvatar) userAvatar.textContent = initials(shortName(user.name || user.full_name || 'Utilizador'));
            if (userEmail) userEmail.textContent = user.email || '--';
            if (userAcademic) {
                if (user.academic_number) {
                    userAcademic.textContent = `Nº Académico: ${user.academic_number}`;
                    userAcademic.style.display = '';
                } else {
                    // hide academic number for non-students or users without it
                    userAcademic.textContent = '';
                    userAcademic.style.display = 'none';
                }
            }
            if (userCourse) userCourse.textContent = user.course || 'Curso não atribuído';
            if (userPhone) userPhone.textContent = user.phone || '--';
            if (userLocation) userLocation.textContent = user.location || '--';
            if (userYear) userYear.textContent = user.year || '1º Ano';
            if (userAverage) userAverage.textContent = user.average || '0.0';
            if (userECTS) userECTS.textContent = `${user.ects || 0} ECTS`;
            const progress = parseInt(user.progress || 0);
            if (progressBar) progressBar.style.width = progress + '%';
            if (userProgress) userProgress.textContent = progress + '% concluído';
        } else if (userName || userAvatar) {
            if (userName) userName.textContent = 'Utilizador não autenticado';
            if (userAvatar) userAvatar.textContent = 'U';
            if (userEmail) userEmail.textContent = 'Entre no sistema para ver os dados do perfil.';
        }
    };

    const loadDashboard = async () => {
        if (!document.querySelector('[data-dashboard-page]')) return;
        try {
            const data = await apiGet('/api/dashboard');
            setText('[data-stat="students"]', formatNumber(data.students));
            setText('[data-stat="active_enrollments"]', formatNumber(data.active_enrollments));
            setText('[data-stat="courses"]', formatNumber(data.courses));
            setText('[data-stat="average"]', Number(data.average || 0).toFixed(1));
            setText('[data-stat="approval_rate"]', `${Number(data.approval_rate || 0).toFixed(1)}%`);

            document.querySelectorAll('[data-approval-donut]').forEach((donut) => {
                renderDonut(donut, data.approval_rate || 0);
            });

            document.querySelectorAll('[data-enrollment-trend]').forEach((chart) => {
                renderLineChart(chart, data.enrollment_trend || []);
            });

            const bars = document.querySelector('[data-course-bars]');
            if (bars) {
                const courses = data.course_distribution || [];
                renderCourseBars(bars, courses);
            }

            document.querySelectorAll('[data-recent-activities]').forEach((container) => {
                renderRecentActivities(container, data.recent_activities || []);
            });
        } catch (error) {
            document.querySelectorAll('[data-stat]').forEach((element) => {
                element.textContent = element.textContent.includes('%') ? '0%' : '0';
            });
            document.querySelectorAll('[data-approval-donut]').forEach((donut) => {
                renderDonut(donut, 0);
            });
            document.querySelectorAll('[data-enrollment-trend]').forEach((chart) => {
                renderLineChart(chart, []);
            });
            document.querySelectorAll('[data-recent-activities]').forEach((container) => {
                renderRecentActivities(container, []);
            });
        }
    };

    const loadStudents = async () => {
        const table = document.querySelector('[data-students-table]');
        if (!table) return;
        try {
            const students = await apiGet('/api/students');

            const searchInput = document.querySelector('[data-students-search]');
            const courseFilter = document.querySelector('[data-students-course-filter]');
            const query = (searchInput?.value || '').trim().toLowerCase();
            const courseVal = (courseFilter?.value || '').trim().toLowerCase();

            const filtered = students.filter((student) => {
                // course matching: allow by name or id
                const studentCourse = String(student.course || '').toLowerCase();
                const studentCourseId = String(student.course_id || '');
                const matchesCourse = !courseVal || studentCourse === courseVal || studentCourseId === courseVal;

                if (!matchesCourse) return false;
                if (!query) return true;
                const name = String(student.name || student.full_name || '').toLowerCase();
                const academic = String(student.academic_number || '').toLowerCase();
                const email = String(student.email || '').toLowerCase();
                return name.includes(query) || academic.includes(query) || email.includes(query);
            });

            table.innerHTML = filtered.length ? filtered.map((student) => `
                <tr>
                    <td><div class="table-person"><div class="avatar-sm">${initials(student.name || student.full_name)}</div><strong>${escapeHtml(student.name || student.full_name)}</strong></div></td>
                    <td>${escapeHtml(student.academic_number || '--')}</td>
                    <td>${escapeHtml(student.course || '--')}</td>
                    <td><span class="status-pill ${statusClass(student.status)}">${statusLabel(student.status)}</span></td>
                    <td><div class="row-actions"><button type="button">Ver</button><button type="button">Editar</button><button type="button">Eliminar</button></div></td>
                </tr>
            `).join('') : emptyRow(5, 'Ainda não existem estudantes registados.');
        } catch (error) {
            table.innerHTML = emptyRow(5, error.message);
        }
    };

    // wire search/filter controls (if present)
    const studentsSearch = document.querySelector('[data-students-search]');
    if (studentsSearch) {
        studentsSearch.addEventListener('input', () => {
            loadStudents();
        });
    }
    const studentsCourseFilter = document.querySelector('[data-students-course-filter]');
    if (studentsCourseFilter) {
        studentsCourseFilter.addEventListener('change', () => {
            loadStudents();
        });
    }

    // Grades filters
    const gradesCourseFilter = document.querySelector('[data-grades-course-filter]');
    const gradesSubjectSelect = document.querySelector('[data-subject-select]');
    const gradesYearSelect = document.querySelector('[data-grade-year-select]');

    const loadCourses = async () => {
        const selects = Array.from(document.querySelectorAll('[data-course-select], [data-students-course-filter], [data-grades-course-filter]'));
        if (!selects.length) return;
        try {
            const courses = await apiGet('/api/courses');
            const list = courses.length ? courses : fallbackCourses;
            selects.forEach((select) => {
                if (select.hasAttribute('data-course-select')) {
                    select.innerHTML = list.map((course) => `<option value="${escapeHtml(course.id)}">${escapeHtml(course.name)}</option>`).join('');
                } else if (select.hasAttribute('data-students-course-filter') || select.hasAttribute('data-grades-course-filter')) {
                    // use id as value for reliable matching
                    select.innerHTML = [`<option value="">Todos os cursos</option>`].concat(list.map((course) => `<option value="${escapeHtml(course.id)}">${escapeHtml(course.name)}</option>`)).join('');
                }
                select.disabled = false;
            });
            loadSubjects();
        } catch (error) {
            selects.forEach((select) => {
                if (select.hasAttribute('data-course-select')) {
                    select.innerHTML = fallbackCourses.map((course) => `<option value="${escapeHtml(course.id)}">${escapeHtml(course.name)}</option>`).join('');
                } else if (select.hasAttribute('data-students-course-filter') || select.hasAttribute('data-grades-course-filter')) {
                    select.innerHTML = [`<option value="">Todos os cursos</option>`].concat(fallbackCourses.map((course) => `<option value="${escapeHtml(course.id)}">${escapeHtml(course.name)}</option>`)).join('');
                }
                select.disabled = false;
            });
            loadSubjects();
        }
    };

    const loadSubjects = async () => {
        const selects = Array.from(document.querySelectorAll('[data-subject-select]'));
        if (!selects.length) return;
        try {
            const subjects = await apiGet('/api/subjects');
            const list = subjects.length ? subjects : [];
            selects.forEach((select) => {
                select.innerHTML = [`<option value="">Todas as disciplinas</option>`].concat(list.map((s) => `<option value="${escapeHtml(s.id)}">${escapeHtml(s.name)}</option>`)).join('');
                select.disabled = false;
            });
        } catch (error) {
            selects.forEach((select) => {
                select.innerHTML = `<option value="">Erro a carregar</option>`;
                select.disabled = false;
            });
        }
    };

    // populate years (last 5 years)
    if (gradesYearSelect) {
        const yearNow = new Date().getFullYear();
        const opts = ['<option value="">Todos os anos letivos</option>'];
        for (let y = yearNow; y >= yearNow - 5; y--) opts.push(`<option value="${y}">${y}/${y + 1}</option>`);
        gradesYearSelect.innerHTML = opts.join('');
        gradesYearSelect.addEventListener('change', () => loadGrades());
    }

    if (gradesCourseFilter) {
        gradesCourseFilter.addEventListener('change', () => {
            loadGrades();
        });
    }
    if (gradesSubjectSelect) {
        gradesSubjectSelect.addEventListener('change', () => loadGrades());
    }

    const loadEnrollments = async () => {
        const subjectGrid = document.querySelector('[data-subjects-grid]');
        if (!subjectGrid) return;
        try {
            const data = await apiGet('/api/enrollments');
            const subjects = data.subjects || [];
            subjectGrid.innerHTML = subjects.length ? subjects.map((subject) => `
                <div class="subject-card">
                    <strong>${escapeHtml(subject.name)}</strong>
                    <span>${escapeHtml(subject.course)}</span>
                    <small>${subject.enrolled}/${subject.capacity} vagas ocupadas</small>
                    <small class="status-pill ${statusClass(subject.status)}">${statusLabel(subject.status)}</small>
                </div>
            `).join('') : '<p class="empty-state">Ainda não existem disciplinas para matrícula.</p>';
        } catch (error) {
            subjectGrid.innerHTML = `<p class="empty-state">${error.message}</p>`;
        }
    };

    const loadGrades = async () => {
        const table = document.querySelector('[data-grades-table]');
        if (!table) return;
        try {
            // build query from filters
            const courseId = Number(document.querySelector('[data-grades-course-filter]')?.value || 0);
            const subjectId = Number(document.querySelector('[data-subject-select]')?.value || 0);
            const year = Number(document.querySelector('[data-grade-year-select]')?.value || 0);
            const params = new URLSearchParams();
            if (Number.isFinite(courseId) && courseId > 0) params.set('course_id', String(courseId));
            if (Number.isFinite(subjectId) && subjectId > 0) params.set('subject_id', String(subjectId));
            if (Number.isFinite(year) && year > 0) params.set('year', String(year));
            const url = '/api/grades' + (params.toString() ? ('?' + params.toString()) : '');
            const data = await apiGet(url);
            const grades = data.grades || [];
            const shortName = (full) => {
                if (!full) return '';
                const parts = String(full).trim().split(/\s+/).filter(Boolean);
                if (parts.length === 0) return '';
                if (parts.length === 1) return parts[0];
                return parts[0] + ' ' + parts[parts.length - 1];
            };

            table.innerHTML = grades.length ? grades.map((grade) => `
                <tr>
                    <td>${escapeHtml(shortName(grade.student))}</td>
                    <td>${escapeHtml(grade.subject)}</td>
                    <td>${Number(grade.score || 0).toFixed(1)}</td>
                    <td><span class="status-pill ${statusClass(grade.status)}">${statusLabel(grade.status)}</span></td>
                </tr>
            `).join('') : emptyRow(4, 'Ainda não existem notas lançadas.');

            setText('[data-grade-summary="average"]', Number(data.summary?.average || 0).toFixed(1));
            setText('[data-grade-summary="approved"]', formatNumber(data.summary?.approved));
            setText('[data-grade-summary="failed"]', formatNumber(data.summary?.failed));
            setText('[data-grade-summary="pending"]', formatNumber(data.summary?.pending));
        } catch (error) {
            table.innerHTML = emptyRow(4, error.message);
            setText('[data-grade-summary="average"]', '0');
            setText('[data-grade-summary="approved"]', '0');
            setText('[data-grade-summary="failed"]', '0');
            setText('[data-grade-summary="pending"]', '0');
        }
    };

    hydrateHeaderProfile();
    loadUserProfile();
    loadDashboard();
    loadCourses();
    loadStudents();
    loadEnrollments();
    loadGrades();
});
