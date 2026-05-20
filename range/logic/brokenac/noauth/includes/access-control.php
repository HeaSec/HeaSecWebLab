<?php
/**
 * HeaSec天积安全团队 - 未授权访问靶场 - 访问控制
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 用于检查当前请求的路径是否为当前关卡生效的随机路径
 */

/**
 * 检查访问权限并处理未授权访问
 * @param int $level 关卡编号
 * @param string $currentPath 当前请求的路径（文件名或目录名）
 * @param PDO $pdo 数据库连接
 * @return array|null 返回配置数据，不允许访问时返回null
 */
function checkNoauthAccess($level, $currentPath, $pdo) {
    // 获取关卡配置
    $stmt = $pdo->prepare("SELECT * FROM heasec_noauth_config WHERE level = ?");
    $stmt->execute([$level]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    // 如果配置不存在或路径不匹配，拒绝访问
    if (!$config || $config['random_path'] !== $currentPath) {
        return null;
    }

    return $config;
}

/**
 * 显示404页面并终止执行
 * @param string $commonBasePath 公共组件基础路径
 */
function showNoauth404($commonBasePath = '../../../common/') {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - 页面未找到</title>
        <link rel="stylesheet" href="<?php echo $commonBasePath; ?>assets/css/font-awesome.min.css">
        <link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range_common.css">
        <style>
            body {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .error-container {
                text-align: center;
                padding: 60px 40px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 16px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
            }
            .error-code {
                font-size: 120px;
                font-weight: bold;
                color: #e94560;
                margin: 0;
                line-height: 1;
                text-shadow: 0 0 30px rgba(233, 69, 96, 0.5);
            }
            .error-message {
                font-size: 24px;
                color: #fff;
                margin: 20px 0;
            }
            .error-description {
                color: #888;
                font-size: 14px;
                margin-bottom: 30px;
            }
            .back-btn {
                display: inline-block;
                padding: 12px 30px;
                background: linear-gradient(135deg, #0f3460 0%, #16537e 100%);
                color: #fff;
                text-decoration: none;
                border-radius: 8px;
                transition: all 0.3s ease;
            }
            .back-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 20px rgba(15, 52, 96, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1 class="error-code">404</h1>
            <p class="error-message">页面未找到</p>
            <p class="error-description">您访问的页面不存在或已被移除</p>
            <a href="javascript:history.back()" class="back-btn">
                <i class="fa fa-arrow-left"></i> 返回上一页
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * 验证访问权限，无权限时显示404
 * @param int $level 关卡编号
 * @param string $currentPath 当前请求的路径
 * @param PDO $pdo 数据库连接
 * @param string $commonBasePath 公共组件基础路径
 * @return array 配置数据
 */
function requireNoauthAccess($level, $currentPath, $pdo, $commonBasePath = '../../../common/') {
    $config = checkNoauthAccess($level, $currentPath, $pdo);
    if (!$config) {
        showNoauth404($commonBasePath);
    }
    return $config;
}
