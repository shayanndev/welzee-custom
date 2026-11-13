<?php
// post.php - Dynamic handler for /blog/:slug
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/db.php';

$base_url = app_base_url();

$slug = (isset($_GET['slug']) && is_string($_GET['slug'])) ? trim($_GET['slug']) : '';
if (strlen($slug) > 255) {
    $slug = '';
}
if ($slug === '') {
    require __DIR__ . '/404.php';
    exit;
}

// Fetch specific post
$stmt = $conn->prepare("SELECT title, category, excerpt, content, image_url, created_at FROM blog_posts WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    require __DIR__ . '/404.php';   // real HTTP 404 instead of a soft redirect
    exit;
}

$post = $result->fetch_assoc();

// SEO Title & Description mapping
$pageTitle = ($slug === 'dtg-vs-screen-print')
    ? 'DTG vs Screen Printing for Custom T-Shirts | Welzee'
    : (htmlspecialchars($post['title']) . ' | Welzee Blog');

$pageDescription = ($slug === 'dtg-vs-screen-print')
    ? 'Compare DTG and screen printing for custom T-shirts: artwork, fabric, order quantities, print feel, durability, cost factors, and how to choose.'
    : htmlspecialchars(!empty($post['excerpt']) ? $post['excerpt'] : mb_substr(strip_tags($post['content']), 0, 155));

$canonicalUrl = 'https://welzee.com/blog/' . rawurlencode($slug);
$pageOgType = 'article';

// Resolve image URL
$coverImage = !empty($post['image_url']) ? $post['image_url'] : 'assets/images/og-image.jpg';
if (!preg_match('#^https?://#i', $coverImage)) {
    $coverImage = $base_url . ltrim($coverImage, '/');
}
$pageOgImage = $coverImage;

// Editorial Alt Text
$imageAlt = ($slug === 'dtg-vs-screen-print')
    ? 'DTG printer and screen-printing press producing different designs on cotton T-shirts'
    : htmlspecialchars($post['title']);

// Published and Modified Dates (consistent & truthful; never dynamic per-request)
$datePublished = date('c', strtotime($post['created_at']));
$dateModified = !empty($post['updated_at'])
    ? date('c', strtotime($post['updated_at']))
    : (($slug === 'dtg-vs-screen-print') ? '2026-11-13T00:00:00+05:00' : $datePublished);

$pageJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post['title'],
    'description' => $pageDescription,
    'image' => $coverImage,
    'datePublished' => $datePublished,
    'dateModified' => $dateModified,
    'author' => [
        '@type' => 'Organization',
        'name' => 'Welzee',
        'url' => 'https://welzee.com/'
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Welzee',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => 'https://welzee.com/assets/images/favicon.png'
        ]
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $canonicalUrl
    ]
];

// Content preparation: root-relative internal links and mobile-responsive table container
$articleBody = $post['content'];

// Map root-relative internal links to active environment base_url
if ($base_url !== '/' && $base_url !== '') {
    $articleBody = str_replace(
        ['href="/services"', 'href="/custom_order"', 'href="/about"', 'href="/contact"'],
        ['href="' . $base_url . 'services"', 'href="' . $base_url . 'custom_order"', 'href="' . $base_url . 'about"', 'href="' . $base_url . 'contact"'],
        $articleBody
    );
}

// Wrap tables in an overflow container for mobile responsiveness if not already wrapped
if (strpos($articleBody, '<table') !== false && strpos($articleBody, 'table-responsive-container') === false) {
    $articleBody = preg_replace(
        '/(<table\b[^>]*>.*?<\/table>)/is',
        '<div class="table-responsive-container overflow-x-auto my-8 border border-gray-200 rounded-2xl shadow-sm bg-white"><div class="inline-block min-w-full align-middle">$1</div></div>',
        $articleBody
    );
}

include __DIR__ . '/includes/header.php';
?>

<!-- Article Header -->
<div class="bg-gray-50 py-16 border-b border-gray-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div
            class="flex items-center justify-center gap-4 text-sm font-semibold text-primary uppercase tracking-wider mb-6">
            <span><?php echo htmlspecialchars($post['category']); ?></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
            <span class="text-gray-500"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-secondary tracking-tight mb-8 leading-tight">
            <?php echo htmlspecialchars($post['title']); ?>
        </h1>
        <div class="aspect-w-16 aspect-h-9 rounded-2xl overflow-hidden shadow-xl bg-gray-100">
            <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($imageAlt); ?>"
                class="w-full h-full object-cover">
        </div>
    </div>
</div>

<!-- Article Content -->
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="prose prose-lg prose-brand prose-content max-w-none text-gray-700">
        <?php
        // Trusted editorial HTML from database
        echo $articleBody;
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