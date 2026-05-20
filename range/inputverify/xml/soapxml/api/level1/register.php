<?php
/**
 * HeaSec天积安全团队 - SOAP与XML靶场第一关注册接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../../common/';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once dirname(__DIR__) . '/../includes/functions.php';

// 读取原始SOAP XML请求
$rawXml = file_get_contents('php://input');

// 提取innerXML（包含完整的操作元素子节点XML）
$innerXml = getSoapOperationInnerXml($rawXml, 'Register');

// 同时提取个别参数用于验证
$params = parseSoapRequest($rawXml, 'Register');
$username = isset($params['username']) ? $params['username'] : '';
$password = isset($params['password']) ? $params['password'] : '';

/**
 * 处理用户注册
 * @param string $username 用户名
 * @param string $password 密码
 * @param string $innerXml Register元素的innerXML
 */
function handleRegister($username, $password, $innerXml) {
    $usersFile = dirname(__DIR__) . '/../data/level1_users.xml';

    if (empty($username) || empty($password)) {
        return sendJsonResponse(false, '用户名和密码不能为空');
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        return sendJsonResponse(false, '用户名仅允许字母、数字和下划线，长度3-20字符');
    }

    // 检查用户名是否已存在
    $existingUser = readUserFromXml($usersFile, $username);
    if ($existingUser !== null) {
        return sendJsonResponse(false, '用户名已存在');
    }

    // 确保数据文件存在
    ensureDataFile(1);

    // 将Register元素的内部XML包裹到<user>根元素中构造内部文档
    // 末尾追加固定的<role>user</role>标签作为默认角色
    $userXmlString = '<user>' . $innerXml . '<role>user</role></user>';

    appendUserXml($usersFile, $userXmlString);

    // 从写入的XML文件中读取回用户数据
    $savedUser = readUserFromXml($usersFile, $username);

    if ($savedUser === null) {
        return sendJsonResponse(false, '注册失败，请重试');
    }

    return sendJsonResponse(true, '注册成功', [
        'data' => [
            'username' => $savedUser['username'],
            'role' => $savedUser['role']
        ]
    ]);
}

handleRegister($username, $password, $innerXml);
