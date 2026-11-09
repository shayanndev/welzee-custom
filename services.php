<?php
// services.php
$extraHead = '<title>Our Services | Premium Embroidery & Printing</title>';
include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-secondary text-white py-20 relative overflow-hidden">
<section class="bg-secondary text-white py-20 relative overflow-hidden">
    <!-- Decorative transparent pattern -->
    <div class="absolute inset-0 opacity-5" style="background-image: radial-gradient(#fff 2px, transparent 2px); background-size: 30px 30px;"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Our Premium Services</h1>
        <p class="text-lg text-gray-400 max-w-2xl mx-auto">We offer state-of-the-art textile finishing techniques tailored to your exact brand requirements.</p>
    </div>
</section>

<!-- Our Materials & Quality Control -->
<section class="py-20 bg-gray-50 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary tracking-tight mb-4">Uncompromising Quality Control</h2>
            <p class="text-gray-500 max-w-2xl mx-auto">We don't cut corners. From the thread we spool to the optical sensors on our printers.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl transition duration-300">
                <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex items-center justify-center text-2xl mb-6">
                    <i class="fa-solid fa-gem"></i>
                </div>
                <h3 class="text-2xl font-bold text-secondary mb-4">Premium Materials Only</h3>
                <p class="text-gray-600 mb-6 leading-relaxed">
                    We source our blanks exclusively from recognized, ethical suppliers. Our embroidery threads are colorfast, bleach-resistant poly-neon threads that will outlast the garment itself. For printing, we utilize eco-friendly, water-based inks that penetrate the fabric rather than sitting heavily on top.
                </p>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-primary"></i> <span class="text-sm font-medium text-gray-700">Oeko-Tex Standard 100 Certified</span></li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-primary"></i> <span class="text-sm font-medium text-gray-700">Bleach & UV Resistant Threads</span></li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-primary"></i> <span class="text-sm font-medium text-gray-700">Recycled Polyester Options</span></li>
                </ul>
            </div>
            
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl transition duration-300">
                <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex items-center justify-center text-2xl mb-6">
                    <i class="fa-solid fa-microscope"></i>
                </div>
                <h3 class="text-2xl font-bold text-secondary mb-4">3-Point Inspection</h3>
                <p class="text-gray-600 mb-6 leading-relaxed">
                    Every single garment passes through three human sets of eyes before it is boxed. We check for registration accuracy, thread tension, puckering, backing cleanliness, and ink curing temperatures to guarantee absolute perfection.
                </p>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-primary"></i> <span class="text-sm font-medium text-gray-700">Tension & Registration Check</span></li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-primary"></i> <span class="text-sm font-medium text-gray-700">Backing Cleanup & Trimming</span></li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-primary"></i> <span class="text-sm font-medium text-gray-700">Final Fold & Presentation</span></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    
    <!-- Embroidery Section -->
    <div class="flex flex-col lg:flex-row gap-16 items-center mb-24">
        <div class="w-full lg:w-1/2 rounded-3xl overflow-hidden shadow-2xl relative group">
            <img src="https://placehold.co/800x600/f8fafc/64748b?text=Embroidery+Machine" alt="Embroidery Service" class="w-full h-auto object-cover transform group-hover:scale-105 transition duration-700">
            <div class="absolute inset-0 ring-1 ring-inset ring-black/10 rounded-3xl"></div>
        </div>
        <div class="w-full lg:w-1/2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary font-bold text-sm mb-6 uppercase tracking-widest border border-primary/20">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Highest Quality
            </div>
            <h2 class="text-3xl md:text-4xl font-extrabold text-secondary mb-6">Expert Embroidery</h2>
            <p class="text-gray-600 mb-6 text-lg leading-relaxed">
                Embroidery is the undisputed king of textile finishing. It offers an incredibly high-quality, three-dimensional look that exudes professionalism and durability. Perfect for corporate workwear, heavy jackets, caps, and premium polos.
            </p>
            <ul class="space-y-4 mb-8">
                <li class="flex items-start gap-4">
                    <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-primary mt-1 shrink-0"><i class="fa-solid fa-check text-xs"></i></div>
                    <span class="text-gray-700 font-medium">Extremely durable and wash-resistant up to 95°C</span>
                </li>
                <li class="flex items-start gap-4">
                    <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-primary mt-1 shrink-0"><i class="fa-solid fa-check text-xs"></i></div>
                    <span class="text-gray-700 font-medium">Noble, slightly raised 3D feel</span>
                </li>
                <li class="flex items-start gap-4">
                    <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-primary mt-1 shrink-0"><i class="fa-solid fa-check text-xs"></i></div>
                    <span class="text-gray-700 font-medium">Ideal for structured fabrics and heavier garments</span>
                </li>
            </ul>
            <a href="custom_order" class="inline-flex items-center gap-2 text-primary font-bold hover:text-primary-dark transition text-lg border-b-2 border-primary pb-1">
                Design Embroidery <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Printing Section -->
    <div class="flex flex-col lg:flex-row-reverse gap-16 items-center">
        <div class="w-full lg:w-1/2 rounded-3xl overflow-hidden shadow-2xl relative group">
            <img src="https://placehold.co/800x600/f8fafc/64748b?text=Textile+Printing" alt="Printing Service" class="w-full h-auto object-cover transform group-hover:scale-105 transition duration-700">
            <div class="absolute inset-0 ring-1 ring-inset ring-black/10 rounded-3xl"></div>
        </div>
        <div class="w-full lg:w-1/2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary font-bold text-sm mb-6 uppercase tracking-widest border border-primary/20">
                <i class="fa-solid fa-print"></i> Most Versatile
            </div>
            <h2 class="text-3xl md:text-4xl font-extrabold text-secondary mb-6">Flex & Digital Printing</h2>
            <p class="text-gray-600 mb-6 text-lg leading-relaxed">
                Textile printing is ideal for large, complex designs with many colors, gradients, or fine details that cannot be stitched. We utilize modern transfer and direct-to-garment (DTG) technologies to ensure vibrant, long-lasting colors.
            </p>
            <ul class="space-y-4 mb-8">
                <li class="flex items-start gap-4">
                    <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-primary mt-1 shrink-0"><i class="fa-solid fa-check text-xs"></i></div>
                    <span class="text-gray-700 font-medium">Perfect for large back designs & photorealistic images</span>
                </li>
                <li class="flex items-start gap-4">
                    <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-primary mt-1 shrink-0"><i class="fa-solid fa-check text-xs"></i></div>
                    <span class="text-gray-700 font-medium">Lighter feel, ideal for thin summer T-Shirts</span>
                </li>
                <li class="flex items-start gap-4">
                    <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-primary mt-1 shrink-0"><i class="fa-solid fa-check text-xs"></i></div>
                    <span class="text-gray-700 font-medium">Cost-effective for very large volumes</span>
                </li>
            </ul>
            <a href="custom_order" class="inline-flex items-center gap-2 text-primary font-bold hover:text-primary-dark transition text-lg border-b-2 border-primary pb-1">
                Design Print <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

