<?php
// 引入HeaSec系统引导文件
require_once 'bootstrap.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="天积安全 (HeavenlySecret) - 日积寸功，乐享安全。提供全面的网络安全靶场与学习资源。">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <title>WEB安全基础知识 - 日积寸功，乐享安全</title>

    <!-- 团队Meta信息 -->
    <meta name="author" content="天积安全 HeavenlySecret">
    <meta name="keywords" content="天积安全,HeavenlySecret,HeaSec,靶场平台,安全管理,网络安全">
    <meta name="description" content="天积安全靶场平台 - 日积寸功，乐享安全">
    <meta name="generator" content="HeavenlySecret/HeaSec v<?php echo HEASEC_VERSION; ?> (Enhanced Path Manager)">

    <!-- 样式文件 - 使用相对路径 -->
    <link rel="stylesheet" href="css/style.css?v=1.0.1">
    <link rel="stylesheet" href="css/heasec_style.css?v=1.0.3">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">

    <!-- 提取内联样式到单独的CSS文件中 -->
    <link rel="stylesheet" href="css/index.css?v=1.0.0">

    <!-- Vue.js - 使用相对路径 -->
    <script src="assets/js/vue.js"></script>

    <!-- Vue组件 - 使用相对路径 -->
    <script src="js/vue-components.js?v=1.1.0"></script>

    <!-- HeaSec优化：移除复杂的JavaScript自适应组件，使用CSS clamp()替代 -->

    <!-- Vue应用 - 使用相对路径 -->
    <script src="js/app.js?v=<?php echo time(); ?>"></script>

    <!-- HeaSec优化：简化JavaScript，移除复杂的自适应组件 -->
</head>

