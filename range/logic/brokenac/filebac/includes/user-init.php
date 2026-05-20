<?php
/**
 * HeaSec天积安全团队 - 文件越权访问靶场 - 数据初始化
 * 版本: v1.0.0
 * 创建日期: 2026-03-06
 * 团队: 天积安全 (HeavenlySecret)
 */

require_once __DIR__ . '/image_generator.php';

/**
 * 获取初始化锁名称
 *
 * @param int $level 关卡编号
 * @return string 锁名称
 */
function getInitLockName($level) {
    return 'heasec_filebac_init_level_' . (int) $level;
}

/**
 * 获取关卡数据是否已存在
 *
 * @param PDO $pdo 数据库连接
 * @param int $level 关卡编号
 * @return bool 是否存在
 */
function isLevelDataExists($pdo, $level) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM heasec_filebac_data WHERE level = ?");
    $stmt->execute([$level]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * 获取初始化锁
 *
 * @param PDO $pdo 数据库连接
 * @param int $level 关卡编号
 * @return bool 是否获取成功
 */
function acquireInitLock($pdo, $level) {
    $stmt = $pdo->prepare('SELECT GET_LOCK(?, 10)');
    $stmt->execute([getInitLockName($level)]);
    return (int) $stmt->fetchColumn() === 1;
}

/**
 * 释放初始化锁
 *
 * @param PDO $pdo 数据库连接
 * @param int $level 关卡编号
 * @return void
 */
function releaseInitLock($pdo, $level) {
    try {
        $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $stmt->execute([getInitLockName($level)]);
    } catch (Exception $exception) {
        // 忽略释放锁失败，避免覆盖主异常
    }
}

/**
 * 清理生成失败时产生的文件
 *
 * @param array $createdFiles 已生成文件列表
 * @return void
 */
function cleanupCreatedFiles($createdFiles) {
    foreach ($createdFiles as $filePath) {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }
}

/**
 * 生成图片并在失败时抛出异常
 *
 * @param bool $result 生成结果
 * @param string $outputPath 输出路径
 * @param array $createdFiles 已生成文件列表（引用）
 * @param string $errorMessage 错误信息
 * @return void
 * @throws Exception
 */
function assertImageGenerated($result, $outputPath, &$createdFiles, $errorMessage) {
    if (!$result || !is_file($outputPath)) {
        throw new Exception($errorMessage);
    }

    $createdFiles[] = $outputPath;
}

/**
 * 初始化第一关数据：成绩查看系统
 *
 * @param PDO $pdo 数据库连接
 * @return array 初始化结果
 */
function initLevel1Data($pdo) {
    if (isLevelDataExists($pdo, 1)) {
        return ['success' => true, 'message' => '第一关数据已存在'];
    }

    if (!acquireInitLock($pdo, 1)) {
        throw new Exception('第一关初始化锁获取失败，请稍后重试');
    }

    $createdFiles = [];

    try {
        if (isLevelDataExists($pdo, 1)) {
            return ['success' => true, 'message' => '第一关数据已存在'];
        }

        // 测试学生信息（固定）
        $testStudent = [
            'name' => '卓策仕',
            'grade' => '2018',
            'class' => '5',
            'student_id' => '3182427520',
            'score' => 98
        ];

        // 干扰学生姓名
        $interferenceNames = ['张三', '李四', '王五', '赵六', '钱七', '孙八', '周九', '吴十'];

        // 生成不重复的学号后三位（班级和座位号不能为0）
        $usedSuffixes = [520];
        $interferenceStudents = [];

        for ($i = 0; $i < 8; $i++) {
            do {
                $classNum = mt_rand(1, 9);
                $seatNum = mt_rand(1, 99);
                $suffix = $classNum * 100 + $seatNum;
            } while (in_array($suffix, $usedSuffixes));

            $usedSuffixes[] = $suffix;
            $studentId = '3182427' . str_pad($suffix, 3, '0', STR_PAD_LEFT);
            $score = mt_rand(50, 100);

            $interferenceStudents[] = [
                'name' => $interferenceNames[$i],
                'student_id' => $studentId,
                'score' => $score
            ];
        }

        $targetIndex = mt_rand(0, 7);
        $targetStudent = $interferenceStudents[$targetIndex];
        $passcode = generatePasscode();

        $transcriptDir = dirname(__DIR__) . '/transcript/';
        if (!is_dir($transcriptDir)) {
            mkdir($transcriptDir, 0755, true);
        }

        $testImagePath = $transcriptDir . $testStudent['student_id'] . '.png';
        assertImageGenerated(
            generateTranscriptImage($testStudent['name'], $testStudent['score'], null, $testImagePath),
            $testImagePath,
            $createdFiles,
            '第一关测试成绩单生成失败'
        );

        $fileData = [];
        foreach ($interferenceStudents as $index => $student) {
            $imagePath = $transcriptDir . $student['student_id'] . '.png';
            $result = $index === $targetIndex
                ? generateTranscriptImage($student['name'], $student['score'], $passcode, $imagePath)
                : generateTranscriptImage($student['name'], $student['score'], null, $imagePath);

            assertImageGenerated($result, $imagePath, $createdFiles, '第一关干扰成绩单生成失败');

            $fileData[] = [
                'name' => $student['name'],
                'student_id' => $student['student_id'],
                'score' => $student['score']
            ];
        }

        $userData = [
            'name' => $testStudent['name'],
            'grade' => $testStudent['grade'],
            'class' => $testStudent['class'],
            'student_id' => $testStudent['student_id'],
            'score' => $testStudent['score']
        ];

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO heasec_filebac_data
            (level, account, password, user_data, target_identifier, passcode, file_data)
            VALUES (1, 'test', '123456', ?, ?, ?, ?)");

        $stmt->execute([
            json_encode($userData, JSON_UNESCAPED_UNICODE),
            $targetStudent['student_id'],
            $passcode,
            json_encode($fileData, JSON_UNESCAPED_UNICODE)
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => '第一关数据初始化成功',
            'target_identifier' => $targetStudent['student_id']
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        cleanupCreatedFiles($createdFiles);

        if (isLevelDataExists($pdo, 1)) {
            return ['success' => true, 'message' => '第一关数据已存在'];
        }

        throw $e;
    } finally {
        releaseInitLock($pdo, 1);
    }
}

/**
 * 初始化第二关数据：订单查询系统
 *
 * @param PDO $pdo 数据库连接
 * @return array 初始化结果
 */
function initLevel2Data($pdo) {
    if (isLevelDataExists($pdo, 2)) {
        return ['success' => true, 'message' => '第二关数据已存在'];
    }

    if (!acquireInitLock($pdo, 2)) {
        throw new Exception('第二关初始化锁获取失败，请稍后重试');
    }

    $createdFiles = [];

    try {
        if (isLevelDataExists($pdo, 2)) {
            return ['success' => true, 'message' => '第二关数据已存在'];
        }

        $testOrders = [
            [
                'order_id' => '65432023082318',
                'customer' => '张三',
                'phone' => '13800138001',
                'amount' => 299.00,
                'status' => '已完成',
                'date' => '2023-08-23'
            ],
            [
                'order_id' => '65432023082317',
                'customer' => '李四',
                'phone' => '13800138002',
                'amount' => 159.00,
                'status' => '已完成',
                'date' => '2023-08-23'
            ],
            [
                'order_id' => '65432024091821',
                'customer' => '王五',
                'phone' => '13800138003',
                'amount' => 599.00,
                'status' => '已完成',
                'date' => '2024-09-18'
            ]
        ];

        $usedImagePaths = [];
        foreach ($testOrders as $order) {
            $monthDir = substr($order['order_id'], 4, 6);
            $fileSuffix = substr($order['order_id'], -4);
            $usedImagePaths[$monthDir . '/FJDLkhdd' . $fileSuffix . '.png'] = true;
        }

        $interferenceOrders = [];
        for ($i = 0; $i < 8; $i++) {
            do {
                $year = 2023;
                $month = mt_rand(1, 12);
                $day = mt_rand(1, 28);
                $dateStr = sprintf('%04d%02d%02d', $year, $month, $day);
                $randomSuffix = str_pad(mt_rand(0, 99), 2, '0', STR_PAD_LEFT);
                $orderId = '6543' . $dateStr . $randomSuffix;
                $monthDir = substr($orderId, 4, 6);
                $fileSuffix = substr($orderId, -4);
                $imageKey = $monthDir . '/FJDLkhdd' . $fileSuffix . '.png';
            } while (isset($usedImagePaths[$imageKey]));

            $usedImagePaths[$imageKey] = true;
            $dateFormatted = sprintf('%04d-%02d-%02d', $year, $month, $day);

            $interferenceOrders[] = [
                'order_id' => $orderId,
                'date' => $dateFormatted
            ];
        }

        $targetIndex = mt_rand(0, 7);
        $targetOrder = $interferenceOrders[$targetIndex];
        $passcode = generatePasscode();

        $orderDir = dirname(__DIR__) . '/filebac_level2/order/';
        if (!is_dir($orderDir)) {
            mkdir($orderDir, 0755, true);
        }

        foreach ($testOrders as $order) {
            $monthDir = substr($order['order_id'], 4, 6);
            $fullDir = $orderDir . $monthDir . '/';
            if (!is_dir($fullDir)) {
                mkdir($fullDir, 0755, true);
            }

            $fileSuffix = substr($order['order_id'], -4);
            $imagePath = $fullDir . 'FJDLkhdd' . $fileSuffix . '.png';
            assertImageGenerated(
                generateOrderImage(true, mt_rand(-15, 15), null, $imagePath),
                $imagePath,
                $createdFiles,
                '第二关测试订单图片生成失败'
            );
        }

        $fileData = [];
        foreach ($interferenceOrders as $index => $order) {
            $monthDir = substr($order['order_id'], 4, 6);
            $fullDir = $orderDir . $monthDir . '/';
            if (!is_dir($fullDir)) {
                mkdir($fullDir, 0755, true);
            }

            $fileSuffix = substr($order['order_id'], -4);
            $imagePath = $fullDir . 'FJDLkhdd' . $fileSuffix . '.png';
            $result = $index === $targetIndex
                ? generateOrderImage(true, mt_rand(-15, 15), $passcode, $imagePath)
                : generateOrderImage(true, mt_rand(-15, 15), null, $imagePath);

            assertImageGenerated($result, $imagePath, $createdFiles, '第二关干扰订单图片生成失败');

            $fileData[] = [
                'order_id' => $order['order_id'],
                'date' => $order['date']
            ];
        }

        $userData = [
            'name' => '测试用户',
            'orders' => $testOrders
        ];

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO heasec_filebac_data
            (level, account, password, user_data, target_identifier, passcode, file_data)
            VALUES (2, 'test', '123456', ?, ?, ?, ?)");

        $stmt->execute([
            json_encode($userData, JSON_UNESCAPED_UNICODE),
            $targetOrder['order_id'],
            $passcode,
            json_encode($fileData, JSON_UNESCAPED_UNICODE)
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => '第二关数据初始化成功',
            'target_identifier' => $targetOrder['order_id']
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        cleanupCreatedFiles($createdFiles);

        if (isLevelDataExists($pdo, 2)) {
            return ['success' => true, 'message' => '第二关数据已存在'];
        }

        throw $e;
    } finally {
        releaseInitLock($pdo, 2);
    }
}

/**
 * 初始化第三关数据：实名登记系统
 *
 * @param PDO $pdo 数据库连接
 * @return array 初始化结果
 */
function initLevel3Data($pdo) {
    if (isLevelDataExists($pdo, 3)) {
        return ['success' => true, 'message' => '第三关数据已存在'];
    }

    if (!acquireInitLock($pdo, 3)) {
        throw new Exception('第三关初始化锁获取失败，请稍后重试');
    }

    $createdFiles = [];

    try {
        if (isLevelDataExists($pdo, 3)) {
            return ['success' => true, 'message' => '第三关数据已存在'];
        }

        $testUser = [
            'name' => '关莉媛',
            'phone' => '13805916688',
            'idcard' => '350105200206068888'
        ];

        $interferenceNames = ['张三', '李四', '王五', '赵六', '钱七', '孙八', '周九', '吴十'];
        $interferenceIdcards = [
            '350105203003033333',
            '350105203004044444',
            '350105203005055555',
            '350105203006066666',
            '350105203007077777',
            '350105203008088888',
            '350105203009099999',
            '350105203010100000'
        ];

        $usedSuffixes = [6688];
        $interferenceUsers = [];

        for ($i = 0; $i < 8; $i++) {
            do {
                $suffix = mt_rand(0, 9999);
            } while (in_array($suffix, $usedSuffixes));

            $usedSuffixes[] = $suffix;
            $phone = '1380591' . str_pad($suffix, 4, '0', STR_PAD_LEFT);

            $interferenceUsers[] = [
                'name' => $interferenceNames[$i],
                'phone' => $phone,
                'idcard' => $interferenceIdcards[$i]
            ];
        }

        $targetIndex = mt_rand(0, 7);
        $targetUser = $interferenceUsers[$targetIndex];
        $passcode = generatePasscode();

        $idcardDir = dirname(__DIR__) . '/filebac_level3/idcard/';
        if (!is_dir($idcardDir)) {
            mkdir($idcardDir, 0755, true);
        }

        $testPhoneMd5 = md5($testUser['phone']);
        $testImagePath = $idcardDir . $testPhoneMd5 . '.png';
        assertImageGenerated(
            generateIdcardImage($testUser['name'], $testUser['idcard'], null, $testImagePath),
            $testImagePath,
            $createdFiles,
            '第三关测试身份证图片生成失败'
        );

        $fileData = [];
        foreach ($interferenceUsers as $index => $user) {
            $phoneMd5 = md5($user['phone']);
            $imagePath = $idcardDir . $phoneMd5 . '.png';
            $result = $index === $targetIndex
                ? generateIdcardImage($user['name'], $user['idcard'], $passcode, $imagePath)
                : generateIdcardImage($user['name'], $user['idcard'], null, $imagePath);

            assertImageGenerated($result, $imagePath, $createdFiles, '第三关干扰身份证图片生成失败');

            $fileData[] = [
                'name' => $user['name'],
                'phone' => $user['phone'],
                'idcard' => $user['idcard']
            ];
        }

        $userData = [
            'name' => $testUser['name'],
            'phone' => $testUser['phone'],
            'idcard' => $testUser['idcard']
        ];

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO heasec_filebac_data
            (level, account, password, user_data, target_identifier, passcode, file_data)
            VALUES (3, '13805916688', '123456', ?, ?, ?, ?)");

        $stmt->execute([
            json_encode($userData, JSON_UNESCAPED_UNICODE),
            $targetUser['phone'],
            $passcode,
            json_encode($fileData, JSON_UNESCAPED_UNICODE)
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => '第三关数据初始化成功',
            'target_identifier' => $targetUser['phone']
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        cleanupCreatedFiles($createdFiles);

        if (isLevelDataExists($pdo, 3)) {
            return ['success' => true, 'message' => '第三关数据已存在'];
        }

        throw $e;
    } finally {
        releaseInitLock($pdo, 3);
    }
}

/**
 * 获取关卡数据
 *
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 关卡数据
 */
function getLevelData($level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_filebac_data WHERE level = ?");
    $stmt->execute([$level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 验证通关密码
 *
 * @param int $level 关卡编号
 * @param string $passcode 通关密码
 * @param PDO $pdo 数据库连接
 * @return bool 是否验证通过
 */
function verifyPasscode($level, $passcode, $pdo) {
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_filebac_data WHERE level = ?");
    $stmt->execute([$level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['passcode'] === $passcode) {
        return true;
    }

    return false;
}
