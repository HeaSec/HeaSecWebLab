<?php
/**
 * HeaSec天积安全团队 - 短信模拟器发送接口集成库
 * SMS Simulator Sender Integration Library
 * 版本: v1.0.0
 * 创建日期: 2026-01-07
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能说明:
 *   - 供各个靶场集成，用于调用短信模拟器发送接口
 *   - 自动处理路径问题，确保在任何层级目录下都能正确调用
 *   - 提供简单的函数接口，使用便捷
 *
 * 使用方法:
 *   require_once 'path/to/HeaSec_SmsSender.php';
 *   $result = HeaSec_SmsSender::send('13800138000', '验证码123456', 'my_range_code');
 *
 * 注意：
 *   - 此类仅使用PHP后端进行调用，不依赖前端JavaScript
 *   - 需要确保服务器支持cURL或file_get_contents
 */

if (!defined('HEASEC_SMS_SENDER_LOADED')) {
    define('HEASEC_SMS_SENDER_LOADED', true);

    class HeaSec_SmsSender {

        /**
         * 发送短信
         *
         * @param string $phone 手机号（1开头，11位数字）
         * @param string $message 短信内容（不超过500个字符）
         * @param string $rangeCode 靶场代码（用于标识发送者）
         * @return array 返回结果数组，包含success、message、data字段
         */
        public static function send($phone, $message, $rangeCode = 'unknown') {
            // 获取API接口的URL
            $apiUrl = self::getApiUrl();

            // 准备请求数据
            $postData = array(
                'phone' => $phone,
                'message' => $message,
                'range_code' => $rangeCode
            );

            // 发送POST请求
            $response = self::sendPostRequest($apiUrl, $postData);

            // 解析响应
            return self::parseResponse($response);
        }

        /**
         * 获取短信发送API的URL
         * 自动处理路径问题，确保在任何层级目录下都能正确调用
         *
         * @return string API接口的完整URL
         */
        private static function getApiUrl() {
            // 获取当前文件的绝对路径
            $currentFile = __FILE__;

            // 获取API文件的绝对路径
            // 当前文件位于：range/common/components/sms-simulator/includes/HeaSec_SmsSender.php
            // API文件位于：range/common/components/sms-simulator/api/send-sms.php
            $apiFile = dirname(dirname($currentFile)) . '/api/send-sms.php';

            // 将绝对路径转换为相对于网站根目录的路径
            $documentRoot = $_SERVER['DOCUMENT_ROOT'];

            // 标准化路径分隔符（Windows使用反斜杠，Web使用正斜杠）
            $apiFile = str_replace('\\', '/', $apiFile);
            $documentRoot = str_replace('\\', '/', $documentRoot);

            // 移除文档根目录前缀，得到相对路径
            if (strpos($apiFile, $documentRoot) === 0) {
                $relativePath = substr($apiFile, strlen($documentRoot));
                // 确保以斜杠开头
                if (substr($relativePath, 0, 1) != '/') {
                    $relativePath = '/' . $relativePath;
                }
            } else {
                // 如果无法从文档根目录计算，使用HTTP协议构建URL
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                $scriptPath = dirname(dirname($_SERVER['SCRIPT_NAME']));
                $relativePath = $scriptPath . '/components/sms-simulator/api/send-sms.php';
            }

            // 构建完整的URL
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

            return $protocol . '://' . $host . $relativePath;
        }

        /**
         * 发送POST请求
         *
         * @param string $url 请求URL
         * @param array $data 请求数据
         * @return string 响应内容
         */
        private static function sendPostRequest($url, $data) {
            // 优先使用cURL
            if (function_exists('curl_init')) {
                return self::sendWithCurl($url, $data);
            } else {
                // 备用方案：使用file_get_contents
                return self::sendWithFileGetContents($url, $data);
            }
        }

        /**
         * 使用cURL发送POST请求
         *
         * @param string $url 请求URL
         * @param array $data 请求数据
         * @return string 响应内容
         */
        private static function sendWithCurl($url, $data) {
            $ch = curl_init($url);

            // 设置cURL选项
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json'
            ));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            // 执行请求
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return json_encode(array(
                    'success' => false,
                    'message' => '[HeaSec] cURL请求失败: ' . $error,
                    'data' => null,
                    'timestamp' => time()
                ));
            }

            return $response;
        }

        /**
         * 使用file_get_contents发送POST请求
         *
         * @param string $url 请求URL
         * @param array $data 请求数据
         * @return string 响应内容
         */
        private static function sendWithFileGetContents($url, $data) {
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-type: application/json\r\n" .
                                "Accept: application/json\r\n",
                    'content' => json_encode($data),
                    'timeout' => 10
                )
            );

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return json_encode(array(
                    'success' => false,
                    'message' => '[HeaSec] HTTP请求失败',
                    'data' => null,
                    'timestamp' => time()
                ));
            }

            return $response;
        }

        /**
         * 解析API响应
         *
         * @param string $response 响应内容
         * @return array 解析后的结果数组
         */
        private static function parseResponse($response) {
            $result = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return array(
                    'success' => false,
                    'message' => '[HeaSec] 解析API响应失败: ' . json_last_error_msg(),
                    'data' => null
                );
            }

            return $result;
        }

        /**
         * 快速发送短信（静默模式，不抛出异常）
         *
         * @param string $phone 手机号
         * @param string $message 短信内容
         * @param string $rangeCode 靶场代码
         * @return bool 发送是否成功
         */
        public static function sendQuick($phone, $message, $rangeCode = 'unknown') {
            $result = self::send($phone, $message, $rangeCode);
            return isset($result['success']) && $result['success'] === true;
        }
    }
}
?>
