<?php
// blog.php
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Apparel & Embroidery Insights Blog | Welzee';
$pageDescription = 'Read expert textile finishing articles, embroidery digitizing tips, DTG printing guides, and corporate uniform branding advice from the Welzee team.';

// Fetch all posts ordered by date
$stmt = $conn->prepare("SELECT slug, title, category, excerpt, image_url, created_at FROM blog_posts ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-gray-50 py-16 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-secondary tracking-tight mb-4">News & Insights</h1>
        <p class="text-lg text-gray-500 max-w-2xl mx-auto">Discover the latest trends in corporate workwear, sustainable embroidery, and modern textile printing technologies.</p>
    </div>
</div>

<!-- Blog Grid -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if(empty($posts)): ?>
            <div class="col-span-full text-center py-10 text-gray-500">
                No articles published yet. Check back soon!
            </div>
        <?php endif; ?>

        <?php foreach($posts as $post): 
            $imgSrc = $post['image_url'];
            if (!empty($imgSrc) && !preg_match('#^https?://#i', $imgSrc)) {
                $imgSrc = $base_url . ltrim($imgSrc, '/');
            }
        ?>
        <article class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition duration-300 group flex flex-col">
            <div class="aspect-w-16 aspect-h-10 bg-gray-200 overflow-hidden relative">
                <a href="<?php echo $base_url; ?>blog/<?php echo urlencode($post['slug']); ?>" class="absolute inset-0 z-10 w-full h-full"></a>
                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
            </div>
            <div class="p-6 flex-grow flex flex-col">
                <div class="flex items-center gap-4 text-xs font-semibold text-primary uppercase tracking-wider mb-3">
                    <span><?php echo htmlspecialchars($post['category']); ?></span>
                    <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                    <span class="text-gray-400"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                </div>
                <h2 class="text-xl font-bold text-secondary mb-3 group-hover:text-primary transition leading-tight relative">
                    <a href="<?php echo $base_url; ?>blog/<?php echo urlencode($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                </h2>
                <p class="text-gray-600 text-sm mb-6 flex-grow">
                    <?php echo htmlspecialchars($post['excerpt']); ?>
                </p>
                <a href="<?php echo $base_url; ?>blog/<?php echo urlencode($post['slug']); ?>" class="inline-flex items-center gap-1 text-sm font-bold text-secondary group-hover:text-primary transition">
                    Read Article <i class="fa-solid fa-arrow-right-long ml-1"></i>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>
