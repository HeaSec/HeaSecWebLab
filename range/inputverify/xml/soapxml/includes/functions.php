<?php
/**
 * HeaSec天积安全团队 - SOAP与XML安全靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 解析SOAP XML请求，提取指定操作的参数
 *
 * @param string $rawXml 原始SOAP XML字符串
 * @param string $operation 期望的操作名
 * @return array 操作参数关联数组
 */
function parseSoapRequest($rawXml, $operation) {
    $params = [];

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($rawXml);

    if ($xml === false) {
        return $params;
    }

    $xml->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
    $operationNodes = $xml->xpath('//soap:Body/' . $operation);

    if (empty($operationNodes)) {
        return $params;
    }

    $opElement = $operationNodes[0];
    foreach ($opElement->children() as $child) {
        $name = $child->getName();
        // 只取第一个出现的值，避免重复标签覆盖
        if (!isset($params[$name])) {
            $params[$name] = (string)$child;
        }
    }

    return $params;
}

/**
 * 获取SOAP操作元素的innerXML（原始XML片段）
 *
 * @param string $rawXml 原始SOAP XML字符串
 * @param string $operation 期望的操作名
 * @return string 操作元素的innerXML，失败返回空字符串
 */
function getSoapOperationInnerXml($rawXml, $operation) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if (!$dom->loadXML($rawXml)) {
        return '';
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
    $nodes = $xpath->query('//soap:Body/' . $operation);

    if ($nodes->length === 0) {
        return '';
    }

    $innerXml = '';
    foreach ($nodes->item(0)->childNodes as $child) {
        $innerXml .= $dom->saveXML($child);
    }

    return $innerXml;
}

/**
 * 生成secret_level{N}.php文件
 * @param string $filePath 密码文件路径
 * @return void
 */
