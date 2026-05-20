/**
 * HeaSec Vue组件集合
 * @package HeavenlySecret\Frontend
 * @version HeaSec v1.0.0
 */

// 分类项目组件
// 分类项目组件
Vue.component('category-item', {
    props: {
        category: Object,
        subcategories: Array,
        selectedCategory: Number,
        selectedSubcategory: Number,
        collapsedCategories: Object,
        sidebarCollapsed: Boolean
    },
    data() {
        return {
            showFlyout: false,
            flyoutTimer: null,
            flyoutStyle: {}
        };
    },
    template: '<div class="category-section" @mouseleave="handleMouseLeave">' +
        '<div ' +
        'class="category-header" ' +
        ':class="{ ' +
        'active: String(selectedCategory) === String(category.id), ' +
        '\'no-subcategories\': subcategories.length === 0, ' +
        '\'active-parent\': hasActiveChild ' +
        '}" ' +
        '@click="handleCategoryClick" ' +
        '@mouseover="handleMouseOver($event)" ' +
        ':data-title="category.name" ' +
        '>' +
        '<i :class="getCategoryIcon(category.id)"></i> ' +
        '<span>{{ category.name }}</span> ' +
        '<i ' +
        'v-if="subcategories.length > 0" ' +
        'class="toggle-icon" ' +
        ':class="[' +
        '\'fa\',' +
        'isCollapsed ? \'fa-chevron-right\' : \'fa-chevron-down\'' +
        ']" ' +
        '@click.stop="toggleCategory" ' +
        '></i>' +
        '</div>' +

        '<!-- 常规折叠菜单 -->' +
        '<div ' +
        'class="subcategory-list" ' +
        ':class="{ collapsed: isCollapsed }"' +
        '>' +
        '<div ' +
        'v-for="subcategory in subcategories" ' +
        ':key="subcategory.id" ' +
        'class="subcategory-item" ' +
        ':class="{ active: String(selectedSubcategory) === String(subcategory.id) }" ' +
        '@click="selectSubcategory(subcategory.id)" ' +
        '>' +
        '<i class="fa fa-folder-o"></i> ' +
        '<span>{{ subcategory.name }}</span>' +
        '</div>' +
        '</div>' +

        '<!-- 侧边栏收起时的悬浮菜单 -->' +
        '<div ' +
        'v-if="sidebarCollapsed && subcategories.length > 0" ' +
        'class="flyout-menu" ' +
        ':class="{ visible: showFlyout }" ' +
        ':style="flyoutStyle" ' +
        '@mouseover="keepFlyoutOpen" ' +
        '>' +
        '<div class="flyout-header">{{ category.name }}</div>' +
        '<div ' +
        'v-for="subcategory in subcategories" ' +
        ':key="\'flyout-\' + subcategory.id" ' +
        'class="flyout-item" ' +
        ':class="{ active: String(selectedSubcategory) === String(subcategory.id) }" ' +
        '@click="handleFlyoutClick(subcategory.id)" ' +
        '>' +
        '<i class="fa fa-folder-o"></i> ' +
        '<span>{{ subcategory.name }}</span>' +
        '</div>' +
        '</div>' +

        '</div>',
    computed: {
        isCollapsed() {
            return this.collapsedCategories[String(this.category.id)] || false;
        },
        hasActiveChild() {
            if (!this.subcategories || this.subcategories.length === 0) return false;
            // Check if any subcategory is currently selected
            return this.subcategories.some(sub => String(sub.id) === String(this.selectedSubcategory));
        }
    },
    methods: {
        handleCategoryClick() {
            // Emit special event for auto-expand logic
            this.$emit('category-click', this.category.id);
            this.selectCategory();
        },
        getCategoryIcon(categoryId) {
            const iconMap = {
                1: 'fa fa-code',
                2: 'fa fa-paint-brush',
                3: 'fa fa-graduation-cap',
                4: 'fa fa-newspaper-o',
                5: 'fa fa-gamepad'
            };
            return iconMap[categoryId] || 'fa fa-folder';
        },
        selectCategory() {
            this.$emit('select-category', this.category.id);
        },
        selectSubcategory(subcategoryId) {
            this.$emit('select-subcategory', subcategoryId);
        },
        toggleCategory() {
            this.$emit('toggle-category', this.category.id);
        },
        // HeaSec Update: Consolidated hover logic
        handleMouseOver(event) {
            // Show tooltip if needed
            this.$emit('show-tooltip', event, this.category.name);

            // Handle flyout menu
            if (this.sidebarCollapsed && this.subcategories.length > 0) {
                clearTimeout(this.flyoutTimer);

                // Calculate fixed position
                const target = event.target.closest('.category-section');
                if (target) {
                    const rect = target.getBoundingClientRect();
                    this.flyoutStyle = {
                        top: rect.top + 'px',
                        left: (rect.right + 10) + 'px' // 10px gap
                    };
                }

                this.showFlyout = true;
            }
        },
        handleMouseLeave() {
            this.$emit('hide-tooltip');

            if (this.sidebarCollapsed) {
                this.flyoutTimer = setTimeout(() => {
                    this.showFlyout = false;
                }, 300); // 300ms delay to allow moving to the flyout
            }
        },
        keepFlyoutOpen() {
            clearTimeout(this.flyoutTimer);
            this.showFlyout = true;
        },
        handleFlyoutClick(subcategoryId) {
            this.selectSubcategory(subcategoryId);
            this.showFlyout = false;
        }
    }
});

