<?php
// index.php
$pageTitle = 'Custom Embroidery & Printing Services | Welzee';
$pageDescription = 'Welzee offers custom embroidery, garment printing, and personalized apparel for customers in Pakistan and the USA. Customize T-shirts, polos, hoodies, and caps.';
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="relative bg-secondary text-white border-b-4 border-primary">
    <!-- HTML5 Video Background -->
    <div class="absolute inset-0 overflow-hidden bg-secondary-dark">
        <video autoplay loop muted playsinline
            class="w-full h-full object-cover opacity-60 mix-blend-screen pointer-events-none">
            <source src="assets/images/bannerVideo.mp4" type="video/mp4">
            <!-- Fallback text if video fails -->
            Your browser does not support the video tag.
        </video>
        <!-- Optional overlay gradient for better text readability -->
        <div class="absolute inset-0 bg-gradient-to-r from-secondary/80 to-secondary/30"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32 flex flex-col items-center text-center">
        <span
            class="inline-block py-1.5 px-4 rounded-full bg-primary/20 text-primary border border-primary/30 text-xs font-bold tracking-widest uppercase mb-6">Expert Apparel Finishing</span>
        <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tighter mb-6 leading-tight">Premium
            Corporate <br><span
                class="text-transparent bg-clip-text bg-gradient-to-r from-primary-light to-primary">Embroidery &
                Print</span></h1>
        <p class="text-lg md:text-xl text-gray-300 max-w-2xl mb-10 font-medium">
            Elevate your brand with high-quality, customized workwear, polos, and promotional textiles. Expertly
            stitched or printed according to your exact specifications.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
            <a href="custom_order"
                class="bg-primary hover:bg-primary-dark text-white font-bold py-4 px-8 rounded-xl transition shadow-xl shadow-primary/20 text-lg flex items-center justify-center gap-2 transform hover:-translate-y-1">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Design Your Own
            </a>
            <a href="services"
                class="bg-white/5 hover:bg-white/10 backdrop-blur-sm border border-white/20 text-white font-bold py-4 px-8 rounded-xl transition text-lg flex items-center justify-center">
                Explore Services
            </a>
        </div>

        <!-- Trust indicators -->
        <div class="mt-16 flex flex-wrap justify-center gap-8 text-sm text-gray-400 font-medium">
            <span class="flex items-center gap-2"><i class="fa-solid fa-check text-primary"></i> No hidden fees</span>
            <span class="flex items-center gap-2"><i class="fa-solid fa-check text-primary"></i> Fast 5–7 day production</span>
            <span class="flex items-center gap-2"><i class="fa-solid fa-check text-primary"></i> Volume tier discounts</span>
        </div>
    </div>
</section>

<!-- Features Row -->
<section class="py-16 bg-white border-b border-gray-100">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div
                class="flex items-start gap-4 p-8 rounded-2xl bg-gray-50 border border-gray-100 hover:shadow-lg hover:border-primary/30 transition duration-300 group">
                <div
                    class="w-14 h-14 rounded-xl bg-white shadow-sm flex items-center justify-center flex-shrink-0 group-hover:bg-primary group-hover:text-white transition duration-300 text-primary">
                    <i class="fa-solid fa-medal text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-secondary text-xl mb-2">Premium Quality</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">We use state-of-the-art multi-head embroidery
                        machines and sustainable threads to guarantee a brilliant look that lasts years.</p>
                </div>
            </div>
            <div
                class="flex items-start gap-4 p-8 rounded-2xl bg-gray-50 border border-gray-100 hover:shadow-lg hover:border-primary/30 transition duration-300 group">
                <div
                    class="w-14 h-14 rounded-xl bg-white shadow-sm flex items-center justify-center flex-shrink-0 group-hover:bg-primary group-hover:text-white transition duration-300 text-primary">
                    <i class="fa-solid fa-truck-fast text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-secondary text-xl mb-2">Fast Turnaround</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Standard production completes in 5 to 7 business days following digital proof approval, with expedited express options available for urgent events.</p>
                </div>
            </div>
            <div
                class="flex items-start gap-4 p-8 rounded-2xl bg-gray-50 border border-gray-100 hover:shadow-lg hover:border-primary/30 transition duration-300 group">
                <div
                    class="w-14 h-14 rounded-xl bg-white shadow-sm flex items-center justify-center flex-shrink-0 group-hover:bg-primary group-hover:text-white transition duration-300 text-primary">
                    <i class="fa-solid fa-tags text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-secondary text-xl mb-2">Volume Discounts</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">The more you order, the less you pay per item.
                        Transparent, tiered pricing available directly on your custom project quote.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Category Grid -->
