<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// 控制表单显示
$showForm = false;

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    deleteApp($id);
    header('Location: apps.php');
    exit();
}

if (isset($_GET['edit'])) {
    $editApp = getAppById(intval($_GET['edit']));
    $showForm = true; // 编辑时显示表单
}

if (isset($_GET['add'])) {
    $showForm = true; // 添加时显示表单
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理 Logo 上传
    if (!empty($_FILES['logo_upload']['tmp_name'])) {
        $logoExtension = pathinfo($_FILES['logo_upload']['name'], PATHINFO_EXTENSION);
        $logoFilename = time() . '_logo.' . $logoExtension;
        $logoDestination = '../uploads/' . $logoFilename;
        move_uploaded_file($_FILES['logo_upload']['tmp_name'], $logoDestination);
        $logo = '/uploads/' . $logoFilename;
        $data['logo'] = $logo;
    }

    // 处理截图上传
    $screenshots = [];
    if (!empty($_FILES['screenshots_upload']['name'][0])) {
        foreach ($_FILES['screenshots_upload']['tmp_name'] as $index => $tmpName) {
            if (!empty($tmpName)) {
                $screenshotExtension = pathinfo($_FILES['screenshots_upload']['name'][$index], PATHINFO_EXTENSION);
                $screenshotFilename = time() . '_' . $index . '.' . $screenshotExtension;
                $screenshotDestination = '../uploads/' . $screenshotFilename;
                move_uploaded_file($tmpName, $screenshotDestination);
                $screenshots[] = '/uploads/' . $screenshotFilename;
            }
        }
    }
    // 合并用户填写的截图 URL 和上传的截图路径
    if (!empty($_POST['data']['screenshots'])) {
        $screenshots = array_merge($screenshots, explode(',', $_POST['data']['screenshots']));
    }
    $_POST['data']['screenshots'] = implode(',', $screenshots);

    // 编辑或新增逻辑
    if (isset($_POST['id'])) {
        editApp($_POST['id'], $_POST['data']);
    } else {
        addApp($_POST['data']);
    }
    header('Location: apps.php');
    exit();
}

