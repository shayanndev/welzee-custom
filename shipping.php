<?php
// shipping.php
$pageTitle = 'Shipping Policy | Pakistan & International Delivery | Welzee';
$pageDescription = 'Transparent shipping terms for Welzee. Products are manufactured and dispatched from Pakistan with fast nationwide couriers and tracked international shipping to the USA.';
include __DIR__ . '/includes/header.php';
?>

<div class="bg-secondary text-white py-16 border-b-4 border-primary">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tighter mb-4">Shipping Policy & Fulfillment Information</h1>
        <p class="text-lg text-gray-300">Transparent fulfillment and tracked shipping for domestic and international clients.</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 space-y-8">
        
        <!-- Crucial Origin & Fulfillment Disclosure Notice -->
        <div class="bg-primary/10 border-l-4 border-primary p-6 rounded-r-xl">
            <h2 class="text-lg font-bold text-secondary mb-2 flex items-center gap-2">
                <i class="fa-solid fa-plane-departure text-primary"></i> Manufacturing & Origin Statement
            </h2>
            <p class="text-gray-700 text-sm leading-relaxed font-medium">
                <strong>Orders are manufactured and dispatched from Pakistan.</strong> Shipping availability, costs, and delivery times depend on the destination and order requirements.
            </p>
        </div>

        <!-- Section 1: Shipping within Pakistan -->
        <div class="border-b pb-8">
            <h2 class="text-2xl font-bold text-secondary mb-4 flex items-center gap-2">
                <i class="fa-solid fa-truck text-primary"></i> 1. Shipping within Pakistan (Domestic Orders)
            </h2>
            <div class="space-y-4 text-sm text-gray-600 leading-relaxed">
                <p>
                    <strong>Availability & Coverage:</strong> We provide nationwide delivery across all cities and regions of Pakistan, including Karachi, Lahore, Islamabad, Rawalpindi, Faisalabad, Multan, Peshawar, and Quetta.
                </p>
                <p>
                    <strong>Production & Handling Time:</strong> Custom embroidered and printed apparel requires <strong>5 to 7 business days</strong> of production time. Production commences after digital artwork proof approval and quotation confirmation.
                </p>
                <p>
                    <strong>Transit & Delivery Time:</strong> Once dispatched from our Karachi workshop, delivery typically takes <strong>2 to 4 business days</strong>.
                </p>
                <p>
                    <strong>Courier Arrangements:</strong> Shipments are handled via established domestic logistics partners, including TCS, Leopards Courier, and CallCourier.
                </p>
                <p>
                    <strong>Shipping Costs:</strong> Domestic courier rates are calculated based on parcel weight and destination, presented transparently during your order quotation. Free shipping promotions apply to qualified bulk commercial orders.
                </p>
                <p>
                    <strong>Domestic Payment Methods:</strong> Direct Bank Transfer (IBFT), JazzCash, Easypaisa, or Cash on Delivery (COD where eligible for approved volumes).
                </p>
                <p>
                    <strong>Order Tracking:</strong> A live carrier tracking code is provided via WhatsApp and email as soon as your shipment is dispatched.
                </p>
                <p>
                    <strong>Undeliverable Shipments:</strong> If a delivery attempt fails due to an incorrect address or customer unavailability, the courier attempts re-delivery. Customers are contacted promptly to coordinate handover.
                </p>
            </div>
        </div>

        <!-- Section 2: Shipping to the United States -->
        <div class="border-b pb-8">
            <h2 class="text-2xl font-bold text-secondary mb-4 flex items-center gap-2">
                <i class="fa-solid fa-earth-americas text-primary"></i> 2. Shipping to the United States (US Orders)
            </h2>
            <div class="space-y-4 text-sm text-gray-600 leading-relaxed">
                <p>
                    <strong>Origin & Warehouse Notice:</strong> All products originate and are manufactured at our facility in Pakistan. <em>Welzee does not operate a US warehouse.</em> Orders are exported directly to US customer addresses.
                </p>
                <p>
                    <strong>Availability:</strong> International shipping to the United States is active and available for confirmed orders meeting our international courier parameters.
                </p>
                <p>
                    <strong>Production & Handling Time:</strong> Standard production time is <strong>5 to 7 business days</strong> following digital embroidery proof approval and invoice clearance.
                </p>
                <p>
                    <strong>International Transit Time:</strong> Dispatched via tracked international air couriers. Transit times vary depending on the destination state, selected courier service, and customs clearance procedures. An estimated delivery timeframe is provided with your individual quotation before dispatch.
                </p>
                <p>
                    <strong>Shipping Fees:</strong> International freight charges are calculated based on shipment weight/dimensions and destination, and are included in your preliminary quotation in USD.
                </p>
                <p>
                    <strong>Tracking Availability:</strong> Complete door-to-door courier tracking numbers are provided upon dispatch.
                </p>
                <p>
                    <strong>Customs Duties, Import Taxes & Clearance:</strong> Shipments are dispatched Delivered At Place (DAP / DDU). Welzee does not collect foreign customs duties or import taxes at quotation or checkout. Any import duties, tariffs, customs clearance fees, broker handling charges, or local/state taxes assessed by US Customs and Border Protection (CBP) or relevant authorities are the sole responsibility of the recipient/importer.
                </p>
                <p>
                    <strong>International Return Process:</strong> Because customized goods are permanently embroidered or printed with proprietary client logos, cross-border return to Pakistan is generally not economically feasible. For any manufacturing defects, please refer to our <a href="<?php echo $base_url; ?>returns" class="text-primary hover:underline font-semibold">Return & Replacement Policy</a> for our free reprint / resolution process.
                </p>
            </div>
        </div>

        <!-- Section 3: Other International Destinations -->
        <div class="border-b pb-8">
            <h2 class="text-2xl font-bold text-secondary mb-4 flex items-center gap-2">
                <i class="fa-solid fa-globe text-primary"></i> 3. Other International Destinations
            </h2>
            <div class="space-y-4 text-sm text-gray-600 leading-relaxed">
                <p>
                    We welcome inquiries from commercial clients worldwide (including the UK, Canada, UAE, and Europe). For destinations without pre-configured courier schedules:
                </p>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <p class="font-semibold text-secondary mb-1">Contact us for international shipping availability and quotation.</p>
                    <p class="text-xs text-gray-500">We verify courier coverage, calculate airfreight rates, and confirm delivery estimates before accepting your order.</p>
                </div>
            </div>
        </div>

        <!-- Section 4: Damaged or Missing Shipments -->
        <div>
            <h2 class="text-2xl font-bold text-secondary mb-4 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-primary"></i> 4. Transit Insurance & Claims
            </h2>
            <p class="text-sm text-gray-600 leading-relaxed">
                If your carton arrives visibly damaged or tampered with, take photographs before unboxing and notify us within <strong>48 hours</strong> at <a href="mailto:info@welzee.com" class="text-primary hover:underline">info@welzee.com</a> or WhatsApp (+92 320 5993161). If a package is confirmed lost in transit by the carrier, Welzee will expedite an investigation and reprint the order at our expense.
            </p>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