<section class="py-24 bg-gray-50">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-extrabold text-secondary mb-4 tracking-tight">Shop by Category</h2>
            <p class="text-lg text-gray-500 max-w-2xl mx-auto">Browse our extensive catalogue of high-quality premium
                blanks perfectly suited for your custom logo placement.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- T-Shirts -->
            <a href="custom_order?category=tshirt"
                class="group block relative rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl transition duration-500 bg-white">
                <div class="aspect-w-3 aspect-h-4 bg-gray-100 overflow-hidden">
                    <img src="assets/images/tshirt1.jpg" alt="T-Shirts"
                        class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                </div>
                <div
                    class="absolute inset-0 bg-gradient-to-t from-secondary/90 via-secondary/20 to-transparent opacity-80 group-hover:opacity-100 transition duration-300">
                </div>
                <div
                    class="absolute bottom-0 left-0 p-6 w-full transform translate-y-2 group-hover:translate-y-0 transition duration-300">
                    <h3 class="text-white font-bold text-2xl mb-1 group-hover:text-primary transition">T-Shirts</h3>
                    <div class="flex justify-between items-center">
                        <p class="text-gray-300 text-sm font-medium">Starting at <span class="price-display"
                                data-usd="12.50" data-pkr="2500">$12.50</span> ea</p>
                        <i
                            class="fa-solid fa-arrow-right text-primary opacity-0 group-hover:opacity-100 transition duration-300 transform -translate-x-4 group-hover:translate-x-0"></i>
                    </div>
                </div>
            </a>

            <!-- Polos -->
            <a href="custom_order?category=polo"
                class="group block relative rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl transition duration-500 bg-white">
                <div class="aspect-w-3 aspect-h-4 bg-gray-100 overflow-hidden">
                    <img src="assets/images/poloshirt1.jpg" alt="Polo Shirts"
                        class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                </div>
                <div
                    class="absolute inset-0 bg-gradient-to-t from-secondary/90 via-secondary/20 to-transparent opacity-80 group-hover:opacity-100 transition duration-300">
                </div>
                <div
                    class="absolute bottom-0 left-0 p-6 w-full transform translate-y-2 group-hover:translate-y-0 transition duration-300">
                    <h3 class="text-white font-bold text-2xl mb-1 group-hover:text-primary transition">Polo Shirts</h3>
                    <div class="flex justify-between items-center">
                        <p class="text-gray-300 text-sm font-medium">Starting at <span class="price-display"
                                data-usd="18.00" data-pkr="3500">$18.00</span> ea</p>
                        <i
                            class="fa-solid fa-arrow-right text-primary opacity-0 group-hover:opacity-100 transition duration-300 transform -translate-x-4 group-hover:translate-x-0"></i>
                    </div>
                </div>
            </a>

            <!-- Hoodies -->
            <a href="custom_order?category=hoodie"
                class="group block relative rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl transition duration-500 bg-white">
                <div class="aspect-w-3 aspect-h-4 bg-gray-100 overflow-hidden">
                    <img src="assets/images/hoodie1.jpg" alt="Hoodies"
                        class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                </div>
                <div
                    class="absolute inset-0 bg-gradient-to-t from-secondary/90 via-secondary/20 to-transparent opacity-80 group-hover:opacity-100 transition duration-300">
                </div>
                <div
                    class="absolute bottom-0 left-0 p-6 w-full transform translate-y-2 group-hover:translate-y-0 transition duration-300">
                    <h3 class="text-white font-bold text-2xl mb-1 group-hover:text-primary transition">Hoodies</h3>
                    <div class="flex justify-between items-center">
                        <p class="text-gray-300 text-sm font-medium">Starting at <span class="price-display"
                                data-usd="28.50" data-pkr="5500">$28.50</span> ea</p>
                        <i
                            class="fa-solid fa-arrow-right text-primary opacity-0 group-hover:opacity-100 transition duration-300 transform -translate-x-4 group-hover:translate-x-0"></i>
                    </div>
                </div>
            </a>

            <!-- Hats & Caps -->
            <a href="custom_order?category=cap"
                class="group block relative rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl transition duration-500 bg-white">
                <div class="aspect-w-3 aspect-h-4 bg-gray-100 overflow-hidden">
                    <img src="assets/images/capwear.jpg" alt="Headwear"
                        class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                </div>
                <div
                    class="absolute inset-0 bg-gradient-to-t from-secondary/90 via-secondary/20 to-transparent opacity-80 group-hover:opacity-100 transition duration-300">
                </div>
                <div
                    class="absolute bottom-0 left-0 p-6 w-full transform translate-y-2 group-hover:translate-y-0 transition duration-300">
                    <h3 class="text-white font-bold text-2xl mb-1 group-hover:text-primary transition">Caps & Hats</h3>
                    <div class="flex justify-between items-center">
                        <p class="text-gray-300 text-sm font-medium">Starting at <span class="price-display"
                                data-usd="9.00" data-pkr="1800">$9.00</span> ea</p>
                        <i
                            class="fa-solid fa-arrow-right text-primary opacity-0 group-hover:opacity-100 transition duration-300 transform -translate-x-4 group-hover:translate-x-0"></i>
                    </div>
                </div>
            </a>
        </div>
        <p class="text-xs text-gray-400 mt-6 text-center">Indicative starting prices for customized blanks. Final quotes depend on order volume, stitch density, and printing specifications.</p>
        <div class="mt-16 text-center">
            <a href="custom_order"
                class="inline-flex items-center gap-2 text-secondary font-bold hover:text-primary transition group border-b-2 border-transparent hover:border-primary pb-1 text-lg">
                View Full Catalog <i class="fa-solid fa-arrow-right transform group-hover:translate-x-1 transition"></i>
            </a>
        </div>
    </div>
