<?php
/**
 * HeaSec天积安全团队 - JWT签名算法绕过靶场 - 第三关JWT逻辑
 * 版本: v1.0.0
 * 创建日期: 2026-03-02
 * 团队: 天积安全 (HeavenlySecret)
 */

// 防止直接访问
if (!defined('HEASEC_RANGE_ACCESS')) {
    exit('Access denied');
}

/**
 * 第三关JWT类 - 非对称加密验证
 */
class JWT_Level3
{
    /** @var string 私钥文件路径 */
    private static $privateKeyFile = __DIR__ . '/keys/level3_private.pem';

    /** @var string 公钥文件路径 */
    private static $publicKeyFile = __DIR__ . '/keys/level3_public.pem';

    /** @var resource|null 私钥 */
    private static $privateKey = null;

    /** @var resource|null 公钥 */
    private static $publicKey = null;

    /**
     * 获取私钥
     * @return resource|false
     */
    public static function getPrivateKey()
    {
        if (self::$privateKey !== null) {
            return self::$privateKey;
        }

        // 检查私钥文件是否存在
        if (file_exists(self::$privateKeyFile)) {
            $keyContent = file_get_contents(self::$privateKeyFile);
            self::$privateKey = openssl_pkey_get_private($keyContent);
        } else {
            // 生成新的RSA密钥对
            self::generateKeyPair();
        }

        return self::$privateKey;
    }

    /**
     * 获取公钥
     * @return resource|false
     */
    public static function getPublicKey()
    {
        if (self::$publicKey !== null) {
            return self::$publicKey;
        }

        // 检查公钥文件是否存在
        if (file_exists(self::$publicKeyFile)) {
            $keyContent = file_get_contents(self::$publicKeyFile);
            self::$publicKey = openssl_pkey_get_public($keyContent);
        } else {
            // 生成新的RSA密钥对
            self::generateKeyPair();
        }

        return self::$publicKey;
    }

    /**
     * 获取公钥PEM格式字符串
     * @return string|false
     */
    public static function getPublicKeyPem()
    {
        if (!file_exists(self::$publicKeyFile)) {
            self::generateKeyPair();
        }
        return file_get_contents(self::$publicKeyFile);
    }

