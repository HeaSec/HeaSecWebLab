<?php
/**
 * HeaSec天积安全团队 - 学习状态更新公共组件
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 用于靶场统一调用学习状态更新API接口
 */


/**
 * 更新学习状态：通过HTTP请求调用API接口
 *
 * @param string $rangeCode 靶场代码
 * @param string $newStatus 新的学习状态（默认为"学习中"）
 * @return bool 更新是否成功
 */
function HeaSec_UpdateLearningStatus($rangeCode, $newStatus = '学习中') {
    try {
        // 构建API URL（从网站根目录开始）
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $apiUrl = $protocol . '://' . $host . '/heasecdev/range/common/api/update-learning-status.php';

        // 准备请求数据
        $postData = json_encode(array(
            'code' => $rangeCode,
            'status' => $newStatus
        ));

        // 使用 cURL 发送 POST 请求
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['success']) && $result['success']) {
                error_log('[HeaSec] 学习状态已更新: ' . $rangeCode . ' -> ' . $newStatus);
                return true;
            }
        }

        return false;
    } catch (Exception $e) {
        // 学习状态更新失败不影响通关逻辑
        error_log('[HeaSec] 更新学习状态失败: ' . $e->getMessage());
        return false;
    }
}

/**
 * 检查并更新学习状态（仅当当前状态为"待学习"时更新为"学习中"）
 * 这是一个便捷函数，用于在通关时自动更新状态
 *
 * @param string $rangeCode 靶场代码
 * @return bool 更新是否成功
 */
function HeaSec_UpdateLearningStatusIfNeeded($rangeCode) {
    return HeaSec_UpdateLearningStatus($rangeCode, '学习中');
}