</section>

<!-- Production Capabilities & Standards -->
<section class="bg-white py-20">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-extrabold text-secondary tracking-tight mb-4">Engineered for Brand Precision</h2>
            <p class="text-lg text-gray-500 max-w-2xl mx-auto">From corporate uniform programs to custom team merchandise, we combine industrial technology with meticulous hand inspection.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50 p-8 rounded-2xl shadow-sm border border-gray-100 relative hover:border-primary/30 transition">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-xl mb-6 font-bold">
                    <i class="fa-solid fa-bezier-curve"></i>
                </div>
                <h3 class="font-bold text-secondary text-xl mb-3">Custom Digitizing & Proofing</h3>
                <p class="text-gray-600 text-sm leading-relaxed mb-4">Every logo is manually punched into stitch files with tailored thread tension and pull compensation for your chosen fabric. You review a high-resolution digital visual mockup before a single stitch is made.</p>
                <span class="text-xs font-semibold text-primary uppercase tracking-wider flex items-center gap-1.5"><i class="fa-solid fa-check"></i> Zero Reorder Setup Fees</span>
            </div>
            <div class="bg-gray-50 p-8 rounded-2xl shadow-sm border border-gray-100 relative hover:border-primary/30 transition">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-xl mb-6 font-bold">
                    <i class="fa-solid fa-industry"></i>
                </div>
                <h3 class="font-bold text-secondary text-xl mb-3">Multi-Head Industrial Capacity</h3>
                <p class="text-gray-600 text-sm leading-relaxed mb-4">Our workshop operates multi-head computerized embroidery machines and calibrated DTG printers capable of handling uniform batches from 10 pieces up to large-scale franchise rollouts.</p>
                <span class="text-xs font-semibold text-primary uppercase tracking-wider flex items-center gap-1.5"><i class="fa-solid fa-check"></i> Standard 5–7 Day Turnaround</span>
            </div>
            <div class="bg-gray-50 p-8 rounded-2xl shadow-sm border border-gray-100 relative hover:border-primary/30 transition">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-xl mb-6 font-bold">
                    <i class="fa-solid fa-boxes-packing"></i>
                </div>
                <h3 class="font-bold text-secondary text-xl mb-3">Domestic & US Tracked Shipping</h3>
                <p class="text-gray-600 text-sm leading-relaxed mb-4">Manufactured and dispatched directly from Pakistan. Fast nationwide delivery via TCS, Leopards, and CallCourier, with reliable tracked international air courier service to the United States.</p>
                <span class="text-xs font-semibold text-primary uppercase tracking-wider flex items-center gap-1.5"><i class="fa-solid fa-check"></i> PKR & USD Transparent Quoting</span>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Preview -->
