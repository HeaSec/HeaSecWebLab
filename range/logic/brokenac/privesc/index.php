<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战靶场
 * 版本: v1.0.1
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec 越权访问综合实战 Range v1.0.1');
header('Content-Type: text/html; charset=utf-8');

$pageTitle = '越权访问综合实战靶场';
$rangeName = '越权访问综合实战';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.1';
$commonBasePath = '../../../common/';
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;
$resetUrl = 'api/reset.php';

define('HEASEC_RANGE_ACCESS', true);
require_once 'includes/bootstrap.php';

$config = privesc_get_config();
$sessionState = [
    'logged_in' => false,
    'user' => null,
    'users' => [],
];

$vulnCardConfig = [
    'title' => $config['ui']['title'],
    'rangeCode' => $config['range_code'],
    'starCount' => $config['ui']['star_count'],
    'scoreThresholds' => $config['ui']['score_thresholds'],
    'starTitles' => $config['ui']['star_titles'],
    'vulnTypes' => $config['ui']['vuln_types'],
    'submittedRecords' => [],
    'totalScore' => 0,
    'maxScore' => $config['ui']['max_score'],
    'vulnConfig' => [
        'validateApiUrl' => 'api/validate-vuln.php',
        'submitMethod' => 'POST',
    ],
];

require_once $commonBasePath . 'includes/header.php';
require_once $commonBasePath . 'components/vuln-card/includes/HeaSec_VulnCard.php';

if ($dbStatus === 'normal') {
    $pdo = privesc_get_pdo();
    privesc_ensure_seed_data($pdo);



    $sessionState = privesc_build_session_state($pdo, null, false);
    $vulnCardConfig['submittedRecords'] = privesc_get_submitted_records($pdo, 'global');
    $vulnCardConfig['totalScore'] = privesc_get_total_score($pdo, 'global');
}
?>
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
<link rel="shortcut icon" href="<?php echo $commonBasePath; ?>favicon.ico">


