<?php
/**
 * HeaSec天积安全团队 - SOAP与XML靶场第三关商品查询接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../../common/';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once dirname(__DIR__) . '/../includes/functions.php';

// 确保SSRF token存在
ensureSsrfToken();

// 读取原始SOAP XML
$rawXml = file_get_contents('php://input');

// 配置XML解析选项
libxml_disable_entity_loader(false);
libxml_use_internal_errors(true);

// 加载并解析SOAP XML请求
$simpleXml = simplexml_load_string($rawXml, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_DTDLOAD);

$keyword = '';

if ($simpleXml !== false) {
    $simpleXml->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
    $keywordNodes = $simpleXml->xpath('//soap:Body/QueryProducts/keyword');
    if (!empty($keywordNodes)) {
        $keyword = (string)$keywordNodes[0];
    }
}

/**
 * 处理商品查询
 * @param string $keyword 查询关键词
 */
function handleQuery($keyword) {
    $productsFile = dirname(__DIR__) . '/../data/level3_products.xml';

    $productsXml = simplexml_load_file($productsFile);
    $results = [];

    if (!empty($keyword)) {
        $escapedKeyword = htmlspecialchars($keyword, ENT_XML1, 'UTF-8');
        $searchResults = $productsXml->xpath(
            "//product[contains(name, '{$escapedKeyword}') or contains(category, '{$escapedKeyword}')]"
        );
        foreach ($searchResults as $product) {
            $results[] = [
                'id'       => (int)$product->id,
                'name'     => (string)$product->name,
                'category' => (string)$product->category,
                'price'    => (float)$product->price
            ];
        }
    }

    // 如果关键词中包含有效的内部API动态token，返回通关密码
    $validToken = getSsrfToken();
    if (!empty($validToken) && strpos($keyword, $validToken) !== false) {
        $passcode = extractPasscode(getSecretFilePath(3));
        sendJsonResponse(true, '查询完成（检测到内部API响应）', [
            'ssrf_detected' => true,
            'passcode' => $passcode,
            'products' => $results
        ]);
    }

    sendJsonResponse(true, '查询完成，共找到' . count($results) . '条商品', [
        'products' => $results
    ]);
}

handleQuery($keyword);