<section class="bg-gray-50 py-20 border-t border-gray-200">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-extrabold text-secondary tracking-tight mb-4">Frequently Asked Questions</h2>
            <p class="text-lg text-gray-500 max-w-2xl mx-auto">Common questions about custom embroidery, minimums, and shipping.</p>
        </div>
        <div class="space-y-4 max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-lg text-secondary mb-2">What is the minimum order quantity (MOQ)?</h3>
                <p class="text-gray-600 text-sm leading-relaxed">For custom embroidery, our standard minimum order quantity is 10 pieces per design to cover machine hooping and setup. For direct-to-garment (DTG) printing, smaller flexible runs can be accommodated.</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-lg text-secondary mb-2">How long does production and shipping take?</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Standard production takes 5 to 7 business days following digital proof approval. Once dispatched from our Karachi facility, nationwide delivery across Pakistan takes 2 to 4 business days. International delivery to the US is shipped via tracked air couriers.</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-lg text-secondary mb-2">Can I supply my own garments for decoration (CMT)?</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Yes! In addition to our wholesale apparel blanks, we accept client-supplied garments subject to fabric inspection for embroidery or printing compatibility.</p>
            </div>
        </div>
        <div class="text-center mt-8">
            <a href="faq" class="inline-flex items-center gap-2 text-primary font-bold hover:underline text-base">
                View all 20+ Frequently Asked Questions <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>
    </div>
</section>

<!-- Call To Action Section -->
<section class="py-24 bg-primary relative overflow-hidden">
    <!-- Decorative Elements -->
    <div class="absolute top-0 right-0 -mr-32 -mt-32 w-96 h-96 rounded-full bg-white opacity-20 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -ml-32 -mb-32 w-96 h-96 rounded-full bg-secondary opacity-20 blur-3xl"></div>
    <!-- Pattern Texture -->
    <div class="absolute inset-0 opacity-10"
        style="background-image: radial-gradient(#fff 2px, transparent 2px); background-size: 30px 30px;"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-4xl md:text-5xl font-extrabold text-white mb-6 tracking-tight">Ready to upgrade your team's
            look?</h2>
        <p class="text-xl text-secondary mb-10 font-medium max-w-2xl mx-auto">Use our online designer to upload your
            logo, choose your products, and request a custom, no-obligation quote in under 2 minutes.</p>
        <a href="custom_order"
            class="bg-secondary hover:bg-black text-white font-bold py-5 px-10 rounded-2xl transition shadow-xl text-lg inline-flex items-center gap-3 transform hover:-translate-y-1">
            <i class="fa-solid fa-shirt"></i> Start Designing Now
        </a>
    </div>
</section>

<!-- Dynamic Pricing Script -->
<script>
    document.addEventListener('CurrencyReady', updatePrices);
    document.addEventListener('CurrencyChanged', updatePrices);

    function updatePrices() {
        const store = window.Welzee;
        const isPkr = store && store.currency === 'PKR';

        document.querySelectorAll('.price-display').forEach(el => {
            const usd = parseFloat(el.getAttribute('data-usd'));
            const pkr = parseInt(el.getAttribute('data-pkr'), 10) || Math.round(usd * 280);
            if (isPkr) {
                el.innerText = 'Rs. ' + pkr.toLocaleString();
            } else {
                el.innerText = '$' + usd.toFixed(2);
            }
        });
    }

    // Initial run in case already fully loaded
    if (window.Welzee && window.Welzee.currency) updatePrices();
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>