<div class="range-container heasec-privesc-layout">
    <div class="heasec-privesc-main">
        <div id="guestPanel"
            class="heasec-panel-stack<?php echo !empty($sessionState['logged_in']) ? ' is-hidden' : ''; ?>">
            <div class="tech-card">
                <div class="tech-card-header">
                    <h3><i class="fa fa-sign-in"></i>用户中心</h3>
                </div>
                <div class="tech-card-body">
                    <div class="alert alert-warning">
                        <div>
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>提示</strong>
                        </div>
                        <span class="alert-hint">本系统为用户管理中心，存在普通用户和管理员两种角色，请尝试注册用户使用平台，挖掘并提交漏洞。</span>
                    </div>

                    <form id="loginForm" class="tech-form">
                        <div class="form-group">
                            <label class="form-label" for="loginUsername"><i class="fa fa-user"></i>用户名</label>
                            <div class="input-wrapper">
                                <input type="text" id="loginUsername" name="username" class="tech-input" maxlength="20"
                                    placeholder="请输入用户名">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="loginPassword"><i class="fa fa-lock"></i>密码</label>
                            <div class="input-wrapper">
                                <input type="password" id="loginPassword" name="password" class="tech-input"
                                    maxlength="50" placeholder="请输入密码">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="tech-btn tech-btn-primary"><i
                                    class="fa fa-sign-in"></i>立即登录</button>
                            <button type="button" class="tech-btn tech-btn-info" id="registerBtn"><i
                                    class="fa fa-user-plus"></i>注册新用户</button>
                        </div>
                    </form>
                    <div class="heasec-page-message"></div>
                </div>
            </div>
        </div>

        <div id="userPanel"
            class="heasec-panel-stack<?php echo empty($sessionState['logged_in']) ? ' is-hidden' : ''; ?>">
            <div class="tech-card">
                <div class="tech-card-header"
                    style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><i class="fa fa-user-circle"></i>个人中心</h3>
                    <button type="button" id="logoutBtn" class="tech-btn tech-btn-secondary"
                        style="padding: 5px 12px; font-size: 13px; height: auto;"><i
                            class="fa fa-sign-out"></i>退出登录</button>
                </div>
                <div class="tech-card-body">
                    <!-- 基本信息展示区 -->
                    <div class="tech-info-panel heasec-avatar-panel" style="margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div class="heasec-avatar-preview-wrap cursor-pointer" id="avatarDisplayBtn" title="点击管理头像">
                                <img id="avatarPreview" class="heasec-avatar-preview is-hidden" src="" alt="当前头像">
                                <div id="avatarEmpty" class="heasec-avatar-empty">暂无头像</div>
                                <div class="avatar-edit-overlay"><i class="fa fa-camera"></i></div>
                            </div>
                            <div class="heasec-data-grid" style="flex: 1;">
                                <div><span>用户名</span><strong id="profileUsername"></strong></div>
                                <div><span>姓名</span><strong id="profileName"></strong></div>
                                <div><span>手机号</span><strong id="profilePhone"></strong></div>
                                <div><span>角色</span><strong id="profileRole"></strong></div>
                                <div><span>地址</span><strong id="profileAddress"></strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- 操作区域 -->
                    <div class="form-actions"
                        style="margin-bottom: 20px; justify-content: flex-start; gap: 10px; border-bottom: 1px solid rgba(0, 123, 255, 0.1); padding-bottom: 20px;">
                        <button type="button" id="toggleProfileFormBtn" class="tech-btn tech-btn-primary"><i
                                class="fa fa-edit"></i>编辑个人资料</button>
                        <button type="button" id="toggleAddressFormBtn" class="tech-btn tech-btn-primary"><i
                                class="fa fa-map-marker"></i>地址管理</button>
                        <button type="button" id="togglePasswordFormBtn" class="tech-btn tech-btn-primary"><i
                                class="fa fa-key"></i>修改密码</button>
                    </div>

                    <!-- 折叠表单区：编辑个人信息 -->
                    <div id="editProfileSection" class="tech-info-panel is-hidden">
                        <h4>编辑您的个人信息</h4>
                        <form id="profileForm" class="tech-form">
                            <div class="form-group">
                                <label class="form-label" for="editName"><i class="fa fa-address-card"></i>姓名</label>
                                <div class="input-wrapper">
                                    <input type="text" id="editName" name="name" class="tech-input" maxlength="50"
                                        placeholder="请输入姓名">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="editPhone"><i class="fa fa-phone"></i>手机号</label>
                                <div class="input-wrapper">
                                    <input type="text" id="editPhone" name="phone" class="tech-input" maxlength="20"
                                        placeholder="请输入手机号">
                                </div>
                            </div>
                            <div class="form-group" id="profileRoleGroup">
                                <label class="form-label" for="editRole"><i class="fa fa-user-secret"></i>角色</label>
                                <div class="input-wrapper">
                                    <select id="editRole" name="role" class="tech-input heasec-select">
                                        <option value="0">普通用户</option>
                                        <option value="2">管理员</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="tech-btn tech-btn-success"><i
                                        class="fa fa-save"></i>保存资料</button>
                                <button type="button" class="tech-btn tech-btn-secondary cancel-edit-btn"><i
                                        class="fa fa-times"></i>取消</button>
                            </div>
                        </form>
                    </div>

                    <!-- 折叠表单区：地址管理 -->
                    <div id="editAddressSection" class="tech-info-panel is-hidden">
                        <h4>编辑收货地址</h4>
                        <form id="addressForm" class="tech-form">
                            <input type="hidden" id="addressId" name="address_id">
                            <div class="form-group">
                                <label class="form-label" for="addressValue"><i class="fa fa-home"></i>地址内容</label>
                                <div class="input-wrapper">
                                    <textarea id="addressValue" name="address" class="tech-input heasec-textarea"
                                        rows="3" placeholder="请输入具体地址内容"></textarea>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="tech-btn tech-btn-success"><i
                                        class="fa fa-save"></i>更新地址</button>
                                <button type="button" class="tech-btn tech-btn-secondary cancel-edit-btn"><i
                                        class="fa fa-times"></i>取消</button>
                            </div>
                        </form>
                    </div>

                    <!-- 折叠表单区：修改密码 -->
                    <div id="editPasswordSection" class="tech-info-panel is-hidden">
                        <h4>修改密码</h4>
                        <form id="passwordForm" class="tech-form">
                            <input type="hidden" id="userHash" name="user_hash">
                            <div class="form-group">
                                <label class="form-label" for="newPassword"><i class="fa fa-lock"></i>新密码</label>
                                <div class="input-wrapper">
                                    <input type="password" id="newPassword" name="new_password" class="tech-input"
                                        maxlength="50" placeholder="请输入新密码">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirmPassword"><i
                                        class="fa fa-check-circle"></i>确认密码</label>
                                <div class="input-wrapper">
                                    <input type="password" id="confirmPassword" name="confirm_password"
                                        class="tech-input" maxlength="50" placeholder="请再次输入新密码">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="tech-btn tech-btn-success"><i
                                        class="fa fa-refresh"></i>确认修改</button>
                                <button type="button" class="tech-btn tech-btn-secondary cancel-edit-btn"><i
                                        class="fa fa-times"></i>取消</button>
                            </div>
                        </form>
                    </div>

                    <!-- 折叠表单区：头像管理 -->
                    <div id="editAvatarSection" class="tech-info-panel is-hidden">
                        <h4>头像管理</h4>
                        <div class="alert alert-warning" style="margin-bottom: 20px;">
                            <div>
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>提示</strong>
                            </div>
                            <span class="alert-hint">仅支持 PNG/JPG/JPEG，最大 2MB。</span>
                        </div>
                        <form id="avatarForm" class="tech-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="form-label" for="avatarFile"><i class="fa fa-image"></i>选择图片</label>
                                <div class="heasec-file-upload-area" id="avatarUploadArea">
                                    <input type="file" id="avatarFile" name="avatar" class="heasec-file-input-hidden"
                                        accept=".png,.jpg,.jpeg,image/png,image/jpeg">
                                    <div class="upload-area-content">
                                        <i class="fa fa-cloud-upload"></i>
                                        <p class="upload-text">点击或将文件拖拽到此处上传</p>
                                        <p class="upload-filename" id="avatarFilenameDisplay">未选择任何文件</p>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="tech-btn tech-btn-success"><i
                                        class="fa fa-cloud-upload"></i>上传更新</button>
                                <button type="button" id="deleteAvatarBtn" class="tech-btn tech-btn-danger"><i
                                        class="fa fa-trash"></i>删除头像</button>
                                <button type="button" class="tech-btn tech-btn-secondary cancel-edit-btn"><i
                                        class="fa fa-times"></i>取消</button>
                            </div>
                        </form>
                    </div>

                    <div class="heasec-page-message"></div>
                </div>
            </div>

            <div id="adminPanel"
                class="tech-card<?php echo isset($_COOKIE['type']) && $_COOKIE['type'] === '2' ? '' : ' is-hidden'; ?>">
                <div class="tech-card-header">
                    <h3><i class="fa fa-users"></i>用户管理</h3>
                </div>
                <div class="tech-card-body">
                    <div class="tech-info-panel">
                        <h4>用户列表</h4>
                        <div id="adminUserTable" class="heasec-user-table"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="heasec-privesc-side">
        <?php echo renderVulnCard($vulnCardConfig, $commonBasePath); ?>
    </div>
