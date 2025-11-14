<?php if (!isset($pageTitle)) { $pageTitle = 'เว็บไซต์โครงการ'; } 
// Ensure session available to show admin login state
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$adminLogged = !empty($_SESSION['admin_logged']);
$adminRole = $_SESSION['admin_role'] ?? null;
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
            <div class="flex items-center justify-between py-4 flex-wrap">
                <a href="<?php echo BASE_URL; ?>" class="flex items-center gap-3 flex-shrink-0 max-w-full">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="logo" class="h-12 w-12 object-contain rounded-md bg-gray-100 p-1">
                    <div>
                        <div class="font-semibold text-lg">งานบริการ เภสัชกรรมและคุ้มครองผู้บริโภค</div>
                        <div class="text-sm text-gray-500">ศูนย์ดาวน์โหลด ข้อมูลข่าวสาร ศูนย์บริการประจำ CUP สันโค้งและลูกข่าย อำเภอสันกำแพง จังหวัดเชียงใหม่</div>
                    </div>
                </a>

                <div class="ml-auto flex items-center gap-4">
                        <!-- Desktop nav -->
                        <nav class="hidden md:flex items-center space-x-4 text-sm" aria-label="Primary">
                            <a href="<?php echo BASE_URL; ?>" class="text-gray-700 hover:text-blue-600">หน้าแรก</a>
                            <a href="<?php echo BASE_URL; ?>news.php" class="text-gray-700 hover:text-blue-600">ข่าวประชาสัมพันธ์</a>
                            <a href="<?php echo BASE_URL; ?>downloads.php" class="text-gray-700 hover:text-blue-600">ดาวน์โหลด</a>
                            <a href="<?php echo BASE_URL; ?>contact.php" class="text-gray-700 hover:text-blue-600">ติดต่อ</a>
                            <?php if ($adminLogged): ?>
                                <!-- Compact admin menu: single button that opens a small popup -->
                                <div class="relative">
                                    <button id="admin-menu-btn" class="flex items-center gap-2 text-sm text-gray-700 hover:text-blue-600 focus:outline-none" aria-expanded="false" aria-haspopup="true" type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/></svg>
                                        <span class="sr-only">เมนูผู้ดูแล</span>
                                    </button>
                                    <div id="admin-menu-popup" role="menu" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 hidden transform opacity-0 scale-95 transition ease-out duration-150">
                                        <div class="py-1" role="none">
                                            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">หน้าจัดการ</a>
                                            <?php if ($adminRole === 'admin'): ?>
                                                <a href="<?php echo BASE_URL; ?>admin/register.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">จัดการแอดมิน</a>
                                            <?php endif; ?>
                                            <a href="<?php echo BASE_URL; ?>admin/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" role="menuitem">ออกจากระบบ</a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>admin/login.php" class="text-gray-700 hover:text-blue-600">เข้าสู่ระบบเจ้าหน้าที่</a>
                            <?php endif; ?>
                        </nav>

                        <!-- Search (desktop) -->
                        <form action="<?php echo BASE_URL; ?>search.php" method="get" class="hidden md:block">
                            <div class="relative">
                                <input name="q" type="search" placeholder="ค้นหา ข่าว หรือ เอกสาร..." class="border rounded-lg px-3 py-2 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-300" value="<?php echo isset($_GET['q']) ? e($_GET['q']) : ''; ?>">
                                <button type="submit" class="absolute right-1 top-1 text-sm text-white bg-blue-600 px-3 py-1 rounded">ค้นหา</button>
                            </div>
                        </form>
                    </div>

                    <!-- Mobile menu button -->
                    <button id="nav-toggle" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:bg-gray-100" aria-expanded="false" aria-controls="nav-menu">
                        <svg id="nav-open-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg id="nav-close-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile nav menu -->
        <div id="nav-menu" class="md:hidden bg-white border-t hidden">
            <div class="px-4 py-3 space-y-2">
                <a href="<?php echo BASE_URL; ?>" class="block text-gray-700 py-2">หน้าแรก</a>
                <a href="<?php echo BASE_URL; ?>news.php" class="block text-gray-700 py-2">ข่าวประชาสัมพันธ์</a>
                <a href="<?php echo BASE_URL; ?>downloads.php" class="block text-gray-700 py-2">ดาวน์โหลด</a>
                <a href="<?php echo BASE_URL; ?>contact.php" class="block text-gray-700 py-2">ติดต่อ</a>
                <?php if ($adminLogged): ?>
                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="block text-gray-700 py-2">หน้าจัดการ</a>
                    <?php if ($adminRole === 'admin'): ?>
                        <a href="<?php echo BASE_URL; ?>admin/register.php" class="block text-gray-700 py-2">จัดการแอดมิน</a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>admin/logout.php" class="block text-red-600 py-2">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>admin/login.php" class="block text-gray-700 py-2">เข้าสู่ระบบเจ้าหน้าที่</a>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>search.php" method="get" class="mt-2">
                    <div class="relative">
                        <input name="q" type="search" placeholder="ค้นหา ข่าว หรือ เอกสาร..." class="border rounded-lg px-3 py-2 text-sm w-full" value="<?php echo isset($_GET['q']) ? e($_GET['q']) : ''; ?>">
                        <button type="submit" class="absolute right-2 top-2 text-sm text-white bg-blue-600 px-3 py-1 rounded">ค้นหา</button>
                    </div>
                </form>
            </div>
        </div>
    </header>
        <script>
        // Admin popup toggle and outside-click auto-close
        (function(){
            var btn = document.getElementById('admin-menu-btn');
            var popup = document.getElementById('admin-menu-popup');
            if (!btn || !popup) return;

            function show() {
                popup.classList.remove('hidden');
                // force reflow to allow transition
                void popup.offsetWidth;
                popup.classList.remove('opacity-0','scale-95');
                popup.classList.add('opacity-100','scale-100');
                btn.setAttribute('aria-expanded','true');
            }
            function hide() {
                popup.classList.remove('opacity-100','scale-100');
                popup.classList.add('opacity-0','scale-95');
                btn.setAttribute('aria-expanded','false');
                // after transition, hide to remove from flow
                setTimeout(function(){ if (popup.classList.contains('opacity-0')) popup.classList.add('hidden'); }, 200);
            }

            btn.addEventListener('click', function(e){
                e.stopPropagation();
                if (popup.classList.contains('hidden')) show(); else hide();
            });
            // prevent clicks inside popup from closing
            popup.addEventListener('click', function(e){ e.stopPropagation(); });
            // close when clicking outside
            document.addEventListener('click', function(){ if (!popup.classList.contains('hidden')) hide(); });
            // close on escape
            document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { if (!popup.classList.contains('hidden')) hide(); } });
        })();
        </script>
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

<?php // page content starts here ?>
