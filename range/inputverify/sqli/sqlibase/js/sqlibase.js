/**
 * HeaSec天积安全团队 - SQL注入基础靶场前端交互
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */
(function () {
    'use strict';

    var app = {
        init: function () {
            this.bindEvents();
            this.loadInitialData();
        },

        bindEvents: function () {
            var self = this;

            // 登录
            document.getElementById('loginForm').addEventListener('submit', function (e) {
                e.preventDefault();
                self.login();
            });

            // 注册
            document.getElementById('btnShowRegister').addEventListener('click', function () {
                self.showRegisterModal();
            });
            document.getElementById('registerForm').addEventListener('submit', function (e) {
                e.preventDefault();
                self.register();
            });

            // 退出
            document.getElementById('btnLogout').addEventListener('click', function () {
                self.logout();
            });

            // 搜索
            document.getElementById('btnSearch').addEventListener('click', function () {
                self.searchArticles();
            });
            document.getElementById('btnClearSearch').addEventListener('click', function () {
                self.clearSearch();
            });

            // 反馈
            document.getElementById('feedbackForm').addEventListener('submit', function (e) {
                e.preventDefault();
                self.submitFeedback();
            });

            // 偏好保存
            document.getElementById('preferencesForm').addEventListener('submit', function (e) {
                e.preventDefault();
                self.savePreferences();
            });

            // 资讯列表点击（事件委托）
            document.getElementById('articleList').addEventListener('click', function (e) {
                self.handleArticleClick(e);
            });
            document.getElementById('btnBack').addEventListener('click', function () {
                self.showArticleList();
            });

            // 模态框关闭
            var modalCloseBtns = document.querySelectorAll('#registerModal .modal-close, #registerModal .modal-cancel');
            for (var i = 0; i < modalCloseBtns.length; i++) {
                modalCloseBtns[i].addEventListener('click', function () {
                    self.hideRegisterModal();
                });
            }

            // Tab切换
            var tabBtns = document.querySelectorAll('.heasec-tab-btn');
            for (var t = 0; t < tabBtns.length; t++) {
                tabBtns[t].addEventListener('click', function () {
                    var targetTab = this.getAttribute('data-tab');
                    var allBtns = document.querySelectorAll('.heasec-tab-btn');
                    for (var b = 0; b < allBtns.length; b++) {
                        allBtns[b].classList.remove('active');
                    }
                    this.classList.add('active');
                    var allContents = document.querySelectorAll('.heasec-tab-content');
                    for (var c = 0; c < allContents.length; c++) {
                        allContents[c].classList.remove('active');
                        allContents[c].classList.add('is-hidden');
                    }
                    var targetId = 'tab' + targetTab.charAt(0).toUpperCase() + targetTab.slice(1);
                    var targetEl = document.getElementById(targetId);
                    if (targetEl) {
                        targetEl.classList.remove('is-hidden');
                        targetEl.classList.add('active');
                    }
                });
            }
        },

        loadInitialData: function () {
            this.loadSessionState();
            this.loadArticleList();
            this.loadPreferences();
            this.logVisit();
        },

        // === 资讯相关 ===

        loadArticleList: function () {
            var self = this;
            fetch('api/get-article-list.php')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        self.renderArticleList(data.data.articles);
                        self.loadCategories(data.data.categories);
                    }
                });
        },

        renderArticleList: function (articles) {
            var html = '';
            for (var i = 0; i < articles.length; i++) {
                var a = articles[i];
                html += '<div class="heasec-sqlibase-article-item" data-id="' + a.id + '">'
                    + '  <h4>' + app.escapeHtml(a.title) + '</h4>'
                    + '  <div class="heasec-sqlibase-meta">'
                    + '    <span>' + app.escapeHtml(a.category_name) + '</span>'
                    + '    <span>' + app.escapeHtml(a.author_name) + '</span>'
                    + '    <span>' + a.publish_date + '</span>'
                    + '    <span>浏览: ' + a.view_count + '</span>'
                    + '  </div>'
                    + '</div>';
            }
            document.getElementById('articleList').innerHTML = html;
        },

        loadCategories: function (categories) {
            if (!categories || !categories.length) return;

            var searchSelect = document.getElementById('searchCategory');
            var feedbackSelect = document.getElementById('feedbackCategory');

            for (var i = 0; i < categories.length; i++) {
                var cat = categories[i];

                var opt1 = document.createElement('option');
                opt1.value = cat.name;
                opt1.textContent = cat.name;
                searchSelect.appendChild(opt1);

                var opt2 = document.createElement('option');
                opt2.value = cat.id;
                opt2.textContent = cat.name;
                feedbackSelect.appendChild(opt2);
            }
        },

        handleArticleClick: function (e) {
            var item = e.target.closest('[data-id]');
            if (item) {
                this.getArticleDetail(item.getAttribute('data-id'));
            }
        },

        getArticleDetail: function (id) {
            var self = this;
            fetch('api/get-article.php?id=' + encodeURIComponent(id))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        document.getElementById('detailTitle').textContent = data.data.title;
                        document.getElementById('detailMeta').innerHTML =
                            '<span>' + app.escapeHtml(data.data.category_name) + '</span>' +
                            '<span>' + app.escapeHtml(data.data.author_name) + '</span>' +
                            '<span>' + data.data.publish_date + '</span>' +
                            '<span>浏览: ' + data.data.view_count + '</span>';
                        document.getElementById('detailContent').textContent = data.data.content;

                        document.getElementById('articleList').style.display = 'none';
                        document.querySelector('.heasec-sqlibase-search').style.display = 'none';
                        document.getElementById('articleDetail').classList.remove('is-hidden');
                    } else {
                        self.showMessage('loginMessage', data.message, false);
                    }
                });
        },

        showArticleList: function () {
            document.getElementById('articleDetail').classList.add('is-hidden');
            document.getElementById('articleList').style.display = '';
            document.querySelector('.heasec-sqlibase-search').style.display = '';
        },

        // === 搜索相关 ===

        searchArticles: function () {
            var category = document.getElementById('searchCategory').value;
            if (!category) return;

            var self = this;
            fetch('api/search-articles.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ category: category })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                        self.renderSearchResults(data.data);
                    } else {
                        self.renderSearchResults([]);
                    }
                });
        },

        renderSearchResults: function (articles) {
            var html = '';
            if (articles.length === 0) {
                html = '<div class="heasec-sqlibase-article-item"><p style="color:#6c757d;margin:0;">未找到相关资讯</p></div>';
            } else {
                for (var i = 0; i < articles.length; i++) {
                    var a = articles[i];
                    html += '<div class="heasec-sqlibase-article-item">'
                        + '  <h4>' + app.escapeHtml(a.title) + '</h4>'
                        + '  <div class="heasec-sqlibase-meta">'
                        + '    <span>' + app.escapeHtml(a.category_name) + '</span>'
                        + '    <span>' + app.escapeHtml(a.author_name) + '</span>'
                        + '    <span>' + a.publish_date + '</span>'
                        + '  </div>'
                        + '</div>';
                }
            }

            document.getElementById('articleList').style.display = 'none';
            document.querySelector('.heasec-sqlibase-search').style.display = 'none';
            document.getElementById('searchResultsList').innerHTML = html;
            document.getElementById('searchResults').classList.remove('is-hidden');
        },

        clearSearch: function () {
            document.getElementById('searchResults').classList.add('is-hidden');
            document.getElementById('articleList').style.display = '';
            document.querySelector('.heasec-sqlibase-search').style.display = '';
            document.getElementById('searchCategory').value = '';
        },

        // === 登录相关 ===

        login: function () {
            var username = document.getElementById('loginUsername').value;
            var password = document.getElementById('loginPassword').value;

            var self = this;
            var params = new URLSearchParams();
            params.append('username', username);
            params.append('password', password);

            fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        self.onLoginSuccess(data.data);
                    } else {
                        self.showMessage('loginMessage', data.message, false);
                    }
                });
        },

        onLoginSuccess: function (user) {
            document.getElementById('guestPanel').classList.add('is-hidden');
            document.getElementById('userPanel').classList.remove('is-hidden');
            document.getElementById('userInfo').textContent = user.name + ' (' + user.role + ')';
        },

        // === 注册相关 ===

        showRegisterModal: function () {
            document.getElementById('registerModal').classList.add('show');
        },

        hideRegisterModal: function () {
            document.getElementById('registerModal').classList.remove('show');
            document.getElementById('registerModalMessage').textContent = '';
        },

        register: function () {
            var username = document.getElementById('regUsername').value;
            var password = document.getElementById('regPassword').value;
            var name = document.getElementById('regName').value;

            var params = new URLSearchParams();
            params.append('username', username);
            params.append('password', password);
            params.append('name', name);

            var self = this;
            fetch('api/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        self.hideRegisterModal();
                        self.showMessage('loginMessage', '注册成功，请登录', true);
                    } else {
                        document.getElementById('registerModalMessage').textContent = data.message;
                        document.getElementById('registerModalMessage').className = 'heasec-modal-message error';
                    }
                });
        },

        // === 退出相关 ===

        logout: function () {
            var self = this;
            fetch('api/logout.php', { method: 'POST' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    document.getElementById('userPanel').classList.add('is-hidden');
                    document.getElementById('guestPanel').classList.remove('is-hidden');
                    self.showMessage('loginMessage', '已退出登录', true);
                });
        },

        // === 会话状态 ===

        loadSessionState: function () {
            var self = this;
            fetch('api/session-state.php')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.data.logged_in) {
                        self.onLoginSuccess(data.data.user);
                    }
                });
        },

        // === 反馈相关 ===

        submitFeedback: function () {
            var form = document.getElementById('feedbackForm');
            var formData = new FormData(form);

            var self = this;
            fetch('api/submit-feedback.php', {
                method: 'POST',
                body: formData
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    self.showMessage('feedbackMessage', data.message, data.success);
                    if (data.success) {
                        form.reset();
                    }
                });
        },

        // === 偏好相关 ===

        loadPreferences: function () {
            fetch('api/get-preferences.php')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        document.getElementById('prefPerPage').textContent = data.data.per_page + ' 条';
                        document.getElementById('prefTheme').textContent = data.data.theme;
                        document.getElementById('prefPerPageSelect').value = data.data.per_page;
                        document.getElementById('prefThemeSelect').value = data.data.theme;
                    }
                });
        },

        savePreferences: function () {
            var perPage = document.getElementById('prefPerPageSelect').value;
            var theme = document.getElementById('prefThemeSelect').value;

            var self = this;
            fetch('api/save-preferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ per_page: parseInt(perPage, 10), theme: theme })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    self.showMessage('prefMessage', data.message, data.success);
                    if (data.success) {
                        self.loadPreferences();
                    }
                });
        },

        // === 访问统计相关 ===

        logVisit: function () {
            fetch('api/log-visit.php', { method: 'POST' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        document.getElementById('totalVisits').textContent = data.data.total_visits || 0;
                        document.getElementById('totalHits').textContent = data.data.total_hits || 0;
                        document.getElementById('todayVisits').textContent = data.data.today_visits || 0;

                        var visitorInfo = document.getElementById('visitorInfo');
                        var visitorDetail = document.getElementById('visitorDetail');
                        if (data.data.matched_ua && visitorInfo && visitorDetail) {
                            visitorDetail.textContent = '匹配记录ID: ' + data.data.matched_ua.id + ' | 访问次数: ' + data.data.matched_ua.visit_count;
                            visitorInfo.style.display = 'block';
                        }

                        var errorInfo = document.getElementById('visitError');
                        var errorDetail = document.getElementById('visitErrorDetail');
                        if (data.data.error && errorInfo && errorDetail) {
                            errorDetail.textContent = data.data.error;
                            errorInfo.style.display = 'block';
                        }
                    }
                });
        },

        // === 工具方法 ===

        showMessage: function (elementId, message, isSuccess) {
            var el = document.getElementById(elementId);
            if (!el) return;
            el.textContent = message;
            el.className = 'heasec-sqlibase-message ' + (isSuccess ? 'success' : 'error');
        },

        escapeHtml: function (text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        app.init();
    });

    window.HeaSecSQLiBase = app;
})();
