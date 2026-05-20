<?php
/**
 * HeaSec天积安全团队 - XXE基础靶场 - 第三关XML处理接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-09
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec XXEBase Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

// 检查文件上传
if (!isset($_FILES['xml_file']) || $_FILES['xml_file']['error'] !== UPLOAD_ERR_OK) {
    sendJsonResponse(false, '请选择有效的XML文件');
}

$xmlFile = $_FILES['xml_file'];

// 检查文件后缀
$fileName = strtolower($xmlFile['name']);
if (!preg_match('/\.xml$/', $fileName)) {
    sendJsonResponse(false, '仅支持XML文件格式');
}

// 读取XML内容
$xmlData = file_get_contents($xmlFile['tmp_name']);

// 启用外部实体加载
libxml_disable_entity_loader(false);
libxml_use_internal_errors(true);

// 解析XML
$xml = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_DTDLOAD);

if ($xml !== false) {
    // 解析成功：仅返回成功提示
    // 业务数据校验：对导入数据进行基本格式化，确保数据质量
    $rawName = (string)$xml->name;
    $rawCategory = (string)$xml->category;
    $rawPrice = (string)$xml->price;
    $rawDescription = (string)$xml->description;

    $product = [
        'name'        => mb_substr($rawName, 0, 10),
        'category'    => mb_substr($rawCategory, 0, 10),
        'price'       => preg_match('/^\d+(\.\d{1,2})?$/', $rawPrice) ? $rawPrice : '0.00',
        'description' => mb_substr($rawDescription, 0, 15)
    ];

    // 存储导入数据
    $dataPath = getDataFilePath(3);
    ensureDataFile($dataPath);
    appendImportedData($dataPath, $product);

    sendJsonResponse(true, '导入成功，已导入1条商品数据');
} else {
    // 解析失败：仅返回通用错误信息，不泄露任何解析细节
    libxml_clear_errors();

    sendJsonResponse(false, 'XML文件格式错误，请检查文件内容');
}