$apps = getApps();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <title>应用管理</title>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center">
             <h1 onclick="window.location.href='apps.php';" style="cursor: pointer;">应用管理</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回后台首页</a>
        </div>

        <?php if (!$showForm): ?>
        <!-- 显示列表和“添加应用”按钮 -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <h2>现有应用</h2>
            <a href="apps.php?add=1" class="btn btn-primary">添加应用</a>
        </div>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>包名</th>
                    <th>状态</th>
                    <th>分享 URL</th>
                    <th>二维码</th>
                    <th>隐私政策</th>
                    <th>用户协议</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apps as $app): ?>
                <?php $url = getScheme() . $_SERVER['HTTP_HOST'] . '/app/' . $app['id'] ;?>
                    <tr>
                        <td><?= htmlspecialchars($app['id']) ?></td>
                        <td><?= htmlspecialchars($app['name']) ?></td>
                        <td><?= htmlspecialchars($app['package_name']) ?></td>
                        <td><?= $app['status'] ? '启用' : '禁用' ?></td>
                        <td><?= $url?></td>
                        <td><img src='https://tool.lvtao.net/qr?t=<?=$url?>' width="48"/></td>
                        <td><a href="/app/docment.php?t=p&id=<?= $app['id'] ?>" target="_blank">查看</a></td>
                         <td><a href="/app/docment.php?t=s&id=<?= $app['id'] ?>" target="_blank">查看</a></td>
                        <td>
                            <a href="apps.php?edit=<?= $app['id'] ?>" class="btn btn-warning btn-sm">编辑</a>
                            <a href="apps.php?delete=<?= $app['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('确认删除此应用？');">删除</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <!-- 显示表单 -->
        <form method="post" enctype="multipart/form-data" class="mt-4">
            <?php if (isset($editApp)): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($editApp['id']) ?>">
            <?php endif; ?>
            <!-- 表单内容 -->
            <div class="mb-3">
                <label for="name" class="form-label">应用名称</label>
                <input type="text" name="data[name]" id="name" class="form-control" value="<?= $editApp['name'] ?? '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="package_name" class="form-label">包名</label>
                <input type="text" name="data[package_name]" id="package_name" class="form-control" value="<?= $editApp['package_name'] ?? '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="company" class="form-label">公司全称</label>
                <input type="text" name="data[company]" id="company" class="form-control" value="<?= $editApp['company'] ?? '' ?>"/>
            </div>
            <div class="mb-3">
                <label for="com" class="form-label">公司简称</label>
                <input type="text" name="data[com]" id="com" class="form-control" value="<?= $editApp['com'] ?? '' ?>"/>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">联系邮箱</label>
                <input type="text" name="data[email]" id="com" class="form-control" value="<?= $editApp['email'] ?? '' ?>"/>
            </div>
            <div class="mb-3">
                <label for="logo" class="form-label">Logo 图片</label>
                <input type="file" name="logo_upload" id="logo_upload" class="form-control">
                <small>或手动填写 Logo URL</small>
                <input type="text" name="data[logo]" id="logo" class="form-control mt-2" value="<?= $editApp['logo'] ?? '' ?>">
                <?php if (!empty($editApp['logo'])): ?>
                    <img src="<?= htmlspecialchars($editApp['logo']) ?>" alt="Logo" style="max-width: 100px; margin-top: 10px;">
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">描述</label>
                <textarea name="data[description]" id="description" class="form-control"><?= $editApp['description'] ?? '' ?></textarea>
            </div>
            <div class="mb-3">
                <label for="summary" class="form-label">服务简介</label>
                <textarea name="data[summary]" id="summary" class="form-control"><?= $editApp['summary'] ?? '' ?></textarea>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">分类</label>
                <select name="data[category]" id="category" class="form-select">
                    <option value="工具" <?= isset($editApp) && $editApp['category'] == '工具' ? 'selected' : '' ?>>工具</option>
                    <option value="社交" <?= isset($editApp) && $editApp['category'] == '社交' ? 'selected' : '' ?>>社交</option>
                    <option value="游戏" <?= isset($editApp) && $editApp['category'] == '游戏' ? 'selected' : '' ?>>游戏</option>
                    <option value="教育" <?= isset($editApp) && $editApp['category'] == '教育' ? 'selected' : '' ?>>教育</option>
                    <option value="健康与健身" <?= isset($editApp) && $editApp['category'] == '健康与健身' ? 'selected' : '' ?>>健康与健身</option>
                    <option value="音乐" <?= isset($editApp) && $editApp['category'] == '音乐' ? 'selected' : '' ?>>音乐</option>
                    <option value="购物" <?= isset($editApp) && $editApp['category'] == '购物' ? 'selected' : '' ?>>购物</option>
                    <option value="旅行" <?= isset($editApp) && $editApp['category'] == '旅行' ? 'selected' : '' ?>>旅行</option>
                    <option value="美食与饮料" <?= isset($editApp) && $editApp['category'] == '美食与饮料' ? 'selected' : '' ?>>美食与饮料</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="age" class="form-label">适合年龄</label>
                <select name="data[age]" id="age" class="form-select">
                    <option value="4+" <?= isset($editApp) && $editApp['age'] == '4+' ? 'selected' : '' ?>>4+</option>
                    <option value="9+" <?= isset($editApp) && $editApp['age'] == '9+' ? 'selected' : '' ?>>9+</option>
                    <option value="12+" <?= isset($editApp) && $editApp['age'] == '12+' ? 'selected' : '' ?>>12+</option>
                    <option value="17+" <?= isset($editApp) && $editApp['age'] == '17+' ? 'selected' : '' ?>>17+</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="screenshots" class="form-label">截图上传 (可上传多张)</label>
                <input type="file" name="screenshots_upload[]" id="screenshots_upload" class="form-control" multiple>
                <small>或手动填写截图 URL，用英文逗号分隔</small>
                <textarea name="data[screenshots]" id="screenshots" class="form-control mt-2"><?= $editApp['screenshots'] ?? '' ?></textarea>
                <?php if (!empty($editApp['screenshots'])): ?>
                    <div style="margin-top: 10px;">
                        <?php foreach (explode(',', $editApp['screenshots']) as $screenshot): ?>
                            <img src="<?= htmlspecialchars($screenshot) ?>" alt="截图" style="max-width: 100px; margin: 5px;">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="s_google" class="form-label">Google商店</label>
                <input type="text" name="data[s_google]" id="s_google" class="form-control" value="<?= $editApp['s_google'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <label for="s_huawei" class="form-label">华为商店</label>
                <input type="text" name="data[s_huawei]" id="s_huawei" class="form-control" value="<?= $editApp['s_huawei'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <label for="s_honor" class="form-label">荣耀商店</label>
                <input type="text" name="data[s_honor]" id="s_honor" class="form-control" value="<?= $editApp['s_honor'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <label for="s_mi" class="form-label">小米商店</label>
                <input type="text" name="data[s_mi]" id="s_mi" class="form-control" value="<?= $editApp['s_mi'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <label for="s_qq" class="form-label">应用宝商店</label>
                <input type="text" name="data[s_qq]" id="s_qq" class="form-control" value="<?= $editApp['s_qq'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <label for="s_vivo" class="form-label">vivo商店</label>
                <input type="text" name="data[s_vivo]" id="s_vivo" class="form-control" value="<?= $editApp['s_vivo'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <label for="s_oppo" class="form-label">oppo商店</label>
                <input type="text" name="data[s_oppo]" id="s_oppo" class="form-control" value="<?= $editApp['s_oppo'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <label for="s_apple" class="form-label">iOS 商店链接</label>
                <input type="text" name="data[s_apple]" id="s_apple" class="form-control" value="<?= $editApp['s_apple'] ?? '' ?>">
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">状态</label>
                <select name="data[status]" id="status" class="form-select">
                    <option value="1" <?= isset($editApp) && $editApp['status'] == 1 ? 'selected' : '' ?>>启用</option>
                    <option value="0" <?= isset($editApp) && $editApp['status'] == 0 ? 'selected' : '' ?>>禁用</option>
                </select>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary"><?= isset($editApp) ? '更新应用' : '添加应用' ?></button>
                <a href="apps.php" class="btn btn-secondary">返回列表</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>