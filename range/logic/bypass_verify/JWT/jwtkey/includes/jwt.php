<?php
/**
 * HeaSec天积安全团队 - JWT密钥注入靶场 - JWT逻辑
 * JWT Key Injection Range - JWT Logic with Vulnerabilities
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 防止直接访问
if (!defined('HEASEC_RANGE_ACCESS')) {
    exit('Access denied');
}

/**
 * JWT类 - RS256算法实现
 */
class JWT_KeyInjection
{
    /** @var string 私钥文件路径 */
    private static $privateKeyFile = __DIR__ . '/../keys/private.pem';

    /** @var string 公钥文件路径 */
    private static $publicKeyFile = __DIR__ . '/../keys/public.pem';

    /** @var string 默认密钥文件路径 */
    private static $defaultKeyFile = __DIR__ . '/../keys/default-key.pem';

    /** @var string OpenSSL配置文件路径 */
    private static $opensslConfigFile = __DIR__ . '/../keys/openssl.cnf';

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
                    'kid' => 'default-key'
                ]
            ]
        ];
    }

    /**
     * 生成RSA密钥对
     */
    public static function generateKeyPair()
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

        // 创建默认密钥文件（复制公钥）
        copy(self::$publicKeyFile, self::$defaultKeyFile);

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
            'typ' => 'JWT',
            'kid' => 'default-key'
        ];

        // Payload
        $payload = [
            'iss' => 'heasec.com',
            'sub' => $username,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + JWTKEY_TOKEN_EXPIRE
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

        // 只支持RS256算法
        if ($alg !== 'RS256') {
            return false;
        }

        // 获取公钥进行验证
        $publicKey = self::getPublicKeyForVerification($header);
        if (!$publicKey) {
            return false;
        }

        // 验证签名
        $decodedSignature = self::base64UrlDecode($signature);
        if ($decodedSignature === false) {
            return false;
        }

        $verifyResult = openssl_verify($message, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verifyResult !== 1) {
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
     * 获取用于验证的公钥
     * @param array $header JWT Header
     * @return resource|false
     */
    private static function getPublicKeyForVerification($header)
    {
        $kid = isset($header['kid']) ? $header['kid'] : 'default-key';
        $jku = isset($header['jku']) ? $header['jku'] : null;

        // 支持jku头部获取JWKS
        if (!empty($jku)) {
            $jwks = self::fetchJWKS($jku);
            if ($jwks && isset($jwks['keys'][0])) {
                $jwk = $jwks['keys'][0];
                $publicKey = self::jwkToPem($jwk);
                if ($publicKey) {
                    return openssl_pkey_get_public($publicKey);
                }
            }
        }

        // 检查kid是否包含密钥特征
        $rsaPatterns = [
            'BEGIN PUBLIC KEY',
            'BEGIN RSA PUBLIC KEY',
            'MIIBIjANBg',
            'MFkwE',
            'MIIBojANBg'
        ];

        foreach ($rsaPatterns as $pattern) {
            if (stripos($kid, $pattern) !== false) {
                // kid包含RSA公钥特征，尝试直接作为公钥使用
                // kid可能是Base64URL编码的PEM内容（不含头尾）

                // 如果kid已经包含PEM头尾，直接使用
                if (strpos($kid, 'BEGIN PUBLIC KEY') !== false) {
                    $publicKey = openssl_pkey_get_public($kid);
                    if ($publicKey) {
                        return $publicKey;
                    }
                }

                // kid是Base64URL编码的PEM内容，需要转换为标准Base64并包装
                // 将Base64URL转换回标准Base64（替换-_为+，添加填充）
                $base64Content = strtr($kid, '-_', '+/');
                $remainder = strlen($base64Content) % 4;
                if ($remainder) {
                    $base64Content .= str_repeat('=', 4 - $remainder);
                }

                // 包装为PEM格式
                $pemContent = "-----BEGIN PUBLIC KEY-----\n" .
                              wordwrap($base64Content, 64, "\n", true) .
                              "\n-----END PUBLIC KEY-----";

                $publicKey = openssl_pkey_get_public($pemContent);
                if ($publicKey) {
                    return $publicKey;
                }
            }
        }

        // 构造密钥文件路径（不限制后缀名，直接读取文件内容）
        $keyPath = __DIR__ . '/../keys/' . $kid;

        // 检查文件是否存在
        if (file_exists($keyPath)) {
            $keyContent = file_get_contents($keyPath);
            $publicKey = openssl_pkey_get_public($keyContent);
            if ($publicKey) {
                return $publicKey;
            }
        }

        // 使用默认公钥
        return self::getPublicKey();
    }

    /**
     * 从URL获取JWKS
     * @param string $url JWKS URL
     * @return array|false
     */
    private static function fetchJWKS($url)
    {
        // 使用curl获取JWKS
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * 将JWK转换为PEM格式
     * @param array $jwk JWK对象
     * @return string|false
     */
    private static function jwkToPem($jwk)
    {
        if (!isset($jwk['n']) || !isset($jwk['e'])) {
            return false;
        }

        // 解码模数和指数
        $n = self::base64UrlDecode($jwk['n']);
        $e = self::base64UrlDecode($jwk['e']);

        if ($n === false || $e === false) {
            return false;
        }

        // 构建RSA公钥
        $modulus = self::positiveIntegerToDER($n);
        $publicExponent = self::positiveIntegerToDER($e);

        // RSA公钥的ASN.1结构
        $sequence = chr(0x30) . self::lengthToDER(strlen($modulus) + strlen($publicExponent));
        $sequence .= $modulus . $publicExponent;

        // 包装为BIT STRING
        $bitString = chr(0x03) . self::lengthToDER(strlen($sequence) + 1) . chr(0x00) . $sequence;

        // RSA PublicKey的OID
        $oid = chr(0x06) . chr(0x09) . chr(0x2A) . chr(0x86) . chr(0x48) . chr(0x86) . chr(0xF7) . chr(0x0D) . chr(0x01) . chr(0x01) . chr(0x01);
        $null = chr(0x05) . chr(0x00);
        $algorithm = chr(0x30) . self::lengthToDER(strlen($oid) + strlen($null)) . $oid . $null;

        // 最终的SubjectPublicKeyInfo
        $spki = chr(0x30) . self::lengthToDER(strlen($algorithm) + strlen($bitString)) . $algorithm . $bitString;

        // 转换为PEM格式
        $pem = "-----BEGIN PUBLIC KEY-----\n";
        $pem .= wordwrap(base64_encode($spki), 64, "\n", true);
        $pem .= "\n-----END PUBLIC KEY-----";

        return $pem;
    }

    /**
     * 将正整数转换为DER格式
     * @param string $data
     * @return string
     */
    private static function positiveIntegerToDER($data)
    {
        // 如果最高位是1，需要在前面加0x00
        if (ord($data[0]) & 0x80) {
            $data = chr(0x00) . $data;
        }
        return chr(0x02) . self::lengthToDER(strlen($data)) . $data;
    }

    /**
     * 将长度转换为DER格式
     * @param int $length
     * @return string
     */
    private static function lengthToDER($length)
    {
        if ($length < 128) {
            return chr($length);
        }

        $lengthBytes = '';
        while ($length > 0) {
            $lengthBytes = chr($length & 0xFF) . $lengthBytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($lengthBytes)) . $lengthBytes;
    }

    /**
     * 获取OpenSSL配置文件路径
     * 优先使用项目内置配置文件，确保跨平台兼容性
     * @return string|null
     */
    private static function getOpenSSLConfigPath()
    {
        // 优先使用项目内置的OpenSSL配置文件
        if (file_exists(self::$opensslConfigFile)) {
            return self::$opensslConfigFile;
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
    public static function resetKeys()
    {
        if (file_exists(self::$privateKeyFile)) {
            unlink(self::$privateKeyFile);
        }
        if (file_exists(self::$publicKeyFile)) {
            unlink(self::$publicKeyFile);
        }
        if (file_exists(self::$defaultKeyFile)) {
            unlink(self::$defaultKeyFile);
        }
        self::$privateKey = null;
        self::$publicKey = null;
    }

    /**
     * 解码JWT Token（仅解码不验证，用于获取Header）
     * @param string $token
     * @return array|null 包含header和payload的数组
     */
    public static function decodeWithoutVerification($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $headerJson = self::base64UrlDecode($parts[0]);
        $payloadJson = self::base64UrlDecode($parts[1]);

        if ($headerJson === false || $payloadJson === false) {
            return null;
        }

        return [
            'header' => json_decode($headerJson, true),
            'payload' => json_decode($payloadJson, true)
        ];
    }
}
?>
