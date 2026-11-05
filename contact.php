<?php
// contact.php
$extraHead = '<title>Contact Us | Premium Embroidery & Printing</title>';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="bg-secondary text-white py-20 relative overflow-hidden">
    <!-- Decorative background -->
    <div class="absolute inset-0 overflow-hidden bg-secondary-light">
        <img src="https://placehold.co/1920x400/1e293b/0f172a?text=Contact+Us" alt="Contact Header" class="w-full h-full object-cover opacity-20 mix-blend-overlay">
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Get In Touch</h1>
        <p class="text-lg text-gray-400 max-w-2xl mx-auto">Have a question about a large corporate order, a specific finishing technique, or general support? We are here to help.</p>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
        
        <!-- Contact Information -->
        <div>
            <h2 class="text-3xl font-extrabold text-secondary mb-8">We'd love to hear from you.</h2>
            
            <div class="space-y-8 mb-10">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 text-xl">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-lg mb-1">Our Headquarters</h4>
                        <p class="text-gray-600">R106 Sheet 30 Block A, Saima Arabian Villas,<br> Karachi, Pakistan</p>
                    </div>
                </div>
                
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 text-xl">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-lg mb-1">Give Us a Call</h4>
                        <p class="text-gray-600 mb-2">Mon - Fri, 8:00 AM - 6:00 PM PST</p>
                        <a href="tel:+18001234567" class="text-primary font-bold hover:underline transition">+92 320 5993161</a>
                    </div>
                </div>
                
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 text-xl">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-lg mb-1">Email Us</h4>
                        <p class="text-gray-600 mb-2">We aim to reply within 24 hours.</p>
                        <a href="mailto:info@welzee.com" class="text-primary font-bold hover:underline transition">info@welzee.com</a>
                    </div>
                </div>
            </div>
            
            <h3 class="font-bold text-xl text-secondary mb-4">Looking for a quote?</h3>
            <p class="text-gray-600 mb-6">Skip the general form and use our interactive designer to get a specific price instantly.</p>
            <a href="custom_order" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition inline-flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Open Customizer
            </a>
        </div>
        
        <!-- Contact Form -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 lg:p-10">
            <h3 class="text-2xl font-bold text-secondary mb-6">Send us a message</h3>
            <form onsubmit="event.preventDefault(); alert('Form submitted! (This is a generic template form)');">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">First Name *</label>
                        <input type="text" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Last Name *</label>
                        <input type="text" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50">
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address *</label>
                    <input type="email" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Subject</label>
                    <input type="text" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50">
                </div>
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Message *</label>
                    <textarea required rows="5" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50"></textarea>
                </div>
                <button type="submit" class="w-full bg-secondary hover:bg-black text-white font-bold py-4 rounded-lg transition shadow-md flex justify-center items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
        
    </div>

</div>

<!-- Auto Map Placeholder -->
<div class="w-full h-80 bg-gray-200 relative">
    <img src="https://placehold.co/1920x400/e2e8f0/94a3b8?text=Interactive+Google+Map+Placeholder" class="w-full h-full object-cover">
</div>

<?php
include 'includes/footer.php';
?>
