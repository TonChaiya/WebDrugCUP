<?php if (!isset($pageTitle)) { $pageTitle = 'เว็บไซต์โครงการ'; } 
// Ensure session available to show admin login state
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$adminLogged = !empty($_SESSION['admin_logged']);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- Tailwind via CDN for quick start -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css" />
</head>
<body class="bg-gray-50 text-gray-800">
    <header class="bg-white shadow">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <a href="<?php echo BASE_URL; ?>" class="flex items-center gap-3">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="logo" class="h-10 w-10 object-contain">
                    <div>
                        <div class="font-semibold">โครงการ / หน่วยงาน</div>
                        <div class="text-sm text-gray-500">ประชาสัมพันธ์ และศูนย์ดาวน์โหลด</div>
                    </div>
                </a>
                <nav class="space-x-4 text-sm">
                    <a href="<?php echo BASE_URL; ?>" class="text-gray-700 hover:text-blue-600">หน้าแรก</a>
                    <a href="<?php echo BASE_URL; ?>news.php" class="text-gray-700 hover:text-blue-600">ข่าวประชาสัมพันธ์</a>
                    <a href="<?php echo BASE_URL; ?>downloads.php" class="text-gray-700 hover:text-blue-600">ดาวน์โหลด</a>
                    <a href="<?php echo BASE_URL; ?>contact.php" class="text-gray-700 hover:text-blue-600">ติดต่อ</a>
                    <?php if ($adminLogged): ?>
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="text-gray-700 hover:text-blue-600">หน้าจัดการ</a>
                        <a href="<?php echo BASE_URL; ?>admin/logout.php" class="text-red-600 hover:text-red-800">ออกจากระบบ</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>admin/login.php" class="text-gray-700 hover:text-blue-600">เข้าสู่ระบบเจ้าหน้าที่</a>
                    <?php endif; ?>
                </nav>
                <!-- search form -->
                <form action="<?php echo BASE_URL; ?>search.php" method="get" class="mt-2">
                    <div class="relative">
                        <input name="q" type="search" placeholder="ค้นหา ข่าว หรือ เอกสาร..." class="border rounded px-2 py-1 text-sm w-64" value="<?php echo isset($_GET['q']) ? e($_GET['q']) : ''; ?>">
                        <button type="submit" class="absolute right-0 top-0 mt-1 mr-1 text-sm text-blue-600">ค้นหา</button>
                    </div>
                </form>
            </div>
        </div>
    </header>
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

<?php // page content starts here ?>
