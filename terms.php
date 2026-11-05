<?php
// terms.php
$extraHead = '<title>Terms & Conditions | Welzee</title>';
include 'includes/header.php';
?>

<div class="bg-secondary text-white py-16 border-b-4 border-primary">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tighter mb-4">Terms & Conditions</h1>
        <p class="text-lg text-gray-400">Please read these terms carefully before placing an order.</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 prose prose-lg prose-indigo max-w-none">
    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        
        <p class="text-gray-500 text-xs uppercase font-bold tracking-widest mb-8">Effective Date: <?php echo date('F Y'); ?></p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">1. Acceptance of Terms</h2>
        <p class="text-gray-600 text-sm mb-6">By accessing Welzee.com and placing a custom apparel order, you agree to be bound by these Terms & Conditions. If you disagree with any part of the terms, then you may not access the Service.</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">2. Order Approval & Production</h2>
        <p class="text-gray-600 text-sm mb-6">All custom orders require customer approval of a digital proof before production begins. Once you approve the proof, no further changes can be made to the size, color, or artwork placement. Production timelines (7-10 days) commence on the date of final proof approval and payment clearance.</p>
        
        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">3. Pricing and Payment</h2>
        <p class="text-gray-600 text-sm mb-6">Prices shown on our website or Customizer are estimates based on standard parameters. Final quotes will be provided via email by our sales team. We require a 50% upfront deposit on bulk corporate orders, with the remaining balance due prior to dispatch. We accept all major credit cards and secure bank transfers.</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">4. Copyright and Trademarks</h2>
        <p class="text-gray-600 text-sm mb-6">By uploading a logo or design for printing/embroidery, you warrant that you hold the legal right or license to reproduce that intellectual property. Welzee reserves the right to refuse the production of any artwork that infringes on known copyrights, or that contains hate speech, obscenity, or illegal content.</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">5. Liability Limitations</h2>
        <p class="text-gray-600 text-sm mb-0">While we strive for perfection, textile manufacturing involves variables such as minor fabric dye-lot shifts or slight shrinkage (+/- 5%). Welzee's maximum liability for any defective or delayed order is strictly limited to the total invoice value paid by the customer for that specific order.</p>
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>
