<?php
/**
 * HeaSec天积安全团队 - XXE绕过靶场 - 第二关XML处理接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-10
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec XXEBypass Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

// 检查文件上传
if (!isset($_FILES['xml_file']) || $_FILES['xml_file']['error'] !== UPLOAD_ERR_OK) {
    sendJsonResponse(false, '请选择有效的XML文件');
}

$xmlFile = $_FILES['xml_file'];

// 检查文件后缀
$ext = strtolower(pathinfo($xmlFile['name'], PATHINFO_EXTENSION));
if ($ext !== 'xml') {
    sendJsonResponse(false, '仅支持 .xml 格式文件');
}

// 读取XML内容
$xmlData = file_get_contents($xmlFile['tmp_name']);

// 安全过滤：检查SYSTEM关键字
if (stripos($xmlData, 'SYSTEM') !== false) {
    sendJsonResponse(false, '检测到外部资源引用，禁止导入');
}

// 启用外部实体加载
libxml_disable_entity_loader(false);
libxml_use_internal_errors(true);

// 解析XML（抑制PHP warnings，确保JSON输出格式正确）
$xml = @simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_DTDLOAD);

if ($xml !== false) {
    // 解析成功：提取商品数据并返回完整结果
    $product = [
        'name'        => (string)$xml->name,
        'category'    => (string)$xml->category,
        'price'       => (string)$xml->price,
        'description' => (string)$xml->description
    ];

    // 存储导入数据
    $dataPath = getDataFilePath(2);
    ensureDataFile($dataPath);
    appendImportedData($dataPath, $product);

    sendJsonResponse(true, '导入成功，已导入1条商品数据', ['product' => $product]);
} else {
    // 解析失败：返回错误信息
    $errors = libxml_get_errors();
    $errorMessages = [];
    foreach ($errors as $error) {
        $errorMessages[] = trim($error->message);
    }
    libxml_clear_errors();

    sendJsonResponse(false, 'XML文件格式错误', ['errors' => $errorMessages]);
}
