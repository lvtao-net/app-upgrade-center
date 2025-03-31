<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// 控制表单显示
$showForm = false;

// 删除版本
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    deleteVersion($id);
    header('Location: versions.php');
    exit();
}

// 编辑版本
if (isset($_GET['edit'])) {
    $editVersion = getVersionById(intval($_GET['edit']));
    $showForm = true; // 编辑时显示表单
}

// 添加版本
if (isset($_GET['add'])) {
    $showForm = true; // 添加时显示表单
}

// 提交表单处理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        editVersion($_POST['id'], $_POST['data']);
    } else {
        addVersion($_POST['data']);
    }
    header('Location: versions.php');
    exit();
}

$apps = getApps();
$versions = getAllVersions();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <title>版本管理</title>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center">
            <h1 onclick="window.location.href='versions.php';" style="cursor: pointer;">版本管理</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回后台首页</a>
        </div>

        <?php if (!$showForm): ?>
        <!-- 显示版本列表和“添加版本”按钮 -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <h2>现有版本</h2>
            <a href="versions.php?add=1" class="btn btn-primary">添加版本</a>
        </div>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>所属应用</th>
                    <th>APK版本</th>
                    <th>iOS版本</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($versions as $version): ?>
                    <?php $app = getAppById($version['app_id']); ?>
                    <tr>
                        <td><?= htmlspecialchars($version['id']) ?></td>
                        <td><?= htmlspecialchars($app['name']) ?></td>
                        <td><?= htmlspecialchars($version['version']) ?></td>
                        <td>
                            <?= htmlspecialchars($version['ios_version']) ?>
                        </td>
                        <td width='120'>
                            <a href="versions.php?edit=<?= $version['id'] ?>" class="btn btn-warning btn-sm">编辑</a>
                            <a href="versions.php?delete=<?= $version['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('确认删除此版本？');">删除</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <!-- 显示表单 -->
        <form method="post" class="mt-4">
            <?php if (isset($editVersion)): ?>
                <input type="hidden" name="id" value="<?= $editVersion['id'] ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label for="app_id" class="form-label">选择应用</label>
                <select name="data[app_id]" id="app_id" class="form-select" <?= isset($editVersion) ? 'disabled' : '' ?> required>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars($app['id']) ?>" <?= isset($editVersion) && $editVersion['app_id'] == $app['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($app['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($editVersion)): ?>
                    <input type="hidden" name="data[app_id]" value="<?= $editVersion['app_id'] ?>">
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="changelog" class="form-label">版本变更记录</label>
                <textarea name="data[changelog]" id="changelog" class="form-control"><?= $editVersion['changelog'] ?? '' ?></textarea>
            </div>
            <div class="mb-3">
                <label for="force" class="form-label">强制更新</label>
                <select name="data[force]" id="force" class="form-select" required>
                    <option value="0" <?= isset($editVersion) && $editVersion['force'] == 0 ? 'selected' : '' ?>>不强制</option>
                    <option value="1" <?= isset($editVersion) && $editVersion['force'] == 1 ? 'selected' : '' ?>>强制更新</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="version" class="form-label">APK 版本号</label>
                <input type="text" name="data[version]" id="version" class="form-control" value="<?= $editVersion['version'] ?? '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="apk_url" class="form-label">APK 下载链接</label>
                <input type="text" name="data[apk_url]" id="apk_url" class="form-control" value="<?= $editVersion['apk_url'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <label for="ios_version" class="form-label">iOS 版本号</label>
                <input type="text" name="data[ios_version]" id="ios_version" class="form-control" value="<?= $editVersion['ios_version'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <label for="ipa_url" class="form-label">IPA 下载链接</label>
                <input type="text" name="data[ipa_url]" id="ipa_url" class="form-control" value="<?= $editVersion['ipa_url'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary"><?= isset($editVersion) ? '更新版本' : '添加版本' ?></button>
                <a href="versions.php" class="btn btn-secondary">返回列表</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>