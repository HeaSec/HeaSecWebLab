/**
 * HeaSec Vue前台应用
 * @package HeavenlySecret\Frontend
 * @version HeaSec v1.0.1
 */

// 等待DOM加载完成后初始化Vue
document.addEventListener('DOMContentLoaded', function () {
    window.app = new Vue({
        el: '#app',
        data: {
            // HeaSec团队信息
            team: {
                name: '天积安全',
                nameEn: 'HeavenlySecret',
                abbr: 'HeaSec',
                slogan: '日积寸功，乐享安全'
            },
            siteName: '天积安全靶场平台',
            version: 'v1.01',
            apiBase: 'api/heasec/',

            // 数据状态
            categories: [],
            subcategories: [],
            thirdLevelCategories: [],
            links: [],
            loading: true,

            // 视图状态：home=首页，category=分类内容
            currentView: 'home',

            // 选择状态
            selectedCategory: null,
            selectedSubcategory: null,
            selectedThirdLevelCategory: null,

            // HeaSec修复：保存最后的显示状态，用于侧边栏收起时保持内容显示
            lastValidTitle: null,

            // UI状态
            sidebarCollapsed: false,
            collapsedCategories: {},
            collapsedSubcategories: {},
            collapsedThirdLevelCategories: {},
            // 保存侧边栏收缩前的状态
            savedState: null,

            // 数据库重置功能相关状态
            showDatabaseInitModal: false,
            showDatabaseResetModal: false,
            databaseInitErrorInfo: null,  // 数据库初始化错误详情（增强错误显示）
            isInitializing: false,
            isResetting: false,
            resetConfirmationText: '',
            resetOptions: {
                resetLearningStatus: false,
                resetRangeDatabases: false,
                resetSmsSimulator: false
            },

            // 靶场数据库初始化相关状态
            showRangeDatabaseInitModal: false,
            isInitializingRange: false,
            rangeInitMessage: '',
            pendingRangeDetails: null,

            // 靶场重置结果模态框相关状态
            showRangeResetResultModal: false,
            rangeResetResults: [],
            rangeResetSummary: { success: 0, failed: 0, total: 0 },

            // 学习状态确认相关状态
            showLearningStatusConfirmModal: false,
            pendingLearningStatusUpdate: null,

            // Tooltip State
            tooltipVisible: false,
            tooltipText: '',
            tooltipStyle: {
                top: '0px',
                left: '0px'
            }
        },

        created() {
            this.initializeApp();
        },

        mounted() {
            // 设置全局应用实例
            window.app = this;
            this.loadTeamInfo();
            this.setupEventListeners();

            // 设置数据库重置功能
            setTimeout(() => {
                try {
                    console.log('[HeaSec] 开始设置数据库重置功能...');
                    this.setupDatabaseResetListener();
                } catch (error) {
                    console.warn('[HeaSec] 跳过数据库相关功能:', error.message);
                }
            }, 1000);
        },

        methods: {
            // 初始化应用 - HeaSec优化：先加载主内容再检查数据库，避免模态框闪烁
            async initializeApp() {
                try {
                    // 标记应用开始初始化
                    window.HeaSec = window.HeaSec || {};
                    window.HeaSec.appInitializing = true;

                    // HeaSec修复：先加载主内容数据，让用户快速看到页面框架
                    await this.loadData();
                    this.setDefaultSelection();
                    this.loading = false;

                    // HeaSec修复：主内容加载完成后再检查数据库，此时页面已经可见
                    await this.checkDatabaseInitialization();

                    // 标记应用初始化完成
                    window.HeaSec.appInitialized = true;
                    window.HeaSec.appInitializing = false;

                    this.logOperation('app_initialized', {
                        version: this.version,
                        team_name: this.team.name
                    });
                } catch (error) {
                    window.HeaSec.appInitializing = false;
                    this.handleHeaSecError('应用初始化失败', error);
                }
            },

            // 加载团队信息
            async loadTeamInfo() {
                try {
                    const response = await fetch(`${this.apiBase}categories.php?action=teamInfo`);
                    if (response.ok) {
                        // 从响应头获取团队信息
                        const poweredBy = response.headers.get('X-Powered-By');
                        const teamName = response.headers.get('X-Team-Name');

                        const result = await response.json();
                        if (result.success && result.data) {
                            this.updateTeamInfo(result.data);
                        }

                        if (poweredBy) {
                            this.updateTeamInfo({
                                poweredBy: poweredBy,
                                teamName: teamName
                            });
                        }
                    }
                } catch (error) {
                    this.logError('加载团队信息失败', error);
                }
            },

            // 更新团队信息
            updateTeamInfo(info) {
                if (info.team_name) this.team.name = info.team_name;
                if (info.team_en_name) this.team.nameEn = info.team_en_name;
                if (info.team_abbr) this.team.abbr = info.team_abbr;
                if (info.team_slogan) this.team.slogan = info.team_slogan;
                if (info.version) this.version = info.version;

                this.logOperation('team_info_updated', info);
            },

            // 加载所有数据
            async loadData() {
                try {
                    const fetchData = async (url) => {
                        try {
                            const response = await fetch(url);
                            if (!response.ok) throw new Error(`HTTP ${response.status}`);
                            return await response.json();
                        } catch (error) {
                            this.logError(`获取 ${url} 失败`, error);
                            return { success: false, data: [] };
                        }
                    };

                    const [categoriesRes, subcategoriesRes, thirdCategoriesRes, linksRes] = await Promise.all([
                        fetchData(`${this.apiBase}categories.php`),
                        fetchData(`${this.apiBase}subcategories.php`),
                        fetchData(`${this.apiBase}third_level_categories.php`),
                        fetchData(`${this.apiBase}links.php`)
                    ]);

                    this.categories = (categoriesRes && categoriesRes.data) ? categoriesRes.data : [];
                    this.subcategories = (subcategoriesRes && subcategoriesRes.data) ? subcategoriesRes.data : [];
                    this.thirdLevelCategories = (thirdCategoriesRes && thirdCategoriesRes.data) ? thirdCategoriesRes.data : [];
                    this.links = (linksRes && linksRes.data) ? linksRes.data : [];

                    this.logOperation('data_loaded', {
                        categories: this.categories.length,
                        subcategories: this.subcategories.length,
                        links: this.links.length
                    });
                } catch (error) {
                    this.handleHeaSecError('数据加载失败', error);
                }
            },

            // 设置默认选择
            setDefaultSelection() {
                // 默认显示首页
                this.currentView = 'home';
            },

            // 选择首页
            selectHome() {
                this.currentView = 'home';
                this.selectedCategory = null;
                this.selectedSubcategory = null;
                this.selectedThirdLevelCategory = null;
                this.logOperation('home_selected');
            },

            // 选择一级分类
            selectCategory(categoryId) {
                this.currentView = 'category';
                this.selectedCategory = categoryId;
                this.selectedSubcategory = null;
                this.logOperation('category_selected', { category_id: categoryId });
            },

            // 选择二级分类
            selectSubcategory(subcategoryId) {
                this.currentView = 'category';
                this.selectedSubcategory = subcategoryId;
                this.selectedThirdLevelCategory = null; // 清除三级分类选择

                // HeaSec修复：总是同步更新父级分类选中状态，防止出现两个一级分类同时高亮的Bug
                const sub = this.subcategories.find(s => s.id == subcategoryId);
                if (sub) {
                    this.selectedCategory = sub.category_id;
                }

                // HeaSec修复：侧边栏收起模式下点击Flyout菜单时，
                // 清除savedState以避免计算属性优先使用旧状态，确保内容更新
                if (this.sidebarCollapsed) {
                    this.savedState = null;
                }

                this.logOperation('subcategory_selected', { subcategory_id: subcategoryId });
            },

            // 选择三级分类
            selectThirdLevelCategory(thirdLevelCategoryId) {
                this.selectedThirdLevelCategory = thirdLevelCategoryId;

                // HeaSec修复：侧边栏收起模式下确保状态更新
                if (this.sidebarCollapsed) {
                    this.savedState = null;
                }

                this.logOperation('third_level_category_selected', { third_level_category_id: thirdLevelCategoryId });
            },

            // 切换分类展开状态
            toggleCategory(categoryId) {
                this.$set(this.collapsedCategories, categoryId, !this.collapsedCategories[categoryId]);
            },

            // 切换二级分类展开状态
            toggleSubcategory(subcategoryId) {
                this.$set(this.collapsedSubcategories, subcategoryId, !this.collapsedSubcategories[subcategoryId]);
            },

            // 切换三级分类展开状态
            toggleThirdLevelCategory(thirdLevelCategoryId) {
                this.$set(this.collapsedThirdLevelCategories, thirdLevelCategoryId, !this.collapsedThirdLevelCategories[thirdLevelCategoryId]);
            },

            // HeaSec Redesign: Handle Category Click (Auto-expand logic)
            handleCategoryClick(categoryId) {
                this.currentView = 'category';
                // If sidebar is collapsed, auto-expand it
                if (this.sidebarCollapsed) {
                    console.log('[HeaSec] Sidebar auto-expand triggered by category click');
                    this.sidebarCollapsed = false;
                    this.restoreSavedState();

                    // Ensure the clicked category is expanded if it has subcategories
                    this.$nextTick(() => {
                        const category = this.categories.find(c => c.id === categoryId);
                        if (category && this.getSubcategoriesByCategory(category.id).length > 0) {
                            this.$set(this.collapsedCategories, category.id, false); // Ensure it's NOT collapsed
                        }
                    });
                }
                // Standard selection logic follows in selectCategory
            },

            // HeaSec Redesign: Tooltip Logic
            showTooltip(event, text) {
                // Only show tooltip if sidebar is collapsed
                if (!this.sidebarCollapsed) return;

                this.tooltipText = text;
                this.tooltipVisible = true;

                // Calculate position
                const target = event.target.closest('.category-header');
                if (target) {
                    const rect = target.getBoundingClientRect();
                    this.tooltipStyle = {
                        top: rect.top + (rect.height / 2) + 'px',
                        left: (rect.right + 10) + 'px' // 10px offset from right edge
                    };
                }
            },

            hideTooltip() {
                this.tooltipVisible = false;
            },

            // 切换侧边栏
            toggleSidebar() {
                const wasCollapsed = this.sidebarCollapsed;
                console.log('[HeaSec] 切换侧边栏，当前状态:', wasCollapsed);
                console.log('[HeaSec] 当前选择状态:', {
                    selectedCategory: this.selectedCategory,
                    selectedSubcategory: this.selectedSubcategory
                });

                if (!wasCollapsed) {
                    // 准备收缩：保存当前状态
                    this.saveCurrentState();
                    this.sidebarCollapsed = true;
                    // HeaSec修复：收缩时不再自动清除选择状态，保持内容显示
                    // 不清除selectedSubcategory和collapsedCategories，保持用户的选择状态

                    console.log('[HeaSec] 侧边栏已收起，选择状态保持:', {
                        selectedCategory: this.selectedCategory,
                        selectedSubcategory: this.selectedSubcategory
                    });

                    // Auto-scroll to active icon
                    this.$nextTick(() => {
                        setTimeout(() => {
                            const activeEl = document.querySelector('.sidebar .category-header.active') ||
                                document.querySelector('.sidebar .category-header.active-parent');
                            if (activeEl) {
                                activeEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }, 350);
                    });
                } else {
                    // 准备展开：恢复之前的状态
                    this.sidebarCollapsed = false;
                    this.restoreSavedState();

                    console.log('[HeaSec] 侧边栏已展开，恢复状态:', {
                        selectedCategory: this.selectedCategory,
                        selectedSubcategory: this.selectedSubcategory
                    });

                    // Auto-scroll to active category
                    this.$nextTick(() => {
                        setTimeout(() => {
                            const activeEl = document.querySelector('.sidebar .category-header.active');
                            if (activeEl) {
                                activeEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }, 350);
                    });
                }
            },

            // 保存当前状态
            saveCurrentState() {
                // HeaSec修复：确保只保存有效的选择状态，避免保存undefined状态
                // 使用当前状态或之前保存的状态，优先使用有效的当前状态
                const currentCategory = this.selectedCategory || this.savedState?.selectedCategory;
                const currentSubcategory = this.selectedSubcategory || this.savedState?.selectedSubcategory;

                // HeaSec修复：确保保存的是有效状态
                const validCategory = currentCategory && currentCategory !== undefined ? currentCategory : null;
                const validSubcategory = currentSubcategory && currentSubcategory !== undefined ? currentSubcategory : null;

                this.savedState = {
                    selectedCategory: validCategory,
                    selectedSubcategory: validSubcategory,
                    collapsedCategories: { ...this.collapsedCategories },
                    collapsedSubcategories: { ...this.collapsedSubcategories },
                    timestamp: Date.now() // HeaSec修复：添加时间戳以区分手动和自动保存
                };
                console.log('[HeaSec] 保存当前状态:', this.savedState);
            },

            // 恢复保存的状态
            restoreSavedState() {
                if (this.savedState) {
                    console.log('[HeaSec] 恢复保存的状态:', this.savedState);
                    this.selectedCategory = this.savedState.selectedCategory;
                    this.selectedSubcategory = this.savedState.selectedSubcategory;
                    this.collapsedCategories = { ...this.savedState.collapsedCategories };
                    this.collapsedSubcategories = { ...this.savedState.collapsedSubcategories };

                    // HeaSec修复：确保恢复状态后展开相应的分类
                    if (this.selectedCategory && this.collapsedCategories[this.selectedCategory]) {
                        this.$set(this.collapsedCategories, this.selectedCategory, false);
                    }

                    this.savedState = null; // 清除保存的状态
                }
            },

            // 根据分类获取二级分类
            getSubcategoriesByCategory(categoryId) {
                if (!this.subcategories || !Array.isArray(this.subcategories)) {
                    return [];
                }
                return this.subcategories.filter(sc => sc.category_id === categoryId && sc.status == 1);
            },

            // 根据二级分类获取三级分类
            getThirdLevelCategoriesBySubcategory(subcategoryId) {
                if (!this.thirdLevelCategories || !Array.isArray(this.thirdLevelCategories)) {
                    return [];
                }
                return this.thirdLevelCategories.filter(tc => tc.subcategory_id === subcategoryId && tc.status == 1);
            },

            // 获取分类图标
            getCategoryIcon(categoryId) {
                const iconMap = {
                    1: 'fa-code',        // 开发工具
                    2: 'fa-paint-brush', // 设计资源
                    3: 'fa-graduation-cap', // 学习平台
                    4: 'fa-newspaper-o', // 新闻资讯
                    5: 'fa-gamepad'      // 娱乐休闲
                };
                return iconMap[categoryId] || 'fa-folder';
            },

            // 打开链接
            openLink(url) {
                if (url) {
                    url = this.resolveUrl(url);
                    window.open(url, '_blank');
                    this.logOperation('link_opened', { url: url });
                }
            },

            // 学习状态管理
            async updateLearningStatus(linkId, currentStatus) {
                try {
                    console.log('开始更新学习状态:', { linkId, currentStatus });

                    // 验证参数
                    if (!linkId || isNaN(linkId)) {
                        throw new Error(`无效的链接ID: ${linkId}`);
                    }

                    // 定义状态循环顺序 (使用中文)
                    const statusCycle = {
                        '待学习': '学习中',
                        '学习中': '已掌握',
                        '已掌握': '待学习'
                    };

                    const newStatus = statusCycle[currentStatus] || '学习中'; // 默认为学习中，如果当前状态无效
                    console.log('计算的新状态:', newStatus);

                    // 如果新状态是"已掌握"，显示确认模态框
                    if (newStatus === '已掌握') {
                        this.pendingLearningStatusUpdate = {
                            linkId: linkId,
                            currentStatus: currentStatus,
                            newStatus: newStatus
                        };
                        this.showLearningStatusConfirmModal = true;
                        return;
                    }

                    // 其他状态直接更新
                    this.performLearningStatusUpdate(linkId, currentStatus, newStatus);
                } catch (error) {
                    console.error('学习状态更新失败:', error);
                    this.logError('更新学习状态失败', error);
                    this.showStatusMessage(`更新学习状态失败: ${error.message}`, 'error');
                }
            },

            // 执行学习状态更新
            async performLearningStatusUpdate(linkId, currentStatus, newStatus) {
                try {
                    // 显示更新动画
                    const statusElement = document.querySelector(`[data-learning-status="${linkId}"]`);
                    if (statusElement) {
                        statusElement.classList.add('updating');
                        console.log('已添加更新动画类');
                    } else {
                        console.warn('未找到状态元素:', linkId);
                    }

                    // 构建API请求
                    const requestData = {
                        link_id: parseInt(linkId),
                        learning_status: newStatus
                    };
                    console.log('发送API请求:', requestData);

                    // 发送API请求
                    const response = await fetch(`${this.apiBase}update_status.php?action=updateLearningStatus`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    });

                    console.log('API响应状态:', response.status, response.statusText);

                    if (!response.ok) {
                        throw new Error(`HTTP错误: ${response.status} ${response.statusText}`);
                    }

                    // 获取响应文本并尝试解析JSON，忽略PHP警告
                    const responseText = await response.text();
                    console.log('API响应文本:', responseText);

                    let result;
                    try {
                        // 尝试从响应中提取JSON部分
                        const jsonMatch = responseText.match(/\{.*\}$/);
                        if (jsonMatch) {
                            result = JSON.parse(jsonMatch[0]);
                        } else {
                            result = JSON.parse(responseText);
                        }
                    } catch (parseError) {
                        console.error('JSON解析尝试失败:', parseError);
                        throw new Error('API响应格式错误');
                    }

                    console.log('解析后的API数据:', result);

                    if (result.success) {
                        // 更新本地数据
                        const linkIndex = this.links.findIndex(link => String(link.id) === String(linkId));
                        if (linkIndex !== -1) {
                            this.$set(this.links[linkIndex], 'learning_status', newStatus);
                            console.log('已更新本地数据，索引:', linkIndex);
                        } else {
                            console.warn('未找到要更新的链接:', linkId);
                        }

                        // 移除动画类
                        setTimeout(() => {
                            if (statusElement) {
                                statusElement.classList.remove('updating');
                            }
                        }, 500);

                        this.logOperation('learning_status_updated', {
                            link_id: linkId,
                            old_status: currentStatus,
                            new_status: newStatus
                        });

                        // 移除状态更新提示，保持界面清爽
                    } else {
                        throw new Error(result.message || '更新失败');
                    }
                } catch (error) {
                    console.error('学习状态更新失败:', error);
                    this.logError('更新学习状态失败', error);
                    this.showStatusMessage(`更新学习状态失败: ${error.message}`, 'error');

                    // 移除动画类
                    const statusElement = document.querySelector(`[data-learning-status="${linkId}"]`);
                    if (statusElement) {
                        statusElement.classList.remove('updating');
                    }
                }
            },

            // 确认学习状态更新
            confirmLearningStatusUpdate() {
                if (this.pendingLearningStatusUpdate) {
                    const { linkId, currentStatus, newStatus } = this.pendingLearningStatusUpdate;
                    this.showLearningStatusConfirmModal = false;
                    this.pendingLearningStatusUpdate = null;

                    // 执行状态更新
                    this.performLearningStatusUpdate(linkId, currentStatus, newStatus);
                }
            },

            // 取消学习状态更新
            cancelLearningStatusUpdate() {
                this.showLearningStatusConfirmModal = false;
                this.pendingLearningStatusUpdate = null;
            },

            // 获取学习状态显示文本
            getLearningStatusText(status) {
                // 直接返回数据库存储的中文状态，如果为空则返回默认值
                return status || '待学习';
            },

            // 获取学习状态对应的CSS类
            getLearningStatusClass(status) {
                const statusMap = {
                    '待学习': 'not_started',
                    '学习中': 'in_progress',
                    '已掌握': 'mastered'
                };
                return statusMap[status] || 'not_started';
            },

            // 显示状态消息
            showStatusMessage(message, type = 'info') {
                const messageDiv = document.createElement('div');
                messageDiv.className = `heasec-message heasec-message-${type}`;
                messageDiv.textContent = message;

                // 添加样式
                messageDiv.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                font-size: 14px;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;

                document.body.appendChild(messageDiv);

                // 显示动画
                setTimeout(() => {
                    messageDiv.style.transform = 'translateX(0)';
                }, 100);

                // 自动隐藏
                setTimeout(() => {
                    messageDiv.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (messageDiv.parentNode) {
                            messageDiv.parentNode.removeChild(messageDiv);
                        }
                    }, 300);
                }, 3000);
            },

            // URL解析
            resolveUrl(url) {
                if (!url) return url;

                if (url.startsWith('http://') || url.startsWith('https://')) {
                    return url;
                }

                if (url.startsWith('//')) {
                    return window.location.protocol + url;
                }

                if (url.startsWith('/')) {
                    return window.location.origin + url;
                }

                if (url.startsWith('./') || url.startsWith('../')) {
                    return new URL(url, window.location.href).href;
                }

                return window.location.origin + '/' + url;
            },

            // 设置事件监听器
            setupEventListeners() {
                // 响应式侧边栏处理
                this.setupResponsiveSidebar();

                // 添加全局错误处理
                window.addEventListener('error', (event) => {
                    this.logError('JavaScript错误', {
                        message: event.message,
                        filename: event.filename,
                        lineno: event.lineno,
                        colno: event.colno
                    });
                });

                // 添加未处理的Promise拒绝处理
                window.addEventListener('unhandledrejection', (event) => {
                    this.logError('未处理的Promise拒绝', {
                        reason: event.reason
                    });
                });
            },

            // 设置响应式侧边栏
            setupResponsiveSidebar() {
                // 定义响应式断点
                const BREAKPOINTS = {
                    MOBILE: 768,    // 移动端
                    TABLET: 1024    // 平板
                };

                // 检查当前窗口尺寸并调整侧边栏状态
                const checkResponsiveState = () => {
                    const pageWidth = window.innerWidth;
                    const wasCollapsed = this.sidebarCollapsed;

                    // 移动端自动收起侧边栏
                    if (pageWidth <= BREAKPOINTS.MOBILE) {
                        if (!this.sidebarCollapsed) {
                            // HeaSec修复：确保在自动收起前先保存当前的有效状态
                            this.saveCurrentState();
                            this.sidebarCollapsed = true;
                            // HeaSec修复：移动端自动收起时也保持选择状态，确保内容正常显示
                            // 不清除selectedSubcategory，保持用户的选择状态

                            this.logOperation('sidebar_auto_collapsed', {
                                pageWidth: pageWidth,
                                breakpoint: 'mobile',
                                selectedCategory: this.selectedCategory,
                                selectedSubcategory: this.selectedSubcategory
                            });
                        }

                    } else {
                        // 桌面端恢复之前的状态
                        if (wasCollapsed && pageWidth > BREAKPOINTS.MOBILE && this.savedState) {
                            // Don't auto-restore on resize if it creates a jarring effect, 
                            // but logic implies we should restore user preference.
                            // For now keep existing logic.
                            this.sidebarCollapsed = false;
                            this.restoreSavedState();

                            console.log('[HeaSec] 侧边栏已展开(Responsive)，恢复状态:', {
                                selectedCategory: this.selectedCategory,
                                pageWidth: pageWidth,
                                breakpoint: 'desktop'
                            });
                        }
                    }
                };

                // 初始化检查
                this.$nextTick(() => {
                    checkResponsiveState();
                });

                // 监听窗口大小变化
                let resizeTimer;
                const handleResize = () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        checkResponsiveState();
                    }, 150); // 防抖处理，避免频繁触发
                };

                window.addEventListener('resize', handleResize);

                // 页面方向变化时也要检查
                window.addEventListener('orientationchange', () => {
                    setTimeout(() => {
                        checkResponsiveState();
                    }, 100);
                });

                // 保存清理函数
                this.cleanupResponsive = () => {
                    window.removeEventListener('resize', handleResize);
                    window.removeEventListener('orientationchange', handleResize);
                    clearTimeout(resizeTimer);
                };
            },

            // HeaSec错误处理
            handleHeaSecError(message, error) {
                this.logError(message, error);
                this.showHeaSecError(message);
            },

            // 显示HeaSec错误
            showHeaSecError(message) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'heasec-error';
                errorDiv.textContent = message;

                document.body.insertBefore(errorDiv, document.body.firstChild);

                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);
            },

            // 日志操作
            logOperation(operation, data = {}) {
                if (!window.HeaSec || !window.HeaSec.config) return;

                const logEntry = {
                    team: 'HeaSec',
                    operation: operation,
                    data: data,
                    timestamp: new Date().toISOString(),
                    user_agent: navigator.userAgent,
                    url: window.location.href
                };

                console.log(`[HeaSec] ${operation}:`, data);
            },

            // 日志错误
            logError(message, error) {
                if (!window.HeaSec || !window.HeaSec.config) return;

                const logEntry = {
                    team: 'HeaSec',
                    type: 'error',
                    message: message,
                    error: error,
                    timestamp: new Date().toISOString(),
                    user_agent: navigator.userAgent,
                    url: window.location.href
                };

                console.error(`[HeaSec] ${message}:`, error);
            },

            // ========================================
            // 数据库管理功能方法
            // ========================================

            // 检查数据库是否已初始化（增强版：支持详细的错误显示）
            async checkDatabaseInitialization() {
                try {
                    console.log('[HeaSec] 开始检查数据库初始化状态...');
                    const response = await fetch(`${this.apiBase}check_database.php`, {
                        method: 'GET',
                        credentials: 'include',
                        headers: {
                            'Cache-Control': 'no-cache'
                        }
                    });

                    // 读取响应文本，便于调试
                    const responseText = await response.text();
                    console.log('[HeaSec] 数据库检查原始响应:', responseText.substring(0, 500));

                    // 尝试解析JSON
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('[HeaSec] 响应解析失败:', parseError);
                        throw new Error('服务器响应格式错误，可能是数据库服务未启动或配置错误');
                    }

                    console.log('[HeaSec] 数据库检查结果:', result);

                    // ========== 处理服务器不可达的情况 ==========
                    if (!response.ok || (result.success === false && result.data && result.data.error_type === 'server_unreachable')) {
                        console.error('[HeaSec] MySQL服务器不可达');
                        this.showDatabaseInitModal = true;
                        this.databaseInitErrorInfo = {
                            type: 'server_unreachable',
                            title: '数据库服务不可用',
                            message: result.data ? (result.data.error_message || '无法连接到MySQL服务器') : '服务器无响应',
                            detail: result.data ? result.data.error_detail : `HTTP ${response.status}: ${response.statusText}`,
                            suggestion: result.data ? result.data.suggestion : '请检查MySQL服务是否已启动',
                            canInitialize: false,
                            canReset: false
                        };
                        this.logError('MySQL服务器不可达', result.data);
                        return;
                    }

                    // ========== 处理数据库不存在的情况 ==========
                    if (result.success && result.data && result.data.error_type === 'database_not_exists') {
                        console.log('[HeaSec] 数据库不存在，显示初始化提示');
                        this.showDatabaseInitModal = true;
                        this.databaseInitErrorInfo = {
                            type: 'database_not_exists',
                            title: '数据库不存在',
                            message: result.data.error_message || '数据库尚未创建',
                            detail: result.data.error_detail || '',
                            suggestion: result.data.suggestion || '请点击"确认初始化"按钮创建数据库',
                            canInitialize: true,
                            canReset: true
                        };
                        this.logOperation('database_not_exists', result.data);
                        return;
                    }

                    // ========== 处理正常的检查结果 ==========
                    if (result.success && result.data) {
                        if (!result.data.initialized) {
                            console.log('[HeaSec] 数据库未初始化，显示初始化提示');

                            // 构建详细的错误信息
                            const errorType = result.data.error_type || 'unknown';
                            let errorTitle, errorMessage, errorDetail, suggestion;

                            switch (errorType) {
                                case 'tables_missing':
                                    errorTitle = '数据库表不完整';
                                    errorMessage = result.data.error_message || '缺少必要的数据库表';
                                    errorDetail = result.data.error_detail || '';
                                    suggestion = result.data.suggestion || '请点击初始化按钮创建缺失的表';
                                    break;
                                case 'data_empty':
                                    errorTitle = '数据库为空';
                                    errorMessage = result.data.error_message || '数据库中没有数据';
                                    errorDetail = result.data.error_detail || '';
                                    suggestion = result.data.suggestion || '请点击初始化按钮导入初始数据';
                                    break;
                                default:
                                    errorTitle = '数据库需要初始化';
                                    errorMessage = '检测到数据库尚未完成初始化';
                                    errorDetail = `缺失表: ${(result.data.tables_check && result.data.tables_check.missing) ? result.data.tables_check.missing.join(', ') : '未知'}`;
                                    suggestion = '请点击"确认初始化"按钮完成数据库初始化';
                            }

                            this.showDatabaseInitModal = true;
                            this.databaseInitErrorInfo = {
                                type: errorType,
                                title: errorTitle,
                                message: errorMessage,
                                detail: errorDetail,
                                suggestion: suggestion,
                                canInitialize: true,
                                canReset: true,
                                missingTables: result.data.tables_check ? result.data.tables_check.missing : [],
                                hasData: result.data.data_check ? result.data.data_check.has_data : false
                            };

                            this.logOperation('database_check_uninitialized', {
                                error_type: errorType,
                                tables_missing: result.data.tables_check ? result.data.tables_check.missing : [],
                                has_data: result.data.data_check ? result.data.data_check.has_data : false
                            });
                        } else {
                            console.log('[HeaSec] 数据库已初始化');
                            this.logOperation('database_check_initialized', {
                                tables_existing: result.data.tables_check ? result.data.tables_check.existing : []
                            });
                        }

                        // 检查靶场数据库状态
                        if (result.data.range_check && result.data.range_check.needs_initialization) {
                            // 使用新的uninitialized_ranges数据（如果可用）
                            let uninitializedRanges = [];
                            let uninitializedCount = 0;

                            if (result.data.uninitialized_ranges && result.data.uninitialized_ranges.length > 0) {
                                uninitializedRanges = result.data.uninitialized_ranges;
                                uninitializedCount = uninitializedRanges.length;
                                console.log(`[HeaSec] 发现 ${uninitializedCount} 个靶场数据库未初始化（详细列表）:`, uninitializedRanges);
                            } else {
                                // 回退到旧的计算方式
                                uninitializedCount = result.data.range_check.total_ranges - result.data.range_check.initialized_count;
                                console.log(`[HeaSec] 发现 ${uninitializedCount} 个靶场数据库未初始化（总数统计）`);
                            }

                            // 只在真正需要初始化时才提示用户
                            if (uninitializedCount > 0) {
                                // 使用模态框替代confirm弹窗
                                await this.initializeRangeDatabases(uninitializedRanges, uninitializedCount);
                            }

                            this.logOperation('range_database_check_completed', {
                                total: result.data.range_check.total_ranges,
                                uninitialized: uninitializedCount,
                                initialized: result.data.range_check.initialized_count,
                                uninitialized_ranges: uninitializedRanges
                            });
                        }
                    } else {
                        console.warn('[HeaSec] 数据库检查响应格式异常');
                        this.logError('数据库检查响应格式异常', result);
                        // 即使响应格式异常也显示初始化提示
                        this.showDatabaseInitModal = true;
                        this.databaseInitErrorInfo = {
                            type: 'response_error',
                            title: '数据库检查异常',
                            message: '无法确定数据库状态',
                            detail: '服务器返回了意外的响应格式',
                            suggestion: '建议尝试初始化数据库以恢复正常功能',
                            canInitialize: true,
                            canReset: true
                        };
                    }
                } catch (error) {
                    console.error('[HeaSec] 数据库检查失败:', error);
                    this.logError('数据库检查失败', error);
                    // 如果数据库检查失败，显示初始化提示并附带错误信息
                    this.showDatabaseInitModal = true;
                    this.databaseInitErrorInfo = {
                        type: 'connection_error',
                        title: '数据库连接失败',
                        message: '无法连接到数据库服务',
                        detail: error.message || '未知网络错误',
                        suggestion: '请检查：1. MySQL服务是否已启动 2. 数据库配置是否正确 3. 网络连接是否正常',
                        canInitialize: false,
                        canReset: false
                    };
                }
            },

            // 初始化靶场数据库
            async initializeRangeDatabases(uninitializedRanges, uninitializedCount) {
                try {
                    console.log('[HeaSec] 准备初始化靶场数据库...', uninitializedRanges);

                    // 检查是否有靶场未指定数据库
                    const unspecifiedRanges = uninitializedRanges.filter(range => range.database_not_specified);
                    if (unspecifiedRanges.length > 0) {
                        const rangeNames = unspecifiedRanges.map(range => range.directory || '未知靶场').join('、');
                        const confirmed = confirm(
                            `以下靶场的数据库初始化脚本未指定目标数据库：\n\n${rangeNames}\n\n` +
                            `这可能导致表被创建到错误的数据库中。建议先修复SQL文件后再初始化。\n\n` +
                            `是否仍要继续初始化？`
                        );
                        if (!confirmed) {
                            console.log('[HeaSec] 用户取消了未指定数据库靶场的初始化');
                            return;
                        }
                    }

                    // 构建详细的初始化消息
                    let message = `检测到 ${uninitializedCount} 个靶场数据库未初始化，是否现在初始化？`;

                    // 如果有具体的靶场列表，添加到消息中
                    if (uninitializedRanges && uninitializedRanges.length > 0) {
                        const rangeNames = uninitializedRanges.map(range => {
                            return range.directory || '未知靶场';
                        });
                        message += `\n\n待初始化的靶场：\n${rangeNames.join('\n')}`;
                    }

                    // 显示模态框确认
                    this.showRangeDatabaseInitModal = true;
                    this.rangeInitMessage = message;
                    this.pendingUninitializedRanges = uninitializedRanges;
                    this.pendingUninitializedCount = uninitializedCount;
                } catch (error) {
                    console.error('[HeaSec] 靶场数据库初始化准备失败:', error);
                    this.showErrorMessage('靶场数据库初始化准备失败: ' + error.message);
                }
            },

            // 确认初始化靶场数据库
            async confirmRangeDatabaseInit() {
                if (!this.pendingUninitializedRanges) {
                    return;
                }

                this.isInitializingRange = true;
                try {
                    console.log('[HeaSec] 开始初始化靶场数据库...', this.pendingUninitializedRanges);

                    // 构建靶场目录列表
                    const targetRanges = this.pendingUninitializedRanges.map(range => range.directory).filter(dir => dir);

                    const formData = new FormData();
                    formData.append('confirm', 'YES_RESET_DATABASE');
                    formData.append('selective_range_reset', '1');

                    // 添加每个目标靶场
                    targetRanges.forEach((rangeDir, index) => {
                        formData.append(`target_ranges[${index}]`, rangeDir);
                    });

                    const response = await fetch(`${this.apiBase}reset_database.php`, {
                        method: 'POST',
                        body: formData
                    });

                    const responseText = await response.text();
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        throw new Error(response.ok ? '响应解析失败: ' + parseError.message : `HTTP ${response.status}: ${response.statusText}`);
                    }

                    if (!response.ok) {
                        throw new Error(result.message || `HTTP ${response.status}: ${response.statusText}`);
                    }

                    if (result.success) {
                        this.showRangeDatabaseInitModal = false;
                        this.showSuccessMessage('靶场数据库初始化成功！');
                    } else {
                        this.showErrorMessage('靶场数据库初始化失败: ' + result.message);
                    }
                } catch (error) {
                    console.error('[HeaSec] 靶场数据库初始化失败:', error);
                    this.showErrorMessage('靶场数据库初始化失败: ' + error.message);
                } finally {
                    this.isInitializingRange = false;
                    this.pendingRangeDetails = null;
                }
            },

            // 设置数据库重置按钮事件监听
            setupDatabaseResetListener() {
                try {
                    var resetBtn = document.getElementById('resetDatabaseBtn');
                    if (resetBtn) {
                        var self = this;
                        resetBtn.onclick = function () {
                            self.showDatabaseResetModal = true;
                            self.resetConfirmationText = '';
                            // 重置选项到默认状态
                            self.resetOptions = {
                                resetLearningStatus: false,
                                resetRangeDatabases: false,
                                resetSmsSimulator: false
                            };
                        };
                        console.log('[HeaSec] 数据库重置按钮事件监听设置成功');
                    } else {
                        console.warn('[HeaSec] 未找到数据库重置按钮');
                    }
                } catch (error) {
                    console.warn('[HeaSec] 设置重置按钮监听失败:', error.message);
                }
            },

            // 初始化数据库
            async initializeDatabase() {
                this.isInitializing = true;
                try {
                    const formData = new FormData();
                    formData.append('confirm', 'YES_RESET_DATABASE');
                    formData.append('csrf_token', await this.getCSRFToken());
                    formData.append('is_initialization', '1');

                    const response = await fetch(`${this.apiBase}reset_database.php`, {
                        method: 'POST',
                        body: formData
                    });

                    const responseText = await response.text();
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        throw new Error(response.ok ? '响应解析失败: ' + parseError.message : `HTTP ${response.status}: ${response.statusText}`);
                    }

                    if (!response.ok) {
                        throw new Error(result.message || `HTTP ${response.status}: ${response.statusText}`);
                    }

                    if (result.success) {
                        this.showDatabaseInitModal = false;
                        this.showSuccessMessage('数据库初始化成功！');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        this.showErrorMessage('数据库初始化失败: ' + result.message);
                    }
                } catch (error) {
                    console.error('[HeaSec] 数据库初始化失败:', error);
                    this.showErrorMessage('数据库初始化失败: ' + error.message);
                } finally {
                    this.isInitializing = false;
                }
            },

            // 重置数据库
            async resetDatabase() {
                if (this.resetConfirmationText !== 'YES_RESET_DATABASE') {
                    return;
                }
                this.isResetting = true;
                try {
                    console.log('[HeaSec] 开始重置数据库...');
                    console.log('[HeaSec] 重置选项:', this.resetOptions);

                    const formData = new FormData();
                    formData.append('confirm', 'YES_RESET_DATABASE');
                    formData.append('reset_learning_status', this.resetOptions.resetLearningStatus ? '1' : '0');
                    formData.append('reset_range_databases', this.resetOptions.resetRangeDatabases ? '1' : '0');
                    formData.append('reset_sms_simulator', this.resetOptions.resetSmsSimulator ? '1' : '0');

                    const response = await fetch(`${this.apiBase}reset_database.php`, {
                        method: 'POST',
                        body: formData
                    });

                    const responseText = await response.text();

                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        throw new Error(response.ok ? '响应解析失败: ' + parseError.message : `HTTP ${response.status}: ${response.statusText}`);
                    }

                    if (!response.ok) {
                        throw new Error(result.message || `HTTP ${response.status}: ${response.statusText}`);
                    }

                    if (result.success) {
                        this.showDatabaseResetModal = false;

                        // 检查是否有靶场重置结果需要展示
                        if (result.data && result.data.range_results && result.data.range_results.length > 0) {
                            // 有靶场重置结果，弹出结果模态框
                            this.rangeResetResults = result.data.range_results;
                            const successCount = result.data.range_results.filter(r => r.status === 'success').length;
                            const failedCount = result.data.range_results.filter(r => r.status === 'error').length;
                            this.rangeResetSummary = {
                                success: successCount,
                                failed: failedCount,
                                total: result.data.range_results.length
                            };
                            this.showRangeResetResultModal = true;
                        } else {
                            // 无靶场重置结果，直接刷新
                            this.showSuccessMessage('数据库重置成功！');
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        }
                    } else {
                        this.showErrorMessage('数据库重置失败: ' + result.message);
                    }
                } catch (error) {
                    console.error('[HeaSec] 数据库重置失败:', error);
                    this.showErrorMessage('数据库重置失败: ' + error.message);
                } finally {
                    this.isResetting = false;
                }
            },

            // 获取CSRF令牌
            async getCSRFToken() {
                try {
                    console.log('[HeaSec] 开始获取CSRF令牌...');
                    const response = await fetch(`${this.apiBase}check_database.php`, {
                        method: 'GET',
                        credentials: 'include', // 确保发送cookies
                        headers: {
                            'Cache-Control': 'no-cache'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const result = await response.json();
                    console.log('[HeaSec] CSRF令牌获取结果:', result);

                    if (result.success && result.data && result.data.csrf_token) {
                        return result.data.csrf_token;
                    } else {
                        throw new Error('CSRF令牌获取失败: 响应格式错误');
                    }
                } catch (error) {
                    console.error('[HeaSec] 获取CSRF令牌失败:', error);
                    // 返回一个备用令牌，依赖后端的备用验证
                    return 'backup-token';
                }
            },

            // 显示成功消息
            showSuccessMessage(message) {
                const toast = document.createElement('div');
                toast.className = 'heasec-toast success';
                toast.innerHTML = `<i class="fa fa-check-circle"></i><span>${message}</span>`;
                Object.assign(toast.style, {
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    background: '#28a745',
                    color: 'white',
                    padding: '12px 20px',
                    borderRadius: '6px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    zIndex: '10000',
                    fontSize: '14px',
                    boxShadow: '0 4px 12px rgba(40, 167, 69, 0.3)',
                    animation: 'slideInRight 0.3s ease-out'
                });
                document.body.appendChild(toast);
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 3000);
            },

            // 关闭靶场重置结果模态框并刷新页面
            closeRangeResetResultModal() {
                this.showRangeResetResultModal = false;
                this.rangeResetResults = [];
                this.rangeResetSummary = { success: 0, failed: 0, total: 0 };
                window.location.reload();
            },

            // 显示错误消息
            showErrorMessage(message) {
                const toast = document.createElement('div');
                toast.className = 'heasec-toast error';
                toast.innerHTML = `<i class="fa fa-exclamation-circle"></i><span>${message}</span>`;
                Object.assign(toast.style, {
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    background: '#dc3545',
                    color: 'white',
                    padding: '12px 20px',
                    borderRadius: '6px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    zIndex: '10000',
                    fontSize: '14px',
                    boxShadow: '0 4px 12px rgba(220, 53, 69, 0.3)',
                    animation: 'slideInRight 0.3s ease-out'
                });
                document.body.appendChild(toast);
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 5000);
            }
        },

        computed: {
            // 当前标题
            currentTitle() {
                if (this.currentView === 'home') {
                    return '首页';
                }

                let title = '全部分类';

                if (this.selectedThirdLevelCategory) {
                    const thirdCategory = this.thirdLevelCategories.find(tc => tc.id === this.selectedThirdLevelCategory);
                    title = thirdCategory ? thirdCategory.name : '未知分类';
                } else if (this.selectedSubcategory) {
                    const subcategory = this.subcategories.find(sc => sc.id === this.selectedSubcategory);
                    title = subcategory ? subcategory.name : '未知分类';
                } else if (this.selectedCategory) {
                    const category = this.categories.find(c => c.id === this.selectedCategory);
                    title = category ? category.name : '未知分类';
                }

                // HeaSec修复：保存有效的标题状态，用于侧边栏收起时保持显示
                if (title !== '全部分类' && title !== '未知分类') {
                    this.lastValidTitle = title;
                }

                // HeaSec修复：当侧边栏收起且没有有效选择时，使用最后保存的标题
                if (this.sidebarCollapsed && !this.selectedSubcategory && !this.selectedThirdLevelCategory && this.lastValidTitle) {
                    return this.lastValidTitle;
                }

                return title;
            },

            // 当前描述
            currentDescription() {
                if (this.currentView === 'home') {
                    return '';
                }

                // HeaSec修复：在侧边栏收起时，如果有保存的二级分类状态，继续显示其内容
                let effectiveSubcategory = this.selectedSubcategory;
                let effectiveThirdLevelCategory = this.selectedThirdLevelCategory;

                if (this.sidebarCollapsed && this.savedState && this.savedState.selectedSubcategory) {
                    effectiveSubcategory = this.savedState.selectedSubcategory;
                    effectiveThirdLevelCategory = this.savedState.selectedThirdLevelCategory;
                }

                if (effectiveThirdLevelCategory) {
                    const thirdCategory = this.thirdLevelCategories.find(tc => tc.id === effectiveThirdLevelCategory);
                    return thirdCategory ? thirdCategory.description : '';
                } else if (effectiveSubcategory) {
                    const subcategory = this.subcategories.find(sc => sc.id === effectiveSubcategory);
                    return subcategory ? subcategory.description : '';
                } else if (this.selectedCategory) {
                    const category = this.categories.find(c => c.id === this.selectedCategory);
                    return category ? category.description : '';
                }
                return '';
            },

            // 过滤链接
            filteredLinks() {
                if (this.currentView === 'home') {
                    return [];
                }
                if (!this.links || !Array.isArray(this.links)) {
                    return [];
                }

                let filtered = this.links.filter(link => link.status == 1);

                // HeaSec修复：在侧边栏收起时，如果有保存的二级分类状态，继续显示其内容
                let effectiveSubcategory = this.selectedSubcategory;
                let effectiveThirdLevelCategory = this.selectedThirdLevelCategory;

                if (this.sidebarCollapsed && this.savedState && this.savedState.selectedSubcategory) {
                    effectiveSubcategory = this.savedState.selectedSubcategory;
                    effectiveThirdLevelCategory = this.savedState.selectedThirdLevelCategory;
                }

                if (effectiveThirdLevelCategory) {
                    // 选择三级分类时，只显示该三级分类的链接
                    filtered = filtered.filter(link =>
                        String(link.third_level_category_id) === String(effectiveThirdLevelCategory)
                    );
                } else if (effectiveSubcategory) {
                    // 选择二级分类时，显示：
                    // 1. 直属于该二级分类的链接（third_level_category_id为空）
                    // 2. 属于该二级分类下三级分类的链接
                    filtered = filtered.filter(link => {
                        // 直属于二级分类的链接
                        if (String(link.subcategory_id) === String(effectiveSubcategory) &&
                            (!link.third_level_category_id || link.third_level_category_id === null || link.third_level_category_id === 'null' || link.third_level_category_id === '')) {
                            return true;
                        }

                        // 属于该二级分类下三级分类的链接
                        if (link.third_level_category_id) {
                            const thirdLevelCategory = this.thirdLevelCategories.find(tc => tc.id === link.third_level_category_id);
                            if (thirdLevelCategory && String(thirdLevelCategory.subcategory_id) === String(effectiveSubcategory)) {
                                return true;
                            }
                        }

                        return false;
                    });
                } else if (this.selectedCategory) {
                    filtered = filtered.filter(link =>
                        String(link.category_id) === String(this.selectedCategory) &&
                        (!link.subcategory_id || link.subcategory_id === null || link.subcategory_id === 'null' || link.subcategory_id === '') &&
                        (!link.third_level_category_id || link.third_level_category_id === null || link.third_level_category_id === 'null' || link.third_level_category_id === '')
                    );
                }

                return filtered.sort((a, b) => a.sort_order - b.sort_order);
            },

            // 分组链接
            groupedLinks() {
                if (this.currentView === 'home') {
                    return { direct: [], subcategories: {}, thirdLevelCategories: {} };
                }
                const result = {
                    direct: [],
                    subcategories: {},
                    thirdLevelCategories: {}
                };

                // HeaSec修复：在侧边栏收起时，如果有保存的二级分类状态，继续显示其内容
                let effectiveSubcategory = this.selectedSubcategory;
                let effectiveThirdLevelCategory = this.selectedThirdLevelCategory;

                if (this.sidebarCollapsed && this.savedState && this.savedState.selectedSubcategory) {
                    effectiveSubcategory = this.savedState.selectedSubcategory;
                    effectiveThirdLevelCategory = this.savedState.selectedThirdLevelCategory;
                }

                // 选择三级分类时，只显示该三级分类的直属链接
                if (effectiveThirdLevelCategory) {
                    result.direct = this.links.filter(link =>
                        link.status == 1 &&
                        String(link.third_level_category_id) === String(effectiveThirdLevelCategory)
                    ).sort((a, b) => a.sort_order - b.sort_order);
                    return result;
                }

                // 选择二级分类时，显示该二级分类的直属链接和其下的三级分类
                if (effectiveSubcategory) {
                    // 直属于该二级分类的链接
                    result.direct = this.links.filter(link =>
                        link.status == 1 &&
                        String(link.subcategory_id) === String(effectiveSubcategory) &&
                        (!link.third_level_category_id || link.third_level_category_id === null || link.third_level_category_id === 'null' || link.third_level_category_id === '')
                    ).sort((a, b) => a.sort_order - b.sort_order);

                    // 该二级分类下的三级分类链接
                    const thirdCategories = this.getThirdLevelCategoriesBySubcategory(effectiveSubcategory);
                    thirdCategories.forEach(third => {
                        const thirdLinks = this.links.filter(link =>
                            link.status == 1 &&
                            String(link.third_level_category_id) === String(third.id)
                        ).sort((a, b) => a.sort_order - b.sort_order);

                        if (thirdLinks.length > 0) {
                            result.thirdLevelCategories[third.id] = {
                                thirdLevelCategory: third,
                                links: thirdLinks
                            };
                        }
                    });
                    return result;
                }

                // 选择一级分类时，仅显示该一级分类直接下属的链接，不显示二级分类和三级分类
                if (this.selectedCategory) {
                    // 只显示直属于一级分类的链接
                    result.direct = this.links.filter(link =>
                        link.status == 1 &&
                        String(link.category_id) === String(this.selectedCategory) &&
                        (!link.subcategory_id || link.subcategory_id === null || link.subcategory_id === 'null' || link.subcategory_id === '') &&
                        (!link.third_level_category_id || link.third_level_category_id === null || link.third_level_category_id === 'null' || link.third_level_category_id === '')
                    ).sort((a, b) => a.sort_order - b.sort_order);

                    // 不再显示二级分类和三级分类，根据需求只显示一级分类直接下属的链接
                }

                return result;
            },

            // 全局学习统计（所有靶场）
            overallStats() {
                var allLinks = this.links.filter(function(link) { return link.status == 1; });
                var total = allLinks.length;
                var mastered = allLinks.filter(function(l) { return l.learning_status === '已掌握'; }).length;
                var inProgress = allLinks.filter(function(l) { return l.learning_status === '学习中'; }).length;
                var notStarted = allLinks.filter(function(l) { return l.learning_status === '待学习' || !l.learning_status; }).length;
                var masteryRate = total > 0 ? Math.round((mastered / total) * 100) : 0;
                return { total: total, notStarted: notStarted, inProgress: inProgress, mastered: mastered, masteryRate: masteryRate };
            },

            // 一级分类学习统计（汇总所有子分类）
            categoryStats() {
                if (this.currentView === 'home') {
                    return this.overallStats;
                }
                if (!this.selectedCategory || this.selectedSubcategory || this.selectedThirdLevelCategory) {
                    return null;
                }
                // 收集当前一级分类下所有分类ID（含自身、二级、三级）
                var allCategoryIds = [this.selectedCategory];
                var subIds = this.subcategories
                    .filter(s => String(s.category_id) === String(this.selectedCategory) && s.status == 1)
                    .map(s => s.id);
                subIds.forEach(function(sid) { allCategoryIds.push(sid); });
                var thirdIds = this.thirdLevelCategories
                    .filter(t => subIds.some(function(sid) { return String(t.subcategory_id) === String(sid); }) && t.status == 1)
                    .map(t => t.id);
                thirdIds.forEach(function(tid) { allCategoryIds.push(tid); });

                // 筛选属于这些分类的所有链接
                var categoryLinks = this.links.filter(function(link) {
                    return link.status == 1 && allCategoryIds.some(function(cid) {
                        return String(link.category_id) === String(cid);
                    });
                });
                var total = categoryLinks.length;
                var mastered = categoryLinks.filter(function(l) { return l.learning_status === '已掌握'; }).length;
                var inProgress = categoryLinks.filter(function(l) { return l.learning_status === '学习中'; }).length;
                var notStarted = categoryLinks.filter(function(l) { return l.learning_status === '待学习' || !l.learning_status; }).length;
                var masteryRate = total > 0 ? Math.round((mastered / total) * 100) : 0;
                return { total: total, notStarted: notStarted, inProgress: inProgress, mastered: mastered, masteryRate: masteryRate };
            },

            // 是否有结果
            hasResults() {
                if (this.currentView === 'home') {
                    return false;
                }
                if (this.selectedThirdLevelCategory) {
                    return this.filteredLinks.length > 0;
                } else if (this.selectedSubcategory) {
                    return this.filteredLinks.length > 0;
                } else if (this.selectedCategory) {
                    // 一级分类时只检查直属链接
                    return this.groupedLinks.direct.length > 0;
                }
                return false;
            }
        },

        watch: {
            // 监听选择变化，更新页面标题
            currentTitle(newTitle) {
                document.title = `${newTitle} - ${this.team.slogan}`;
            }
        }
    });
}); // 关闭DOMContentLoaded监听器

// 初始化HeaSec全局配置
window.HeaSec = window.HeaSec || {
    team: {
        name: '天积安全',
        nameEn: 'HeavenlySecret',
        abbr: 'HeaSec',
        slogan: '日积寸功，乐享安全'
    },
    siteName: '天积安全靶场平台',
    version: 'v1.01',
    config: {
        enableLogging: true,
        enableErrorTracking: true,
        theme: 'heasec',
        logoPath: 'assets/logo.jpg'
    }
};