// 学习进度统计面板组件
Vue.component('category-stats', {
    props: {
        stats: Object
    },
    template: '<div class="category-stats-panel">' +
        '<div class="stat-cards-grid">' +
        '<div class="stat-card stat-card-total">' +
        '<div class="stat-card-icon"><i class="fa fa-rocket"></i></div>' +
        '<div class="stat-card-value">{{ stats.total }}</div>' +
        '<div class="stat-card-label">靶场总数</div>' +
        '</div>' +
        '<div class="stat-card stat-card-notstarted">' +
        '<div class="stat-card-icon"><i class="fa fa-clock-o"></i></div>' +
        '<div class="stat-card-value">{{ stats.notStarted }}</div>' +
        '<div class="stat-card-label">未学习</div>' +
        '</div>' +
        '<div class="stat-card stat-card-inprogress">' +
        '<div class="stat-card-icon"><i class="fa fa-spinner"></i></div>' +
        '<div class="stat-card-value">{{ stats.inProgress }}</div>' +
        '<div class="stat-card-label">学习中</div>' +
        '</div>' +
        '<div class="stat-card stat-card-mastered">' +
        '<div class="stat-card-icon"><i class="fa fa-check-circle"></i></div>' +
        '<div class="stat-card-value">{{ stats.mastered }}</div>' +
        '<div class="stat-card-label">已掌握</div>' +
        '</div>' +
        '</div>' +
        '<div class="stat-progress-section">' +
        '<div class="stat-progress-header">' +
        '<div class="stat-progress-left">' +
        '<span class="stat-progress-label"><i class="fa fa-trophy"></i> 学习进度</span>' +
        '<span class="stat-progress-hint">完成靶场内的学习任务会自动更新学习状态</span>' +
        '</div>' +
        '<span class="stat-progress-percent">{{ stats.masteryRate }}%</span>' +
        '</div>' +
        '<div class="stat-progress-bar">' +
        '<div class="stat-progress-fill" :style="{ width: stats.masteryRate + \'%\' }"></div>' +
        '</div>' +
        '</div>' +
        '</div>'
});

