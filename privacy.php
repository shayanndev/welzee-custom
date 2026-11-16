<?php
// privacy.php - Privacy Policy
require_once __DIR__ . '/config/bootstrap.php';
$base_url = app_base_url();

$pageTitle = 'Privacy Policy | Welzee';
$pageDescription = 'Read how Welzee protects your personal information, design assets, and order details in accordance with standard international privacy practices.';
$canonicalUrl = 'https://welzee.com/privacy';

include __DIR__ . '/includes/header.php';
?>

<div class="bg-secondary text-white py-16 border-b-4 border-primary">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block py-1 px-3.5 rounded-full bg-primary/20 text-primary border border-primary/30 text-xs font-bold tracking-widest uppercase mb-4">Data Governance</span>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Privacy Policy</h1>
        <p class="text-lg text-gray-300">How we manage, protect, and handle your order information and design assets.</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 prose prose-lg prose-indigo max-w-none">
    <div class="bg-white rounded-2xl p-8 sm:p-12 shadow-sm border border-gray-100">
        
        <p class="text-gray-500 text-xs uppercase font-bold tracking-widest mb-8 pb-4 border-b">Effective Date: November 2026</p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">1. Information We Collect</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">
            We collect personal and commercial information required to process and fulfill your custom apparel orders and quotations. This includes your name, company or organization name, shipping and billing address, email address, telephone/WhatsApp contact number, and uploaded design artwork files (logos, graphics, and text embroidery requests). Technical browsing metadata (such as IP address, browser type, and device information) may be gathered through standard web analytics to monitor site reliability and prevent automated abuse.
        </p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">2. How We Use Your Information</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">
            Your information is used strictly to engineer your digital embroidery and print proofs, calculate quotations, manufacture garments, arrange door-to-door courier dispatch, and provide customer support. We do not sell, rent, or monetize your contact details or uploaded designs to third-party marketing brokers.
        </p>
        
        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">3. Third-Party Service Providers</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">
            We share only strictly necessary transaction data with vetted partners required to complete your order fulfillment:
        </p>
        <ul class="text-gray-600 text-sm list-disc pl-5 mb-6 space-y-1.5 leading-relaxed">
            <li><strong>Logistics & Couriers:</strong> Delivery address and recipient telephone details are provided to established domestic carriers (TCS, Leopards, CallCourier) and international air couriers to ensure physical delivery and tracking.</li>
            <li><strong>Payment Processing:</strong> Financial transactions are processed directly through authorized banking institutions (IBFT / Raast), mobile financial services (JazzCash, Easypaisa), and merchant gateway providers. Welzee does not store full credit card numbers or sensitive CVV codes on our servers.</li>
        </ul>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">4. Proprietary Artwork & Intellectual Property</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">
            All logos, brand emblems, and artwork files uploaded to Welzee remain the exclusive intellectual property of the respective customer. Uploaded assets are securely archived on our protected production storage solely to facilitate order fulfillment, precision stitch file conversion, and seamless reorders. We will never reproduce, resell, or distribute your branded merchandise to third parties without your prior written authorization.
        </p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">5. Data Security & Retention</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">
            Our platform utilizes transport layer security (TLS/SSL encryption) across all web requests and API endpoints. We enforce strict server-side access controls and firewalls to protect customer records. Digitized embroidery machine files (.DST) are archived to ensure permanent color and tension consistency across future reorders. Customers may request the deletion of their contact records or archived artwork at any time by contacting our data desk.
        </p>

        <h2 class="text-2xl font-bold text-secondary mb-4 border-b pb-2">6. Privacy Inquiries & Contact</h2>
        <p class="text-gray-600 text-sm leading-relaxed mb-0">
            For questions regarding this privacy policy or to request data updates, please contact us at <a href="mailto:info@welzee.com" class="text-primary font-semibold hover:underline">info@welzee.com</a> or via phone/WhatsApp at +92 320 5993161.
        </p>
        
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
