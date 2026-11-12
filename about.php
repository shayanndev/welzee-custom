<?php
// about.php
$pageTitle = 'About Welzee | Professional Embroidery & Garment Printing';
$pageDescription = 'Learn about Welzee\'s manufacturing workshop in Pakistan, our dedicated apparel crafting team, and our commitment to premium embroidery and printing quality.';
include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-secondary text-white py-24 relative overflow-hidden">
    <!-- Decorative transparent pattern -->
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#fff 2px, transparent 2px); background-size: 30px 30px;"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight mb-6">Who We Are</h1>
        <p class="text-xl text-gray-300 max-w-2xl mx-auto font-medium">Precision textile craftsmanship woven into every stitch and print.</p>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <div>
            <span class="text-primary font-bold tracking-widest uppercase text-sm mb-2 block">Our Story</span>
            <h2 class="text-3xl md:text-4xl font-extrabold text-secondary mb-6">Crafting Corporate Identity with Precision.</h2>
            <div class="prose prose-lg text-gray-600">
                <p class="mb-4">
                    What started as a dedicated workshop has grown into a trusted provider of premium textile finishing. We understand that your corporate apparel is more than just clothing—it's the first impression your team makes.
                </p>
                <p class="mb-6">
                    We invest heavily in high-performance multi-head embroidery machines and sustainable printing technology. But the real secret to our quality is our people: trained punchers, meticulous machine operators, and dedicated support specialists who ensure your logo looks sharp every single time.
                </p>
            </div>
            
            <div class="grid grid-cols-2 gap-6 mt-10">
                <div class="border-l-4 border-primary pl-4">
                    <div class="text-2xl font-extrabold text-secondary">Pakistan</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Production & Dispatch Origin</div>
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

<!-- Guarantee Section -->
<section class="py-20 bg-primary">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <i class="fa-solid fa-ranking-star text-white text-6xl mb-6"></i>
        <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-6">Our Quality Guarantee</h2>
        <p class="text-xl text-primary-light max-w-3xl mx-auto mb-10 font-medium">
            We don't ship anything we wouldn't wear ourselves. Every stitched logo and printed graphic undergoes a strict 3-point visual inspection before shipping.
        </p>
        <a href="custom_order" class="bg-white text-primary font-bold py-4 px-10 rounded-full transition shadow-lg hover:shadow-xl text-lg inline-block transform hover:-translate-y-1">
            Experience It Now
        </a>
    </div>
</section>

<!-- Meet the Team -->
<section class="py-20 bg-gray-50 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary tracking-tight mb-4">Meet the Leadership</h2>
            <p class="text-gray-500 max-w-2xl mx-auto">The industry veterans ensuring every thread and drop of ink is absolutely perfect.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="text-center bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                <div class="w-24 h-24 rounded-full bg-primary/10 text-primary border-2 border-primary/20 mx-auto mb-4 flex items-center justify-center text-2xl font-extrabold shadow-inner">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <h3 class="text-lg font-bold text-secondary">Ahmad R.</h3>
                <p class="text-primary font-semibold text-xs uppercase tracking-wider mt-1">Managing Director</p>
                <p class="text-xs text-gray-500 mt-2">Production Strategy & Quality</p>
            </div>
            <div class="text-center bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                <div class="w-24 h-24 rounded-full bg-primary/10 text-primary border-2 border-primary/20 mx-auto mb-4 flex items-center justify-center text-2xl font-extrabold shadow-inner">
                    <i class="fa-solid fa-gears"></i>
                </div>
                <h3 class="text-lg font-bold text-secondary">Sara K.</h3>
                <p class="text-primary font-semibold text-xs uppercase tracking-wider mt-1">Head of Operations</p>
                <p class="text-xs text-gray-500 mt-2">Fulfillment & Workflow</p>
            </div>
            <div class="text-center bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                <div class="w-24 h-24 rounded-full bg-primary/10 text-primary border-2 border-primary/20 mx-auto mb-4 flex items-center justify-center text-2xl font-extrabold shadow-inner">
                    <i class="fa-solid fa-bezier-curve"></i>
                </div>
                <h3 class="text-lg font-bold text-secondary">Usman M.</h3>
                <p class="text-primary font-semibold text-xs uppercase tracking-wider mt-1">Lead Digitizer</p>
                <p class="text-xs text-gray-500 mt-2">Vector & Stitch Mapping</p>
            </div>
            <div class="text-center bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                <div class="w-24 h-24 rounded-full bg-primary/10 text-primary border-2 border-primary/20 mx-auto mb-4 flex items-center justify-center text-2xl font-extrabold shadow-inner">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <h3 class="text-lg font-bold text-secondary">Fatima A.</h3>
                <p class="text-primary font-semibold text-xs uppercase tracking-wider mt-1">Quality Assurance</p>
                <p class="text-xs text-gray-500 mt-2">3-Point Inspection Control</p>
            </div>
        </div>
    </div>
</section>

<!-- Company Timeline -->
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold text-center text-secondary tracking-tight mb-16">Our Journey</h2>
        <div class="space-y-12">
            <div class="flex flex-col sm:flex-row gap-6 items-start hover:-translate-y-1 transition placeholder">
                <div class="shrink-0 w-24 pt-2 font-bold text-primary text-xl">2010</div>
                <div class="pb-12 sm:border-l-2 sm:border-gray-200 sm:pl-8 relative">
                    <div class="hidden sm:block absolute w-4 h-4 bg-primary rounded-full -left-[9px] top-3 shadow-[0_0_0_4px_white]"></div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Humble Beginnings</h3>
                    <p class="text-gray-600">Started as a small single-head embroidery operation in a garage, focusing purely on local sports teams in Pakistan.</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-6 items-start hover:-translate-y-1 transition placeholder">
                <div class="shrink-0 w-24 pt-2 font-bold text-primary text-xl">2016</div>
                <div class="pb-12 sm:border-l-2 sm:border-gray-200 sm:pl-8 relative">
                    <div class="hidden sm:block absolute w-4 h-4 bg-primary rounded-full -left-[9px] top-3 shadow-[0_0_0_4px_white]"></div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Expanding Capabilities</h3>
                    <p class="text-gray-600">Invested in industrial DTG and screen printing machines, allowing us to cater to massive corporate events and franchise uniforms.</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-6 items-start hover:-translate-y-1 transition placeholder">
                <div class="shrink-0 w-24 pt-2 font-bold text-primary text-xl">Today</div>
                <div class="sm:border-l-2 sm:border-transparent sm:pl-8 relative">
                    <div class="hidden sm:block absolute w-4 h-4 bg-primary rounded-full -left-[9px] top-3 shadow-[0_0_0_4px_white]"></div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Expanded Custom Fulfillment</h3>
                    <p class="text-gray-600">Supplying customized uniforms, polos, and promotional apparel to corporate clients in Pakistan and fulfilling direct custom orders for customers in the United States.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/includes/footer.php';
?>
