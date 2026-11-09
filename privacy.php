<?php
// privacy.php
$extraHead = '<title>Privacy Policy | Welzee</title>';
include __DIR__ . '/includes/header.php';
?>

<div class="bg-secondary text-white py-16 border-b-4 border-primary">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tighter mb-4">Privacy Policy</h1>
        <p class="text-lg text-gray-400">How we protect and manage your data.</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 prose prose-lg prose-indigo max-w-none">
    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        
        <p class="text-gray-500 text-xs uppercase font-bold tracking-widest mb-8">Last Updated: <?php echo date('F Y'); ?></p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">1. Information We Collect</h2>
        <p class="text-gray-600 text-sm mb-6">We collect personal information necessary to fulfill your custom apparel orders. This includes your name, shipping/billing address, email, phone number, and uploaded artwork files (logos/designs). We also gather browsing data via standard analytics tools to improve website performance.</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">2. How We Use Your Data</h2>
        <p class="text-gray-600 text-sm mb-6">Your information is used strictly to process orders, communicate digital proofs, arrange delivery, and handle customer service requests. We may occasionally send promotional emails, which you can opt out of at any time.</p>
        
        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">3. Data Sharing</h2>
        <p class="text-gray-600 text-sm mb-6">We never sell your personal data. We only share necessary details with trusted third-party partners involved in fulfilling your order, such as courier services (for delivery) and payment gateways (like Stripe or PayPal for secure processing).</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">4. Artwork & Intellectual Property Rights</h2>
        <p class="text-gray-600 text-sm mb-6">The logos and designs you upload remain your property. We only store them securely on our local servers for the exact purpose of manufacturing your goods and facilitating easy re-orders in the future. We will never reproduce your branded apparel for other clients without your written consent.</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">5. Data Security</h2>
        <p class="text-gray-600 text-sm mb-0">Our website uses industry-standard SSL encryption to protect your data during transmission. We employ strict server-level security protocols to prevent unauthorized access to our customer database.</p>
        
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
