<?php
// faq.php
$extraHead = '<title>Frequently Asked Questions | Welzee</title>';
include 'includes/header.php';
?>

<div class="bg-secondary text-white py-16 border-b-4 border-primary">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tighter mb-4">Frequently Asked Questions</h1>
        <p class="text-lg text-gray-400">Everything you need to know about our products and services.</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="space-y-6">
        
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">What is the minimum order quantity (MOQ)?</h3>
            <p class="text-gray-600">For most standard apparel items, we require a minimum order quantity of 10 pieces per design to ensure cost-efficiency during the setup phase of our multi-head embroidery machines.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">How long does production take?</h3>
            <p class="text-gray-600">Standard production completes in 7-10 working days after artwork approval and payment. We also offer expedited express options (3-5 days) for urgent corporate events.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">Can I provide my own garments?</h3>
            <p class="text-gray-600">Yes! While we offer a premium catalog of wholesale blanks, we do accept customer-supplied garments (CMT) subject to a quick fabric inspection to ensure they are suitable for high-speed industrial embroidery or heat-pressing.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">Do you charge a setup or digitizing fee?</h3>
            <p class="text-gray-600">We do not charge artificial setup fees! However, converting your logo into a stitch-file (digitizing) usually incurs a small, one-time flat rate depending on complexity. Once digitized, we keep the file on record for all your future re-orders.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">What format should my logo be in?</h3>
            <p class="text-gray-600">For the best results, we recommend high-resolution vector files (.AI, .EPS, .SVG) or high-quality raster images (.PNG, .JPG) at 300 DPI.</p>
        </div>

    </div>
    
    <div class="mt-12 text-center bg-gray-50 p-8 rounded-2xl border border-gray-100">
        <h3 class="text-2xl font-bold text-secondary mb-2">Still have questions?</h3>
        <p class="text-gray-600 mb-6">Our support team is ready to help you with your custom project.</p>
        <a href="<?php echo $base_url; ?>contact" class="inline-block bg-primary text-white font-bold py-3 px-8 rounded-xl hover:bg-primary-dark transition shadow-md">Contact Us</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
