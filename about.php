<?php
// about.php - About Welzee & Production Craftsmanship
require_once __DIR__ . '/config/bootstrap.php';
$base_url = app_base_url();

$pageTitle = 'About Welzee | Professional Embroidery & Garment Printing';
$pageDescription = 'Learn about Welzee\'s manufacturing workshop in Pakistan, our dedicated apparel crafting team, and our commitment to premium embroidery and printing quality.';
$canonicalUrl = 'https://welzee.com/about';

$pageJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'AboutPage',
    'name' => 'About Welzee',
    'url' => 'https://welzee.com/about',
    'description' => 'Professional custom embroidery, garment printing, and corporate apparel finishing based in Karachi, Pakistan.',
    'mainEntity' => [
        '@type' => 'Organization',
        'name' => 'Welzee',
        'url' => 'https://welzee.com/',
        'logo' => 'https://welzee.com/assets/images/favicon.png',
        'email' => 'info@welzee.com',
        'telephone' => '+92 320 5993161',
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Karachi',
            'addressCountry' => 'PK'
        ]
    ]
];

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-secondary text-white py-24 relative overflow-hidden">
    <!-- Decorative transparent pattern -->
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#fff 2px, transparent 2px); background-size: 30px 30px;"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <span class="inline-block py-1 px-3.5 rounded-full bg-primary/20 text-primary border border-primary/30 text-xs font-bold tracking-widest uppercase mb-4">Textile Craftsmanship</span>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight mb-6">Who We Are</h1>
        <p class="text-xl text-gray-300 max-w-2xl mx-auto font-medium">Precision textile craftsmanship woven into every stitch and print.</p>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <div>
            <span class="text-primary font-bold tracking-widest uppercase text-sm mb-2 block">Our Craftsmanship</span>
            <h2 class="text-3xl md:text-4xl font-extrabold text-secondary mb-6">Crafting Corporate Identity with Precision.</h2>
            <div class="prose prose-lg text-gray-600 leading-relaxed space-y-4">
                <p>
                    Welzee is a specialized apparel decoration workshop based in Karachi, Pakistan. We understand that your corporate apparel is more than just clothing&mdash;it's the first physical impression your team and brand make.
                </p>
                <p>
                    We operate computerized multi-head embroidery machinery and calibrated textile printing technology. But the real foundation of our quality is technical precision: skilled digitizers who map stitch angles and pull compensation for specific fabrics, trained machine operators who balance thread tensions, and inspectors who examine every finished garment before packing.
                </p>
            </div>
            
            <div class="grid grid-cols-2 gap-6 mt-10">
                <div class="border-l-4 border-primary pl-4">
                    <div class="text-2xl font-extrabold text-secondary">Pakistan</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Manufacturing & Dispatch Origin</div>
                </div>
                <div class="border-l-4 border-primary pl-4">
                    <div class="text-2xl font-extrabold text-secondary">B2B & Custom</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Corporate Uniforms & Blanks</div>
                </div>
                <div class="border-l-4 border-primary pl-4">
                    <div class="text-2xl font-extrabold text-secondary">PK & USA</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Serving Domestic & US Clients</div>
                </div>
                <div class="border-l-4 border-primary pl-4">
                    <div class="text-2xl font-extrabold text-secondary">Quality First</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Visual Inspection Before Dispatch</div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <img src="<?php echo $base_url; ?>assets/images/about_workshop.jpg" alt="Industrial Embroidery & Textile Finishing Process" class="rounded-2xl shadow-lg w-full h-[380px] sm:h-[450px] object-cover">
            <img src="<?php echo $base_url; ?>assets/images/about_team.jpg" alt="Apparel Quality Inspection & Finishing" class="rounded-2xl shadow-lg w-full h-[380px] sm:h-[450px] object-cover mt-8">
        </div>
    </div>

</div>

<!-- Guarantee / Commitment Section -->
<section class="py-20 bg-primary">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <i class="fa-solid fa-ranking-star text-white text-6xl mb-6"></i>
        <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-6">Our Quality Commitment</h2>
        <p class="text-xl text-primary-light max-w-3xl mx-auto mb-10 font-medium leading-relaxed">
            Every stitched logo and printed graphic undergoes manual visual inspection before shipping. If an order has an objective manufacturing flaw that deviates from your approved proof, we replace it free of charge.
        </p>
        <a href="<?php echo $base_url, welzee_customizer_href(); ?>" class="bg-white text-primary font-bold py-4 px-10 rounded-full transition shadow-lg hover:shadow-xl text-lg inline-block transform hover:-translate-y-1">
            Design Custom Apparel
        </a>
    </div>
