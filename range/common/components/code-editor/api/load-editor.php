<?php
/**
 * HeaSec天积安全团队 - 代码编辑器懒加载API
 * Code Editor Lazy Load API
 * 版本: v1.0.0
 * 创建日期: 2025-12-30
 * 团队: 天积安全 (HeavenlySecret)
 * 功能: 接收前端请求，返回完整的代码编辑器HTML
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec Code Editor Lazy Load API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义常量允许访问公共组件
define('HEASEC_RANGE_ACCESS', true);

// 引入代码编辑器组件
$commonBasePath = dirname(dirname(dirname(__DIR__))) . '/';
require_once $commonBasePath . 'components/code-editor/includes/HeaSec_CodeEditor.php';

// 获取请求数据
$input = file_get_contents('php://input');
$request = json_decode($input, true);

// 验证请求
if (!isset($request['config'])) {
    echo json_encode([
        'success' => false,
        'message' => '缺少配置参数'
    ]);
    exit;
}

// 设置全局公共组件基础路径
$GLOBALS['commonBasePath'] = $commonBasePath;

// 获取配置
$config = $request['config'];

// 确保不自动加载资源（资源已在主页加载）
$config['autoLoadAssets'] = false;

// 渲染编辑器
try {
    $html = renderCodeEditor($config);

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '渲染编辑器失败: ' . $e->getMessage()
    ]);
}
?>
