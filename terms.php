<?php
// terms.php - Terms & Conditions
require_once __DIR__ . '/config/bootstrap.php';
$base_url = app_base_url();

$pageTitle = 'Terms & Conditions | Welzee';
$pageDescription = 'Commercial terms of service, proof authorization rules, payment guidelines, and manufacturing policies for custom apparel orders placed with Welzee.';
$canonicalUrl = 'https://welzee.com/terms';

include __DIR__ . '/includes/header.php';
?>

<div class="bg-secondary text-white py-16 border-b-4 border-primary">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block py-1 px-3.5 rounded-full bg-primary/20 text-primary border border-primary/30 text-xs font-bold tracking-widest uppercase mb-4">Commercial Guidelines</span>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Terms & Conditions</h1>
        <p class="text-lg text-gray-300">Commercial terms governing custom apparel decoration, quotation approvals, and fulfillment.</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 prose prose-lg prose-indigo max-w-none">
    <div class="bg-white rounded-2xl p-8 sm:p-12 shadow-sm border border-gray-100">
        
        <p class="text-gray-500 text-xs uppercase font-bold tracking-widest mb-8 pb-4 border-b">Effective Date: November 2026</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">1. Scope & Quotation Acceptance</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">
            By submitting an order or approving a quotation on Welzee.com, you agree to these Terms & Conditions. Because all apparel decoration (embroidery, direct-to-garment printing, heat transfers) is customized specifically to client specifications, orders are processed based on individualized formal quotations and approved digital mockups rather than fixed retail checkout.
        </p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">2. Digital Proof Authorization & Production Schedules</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">
            Prior to machine stitching or printing, Welzee generates a digital proof showing logo dimensions, placement, and color references for customer authorization. <strong>Standard production turnaround (5 to 7 business days) begins only after final written proof authorization and required deposit clearance.</strong> Once a digital proof is signed off and production commenced, design modifications cannot be accommodated without incurring re-setup charges.
        </p>
        
        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">3. Pricing, Deposits & Payment Methods</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">
            Indicative prices displayed on our website or Customizer are estimates based on standard parameters; formal binding quotes are provided upon artwork evaluation. For commercial and bulk orders, a standard <strong>50% deposit</strong> is required upon proof approval, with the remaining balance due upon completion prior to dispatch.
        </p>
        <ul class="text-gray-600 text-sm list-disc pl-5 mb-6 space-y-1.5 leading-relaxed">
            <li><strong>Domestic Pakistan Payments:</strong> Direct Online Bank Transfer (IBFT via Raast/interbank wire), JazzCash, Easypaisa, or approved corporate terms.</li>
            <li><strong>US & International Payments:</strong> Major international credit/debit cards (Visa, MasterCard, American Express) or commercial international wire transfers in USD.</li>
        </ul>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">4. Intellectual Property & Artwork Authorization</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">
            By submitting a logo, trademark, or artwork for apparel decoration, the client warrants that they own the legal rights, registered trademark, or express license to reproduce the submitted design. Welzee reserves the right to decline any order containing defamatory, infringing, or unauthorized third-party intellectual property.
        </p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">5. Returns, Replacements & Defect Claims</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">
            Because custom-decorated garments are permanently branded with client-specific designs, <strong>orders cannot be returned or refunded for buyer's remorse, sizing selection errors, or post-approval project cancellations</strong>. However, if garments arrive with an objective manufacturing defect, stitch flaw, or material discrepancy differing from the authorized proof, Welzee will replace or reprint the affected items free of charge upon written notification within <strong>7 days of delivery</strong> (refer to our <a href="<?php echo $base_url; ?>returns" class="text-primary font-semibold hover:underline">Return & Replacement Policy</a>).
        </p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">6. Manufacturing Tolerances & Liability Limitations</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-0">
            Textile manufacturing involves natural physical tolerances, including minor fabric dye-lot variations (+/- 5%) and standard wash shrinkage. Welzee's total liability for any claim arising from an order is strictly limited to the total monetary amount received for the specific items in dispute.
        </p>
        
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