// 首页介绍组件
Vue.component('home-intro', {
    data() {
        return {
            expandedSections: { usage: true, contact: true }
        };
    },
    template: '<div class="home-intro">' +
        '<!-- 平台简介 -->' +
        '<div class="home-intro-card home-brief-card">' +
        '<div class="home-brief-icon"><i class="fa fa-shield"></i></div>' +
        '<div class="home-brief-content">' +
        '<p>本系统是<strong>天积安全团队（HeavenlySecret）</strong>开发的WEB安全靶场平台，适合于<strong>0基础</strong>的用户作为入门学习使用或已有一定基础的用户检验和提升WEB安全技能，也可以作为安全测试的环境。<br />平台由一系列相互独立的安全靶场组成，<strong class="text-highlight-blue">通过左侧导航选择分类并点击靶场卡片</strong>即可进入对应的靶场，完成靶场的任务会<strong class="text-highlight-green">自动更新学习进度</strong>。</p>' +
        '</div>' +
        '</div>' +

        '<slot></slot>' +

        '<!-- 手风琴板块 -->' +
        '<div class="home-accordion">' +
        '<!-- 基础配置 -->' +
        '<div class="home-accordion-item" :class="{ expanded: expandedSections.config }">' +
        '<div class="home-accordion-header" @click="toggleSection(\'config\')">' +
        '<div class="home-accordion-title"><i class="fa fa-cogs"></i><span>基础配置</span></div>' +
        '<i class="fa home-accordion-arrow" :class="expandedSections.config ? \'fa-chevron-down\' : \'fa-chevron-right\'"></i>' +
        '</div>' +
        '<div class="home-accordion-body" v-show="expandedSections.config">' +
        '<div class="home-config-grid">' +
        '<div class="home-config-item">' +
        '<div class="home-config-label"><i class="fa fa-server"></i> 推荐服务器环境</div>' +
        '<div class="home-config-value">PHP 7.3.4，MySQL 5.7+，Apache 2.4+，建议使用<strong>PHPstudy</strong>集成环境进行部署，版本过低可能会导致靶场无法运行，版本过高可能会导致部分漏洞无法利用。</div>' +
        '</div>' +
        '<div class="home-config-item">' +
        '<div class="home-config-label"><i class="fa fa-database"></i> 数据库配置</div>' +
        '<div class="home-config-value">' +
        '<ul class="home-config-list">' +
        '<li>在 <code>config/config.php</code> 中配置数据库连接参数，首次访问时会自动提示用户初始化数据库，无需手动创建数据库，如有异常请检查数据库连接参数是否正确。</li>' +
        '<li>使用前台页面右上角的重置按钮可以重置数据库，默认仅重置前台数据库链接，可以根据需要勾选是否重置靶场后台数据库和学习进度。</li>' +
        '<li>数据库前缀：默认使用 <code>heasec_</code>，<code>heasec_cms</code>为平台前台使用数据库，其他数据库为靶场使用的数据库。</li>' +
        '</ul>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +

        '<!-- 靶场使用 -->' +
        '<div class="home-accordion-item" :class="{ expanded: expandedSections.usage }">' +
        '<div class="home-accordion-header" @click="toggleSection(\'usage\')">' +
        '<div class="home-accordion-title"><i class="fa fa-gamepad"></i><span>靶场使用</span></div>' +
        '<i class="fa home-accordion-arrow" :class="expandedSections.usage ? \'fa-chevron-down\' : \'fa-chevron-right\'"></i>' +
        '</div>' +
        '<div class="home-accordion-body" v-show="expandedSections.usage">' +
        '<div class="heasec-alert-box">' +
        '<div class="heasec-alert-icon"><i class="fa fa-info-circle"></i></div>' +
        '<div class="heasec-alert-content">' +
        '每个靶场根据不同的模式有不同的任务要求，<strong class="text-highlight-green">阅读靶场页面的提示和右上角的靶场说明</strong>可以了解任务要求通关靶场，<strong class="text-highlight-blue">除互动教学模式外，其他模式不提供具体操作过程</strong>，请根据提示<strong class="text-highlight-orange">自行通过互联网或者AI学习</strong>相关技术完成任务操作，也可以<strong class="text-highlight-blue">关注天积安全公众号</strong>获取官方通关攻略（持续更新中……）。' +
        '</div>' +
        '</div>' +

        '<div class="home-subsection">' +
        '<div class="home-subsection-title"><i class="fa fa-puzzle-piece"></i> 靶场模式</div>' +
        '<div class="home-mode-grid">' +
        '<div class="home-mode-card">' +
        '<div class="home-mode-name"><i class="fa fa-graduation-cap"></i> 互动教学模式</div>' +
        '<div class="home-mode-desc">通常用于介绍基础知识，阅读教学内容并根据提示完成练习操作，<strong class="text-highlight-green">点击我已掌握按钮</strong>即可完成通关。</div>' +
        '</div>' +
        '<div class="home-mode-card">' +
        '<div class="home-mode-name"><i class="fa fa-key"></i> 秘密验证模式</div>' +
        '<div class="home-mode-desc">类似CTF模式，需要根据提示完成具体的漏洞利用操作<strong class="text-highlight-orange">获取秘密字符串在页面提交</strong>后即可完成通关，<strong class="text-highlight-blue">秘密字符串具有随机性</strong>，会话变更或重置数据库后秘密字符串会改变。</div>' +
        '</div>' +
        '<div class="home-mode-card">' +
        '<div class="home-mode-name"><i class="fa fa-flag-checkered"></i> 连续闯关模式</div>' +
        '<div class="home-mode-desc">通常有2-3关，需要按照提示完成每关的攻击任务，<strong class="text-highlight-blue">达到目标要求后会出现下一关按钮</strong>进入下一关，直到完成所有关卡后靶场通关。</div>' +
        '</div>' +
        '<div class="home-mode-card">' +
        '<div class="home-mode-name"><i class="fa fa-trophy"></i> 成就系统模式</div>' +
        '<div class="home-mode-desc">通常会要求<strong class="text-highlight-blue">使用不同的攻击技术完成相同的攻击目标</strong>，根据使用不同的攻击方式数量触发成就，<strong class="text-highlight-orange">获得三星成就后即可通关</strong>靶场。</div>' +
        '</div>' +
        '<div class="home-mode-card">' +
        '<div class="home-mode-name"><i class="fa fa-search"></i> 漏洞挖掘模式</div>' +
        '<div class="home-mode-desc">模拟真实场景中的漏洞挖掘过程，需要发现靶场中存在的漏洞并在<strong class="text-highlight-blue">漏洞提交表单中提交</strong>发现的漏洞，根据正确提交的漏洞获得不同的分数奖励，<strong class="text-highlight-green">达到指定分数后即可通关</strong>靶场。</div>' +
        '</div>' +
        '</div>' +
        '</div>' +

        '<div class="home-subsection">' +
        '<div class="home-subsection-title"><i class="fa fa-signal"></i> 靶场难度</div>' +
        '<div class="home-difficulty-list">' +
        '<div class="home-difficulty-item">' +
        '<span class="home-difficulty-badge difficulty-basic">基础</span>' +
        '<span class="home-difficulty-desc">通常用于介绍漏洞的基础原理和操作，有较强的提示信息。</span>' +
        '</div>' +
        '<div class="home-difficulty-item">' +
        '<span class="home-difficulty-badge difficulty-intermediate">进阶</span>' +
        '<span class="home-difficulty-desc">通常用于介绍某些复杂或高级的技巧，需要相对复杂的思考和操作。</span>' +
        '</div>' +
        '<div class="home-difficulty-item">' +
        '<span class="home-difficulty-badge difficulty-practical">实战</span>' +
        '<span class="home-difficulty-desc">通常模拟真实的业务场景或需要综合利用多种技术，提示信息较少。</span>' +
        '</div>' +
        '<div class="home-difficulty-item">' +
        '<span class="home-difficulty-badge difficulty-advanced">拓展</span>' +
        '<span class="home-difficulty-desc">通常用于介绍一些只有在特定应用场景下才存在的漏洞或攻击技术。</span>' +
        '</div>' +
        '</div>' +
        '</div>' +

        '<div class="home-subsection">' +
        '<div class="home-subsection-title"><i class="fa fa-lightbulb-o"></i> 提示信息</div>' +
        '<div class="home-tips-cards">' +
        
        '<div class="home-tip-card">' +
        '<div class="tip-icon tip-icon-shield"><i class="fa fa-shield"></i></div>' +
        '<div class="tip-content">' +
        '<div class="tip-title">会话与数据隔离</div>' +
        '<div class="tip-desc">每个靶场都是独立运行的，靶场使用自己的会话ID（<code class="heasec-code-highlight">HEASEC_RANGE_XXXX_SESSION</code>，XXXX为靶场ID）实现会话隔离，部分靶场可能会和其他靶场共享数据库，但数据库表前缀不同（<code class="heasec-code-highlight">heasec_</code> + 靶场ID），不会影响到其他靶场的数据。</div>' +
        '</div>' +
        '</div>' +

        '<div class="home-tip-card">' +
        '<div class="tip-icon tip-icon-refresh"><i class="fa fa-refresh"></i></div>' +
        '<div class="tip-content">' +
        '<div class="tip-title">独立重置功能</div>' +
        '<div class="tip-desc">每个靶场右上角的重置按钮可以将当前靶场重置为初始化状态，重置靶场<strong class="text-highlight-green">不会影响当前靶场的学习进度</strong>，也不会影响到其他靶场的数据。注意重置靶场后通关密码或其他一些随机性生成的数据会改变。</div>' +
        '</div>' +
        '</div>' +

        '<div class="home-tip-card">' +
        '<div class="tip-icon tip-icon-mobile"><i class="fa fa-mobile" style="font-size: 1.2em;"></i></div>' +
        '<div class="tip-content">' +
        '<div class="tip-title">短信模拟器</div>' +
        '<div class="tip-desc">对于需要使用手机验证码的靶场，<strong class="text-highlight-blue">可以点击靶场右上角的短信模拟器来模拟手机操作</strong>，短信模拟器的数据库独立于平台前台和靶场，具体操作可以参考短信模拟器的说明。</div>' +
        '</div>' +
        '</div>' +

        '<div class="home-tip-card">' +
        '<div class="tip-icon tip-icon-code"><i class="fa fa-code"></i></div>' +
        '<div class="tip-content">' +
        '<div class="tip-title">前端代码分析指引</div>' +
        '<div class="tip-desc">靶场前端代码中会包含靶场的头部、底部和通用的交互组件，这些组件与靶场的任务无关无需关注，分析前端代码时只要重点关注 <code class="heasec-code-highlight">&lt;!-- 靶场主要内容 --&gt;</code> 的区域即可。</div>' +
        '</div>' +
        '</div>' +

        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +

        '<!-- 联系我们 -->' +
        '<div class="home-accordion-item" :class="{ expanded: expandedSections.contact }">' +
        '<div class="home-accordion-header" @click="toggleSection(\'contact\')">' +
        '<div class="home-accordion-title"><i class="fa fa-comments-o"></i><span>联系我们</span></div>' +
        '<i class="fa home-accordion-arrow" :class="expandedSections.contact ? \'fa-chevron-down\' : \'fa-chevron-right\'"></i>' +
        '</div>' +
        '<div class="home-accordion-body" v-show="expandedSections.contact">' +
        '<div class="home-contact-list">' +
        '<div class="home-contact-item">' +
        '<i class="fa fa-github"></i>' +
        '<div class="home-contact-info">' +
        '<div class="home-contact-label">开源项目地址 (GitHub)</div>' +
        '<a class="home-contact-link" href="https://github.com/HeaSec/" target="_blank" rel="noopener">https://github.com/HeaSec/</a>' +  
        '</div>' +
        '</div>' +
        '<div class="home-contact-item">' +
        '<i class="fa fa-git-square" style="color: #c71d23;"></i>' +
        '<div class="home-contact-info">' +
        '<div class="home-contact-label">开源项目地址 (Gitee)</div>' +
        '<a class="home-contact-link" href="https://gitee.com/HeaSec/" target="_blank" rel="noopener">https://gitee.com/HeaSec/</a>' +
        '</div>' +
        '</div>' +
        '<div class="home-contact-item">' +
        '<i class="fa fa-wechat"></i>' +
        '<div class="home-contact-info">' +
        '<div class="home-contact-label">关注微信公众号：天积安全</div>' +
        '<div style="margin-top: 12px;">' +
        '<img src="assets/gzhewm.jpg" alt="天积安全公众号二维码" style="max-width: 160px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); border: 1px solid rgba(0,0,0,0.05);">' +
        '<div class="home-contact-desc">关注公众号后可以加入微信群进行交流</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +

        '<!-- 安全警告 -->' +
        '<div class="home-warning-card">' +
        '<div class="home-warning-header"><i class="fa fa-exclamation-triangle"></i> 安全警告</div>' +
        '<div class="home-warning-content">' +
        '<p>本平台为开源网络安全训练环境，仅供用户进行<strong>合法</strong>的安全学习、技术研究与攻防演练。<strong>严禁</strong>利用本平台及相关技术从事任何危害网络安全、侵犯他人权益或违反现行法律法规的活动。</p>' +
        '</div>' +
        '<div class="home-warning-severe">' +
        '<div class="home-warning-severe-title"><i class="fa fa-ban"></i> 特别警告</div>' +
        '<p>本平台代码<strong>故意包含大量已知安全漏洞</strong>，仅适合在本地隔离环境或严格访问控制的内部网络部署。<strong>切勿直接部署于互联网</strong>，否则极易导致服务器被非法入侵或滥用。因不当部署引发的安全事件及法律责任，由部署者自行承担，项目贡献者不承担任何责任。</p>' +
        '</div>' +
        '</div>' +

        '</div>',
    methods: {
        toggleSection(section) {
            this.$set(this.expandedSections, section, !this.expandedSections[section]);
        }
    }
});