<body>
    <div id="app">
        <!-- 顶部导航栏 -->
        <header class="top-header heasec-header-stable">
            <div class="header-content">
                <!-- 左上角Logo - 使用相对路径 -->
                <div class="logo-section">
                    <img src="assets/logo.jpg" alt="HeavenlySecret" class="main-logo">
                </div>

                <!-- 中间标题和口号 -->
                <div class="title-section heasec-title-container">
                    <div class="title-slogan-container">
                        <h1 class="main-title heasec-title-no-wrap">WEB安全靶场平台</h1>
                        <span class="main-slogan"><?php echo HEASEC_TEAM_SLOGAN; ?></span>
                    </div>
                </div>

                <!-- 右上角重置按钮和版本号 - HeaSec优化布局 -->
                <div class="version-section">
                    <button class="reset-database-btn" id="resetDatabaseBtn" title="重置数据库">
                        <i class="fa fa-refresh"></i>
                        <span class="btn-text">重置</span>
                    </button>
                    <span class="version-badge"><?php echo HEASEC_VERSION; ?></span>
                </div>
            </div>
        </header>

        <!-- 主要内容区域 -->
        <div class="content-wrapper">
            <div class="main-container">
                <!-- 左侧导航栏 -->
                <aside class="sidebar" :class="{ 'collapsed': sidebarCollapsed }">
                    <div class="sidebar-toggle" @click="toggleSidebar">
                        <i class="fa fa-angle-left" v-if="!sidebarCollapsed"></i>
                        <i class="fa fa-angle-right" v-else></i>
                    </div>
                    <div class="sidebar-scroll-wrapper">
                        <div class="sidebar-content">
                            <div class="home-nav-item"
                                :class="{ active: currentView === 'home' }"
                                @click="selectHome">
                                <i class="fa fa-home"></i>
                                <span>首页</span>
                            </div>
                            <category-item v-for="category in categories" :key="category.id" :category="category"
                                :subcategories="getSubcategoriesByCategory(category.id)"
                                :selected-category="selectedCategory" :selected-subcategory="selectedSubcategory"
                                :collapsed-categories="collapsedCategories" :sidebar-collapsed="sidebarCollapsed"
                                @select-category="selectCategory" @select-subcategory="selectSubcategory"
                                @toggle-category="toggleCategory" />
                        </div>
                    </div>
                </aside>

                <!-- 右侧内容区 -->
                <main class="content-area">
                    <div class="content-header">
                        <h2>{{ currentTitle }}</h2>

                    </div>

                    <!-- 首页视图 -->
                    <div v-if="currentView === 'home'">
                        <home-intro>
                            <category-stats v-if="overallStats" :stats="overallStats"></category-stats>
                        </home-intro>
                    </div>

                    <!-- 分类视图 -->
                    <div v-if="currentView !== 'home'">

                    <!-- 分类描述 -->
                    <category-description v-if="currentDescription"
                        :description="currentDescription"></category-description>

                    <!-- 学习进度统计 -->
                    <category-stats v-if="categoryStats" :stats="categoryStats"></category-stats>

                    <div class="cards-container">
                        <link-cards :filtered-links="filteredLinks" :grouped-links="groupedLinks"
                            :selected-subcategory="selectedSubcategory"
                            :selected-third-level-category="selectedThirdLevelCategory"
                            :collapsed-subcategories="collapsedSubcategories"
                            :collapsed-third-level-categories="collapsedThirdLevelCategories"
                            @toggle-subcategory="toggleSubcategory"
                            @toggle-third-level-category="toggleThirdLevelCategory"
                            @select-third-level-category="selectThirdLevelCategory" @open-link="openLink"
                            @update-learning-status="updateLearningStatus" />

                        <div class="no-results" v-show="!hasResults">
                            <i class="fa fa-search"></i>
                            <p>暂无相关链接</p>
                        </div>
                    </div>

                    </div>
                </main>
            </div>

            <!-- 底部版权信息 -->
            <footer class="footer">
                <div class="footer-content">
                    <p>&copy; 2026 {{ team.name }}. All rights reserved.</p>
                    <p>Made with <i class="fa fa-heart" style="color: #e74c3c;"></i> by {{ team.nameEn }} Team</p>
                </div>
            </footer>
        </div>

        <!-- 数据库初始化提示模态框（增强版：支持详细错误显示） -->
        <div class="modal-overlay" id="databaseInitModal" v-show="showDatabaseInitModal" v-cloak>
            <div class="modal-container">
                <div class="modal-header">
                    <h3><i class="fa fa-database"></i> {{ databaseInitErrorInfo ? databaseInitErrorInfo.title : '数据库初始化提示' }}</h3>
                    <button class="modal-close" @click="showDatabaseInitModal = false">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- 错误详情显示区域 -->
                    <div v-if="databaseInitErrorInfo" class="error-detail-section">
                        <div class="error-type-icon" :class="'error-type-' + databaseInitErrorInfo.type">
                            <i class="fa" :class="{
                                'fa-server': databaseInitErrorInfo.type === 'server_unreachable',
                                'fa-database': databaseInitErrorInfo.type === 'database_not_exists',
                                'fa-tables': databaseInitErrorInfo.type === 'tables_missing',
                                'fa-file-text-o': databaseInitErrorInfo.type === 'data_empty',
                                'fa-exclamation-circle': !['server_unreachable', 'database_not_exists', 'tables_missing', 'data_empty'].includes(databaseInitErrorInfo.type)
                            }"></i>
                        </div>

                        <div class="error-message-content">
                            <p class="error-main-message">{{ databaseInitErrorInfo.message }}</p>

                            <div v-if="databaseInitErrorInfo.detail" class="error-detail-info">
                                <strong>详细信息：</strong>
                                <p>{{ databaseInitErrorInfo.detail }}</p>
                            </div>

                            <div v-if="databaseInitErrorInfo.suggestion" class="error-suggestion">
                                <i class="fa fa-lightbulb-o"></i>
                                <strong>建议：</strong>{{ databaseInitErrorInfo.suggestion }}
                            </div>
                        </div>
                    </div>

                    <!-- 默认的初始化提示（当没有详细信息时显示） -->
                    <div v-else class="init-message">
                        <i class="fa fa-info-circle"></i>
                        <p>检测到数据库尚未初始化或数据不完整，是否现在进行初始化？</p>
                    </div>

                    <!-- 警告信息 -->
                    <div class="init-warning" v-if="!databaseInitErrorInfo || databaseInitErrorInfo.canInitialize">
                        <i class="fa fa-exclamation-triangle"></i>
                        <p>初始化将清空现有数据并载入默认数据。</p>
                    </div>

                    <!-- 不可操作时的提示 -->
                    <div v-if="databaseInitErrorInfo && !databaseInitErrorInfo.canInitialize" class="init-warning warning-critical">
                        <i class="fa fa-ban"></i>
                        <p>当前状态下无法执行初始化操作，请先解决上述问题。</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showDatabaseInitModal = false">
                        <i class="fa fa-close"></i> 取消
                    </button>
                    <button class="btn btn-primary" @click="initializeDatabase"
                        :disabled="isInitializing || (databaseInitErrorInfo && !databaseInitErrorInfo.canInitialize)">
                        <i class="fa fa-check" v-if="!isInitializing"></i>
                        <i class="fa fa-spinner fa-spin" v-if="isInitializing"></i>
                        {{ isInitializing ? '正在初始化...' : '确认初始化' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- 数据库重置确认模态框 -->
        <div class="modal-overlay" id="databaseResetModal" v-show="showDatabaseResetModal" v-cloak>
            <div class="modal-container">
                <div class="modal-header">
                    <h3><i class="fa fa-refresh"></i> 数据库重置确认</h3>
                    <button class="modal-close" @click="showDatabaseResetModal = false">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="reset-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <p><strong>警告：此操作将清空所有数据！</strong></p>
                        <ul>
                            <li>所有分类将被重置为默认状态</li>
                            <li>所有链接数据将被清除</li>
                            <li>管理员账户将重置为默认账户</li>
                            <li>此操作不可撤销</li>
                        </ul>
                    </div>

                    <div class="reset-options">
                        <h4><i class="fa fa-cog"></i> 重置选项</h4>
                        <div class="option-group">
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="resetOptions.resetLearningStatus"
                                    :disabled="isResetting">
                                <span class="checkmark"></span>
                                <span class="option-text">重置学习情况</span>
                                <small>勾选后将清空所有链接的学习状态记录（待学习/学习中/已掌握）</small>
                            </label>
                        </div>
                        <div class="option-group">
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="resetOptions.resetRangeDatabases"
                                    :disabled="isResetting">
                                <span class="checkmark"></span>
                                <span class="option-text">重置靶场数据库</span>
                                <small>勾选后将重置所有靶场站点的数据库</small>
                            </label>
                        </div>
                        <div class="option-group">
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="resetOptions.resetSmsSimulator" :disabled="isResetting">
                                <span class="checkmark"></span>
                                <span class="option-text">重置手机模拟器数据库</span>
                                <small>勾选后将重置短信模拟器的数据库，清空所有短信记录</small>
                            </label>
                        </div>
                    </div>

                    <div class="reset-confirmation">
                        <p>请输入 <code>YES_RESET_DATABASE</code> 确认重置：</p>
                        <input type="text" v-model="resetConfirmationText" class="reset-input"
                            placeholder="YES_RESET_DATABASE" :disabled="isResetting">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showDatabaseResetModal = false" :disabled="isResetting">
                        <i class="fa fa-close"></i> 取消
                    </button>
                    <button class="btn btn-danger" @click="resetDatabase"
                        :disabled="isResetting || resetConfirmationText !== 'YES_RESET_DATABASE'">
                        <i class="fa fa-refresh" v-if="!isResetting"></i>
                        <i class="fa fa-spinner fa-spin" v-if="isResetting"></i>
                        {{ isResetting ? '正在重置...' : '确认重置' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- 靶场数据库初始化确认模态框 -->
        <div class="modal-overlay" id="rangeDatabaseInitModal" v-show="showRangeDatabaseInitModal" v-cloak>
            <div class="modal-container">
                <div class="modal-header">
                    <h3><i class="fa fa-database"></i> 靶场数据库初始化确认</h3>
                    <button class="modal-close" @click="showRangeDatabaseInitModal = false">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="init-message">
                        <i class="fa fa-info-circle"></i>
                        <div style="white-space: pre-line;">{{ rangeInitMessage }}</div>
                    </div>
                    <div class="init-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <p>初始化将创建必要的数据库表结构，此操作安全且不会影响现有数据。</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showRangeDatabaseInitModal = false"
                        :disabled="isInitializingRange">
                        <i class="fa fa-close"></i> 取消
                    </button>
                    <button class="btn btn-primary" @click="confirmRangeDatabaseInit" :disabled="isInitializingRange">
                        <i class="fa fa-check" v-if="!isInitializingRange"></i>
                        <i class="fa fa-spinner fa-spin" v-if="isInitializingRange"></i>
                        {{ isInitializingRange ? '正在初始化...' : '确认初始化' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- 靶场数据库重置结果模态框 -->
        <div class="modal-overlay" id="rangeResetResultModal" v-show="showRangeResetResultModal" v-cloak>
            <div class="modal-container">
                <div class="modal-header">
                    <h3><i class="fa fa-list-alt"></i> 靶场数据库重置结果</h3>
                    <button class="modal-close" @click="closeRangeResetResultModal">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- 汇总信息 -->
                    <div class="range-reset-summary">
                        <div class="summary-item summary-success">
                            <div class="summary-number">{{ rangeResetSummary.success || 0 }}</div>
                            <div class="summary-label">成功</div>
                        </div>
                        <div class="summary-item summary-failed">
                            <div class="summary-number">{{ rangeResetSummary.failed || 0 }}</div>
                            <div class="summary-label">失败</div>
                        </div>
                        <div class="summary-item summary-total">
                            <div class="summary-number">{{ rangeResetSummary.total || 0 }}</div>
                            <div class="summary-label">总计</div>
                        </div>
                    </div>
                    <!-- 详细列表 -->
                    <div class="range-reset-list">
                        <div v-for="(item, index) in rangeResetResults" :key="index" class="range-reset-item">
                            <div class="range-reset-item-left">
                                <i class="fa range-reset-item-icon" :class="[item.status === 'success' ? 'fa-check-circle text-success' : 'fa-times-circle text-danger']"></i>
                                <span class="range-reset-item-text">{{ item.directory }}</span>
                            </div>
                            <div class="range-reset-item-right">
                                <span v-if="item.status === 'success'" class="range-reset-badge badge-success">成功</span>
                                <span v-else class="range-reset-badge badge-danger" :title="item.message">失败</span>
                            </div>
                        </div>
                    </div>
                    <!-- 失败原因提示 -->
                    <div v-if="rangeResetSummary.failed > 0" class="range-reset-alert">
                        <p>
                            <i class="fa fa-exclamation-triangle"></i>
                            部分靶场数据库重置失败，可能是因为SQL脚本存在兼容性问题。失败的靶场不影响其他功能正常使用。
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" @click="closeRangeResetResultModal">
                        <i class="fa fa-check"></i> 确定
                    </button>
                </div>
            </div>
        </div>

        <!-- 学习状态确认模态框 -->
        <div class="modal-overlay" id="learningStatusConfirmModal" v-show="showLearningStatusConfirmModal" v-cloak>
            <div class="modal-container">
                <div class="modal-header">
                    <h3><i class="fa fa-check-circle"></i> 确认学习状态</h3>
                    <button class="modal-close" @click="showLearningStatusConfirmModal = false">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="confirm-message">
                        <p><strong>你真的掌握了吗？</strong></p>
                        <p>完成靶场的学习任务，靶场会自动更新为已掌握状态，不要偷懒哦~</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="cancelLearningStatusUpdate">
                        <i class="fa fa-close"></i> 再想想
                    </button>
                    <button class="btn btn-success" @click="confirmLearningStatusUpdate">
                        <i class="fa fa-check"></i> 确认掌握
                    </button>
                </div>
            </div>
        </div>

        <!-- 全局提示框组件 -->
        <div class="heasec-tooltip" :style="tooltipStyle" :class="{ 'visible': tooltipVisible }">
            {{ tooltipText }}
        </div>
    </div>

</body>

</html>