    /**
     * 获取JWKS格式的公钥
     * @return array
     */
    public static function getJWKS()
    {
        $publicKey = self::getPublicKey();
        if (!$publicKey) {
            return [];
        }

        $keyDetails = openssl_pkey_get_details($publicKey);

        // 将模数(n)和指数(e)转换为Base64URL编码
        $n = self::base64UrlEncode($keyDetails['rsa']['n']);
        $e = self::base64UrlEncode($keyDetails['rsa']['e']);

        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'n' => $n,
                    'e' => $e,
                    'kid' => 'heasec-rs256-key'
                ]
            ]
        ];
    }

    /**
     * 生成RSA密钥对
     */
    private static function generateKeyPair()
    {
        $dir = dirname(self::$privateKeyFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // 生成2048位RSA密钥对
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Windows环境下需要指定openssl.cnf路径
        $opensslConfig = self::getOpenSSLConfigPath();
        if ($opensslConfig) {
            $config['config'] = $opensslConfig;
        }

        $keyPair = openssl_pkey_new($config);
        if (!$keyPair) {
            return false;
        }

        // 导出私钥（Windows环境下也需要指定config）
        $exportConfig = $opensslConfig ? ['config' => $opensslConfig] : null;
        openssl_pkey_export($keyPair, $privateKeyPem, null, $exportConfig);
        file_put_contents(self::$privateKeyFile, $privateKeyPem);

        // 导出公钥
        $keyDetails = openssl_pkey_get_details($keyPair);
        $publicKeyPem = $keyDetails['key'];
        file_put_contents(self::$publicKeyFile, $publicKeyPem);

        // 缓存密钥
        self::$privateKey = openssl_pkey_get_private($privateKeyPem);
        self::$publicKey = openssl_pkey_get_public($publicKeyPem);

        return true;
    }

    /**
     * Base64URL编码
     * @param string $data
     * @return string
     */
    public static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL解码
     * @param string $data
     * @return string
     */
    public static function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * 生成JWT Token（使用RS256）
     * @param string $username 用户名
     * @param string $role 角色
     * @return string JWT Token
     */
    public static function encode($username, $role)
    {
        $privateKey = self::getPrivateKey();
        if (!$privateKey) {
            return false;
        }

        // Header
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        // Payload
        $payload = [
            'iss' => 'heasec.com',
            'sub' => $username,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + 86400 // 24小时有效期
        ];

        // 编码Header和Payload
        $encodedHeader = self::base64UrlEncode(json_encode($header));
        $encodedPayload = self::base64UrlEncode(json_encode($payload));

        // 使用RSA私钥签名
        $message = $encodedHeader . '.' . $encodedPayload;
        $signature = self::rsaSign($message, $privateKey);

        return $encodedHeader . '.' . $encodedPayload . '.' . $signature;
    }

    /**
     * RSA签名
     * @param string $message
     * @param resource $privateKey
     * @return string
     */
    private static function rsaSign($message, $privateKey)
    {
        openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return self::base64UrlEncode($signature);
    }

    /**
     * 验证并解码JWT Token
     * @param string $token
     * @return array|false 解码后的payload或false
     */
    public static function decode($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($encodedHeader, $encodedPayload, $signature) = $parts;

        // 解码Header
        $headerJson = self::base64UrlDecode($encodedHeader);
        if ($headerJson === false) {
            return false;
        }
        $header = json_decode($headerJson, true);
        if (!$header || !isset($header['alg'])) {
            return false;
        }

        $alg = strtoupper($header['alg']);
        $message = $encodedHeader . '.' . $encodedPayload;

        // 根据alg字段选择验证方式
        if ($alg === 'HS256') {
            // 使用配置的密钥进行验证
            $publicKeyPem = self::getPublicKeyPem();
            $expectedSignature = self::hmacSign($message, $publicKeyPem);

            if (!self::hashEquals($expectedSignature, $signature)) {
                return false;
            }
        } elseif ($alg === 'RS256') {
            // 正常的RSA验证
            $publicKey = self::getPublicKey();
            if (!$publicKey) {
                return false;
            }

            $decodedSignature = self::base64UrlDecode($signature);
            if ($decodedSignature === false) {
                return false;
            }

            $verifyResult = openssl_verify($message, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);
            if ($verifyResult !== 1) {
                return false;
            }
        } else {
            // 不支持的算法
            return false;
        }

        // 解码Payload
        $payloadJson = self::base64UrlDecode($encodedPayload);
        if ($payloadJson === false) {
            return false;
        }
        $payload = json_decode($payloadJson, true);
        if (!$payload) {
            return false;
        }

        // 检查过期时间
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    /**
     * HMAC签名（用于算法混淆攻击）
     * @param string $message
     * @param string $secret
     * @return string
     */
    private static function hmacSign($message, $secret)
    {
        $signature = hash_hmac('sha256', $message, $secret, true);
        return self::base64UrlEncode($signature);
    }

    /**
     * 时间安全字符串比较
     * @param string $knownString
     * @param string $userString
     * @return bool
     */
    private static function hashEquals($knownString, $userString)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($knownString, $userString);
        }

        // 降级处理
        if (strlen($knownString) !== strlen($userString)) {
            return false;
        }

        $result = 0;
        $length = strlen($knownString);
        for ($i = 0; $i < $length; $i++) {
            $result |= ord($knownString[$i]) ^ ord($userString[$i]);
        }

        return $result === 0;
    }

    /**
     * 获取OpenSSL配置文件路径
     * 优先使用项目内置配置文件，确保跨平台兼容性
     * @return string|null
     */
    private static function getOpenSSLConfigPath()
    {
        // 优先使用项目内置的OpenSSL配置文件
        $projectConfig = __DIR__ . '/keys/openssl.cnf';
        if (file_exists($projectConfig)) {
            return $projectConfig;
        }

        // 如果是Windows环境，尝试查找系统配置文件（备选方案）
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $possiblePaths = [
                dirname(PHP_BINARY) . '\extras\ssl\openssl.cnf',
                dirname(PHP_BINARY) . '\ssl\openssl.cnf',
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * 删除密钥文件（用于重置）
     */
    public static function resetSecret()
    {
        if (file_exists(self::$privateKeyFile)) {
            unlink(self::$privateKeyFile);
        }
        if (file_exists(self::$publicKeyFile)) {
            unlink(self::$publicKeyFile);
        }
        self::$privateKey = null;
        self::$publicKey = null;
    }
}
