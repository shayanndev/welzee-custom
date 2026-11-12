<?php
// post.php - Dynamic handler for /blog/:slug
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/db.php';

$slug = (isset($_GET['slug']) && is_string($_GET['slug'])) ? trim($_GET['slug']) : '';
if (strlen($slug) > 255) { $slug = ''; }
if($slug === '') {
    require __DIR__ . '/404.php';
    exit;
}

// Fetch specific post
$stmt = $conn->prepare("SELECT title, category, content, image_url, created_at FROM blog_posts WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    require __DIR__ . '/404.php';   // real HTTP 404 instead of a soft redirect
    exit;
}

$post = $result->fetch_assoc();

$pageTitle = htmlspecialchars($post['title']) . ' | Welzee Blog';
$pageDescription = htmlspecialchars($post['excerpt'] ?? mb_substr(strip_tags($post['content']), 0, 155));
$pageOgType = 'article';
$pageOgImage = !empty($post['image_url']) ? $post['image_url'] : ($base_url . 'assets/images/og-image.jpg');

$pageJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post['title'],
    'description' => $pageDescription,
    'datePublished' => date('c', strtotime($post['created_at'])),
    'dateModified' => date('c', strtotime($post['created_at'])),
    'author' => [
        '@type' => 'Organization',
        'name' => 'Welzee'
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Welzee',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $base_url . 'assets/images/favicon.png'
        ]
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $base_url . 'blog/' . rawurlencode($slug)
    ]
];

include __DIR__ . '/includes/header.php';
?>

<!-- Article Header -->
<div class="bg-gray-50 py-16 border-b border-gray-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="flex items-center justify-center gap-4 text-sm font-semibold text-primary uppercase tracking-wider mb-6">
            <span><?php echo htmlspecialchars($post['category']); ?></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
            <span class="text-gray-500"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-secondary tracking-tight mb-8 leading-tight">
            <?php echo htmlspecialchars($post['title']); ?>
        </h1>
        <div class="aspect-w-16 aspect-h-9 rounded-2xl overflow-hidden shadow-xl">
            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Article Cover" class="w-full h-full object-cover">
        </div>
    </div>
</div>

<!-- Article Content -->
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="prose prose-lg prose-brand max-w-none text-gray-700">
        <?php 
        // Outputting content assuming source is trusted HTML (Since you will control DB)
        echo $post['content']; 
        ?>
    </div>
    
    <div class="mt-16 pt-8 border-t border-gray-100 flex justify-between items-center">
        <a href="<?php echo $base_url; ?>blog" 
            class="inline-flex items-center gap-2 text-primary font-bold hover:text-primary-dark transition text-lg bg-primary/10 px-6 py-3 rounded-full">
            <i class="fa-solid fa-arrow-left"></i> Back to Blog
        </a>
    </div>
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>
