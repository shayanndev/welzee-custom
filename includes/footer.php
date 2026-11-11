<?php
// includes/footer.php
require_once __DIR__ . '/../config/bootstrap.php';
if (!isset($base_url)) {
    $base_url = app_base_url();
}
?>
    </main> <!-- End of main content area, flex-grow ensures this pushes footer down -->

    <!-- Global Footer -->
    <footer class="bg-secondary text-gray-300 pt-16 pb-8 mt-auto border-t-[6px] border-primary">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                
                <!-- Brand Info Column -->
                <div class="col-span-1 lg:col-span-1">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-primary text-white flex justify-center items-center rounded-lg shadow-md">
                            <i class="fa-solid fa-shirt text-lg"></i>
                        </div>
                        <span class="font-extrabold text-3xl tracking-tighter text-white leading-none">Welzee</span>
                    </div>
                    <p class="text-sm text-gray-400 mb-6 leading-relaxed">
                        Premium quality custom embroidery and printing directly onto high-end apparel. Personalize your teamwear, corporate clothing, or promotional items in Pakistan with stunning, durable results guaranteed to leave an impression.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-9 h-9 rounded-full bg-white/5 border border-white/10 flex items-center justify-center hover:bg-primary hover:border-primary hover:text-white transition duration-300 shadow-sm"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="w-9 h-9 rounded-full bg-white/5 border border-white/10 flex items-center justify-center hover:bg-primary hover:border-primary hover:text-white transition duration-300 shadow-sm"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="w-9 h-9 rounded-full bg-white/5 border border-white/10 flex items-center justify-center hover:bg-primary hover:border-primary hover:text-white transition duration-300 shadow-sm"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#" class="w-9 h-9 rounded-full bg-white/5 border border-white/10 flex items-center justify-center hover:bg-primary hover:border-primary hover:text-white transition duration-300 shadow-sm"><i class="fa-brands fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Quick Links Column -->
                <div>
                    <h4 class="text-white font-bold text-lg mb-6 relative inline-block after:content-[''] after:absolute after:w-1/2 after:h-1 after:bg-primary after:bottom-[-8px] after:left-0 rounded-full">Quick Links</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="<?php echo $base_url; ?>" class="hover:text-primary hover:translate-x-1 transition flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary"></i> Home</a></li>
                        <li><a href="<?php echo $base_url; ?>services" class="hover:text-primary hover:translate-x-1 transition flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary"></i> Services</a></li>
                        <li><a href="<?php echo $base_url; ?>custom_order" class="hover:text-primary hover:translate-x-1 transition flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary"></i> Custom Order</a></li>
                        <li><a href="<?php echo $base_url; ?>about" class="hover:text-primary hover:translate-x-1 transition flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary"></i> About Us</a></li>
                        <li><a href="<?php echo $base_url; ?>contact" class="hover:text-primary hover:translate-x-1 transition flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary"></i> Contact</a></li>
                    </ul>
                </div>

                <!-- Customer Service Column -->
                <div>
                    <h4 class="text-white font-bold text-lg mb-6 relative inline-block after:content-[''] after:absolute after:w-1/2 after:h-1 after:bg-primary after:bottom-[-8px] after:left-0 rounded-full">Support</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="<?php echo $base_url; ?>faq" class="hover:text-primary hover:translate-x-1 transition flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary"></i> FAQ</a></li>
                        <li><a href="<?php echo $base_url; ?>shipping" class="hover:text-primary hover:translate-x-1 transition flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary"></i> Shipping Information</a></li>
                        <li><a href="<?php echo $base_url; ?>returns" class="hover:text-primary hover:translate-x-1 transition flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary"></i> Return Policy</a></li>
                        <li><a href="<?php echo $base_url; ?>privacy" class="hover:text-primary hover:translate-x-1 transition flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary"></i> Privacy Policy</a></li>
                        <li><a href="<?php echo $base_url; ?>terms" class="hover:text-primary hover:translate-x-1 transition flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary"></i> Terms & Conditions</a></li>
                    </ul>
                </div>

                <!-- Contact & Payment Column -->
                <div>
                    <h4 class="text-white font-bold text-lg mb-6 relative inline-block after:content-[''] after:absolute after:w-1/2 after:h-1 after:bg-primary after:bottom-[-8px] after:left-0 rounded-full">Get In Touch</h4>
                    <ul class="space-y-4 text-sm mb-6">
                        <li class="flex items-center gap-4">
                            <div class="w-6 flex justify-center"><i class="fa-solid fa-phone text-primary text-lg"></i></div>
                            <span class="text-gray-400"><a href="tel:+923205993161" class="hover:text-primary transition">+92 320 5993161</a></span>
                        </li>
                        <li class="flex items-center gap-4">
                            <div class="w-6 flex justify-center"><i class="fa-solid fa-envelope text-primary text-lg"></i></div>
                            <span class="text-gray-400"><a href="mailto:info@welzee.com" class="hover:text-primary transition">info@welzee.com</a></span>
                        </li>
                    </ul>
                    
                    <div class="mt-6">
                        <h5 class="text-xs uppercase tracking-wider text-gray-500 font-bold mb-3">Secure Payments</h5>
                        <div class="flex flex-wrap items-center gap-2.5 text-2xl text-gray-400">
                            <!-- Easypaisa -->
                            <span class="inline-flex items-center h-[26px] px-2 rounded bg-white text-[#00A852] font-bold text-[10px] shadow-sm hover:brightness-105 transition cursor-pointer" title="Easypaisa">
                                <svg class="w-3.5 h-3.5 mr-1 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" fill="#00A852"/>
                                    <path d="M12 7C9.24 7 7 9.24 7 12s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zm0 7.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z" fill="#ffffff"/>
                                </svg>
                                <span class="font-extrabold tracking-tight">easypaisa</span>
                            </span>
                            <!-- JazzCash -->
                            <span class="inline-flex items-center h-[26px] px-2 rounded bg-white font-bold text-[10px] shadow-sm hover:brightness-105 transition cursor-pointer" title="JazzCash">
                                <svg class="w-3.5 h-3.5 mr-1 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" fill="#E51B24"/>
                                    <path d="M12 6c-3.3 0-6 2.7-6 6s2.7 6 6 6 6-2.7 6-6c0-1.8-.8-3.4-2.1-4.5l-1.5 1.5c.8.7 1.3 1.8 1.3 3 0 2.2-1.8 4-4 4s-4-1.8-4-4 1.8-4 4-4v-2z" fill="#FFF"/>
                                </svg>
                                <span class="font-extrabold tracking-tight text-[#E51B24]">Jazz</span><span class="font-extrabold tracking-tight text-gray-900">Cash</span>
                            </span>
                            <i class="fa-brands fa-cc-visa hover:text-white transition cursor-pointer" title="Visa"></i>
                            <i class="fa-brands fa-cc-mastercard hover:text-white transition cursor-pointer" title="Mastercard"></i>
                            <i class="fa-brands fa-cc-paypal hover:text-white transition cursor-pointer" title="PayPal"></i>
                            <i class="fa-brands fa-cc-amex hover:text-white transition cursor-pointer" title="American Express"></i>
                        </div>
                    </div>
                </div>

            </div>

            <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> Welzee Embroidery & Print. All rights reserved.</p>
                <div class="mt-4 md:mt-0 flex gap-4">
                    <a href="<?php echo $base_url; ?>privacy" class="hover:text-white transition">Privacy</a>
                    <a href="<?php echo $base_url; ?>terms" class="hover:text-white transition">Terms</a>
                    <a href="<?php echo $base_url; ?>sitemap.xml" class="hover:text-white transition">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
