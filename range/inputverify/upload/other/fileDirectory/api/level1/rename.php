<?php
/**
 * HeaSec天积安全团队 - 文件目录执行权限绕过靶场 - 第一关重命名接口
 * 漏洞机制：路径穿越 - 新文件名未过滤../，可穿越到上级目录
 * 版本: v1.1.0
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件目录执行权限绕过 Range v1.1.0');
header('Content-Type: application/json; charset=utf-8');

// 公共组件路径
$commonBasePath = '../../../../../../common/';

define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('filedirectory');
HeaSec_ValidateSession();

// 处理重命名请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取JSON输入
    $input = json_decode(file_get_contents('php://input'), true);

    $oldName = isset($input['oldName']) ? trim($input['oldName']) : '';
    $newName = isset($input['newName']) ? trim($input['newName']) : '';

    // 验证参数
    if (empty($oldName) || empty($newName)) {
        echo json_encode([
            'success' => false,
            'message' => '文件名不能为空！'
        ]);
        exit;
    }

    // images目录路径
    $imagesDir = dirname(__DIR__, 2) . '/exec/images/';

    // 构建原文件路径（仅限images目录内的文件）
    $oldPath = $imagesDir . basename($oldName);

    // 检查原文件是否存在
    if (!file_exists($oldPath)) {
        echo json_encode([
            'success' => false,
            'message' => '原文件不存在！'
        ]);
        exit;
    }

    // 构建新文件路径
    // 漏洞点：直接使用用户输入的新文件名，未过滤路径穿越字符
    // 攻击者可使用 ../ 将文件移动到上级目录（exec/目录，该目录允许PHP执行）
    $newPath = $imagesDir . $newName;

    // 执行重命名
    if (rename($oldPath, $newPath)) {
        // 返回相对路径供前端显示
        $displayPath = $newName;
        echo json_encode([
            'success' => true,
            'message' => '重命名成功！',
            'oldName' => $oldName,
            'newName' => $newName,
            'newPath' => $displayPath
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '重命名失败，请检查权限！'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => '无效的请求方法'
    ]);
}
?>
