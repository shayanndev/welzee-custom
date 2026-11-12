<?php
// 410.php - Custom "410 Gone" response for permanently discontinued legacy WordPress / leather jacket URLs
http_response_code(410);
header('X-Robots-Tag: noindex, nofollow');
$pageTitle = 'Page Discontinued | Welzee';
$pageDescription = 'This product or page has been permanently discontinued on Welzee.';
$extraHead = '<meta name="robots" content="noindex, nofollow">';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl">
        <i class="fa-solid fa-box-archive"></i>
    </div>
    <p class="text-sm font-bold tracking-widest text-primary uppercase mb-2">HTTP 410 • Discontinued</p>
    <h1 class="text-4xl md:text-5xl font-extrabold text-secondary tracking-tight mb-4">Product or Page Discontinued</h1>
    <p class="text-lg text-gray-600 mb-8 max-w-xl mx-auto">
        This legacy leather-jacket store page has been permanently removed as part of Welzee's transition to custom corporate embroidery, garment printing, and personalized apparel.
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="<?php echo e($base_url); ?>" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
            Visit Homepage
        </a>
        <a href="<?php echo e($base_url); ?>custom_order" class="bg-white border border-gray-200 text-secondary font-bold py-3 px-8 rounded-xl hover:bg-gray-50 transition shadow-sm">
            Design Custom Apparel
        </a>
        <a href="<?php echo e($base_url); ?>services" class="bg-secondary hover:bg-black text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
            View Our Services
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