</div>



<!-- 注册模态框 -->
<div id="registerModal" class="heasec-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container modal-medium">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa fa-user-plus"></i>注册新用户</h3>
            <button type="button" class="modal-close heasec-modal-close">&times;</button>
        </div>
        <div class="modal-content">
            <form id="registerForm" class="tech-form">
                <input type="hidden" id="registerType" name="type" value="0">
                <div class="form-group">
                    <label class="form-label" for="registerUsername"><i class="fa fa-user"></i>用户名</label>
                    <div class="input-wrapper">
                        <input type="text" id="registerUsername" name="username" class="tech-input" maxlength="20"
                            placeholder="3-20 位字母、数字或下划线" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="registerName"><i class="fa fa-address-card"></i>姓名</label>
                    <div class="input-wrapper">
                        <input type="text" id="registerName" name="name" class="tech-input" maxlength="50"
                            placeholder="请输入您的姓名" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="registerPhone"><i class="fa fa-phone"></i>手机号</label>
                    <div class="input-wrapper">
                        <input type="text" id="registerPhone" name="phone" class="tech-input" maxlength="20"
                            placeholder="请输入您的手机号" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="registerPassword"><i class="fa fa-lock"></i>密码</label>
                    <div class="input-wrapper">
                        <input type="password" id="registerPassword" name="password" class="tech-input" maxlength="50"
                            placeholder="请输入登录密码" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="registerConfirmPassword"><i
                            class="fa fa-check-circle"></i>确认密码</label>
                    <div class="input-wrapper">
                        <input type="password" id="registerConfirmPassword" name="confirm_password" class="tech-input"
                            maxlength="50" placeholder="请再次输入密码" autocomplete="off">
                    </div>
                </div>
                <div class="heasec-modal-message" id="registerModalMessage"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="tech-btn tech-btn-secondary modal-cancel"><i
                    class="fa fa-times"></i>取消</button>
            <button type="submit" form="registerForm" class="tech-btn tech-btn-primary"><i
                    class="fa fa-user-plus"></i>注册</button>
        </div>
    </div>
</div>

<!-- 注册成功模态框 -->
<div id="registerSuccessModal" class="heasec-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container modal-small">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa fa-check-circle"></i>注册成功</h3>
            <button type="button" class="modal-close heasec-modal-close">&times;</button>
        </div>
        <div class="modal-content" style="text-align: center; padding-top: 30px;">
            <i class="fa fa-check-circle"
                style="font-size: 64px; color: var(--heasec-success-color); margin-bottom: 20px;"></i>
            <h4 style="margin: 0 0 10px 0; color: #333;">恭喜您，注册成功！</h4>
            <p style="color: #666; margin: 0;">现在您可以使用新账号登录系统了。</p>
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button type="button" class="tech-btn tech-btn-primary" id="registerSuccessBtn" style="min-width: 120px;">
                <i class="fa fa-check"></i> 确认
            </button>
        </div>
    </div>
</div>

<script>
    window.HeaSec = window.HeaSec || {};
    window.HeaSec.team = {
        cnName: '天积安全',
        enName: 'HeavenlySecret',
        shortName: 'HeaSec',
        slogan: '日积寸功，乐享安全'
    };
    window.HeaSec.privescInitialState = <?php echo json_encode([
        'state' => $sessionState,
        'cookieType' => isset($_COOKIE['type']) ? $_COOKIE['type'] : '',
    ], JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="js/privesc.js?v=<?php echo $version; ?>"></script>

<?php require_once $commonBasePath . 'includes/footer.php'; ?>