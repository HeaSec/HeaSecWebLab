<?php
/**
 * HeaSec天积安全团队 - JWT基础漏洞靶场 - 第一关JWT逻辑
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 防止直接访问
if (!defined('HEASEC_RANGE_ACCESS')) {
    exit('Access denied');
}

/**
 * 第一关JWT类
 */
class JWT_Level1
{
    /** @var string 密钥文件路径 */
    private static $keyFile = __DIR__ . '/keys/level1_secret.txt';

    /** @var string 密钥 */
    private static $secret = null;

    /**
     * 获取密钥
     * @return string
     */
    public static function getSecret()
    {
        if (self::$secret !== null) {
            return self::$secret;
        }

        // 检查密钥文件是否存在
        if (file_exists(self::$keyFile)) {
            self::$secret = trim(file_get_contents(self::$keyFile));
        } else {
            // 生成新的10位随机密钥
            self::$secret = self::generateRandomString(10);
            self::saveSecret(self::$secret);
        }

        return self::$secret;
    }

    /**
     * 保存密钥到文件
     * @param string $secret
     */
    private static function saveSecret($secret)
    {
        $dir = dirname(self::$keyFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(self::$keyFile, $secret);
    }

    /**
     * 生成随机字符串
     * @param int $length
     * @return string
     */
    private static function generateRandomString($length = 10)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
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
     * 生成JWT Token
     * @param string $username 用户名
     * @param string $role 角色
     * @param string|null $passcode 通关密码（admin用户时传入）
     * @return string JWT Token
     */
    public static function encode($username, $role, $passcode = null)
    {
        $secret = self::getSecret();

        // Header
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        // Payload
        $payload = [
            'iss' => 'heasec.com',
            'sub' => $username,
            'user' => $username,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + 86400 // 24小时有效期
        ];

        // 如果有通关密码，放入Payload
        if ($passcode !== null) {
            $payload['secret'] = $passcode;
        }

        // 编码Header和Payload
        $encodedHeader = self::base64UrlEncode(json_encode($header));
        $encodedPayload = self::base64UrlEncode(json_encode($payload));

        // 生成签名
        $message = $encodedHeader . '.' . $encodedPayload;
        $signature = self::hmacSign($message, $secret);

        return $encodedHeader . '.' . $encodedPayload . '.' . $signature;
    }

    /**
     * HMAC签名
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

        $alg = strtolower($header['alg']);

        // 只支持HS256算法
        if ($alg !== 'hs256') {
            return false;
        }

        $secret = self::getSecret();
        $message = $encodedHeader . '.' . $encodedPayload;
        $expectedSignature = self::hmacSign($message, $secret);

        // 时间安全比较
        if (!self::hashEquals($expectedSignature, $signature)) {
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
     * 删除密钥文件（用于重置）
     */
    public static function resetSecret()
    {
        if (file_exists(self::$keyFile)) {
            unlink(self::$keyFile);
        }
        self::$secret = null;
    }
}