</section>

<!-- Production Disciplines Section (Replaces fabricated personas) -->
<section class="py-20 bg-gray-50 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="text-xs font-bold text-primary uppercase tracking-widest block mb-2">Technical Capabilities</span>
            <h2 class="text-3xl font-extrabold text-secondary tracking-tight mb-4">Our Production Disciplines</h2>
            <p class="text-gray-500 max-w-2xl mx-auto">The core technical disciplines that ensure every stitch, thread, and ink cure meets our commercial standards.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center text-2xl mb-6 font-bold">
                    <i class="fa-solid fa-bezier-curve"></i>
                </div>
                <h3 class="text-xl font-bold text-secondary mb-2">Vector Digitizing</h3>
                <p class="text-xs text-gray-500 leading-relaxed">Expert manual punch mapping, stitch path optimization, pull compensation, and underlay engineering tailored to pique, fleece, or twill.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center text-2xl mb-6 font-bold">
                    <i class="fa-solid fa-gears"></i>
                </div>
                <h3 class="text-xl font-bold text-secondary mb-2">Machine Calibration</h3>
                <p class="text-xs text-gray-500 leading-relaxed">Multi-head industrial embroidery machines with continuous tension balancing and needle alignment to prevent puckering or gaps.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center text-2xl mb-6 font-bold">
                    <i class="fa-solid fa-palette"></i>
                </div>
                <h3 class="text-xl font-bold text-secondary mb-2">Textile Chemistry</h3>
                <p class="text-xs text-gray-500 leading-relaxed">Colorfast poly-neon embroidery threads and water-based textile inks cured under controlled heat-press temperatures for wash resistance.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center text-2xl mb-6 font-bold">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <h3 class="text-xl font-bold text-secondary mb-2">Quality Inspection</h3>
                <p class="text-xs text-gray-500 leading-relaxed">Hand inspection of backing cleanup, loose thread trimming, print cure adhesion, and neat individual garment folding before carton boxing.</p>
            </div>
        </div>
    </div>
</section>

<!-- Manufacturing Principles Section (Replaces fabricated history timeline) -->
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="text-xs font-bold text-primary uppercase tracking-widest block mb-2">How We Work</span>
            <h2 class="text-3xl font-extrabold text-secondary tracking-tight">Our Manufacturing Principles</h2>
        </div>
        <div class="space-y-12">
            <div class="flex flex-col sm:flex-row gap-6 items-start">
                <div class="shrink-0 w-24 pt-1 font-extrabold text-primary text-xl">01</div>
                <div class="sm:border-l-2 sm:border-gray-200 sm:pl-8">
                    <h3 class="text-xl font-bold text-secondary mb-2">Proof Sign-off Before Production</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">We never guess stitch colors or design dimensions. Every order receives a digital visual mockup detailing scale, placement, and thread specifications for your written authorization.</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-6 items-start">
                <div class="shrink-0 w-24 pt-1 font-extrabold text-primary text-xl">02</div>
                <div class="sm:border-l-2 sm:border-gray-200 sm:pl-8">
                    <h3 class="text-xl font-bold text-secondary mb-2">Permanent Stitch File Archiving</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Once your logo is digitized and approved, your production stitch file is permanently archived on our secure servers. Reorders reproduce the exact same needle tension and thread colors with zero setup fees.</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-6 items-start">
                <div class="shrink-0 w-24 pt-1 font-extrabold text-primary text-xl">03</div>
                <div class="sm:border-l-2 sm:border-gray-200 sm:pl-8">
                    <h3 class="text-xl font-bold text-secondary mb-2">Tracked Door-to-Door Delivery</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">From our workshop in Karachi, domestic shipments are delivered via established courier networks (TCS, Leopards, CallCourier) and international shipments to the US are dispatched via tracked air couriers with tracking codes provided upon dispatch.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/includes/footer.php';
?>
