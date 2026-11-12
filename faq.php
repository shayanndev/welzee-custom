<?php
// faq.php
$pageTitle = 'Frequently Asked Questions | Custom Apparel | Welzee';
$pageDescription = 'Find answers about Welzee\'s minimum order quantities, digitizing setup, manufacturing in Pakistan, shipping to the US, turnaround times, and payment methods.';
include __DIR__ . '/includes/header.php';
?>

<div class="bg-secondary text-white py-16 border-b-4 border-primary">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tighter mb-4">Frequently Asked Questions</h1>
        <p class="text-lg text-gray-400">Everything you need to know about our products, customization, and fulfillment.</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="space-y-6">
        
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">Where are Welzee orders manufactured and shipped from?</h3>
            <p class="text-gray-600">All Welzee products are manufactured, customized, and dispatched directly from our production facility in Karachi, Pakistan. We serve clients nationwide across Pakistan and ship confirmed international orders to the United States and other destinations via tracked express couriers.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">How does shipping work for customers in the United States?</h3>
            <p class="text-gray-600">For US clients, customized orders are crafted at our facility in Pakistan and dispatched via tracked international air couriers. Transit times and shipping costs vary depending on order volume and the destination address, and are confirmed with your customized quote. Applicable import duties, customs tariffs, clearance fees, or taxes assessed by destination customs authorities are the responsibility of the recipient.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">What is the minimum order quantity (MOQ)?</h3>
            <p class="text-gray-600">For custom embroidery, our standard minimum order quantity is 10 pieces per design to cover machine setup and digitizing overhead. For direct-to-garment (DTG) printing, we accept smaller flexible runs. Contact us for bulk tiered discounts.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">How long does production take?</h3>
            <p class="text-gray-600">Standard production takes 5 to 7 business days following digital proof approval and quotation confirmation. Expedited handling is available upon request for urgent events.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">Can I provide my own garments?</h3>
            <p class="text-gray-600">Yes! While we offer a premium catalog of wholesale blanks, we do accept customer-supplied garments (CMT) subject to a quick fabric inspection to ensure they are suitable for high-speed industrial embroidery or heat-pressing.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">Do you charge a setup or digitizing fee?</h3>
            <p class="text-gray-600">Converting your logo into a machine-readable stitch file (digitizing) incurs a small, one-time flat fee depending on complexity. Once digitized, your embroidery file is archived permanently for future reorders with zero repeat fees.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
            <h3 class="text-xl font-bold text-secondary mb-2">What artwork format should I provide?</h3>
            <p class="text-gray-600">Vector artwork (.AI, .EPS, .SVG, .PDF) is preferred for the sharpest reproduction. High-resolution raster files (.PNG, .JPG) at 300 DPI are also accepted.</p>
        </div>

    </div>
    
    <div class="mt-12 text-center bg-gray-50 p-8 rounded-2xl border border-gray-100">
        <h3 class="text-2xl font-bold text-secondary mb-2">Still have questions?</h3>
        <p class="text-gray-600 mb-6">Our support team is ready to help you with your custom project.</p>
        <a href="<?php echo $base_url; ?>contact" class="inline-block bg-primary text-white font-bold py-3 px-8 rounded-xl hover:bg-primary-dark transition shadow-md">Contact Us</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