</div>

<!-- Process Section -->
<section class="bg-gray-50 py-24 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-extrabold text-secondary mb-16">Our Simple Process</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="relative">
                <div class="w-20 h-20 mx-auto bg-white rounded-full shadow-lg flex items-center justify-center text-3xl text-primary mb-6 border-4 border-gray-50 z-10 relative">1</div>
                <h4 class="font-bold text-lg mb-2 text-secondary">Choose Garment</h4>
                <p class="text-sm text-gray-500">Pick from our premium catalogue.</p>
                <div class="hidden md:block absolute top-10 left-[60%] w-[80%] h-0.5 bg-gray-200 -z-0"></div>
            </div>
            <div class="relative">
                <div class="w-20 h-20 mx-auto bg-white rounded-full shadow-lg flex items-center justify-center text-3xl text-primary mb-6 border-4 border-gray-50 z-10 relative">2</div>
                <h4 class="font-bold text-lg mb-2 text-secondary">Upload Design</h4>
                <p class="text-sm text-gray-500">Attach your logo or add text.</p>
                <div class="hidden md:block absolute top-10 left-[60%] w-[80%] h-0.5 bg-gray-200 -z-0"></div>
            </div>
            <div class="relative">
                <div class="w-20 h-20 mx-auto bg-white rounded-full shadow-lg flex items-center justify-center text-3xl text-primary mb-6 border-4 border-gray-50 z-10 relative">3</div>
                <h4 class="font-bold text-lg mb-2 text-secondary">Get Quote</h4>
                <p class="text-sm text-gray-500">Fast pricing directly to your email.</p>
                <div class="hidden md:block absolute top-10 left-[60%] w-[80%] h-0.5 bg-gray-200 -z-0"></div>
            </div>
            <div class="relative">
                <div class="w-20 h-20 mx-auto bg-primary rounded-full shadow-lg flex items-center justify-center text-3xl text-white mb-6 border-4 border-primary-light z-10 relative">4</div>
                <h4 class="font-bold text-lg mb-2 text-secondary">Production</h4>
                <p class="text-sm text-gray-500">We produce and ship quickly!</p>
            </div>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/includes/footer.php';
?>
