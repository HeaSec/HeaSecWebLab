<?php

/**
 * HeaSec天积安全团队 - 手机短信模拟器管理页面
 * SMS Simulator Management Page
 * 版本: v1.1.0
 * 创建日期: 2026-01-06
 * 更新日期: 2026-01-07
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能说明:
 *   - 管理注册的手机号
 *   - 查看和管理短信记录
 *   - 提供三标签页切换功能
 * 更新说明:
 *   - 重构为纯前端控制模态框展示和交互
 *   - 移除PHP条件渲染，改为前端API调用
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('Content-Type: text/html; charset=utf-8');
header('X-HeavenlySecret: HeaSec SMS Simulator Manager v1.1.0');

// 获取公共组件基础路径（使用固定值，防止XSS和文件包含攻击）
$commonBasePath = '../../../common/';

// 读取测试配置，判断是否显示发送日志管理模块
$smsShowLogTab = false;
$testConfigPath = __DIR__ . '/../../config/test_config.json';
if (file_exists($testConfigPath)) {
    $testConfigContent = file_get_contents($testConfigPath);
    $testConfig = json_decode($testConfigContent, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($testConfig['sms_show_log_tab'])) {
        $smsShowLogTab = (bool) $testConfig['sms_show_log_tab'];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="天积安全 (HeavenlySecret)">
    <meta name="keywords" content="短信模拟器,手机号管理,短信管理">
    <meta name="description" content="HeaSec手机短信模拟器管理后台">
    <title>手机短信模拟器 - 管理后台</title>

    <!-- 引入Font Awesome图标库 -->
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>assets/css/font-awesome.min.css">

    <!-- 引入tech-blue组件风格 -->
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>components/tech-blue/css/heasec-tech-blue.css">

    <!-- 引入短信模拟器组件样式 -->
    <link rel="stylesheet" href="css/heasec-sms-simulator.css?v=1.2.0">
</head>

<body class="heasec-sms-manager">
    <!-- 管理页面容器 -->
    <div class="heasec-sms-container">
        <!-- 页面头部 -->
        <div class="heasec-sms-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fa fa-mobile-alt"></i>
                    <h2>手机短信模拟器</h2>
                    <span class="version-badge">v1.1.0</span>
                    <button class="btn-help" id="btnShowHelp" title="查看使用说明">
                        <i class="fa fa-question-circle"></i>
                    </button>
                    <button class="btn-reset-database" id="btnResetDatabase" title="重置短信模拟器数据库">
                        <i class="fa fa-refresh"></i>
                        重置
                    </button>
                </div>
                <div class="header-actions" id="headerActions">
                    <!-- 默认手机号徽章将由前端动态插入 -->
                </div>
            </div>
        </div>

        <!-- 标签页导航 -->
        <div class="heasec-sms-tabs">
            <ul class="tabs-nav">
                <li class="tab-item" data-tab="sms">
                    <a href="#sms" class="tab-link">
                        <i class="fa fa-envelope"></i>
                        短信记录管理
                    </a>
                </li>
                <li class="tab-item active" data-tab="phones">
                    <a href="#phones" class="tab-link">
                        <i class="fa fa-phone"></i>
                        注册手机管理
                    </a>
                </li>
                <?php if ($smsShowLogTab): ?>
                <li class="tab-item" data-tab="logs">
                    <a href="#logs" class="tab-link">
                        <i class="fa fa-history"></i>
                        发送日志管理
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- 标签页内容 -->
        <div class="heasec-sms-content">
            <!-- 短信记录管理标签页 -->
            <div class="tab-pane" id="tab-sms">
                <?php include __DIR__ . '/templates/tab-sms.php'; ?>
            </div>

            <!-- 注册手机管理标签页 -->
            <div class="tab-pane active" id="tab-phones">
                <?php include __DIR__ . '/templates/tab-phones.php'; ?>
            </div>

            <!-- 发送日志管理标签页 -->
            <?php if ($smsShowLogTab): ?>
            <div class="tab-pane" id="tab-logs">
                <?php include __DIR__ . '/templates/tab-logs.php'; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 使用说明模态框（默认隐藏，由前端控制显示） -->
    <div class="heasec-sms-modal" id="helpModal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-container" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fa fa-question-circle"></i>
                    使用说明
                </h3>
                <button class="modal-close" id="btnCloseHelpModal">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="modal-content">
                <div class="help-content">
                    <div class="help-section">
                        <h4><i class="fa fa-mobile"></i> 1. 注册手机号说明</h4>
                        <p>1. 只有注册的手机号才能收到短信（模拟攻击者可控的手机）。系统默认注册了一个手机号 <strong>13866668888</strong>，如需增加可以在"注册手机管理"页面操作。</p>
                        <p>2.  <strong>110</strong>开头的手机号是系统保留手机号，不能注册，用于模拟攻击者无法控制的目标手机号。</p>
                    </div>

                    <div class="help-section">
                        <h4><i class="fa fa-envelope"></i> 2. 短信记录管理说明</h4>
                        <p>在"短信记录管理"页面可以看到收到的短信。</p>
                        <ul>
                            <li>系统默认展示默认手机号的短信记录</li>
                            <li>可通过"选择手机号"调整要查看的手机号码</li>
                            <li>默认手机号可在"注册手机管理"页面配置</li>
                            <li>所有靶场的短信模拟器是同一个,可以通过短信的发件人（靶场编码）来区分不同靶场的短信记录</li>
                        </ul>
                    </div>

                    <?php if ($smsShowLogTab): ?>
                    <div class="help-section">
                        <h4><i class="fa fa-history"></i> 3. 发送日志管理说明</h4>
                        <p>发送日志管理模拟短信网关的日志系统，仅用于错误排查，一般不需要使用。日志记录了所有短信发送尝试（包括成功和失败），便于开发调试。</p>
                    </div>
                    <?php endif; ?>

                    <div class="help-section">
                        <h4><i class="fa fa-lightbulb-o"></i> <?php echo $smsShowLogTab ? '4' : '3'; ?>. 使用提示</h4>
                        <ul>
                            <li>短信模拟器是独立于靶场的，不会对靶场环境造成任何影响。</li>
                            <li>如果遇到异常可以尝试点击"重置"按钮重置数据库</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary modal-confirm" id="btnConfirmHelpModal">
                    <i class="fa fa-check"></i>
                    我知道了
                </button>
            </div>
        </div>
    </div>

    <!-- 数据库重置确认模态框（默认隐藏，由前端控制显示） -->
    <div class="heasec-sms-modal" id="dbResetModal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-container" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fa fa-exclamation-triangle"></i>
                    数据库重置确认
                </h3>
                <button class="modal-close" id="btnCloseResetModal">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="modal-content">
                <div style="text-align: center; padding: 20px 0;">
                    <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #FFC107; margin-bottom: 20px; display: block;"></i>
                    <p style="font-size: 15px; color: #333; line-height: 1.6;">
                        <strong>警告：此操作将清空所有数据！</strong>
                    </p>
                    <div style="background: rgba(220, 53, 69, 0.05); padding: 15px; border-radius: 10px; margin-top: 20px; text-align: left;">
                        <strong style="color: #DC3545;">重置将执行以下操作：</strong>
                        <ul style="margin: 10px 0 0 20px; padding: 0; color: #666; font-size: 14px;">
                            <li>清空所有短信记录</li>
                            <li>清空所有发送日志</li>
                            <li>恢复默认手机号（13866668888）</li>
                            <li><strong style="color: #DC3545;">此操作不可撤销！</strong></li>
                        </ul>
                    </div>
                    <div style="margin-top: 20px;">
                        <label style="display: block; text-align: left; margin-bottom: 8px; font-size: 14px; color: #333;">
                            请输入 <code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">RESET_SMS</code> 确认重置：
                        </label>
                        <input type="text" id="resetConfirmInput" class="form-input" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;" placeholder="输入 RESET_SMS">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel" id="btnCancelReset">
                    <i class="fa fa-times"></i>
                    取消
                </button>
                <button class="btn btn-danger modal-confirm" id="btnConfirmReset" disabled>
                    <i class="fa fa-refresh"></i>
                    确认重置
                </button>
            </div>
        </div>
    </div>

    <!-- 数据库初始化提示模态框（默认隐藏，由前端控制显示） -->
    <div class="heasec-sms-modal" id="dbInitModal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-container" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fa fa-database"></i>
                    数据库初始化提示
                </h3>
            </div>
            <div class="modal-content">
                <div style="text-align: center; padding: 20px 0;">
                    <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #FFC107; margin-bottom: 20px; display: block;"></i>
                    <p style="font-size: 15px; color: #333; line-height: 1.6;">
                        检测到短信模拟器数据库尚未初始化。<br>
                        是否立即初始化数据库？
                    </p>
                    <div style="background: rgba(0, 123, 255, 0.05); padding: 15px; border-radius: 10px; margin-top: 20px; text-align: left;">
                        <strong style="color: #007BFF;">初始化将执行以下操作：</strong>
                        <ul style="margin: 10px 0 0 20px; padding: 0; color: #666; font-size: 14px;">
                            <li>创建必要的数据库表结构</li>
                            <li>插入默认测试手机号</li>
                            <li>配置索引和约束</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel" id="btnReloadPage">
                    <i class="fa fa-refresh"></i>
                    刷新页面
                </button>
                <button class="btn btn-primary modal-confirm" id="btnInitDatabase">
                    <i class="fa fa-check"></i>
                    确认初始化
                </button>
            </div>
        </div>
    </div>

    <!-- 引入JavaScript文件 -->
    <script src="<?php echo $commonBasePath; ?>js/heasec_range_common.js"></script>
    <script src="js/heasec-sms-simulator.js?v=1.2.0"></script>

    <!-- 初始化脚本 -->
    <script>
        // 初始化短信模拟器管理器
        document.addEventListener('DOMContentLoaded', function() {
            // 创建全局对象
            window.HeaSec = window.HeaSec || {};
            window.HeaSec.SmsSimulator = new HeaSecSmsSimulatorManager({
                apiBasePath: 'api/',
                commonBasePath: '<?php echo $commonBasePath; ?>',
                showLogTab: <?php echo $smsShowLogTab ? 'true' : 'false'; ?>
            });

            // 初始化页面状态（检查数据库、设置默认标签页等）
            window.HeaSec.SmsSimulator.initializePageState();
        });
    </script>
</body>

</html>