function generateSecretFile($filePath) {
    if (file_exists($filePath)) {
        return;
    }

    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $passcode = '';
    for ($i = 0; $i < 20; $i++) {
        $passcode .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    $content  = "<?php\n";
    $content .= "/**\n";
    $content .= " * <<系统配置文件>>\n";
    $content .= " * 天积安全 - HeaSec Security\n";
    $content .= " * @version 1.0.0\n";
    $content .= " * &config: data-source='internal' && status='active'\n";
    $content .= " */\n";
    $content .= "\$secret_passcode = '" . $passcode . "'; /* 系统密钥 */\n";

    file_put_contents($filePath, $content);
}

/**
 * 从secret.php文件中提取通关密码
 * @param string $filePath secret.php文件路径
 * @return string|null 通关密码
 */
function extractPasscode($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }
    $content = file_get_contents($filePath);
    if (preg_match('/\$secret_passcode\s*=\s*\'([^\']+)\'/', $content, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * 获取指定关卡的秘密文件路径
 * @param int $level 关卡编号
 * @return string 密码文件绝对路径
 */
function getSecretFilePath($level) {
    $basePath = dirname(__DIR__);
    return $basePath . '/config/secret_level' . $level . '.php';
}

/**
 * 确保数据文件存在
 * @param int $level 关卡编号
 * @return void
 */
function ensureDataFile($level) {
    $basePath = dirname(__DIR__);
    $dir = $basePath . '/data';

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if ($level === 1) {
        $filePath = $dir . '/level1_users.xml';
        if (!file_exists($filePath)) {
            file_put_contents($filePath, '<?xml version="1.0" encoding="UTF-8"?>' . "\n<users>\n</users>\n");
        }
    } elseif ($level === 2) {
        $filePath = $dir . '/level2_users.xml';
        if (!file_exists($filePath)) {
            initLevel2Users($filePath);
        }
    } elseif ($level === 3) {
        $filePath = $dir . '/level3_products.xml';
        if (!file_exists($filePath)) {
            $products = [
                ['id' => 1, 'name' => '智能手机', 'category' => '电子产品', 'price' => 3999.00],
                ['id' => 2, 'name' => '笔记本电脑', 'category' => '电子产品', 'price' => 6999.00],
                ['id' => 3, 'name' => '无线耳机', 'category' => '配件', 'price' => 299.00],
                ['id' => 4, 'name' => '平板电脑', 'category' => '电子产品', 'price' => 3299.00],
                ['id' => 5, 'name' => '手机壳', 'category' => '配件', 'price' => 29.90],
                ['id' => 6, 'name' => '智能手表', 'category' => '穿戴设备', 'price' => 1599.00],
                ['id' => 7, 'name' => '蓝牙音箱', 'category' => '配件', 'price' => 199.00],
                ['id' => 8, 'name' => '充电宝', 'category' => '配件', 'price' => 89.00],
            ];

            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><products></products>');
            foreach ($products as $p) {
                $item = $xml->addChild('product');
                $item->addChild('id', $p['id']);
                $item->addChild('name', $p['name']);
                $item->addChild('category', $p['category']);
                $item->addChild('price', $p['price']);
            }

            $dom = dom_import_simplexml($xml)->ownerDocument;
            $dom->formatOutput = true;
            file_put_contents($filePath, $dom->saveXML());
        }
    }
}

/**
 * 将内部XML字符串追加到用户数据文件
 * @param string $filePath XML文件路径
 * @param string $userXmlString 用户XML字符串
 * @return void
 */
function appendUserXml($filePath, $userXmlString) {
    if (!file_exists($filePath)) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><users></users>');
    } else {
        $xml = simplexml_load_file($filePath);
    }

    $dom = dom_import_simplexml($xml)->ownerDocument;
    $userFragment = $dom->createDocumentFragment();
    $userFragment->appendXML($userXmlString);
    $dom->documentElement->appendChild($userFragment);

    $dom->formatOutput = true;
    file_put_contents($filePath, $dom->saveXML());
}

/**
 * 从XML文件中读取指定用户名的用户信息
 * @param string $filePath XML文件路径
 * @param string $username 用户名
 * @return array|null 用户信息数组
 */
function readUserFromXml($filePath, $username) {
    if (!file_exists($filePath)) {
        return null;
    }

    $xml = simplexml_load_file($filePath);
    if ($xml === false) {
        return null;
    }

    // 使用遍历查找而非XPath拼接，避免XPath注入
    foreach ($xml->user as $user) {
        if ((string)$user->username === $username) {
            return [
                'username' => (string)$user->username,
                'role'     => (string)$user->role,
                'password' => (string)$user->password
            ];
        }
    }

    return null;
}

/**
 * 初始化第二关用户数据（admin账户 + 随机密码）
 * @param string $filePath XML文件路径
 * @return void
 */
function initLevel2Users($filePath) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < 20; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><users></users>');
    $user = $xml->addChild('user');
    $user->addChild('username', 'admin');
    $user->addChild('password', $password);
    $user->addChild('role', 'admin');

    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;
    file_put_contents($filePath, $dom->saveXML());
}

/**
 * 确保第三关的SSRF token已生成
 * token存储在文件中（XXE请求不携带Session Cookie，需使用文件共享）
 * @return string 当前token
 */
function ensureSsrfToken() {
    $tokenFile = dirname(__DIR__) . '/data/ssrf_token.txt';

    if (file_exists($tokenFile)) {
        $token = trim(file_get_contents($tokenFile));
        if (!empty($token)) {
            // 同步到session以便其他逻辑使用
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['soapxml_level3_ssrf_token'] = $token;
            }
            return $token;
        }
    }

    $token = bin2hex(random_bytes(16));

    // 确保data目录存在
    $dir = dirname($tokenFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($tokenFile, $token);

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['soapxml_level3_ssrf_token'] = $token;
    }

    return $token;
}

/**
 * 获取SSRF token（从文件读取）
 * @return string token值
 */
function getSsrfToken() {
    $tokenFile = dirname(__DIR__) . '/data/ssrf_token.txt';
    if (file_exists($tokenFile)) {
        return trim(file_get_contents($tokenFile));
    }
    return '';
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec SOAPXML Range v1.0.0');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $response = array_merge($response, $data);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