// 分类描述组件
Vue.component('category-description', {
    props: {
        description: String
    },
    template: '<div class="category-description-container">' +
        '<div class="category-description-content">' +
        '<div style="display: flex; align-items: flex-start;">' +
        '<i class="fa fa-info-circle description-icon"></i> ' +
        '<div class="description-text">{{ description }}</div>' +
        '</div>' +
        '</div>' +
        '</div>'
});

// 链接卡片组件
Vue.component('link-card', {
    props: {
        link: Object
    },
    template: '<div class="card heasec-card" @click="openLink">' +
        '<div class="card-content">' +
        '<div class="card-title-wrapper">' +
        '<span class="difficulty-badge" :class="getDifficultyClass(link.difficulty)">{{ getDifficultyText(link.difficulty) }}</span>' +
        '<h3 class="card-title">' +
        '{{ link.title }} ' +
        '</h3>' +
        '<i class="fa fa-external-link card-external-link"></i>' +
        '</div>' +
        '<p class="card-description">{{ link.description }}</p>' +
        '</div>' +
        '<div ' +
        'class="learning-status-container" ' +
        ':data-learning-status="link.id" ' +
        '@click.stop="updateLearningStatus" ' +
        '>' +
        '<i class="fa fa-star learning-star" :class="getLearningStatusClass(link.learning_status)"></i>' +
        '<span class="learning-status-text" :class="getLearningStatusClass(link.learning_status)">{{ getLearningStatusText(link.learning_status) }}</span>' +
        '</div>' +
        '</div>',
    methods: {
        getDifficultyClass(difficulty) {
            const classMap = {
                '基础': 'difficulty-basic',
                '进阶': 'difficulty-intermediate',
                '拓展': 'difficulty-advanced',
                '实战': 'difficulty-practical'
            };
            return classMap[difficulty] || 'difficulty-basic';
        },

        getDifficultyText(difficulty) {
            return difficulty || '基础';
        },

        getLearningStatusText(status) {
            return status || '待学习';
        },

        getLearningStatusClass(status) {
            const classMap = {
                '待学习': 'not_started',
                '学习中': 'in_progress',
                '已掌握': 'mastered'
            };
            return classMap[status] || 'not_started';
        },

        openLink() {
            this.$emit('open-link', this.link.url);
        },

        updateLearningStatus() {
            this.$emit('update-learning-status', this.link.id, this.link.learning_status || '待学习');
        }
    }
});

