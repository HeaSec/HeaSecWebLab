<?php
/**
 * HeaSec天积安全团队 - XXE基础靶场 - 第二关XML处理接口
 * 版本: v1.1.0
 * 创建日期: 2026-04-09
 * 更新日期: 2026-04-10
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 第二关：基于外部DTD的XXE回显注入
 * - 过滤内部实体定义，仅允许通过外部DTD引入实体
 * - 解析成功时返回完整解析结果（与第一关一致）
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec XXEBase Range v1.1.0');

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

/**
 * 检查DOCTYPE内部实体定义
 * 仅允许参数实体外部DTD引用（<!ENTITY % name SYSTEM "url">）
 * 拒绝其他所有内部实体定义
 */
if (preg_match('/<!DOCTYPE[^>]*\[(.*?)\]>/is', $xmlData, $doctypeMatch)) {
    $doctypeContent = $doctypeMatch[1];

    // 查找所有ENTITY声明
    if (preg_match_all('/<!ENTITY\s+([^>]+)>/is', $doctypeContent, $entityMatches)) {
        foreach ($entityMatches[1] as $entityDef) {
            $entityDef = trim($entityDef);
            // 仅允许：参数实体 + SYSTEM（外部DTD引用）
            // 格式：% name SYSTEM "url"
            if (!preg_match('/^%\s*\w+\s+SYSTEM\s+/i', $entityDef)) {
                sendJsonResponse(false, '检测到不允许的内部实体定义');
            }
        }
    }
}

// 启用外部实体加载
libxml_disable_entity_loader(false);
libxml_use_internal_errors(true);

// 解析XML
$xml = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_DTDLOAD);

if ($xml !== false) {
    // 解析成功：返回完整解析结果（与第一关一致）
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
