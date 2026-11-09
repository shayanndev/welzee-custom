<?php
// 404.php - custom "page not found" (served with a real 404 status by .htaccess and post.php)
http_response_code(404);
header('X-Robots-Tag: noindex');
$extraHead = '<title>Page Not Found | Welzee</title><meta name="robots" content="noindex">';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
    <p class="text-sm font-bold tracking-widest text-primary uppercase mb-4">Error 404</p>
    <h1 class="text-4xl md:text-5xl font-extrabold text-secondary tracking-tight mb-4">Page not found</h1>
    <p class="text-lg text-gray-500 mb-10">The page you are looking for doesn't exist or has been moved.</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="<?php echo e($base_url); ?>" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-8 rounded-xl transition">Back to Home</a>
        <a href="<?php echo e($base_url); ?>custom_order" class="bg-white border border-gray-200 text-secondary font-bold py-3 px-8 rounded-xl hover:bg-gray-50 transition">Design Your Own</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