// 链接卡片容器组件
Vue.component('link-cards', {
    props: {
        filteredLinks: Array,
        groupedLinks: Object,
        selectedSubcategory: Number,
        selectedThirdLevelCategory: Number,
        collapsedSubcategories: Object,
        collapsedThirdLevelCategories: Object
    },
    template: '<div>' +
        '<!-- 三级分类的直接链接 -->' +
        '<div v-if="selectedThirdLevelCategory && filteredLinks.length > 0">' +
        '<div class="cards-grid">' +
        '<link-card v-for="link in filteredLinks" :key="link.id" :link="link" @open-link="openLink" @update-learning-status="updateLearningStatus"></link-card>' +
        '</div>' +
        '</div>' +

        '<!-- 二级分类的直接链接和三级分类分组 -->' +
        '<div v-else-if="selectedSubcategory && filteredLinks.length > 0">' +
        '<!-- 当前二级分类的直接链接，直接展示 -->' +
        '<div v-if="getDirectLinksForSubcategory().length > 0" class="cards-grid">' +
        '<link-card v-for="link in getDirectLinksForSubcategory()" :key="link.id" :link="link" @open-link="openLink" @update-learning-status="updateLearningStatus"></link-card>' +
        '</div>' +

        '<!-- 三级分类分组显示，与二级分类展示形式一致 -->' +
        '<div ' +
        'v-for="(data, thirdLevelCategoryId) in getThirdLevelCategoriesForSubcategory()" ' +
        ':key="thirdLevelCategoryId" ' +
        'class="links-section"' +
        '>' +
        '<div ' +
        'class="section-header collapsible" ' +
        ':class="{ collapsed: isThirdLevelCategoryCollapsed(data.thirdLevelCategory.id) }" ' +
        '@click="toggleThirdLevelCategory(data.thirdLevelCategory.id)" ' +
        '>' +
        '<h3>' +
        '<i :class="isThirdLevelCategoryCollapsed(data.thirdLevelCategory.id) ? \'fa fa-folder-o\' : \'fa fa-folder-open-o\'"></i> ' +
        '{{ data.thirdLevelCategory.name }} ' +
        '<span class="link-count">({{ data.links.length }})</span>' +
        '</h3>' +
        '<i ' +
        'class="toggle-icon" ' +
        ':class="[' +
        '\'fa\',' +
        'isThirdLevelCategoryCollapsed(data.thirdLevelCategory.id) ? \'fa-chevron-right\' : \'fa-chevron-down\'' +
        ']" ' +
        '></i>' +
        '</div>' +
        '<div class="cards-grid" :class="{ collapsed: isThirdLevelCategoryCollapsed(data.thirdLevelCategory.id) }">' +
        '<link-card v-for="link in data.links" :key="link.id" :link="link" @open-link="openLink" @update-learning-status="updateLearningStatus"></link-card>' +
        '</div>' +
        '</div>' +
        '</div>' +

        '<!-- 一级分类的分组链接 -->' +
        '<div v-else-if="!selectedSubcategory && !selectedThirdLevelCategory">' +
        '<!-- 直属链接 -->' +
        '<div v-if="groupedLinks.direct.length > 0" class="cards-grid direct-links">' +
        '<link-card v-for="link in groupedLinks.direct" :key="link.id" :link="link" @open-link="openLink" @update-learning-status="updateLearningStatus"></link-card>' +
        '</div>' +

        '<!-- 二级分类链接 -->' +
        '<div ' +
        'v-for="(data, subcategoryId) in groupedLinks.subcategories" ' +
        ':key="subcategoryId" ' +
        'class="links-section" ' +
        '>' +
        '<div ' +
        'class="section-header collapsible" ' +
        ':class="{ collapsed: isSubcategoryCollapsed(data.subcategory.id) }" ' +
        '@click="toggleSubcategory(data.subcategory.id)" ' +
        '>' +
        '<h3>' +
        '<i :class="isSubcategoryCollapsed(data.subcategory.id) ? \'fa fa-folder-o\' : \'fa fa-folder-open\'"></i> ' +
        '{{ data.subcategory.name }} ' +
        '<span class="link-count">({{ data.links.length }})</span>' +
        '</h3>' +
        '<i ' +
        'class="toggle-icon" ' +
        ':class="[' +
        '\'fa\',' +
        'isSubcategoryCollapsed(data.subcategory.id) ? \'fa-chevron-right\' : \'fa-chevron-down\'' +
        ']" ' +
        '></i>' +
        '</div>' +
        '<div class="cards-grid" :class="{ collapsed: isSubcategoryCollapsed(data.subcategory.id) }">' +
        '<link-card v-for="link in data.links" :key="link.id" :link="link" @open-link="openLink" @update-learning-status="updateLearningStatus"></link-card>' +
        '</div>' +
        '</div>' +

        '<!-- 三级分类链接 -->' +
        '<div ' +
        'v-for="(data, thirdLevelCategoryId) in groupedLinks.thirdLevelCategories" ' +
        ':key="thirdLevelCategoryId" ' +
        'class="links-section" ' +
        '>' +
        '<div ' +
        'class="section-header collapsible third-level" ' +
        ':class="{ collapsed: isThirdLevelCategoryCollapsed(data.thirdLevelCategory.id) }" ' +
        '@click="selectThirdLevelCategory(data.thirdLevelCategory.id)" ' +
        '>' +
        '<h3>' +
        '<i :class="isThirdLevelCategoryCollapsed(data.thirdLevelCategory.id) ? \'fa fa-folder-o\' : \'fa fa-folder-open\'"></i> ' +
        '{{ data.thirdLevelCategory.name }} ' +
        '<span class="link-count">({{ data.links.length }})</span>' +
        '</h3>' +
        '<i ' +
        'class="toggle-icon" ' +
        ':class="[' +
        '\'fa\',' +
        'isThirdLevelCategoryCollapsed(data.thirdLevelCategory.id) ? \'fa-chevron-right\' : \'fa-chevron-down\'' +
        ']" ' +
        '></i>' +
        '</div>' +
        '<div class="cards-grid" :class="{ collapsed: isThirdLevelCategoryCollapsed(data.thirdLevelCategory.id) }">' +
        '<link-card v-for="link in data.links" :key="link.id" :link="link" @open-link="openLink" @update-learning-status="updateLearningStatus"></link-card>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>',
    methods: {
        isSubcategoryCollapsed(subcategoryId) {
            return this.collapsedSubcategories[String(subcategoryId)] || false;
        },
        toggleSubcategory(subcategoryId) {
            this.$emit('toggle-subcategory', subcategoryId);
        },
        isThirdLevelCategoryCollapsed(thirdLevelCategoryId) {
            return this.collapsedThirdLevelCategories[String(thirdLevelCategoryId)] || false;
        },
        toggleThirdLevelCategory(thirdLevelCategoryId) {
            this.$emit('toggle-third-level-category', thirdLevelCategoryId);
        },
        selectThirdLevelCategory(thirdLevelCategoryId) {
            this.$emit('select-third-level-category', thirdLevelCategoryId);
        },
        openLink(url) {
            this.$emit('open-link', url);
        },
        updateLearningStatus(linkId, currentStatus) {
            this.$emit('update-learning-status', linkId, currentStatus);
        },
        // 获取当前二级分类的直接链接（不属于任何三级分类的链接）
        getDirectLinksForSubcategory() {
            if (!this.selectedSubcategory) return [];
            return this.filteredLinks.filter(link => !link.third_level_category_id);
        },
        // 获取当前二级分类的名称
        getSubcategoryName() {
            if (!this.selectedSubcategory) return '';
            const subcategory = this.filteredLinks.find(link => link.subcategory_id === this.selectedSubcategory);
            return subcategory ? subcategory.subcategory_name : '';
        },
        // 获取当前二级分类下的三级分类及其链接
        getThirdLevelCategoriesForSubcategory() {
            if (!this.selectedSubcategory) return {};

            const thirdLevelCategories = {};

            // 从filteredLinks中获取属于当前二级分类的链接
            this.filteredLinks.forEach(link => {
                if (link.third_level_category_id) {
                    const thirdLevelId = String(link.third_level_category_id);
                    if (!thirdLevelCategories[thirdLevelId]) {
                        thirdLevelCategories[thirdLevelId] = {
                            thirdLevelCategory: {
                                id: link.third_level_category_id,
                                name: link.third_level_category_name
                            },
                            links: []
                        };
                    }
                    thirdLevelCategories[thirdLevelId].links.push(link);
                }
            });

            return thirdLevelCategories;
        },
        // HeaSec修复：动态计算卡片宽度，确保网格统一且不截断
        adjustCardWidths() {
            // 使用requestAnimationFrame避免由于DOM未即使更新导致的计算错误
            requestAnimationFrame(() => {
                const container = this.$el;
                if (!container) return;

                const titles = container.querySelectorAll('.card-title');
                // 恢复默认宽度，以便在内容变短时能收缩
                // 我们通过移除内联样式来重置，让CSS的默认值(280px)生效
                container.style.removeProperty('--card-min-width');

                if (titles.length === 0) return;

                let maxTitleWidth = 0;

                // 使用克隆节点测量内容的真实自然宽度（不受当前容器宽度影响）
                titles.forEach(title => {
                    // 深克隆
                    const clone = title.cloneNode(true);

                    // 获取计算样式以确保字体渲染一致
                    const style = window.getComputedStyle(title);

                    // 设置样式强制自然宽度，并复制关键字体属性
                    clone.style.position = 'absolute';
                    clone.style.visibility = 'hidden';
                    clone.style.width = 'auto'; // 关键：解除宽度限制
                    clone.style.whiteSpace = 'nowrap';
                    clone.style.left = '-9999px';

                    // 复制关键文字属性
                    clone.style.fontFamily = style.fontFamily;
                    clone.style.fontSize = style.fontSize;
                    clone.style.fontWeight = style.fontWeight;
                    clone.style.letterSpacing = style.letterSpacing;
                    clone.style.textTransform = style.textTransform;
                    // 必须追加到body以确保计算正确(有些属性可能依赖根元素)
                    document.body.appendChild(clone);

                    const width = clone.offsetWidth;
                    if (width > maxTitleWidth) {
                        maxTitleWidth = width;
                    }

                    // 清理
                    document.body.removeChild(clone);
                });

                // 4. 计算合适列宽: 最大文字宽 + 卡片Padding(约32px) + 图标间距(约15px) + 难度标签(约60px) + 安全余量
                // 当前布局：Padding 15px * 2 = 30px
                // 标题左侧可能有难度标签(60-80px)？不，标题在单独一行或旁边。
                // 结构是: Badge + Title + ExternalLink
                // Badge width ~60px, Title flex, Link ~20px. 
                // 我们测量的只是Title文字。所以 CardMinWidth = TitleWidth + BadgeWidth(80) + Icon(20) + Paddings(40) + Gaps(20)
                // 估算：Title + 140px

                const optimalWidth = Math.max(280, maxTitleWidth + 140);

                console.log('[HeaSec Debug] Measured Max Title Width:', maxTitleWidth, 'Optimal Card Width:', optimalWidth);

                // 5. 应用到整个容器 (利用CSS继承)
                container.style.setProperty('--card-min-width', optimalWidth + 'px');
            });
        },
        // 设置ResizeObserver以监听容器大小变化(虽然主要受内容影响，但布局变化也可能需要重算)
        setupResizeObserver() {
            if (this.resizeObserver) return;
            this.resizeObserver = new ResizeObserver(() => {
                // 防抖，避免过于频繁
                if (this.resizeTimer) clearTimeout(this.resizeTimer);
                this.resizeTimer = setTimeout(() => {
                    this.adjustCardWidths();
                }, 100);
            });
            this.resizeObserver.observe(this.$el);
        }
    },
    mounted() {
        this.adjustCardWidths();
        this.setupResizeObserver();
        // 额外监听窗口resize作为后备
        window.addEventListener('resize', this.adjustCardWidths);
    },
    updated() {
        // 数据更新(如筛选)后必须重算
        this.adjustCardWidths();
    },
    beforeDestroy() {
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }
        window.removeEventListener('resize', this.adjustCardWidths);
    }
});