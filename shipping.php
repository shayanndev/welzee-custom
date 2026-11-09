<?php
// shipping.php
$extraHead = '<title>Shipping Information | Welzee</title>';
include __DIR__ . '/includes/header.php';
?>

<div class="bg-secondary text-white py-16 border-b-4 border-primary">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tighter mb-4">Shipping Information</h1>
        <p class="text-lg text-gray-400">Fast, reliable, and trackable delivery directly to your door.</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 prose prose-lg prose-indigo max-w-none">
    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        
        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">1. Processing Times</h2>
        <p class="text-gray-600 text-sm mb-6">All customized orders require a standard production time of <strong>7 to 10 business days</strong> before they are handed over to our shipping carriers. Production begins only after the digital proof is approved and the invoice is settled.</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">2. Domestic Shipping (Pakistan)</h2>
        <p class="text-gray-600 text-sm mb-6">We partner with premier courier services (TCS, Leopards, CallCourier) to ensure fast delivery across Pakistan. Standard nationwide delivery usually takes <strong>2-4 business days</strong> once dispatched. Free shipping promotions apply automatically at checkout when available.</p>
        
        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">3. International Shipping</h2>
        <p class="text-gray-600 text-sm mb-6">We ship globally using DHL and FedEx priority networks. Estimated transit time for international orders is <strong>5-10 business days</strong>. Please note that the recipient is responsible for any applicable customs duties or import taxes levied by their country's border authorities.</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">4. Order Tracking</h2>
        <p class="text-gray-600 text-sm mb-6">Once your order is picked up by the courier, you will receive an automated shipping confirmation email containing a live tracking link. Please allow up to 24 hours for the tracking portal to reflect active movement.</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">5. Lost or Damaged Packages</h2>
        <p class="text-gray-600 text-sm mb-0">If your package arrives severely damaged, please take photographs of the exterior before opening and contact us immediately at <a href="mailto:info@welzee.com" class="text-primary hover:underline">info@welzee.com</a>. If an order is lost in transit by the carrier, we will launch an investigation and reprint your order at no additional cost.</p>
        
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
