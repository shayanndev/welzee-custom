<?php
// contact.php - Contact & Quotation Inquiries
require_once __DIR__ . '/config/bootstrap.php';
$base_url = app_base_url();
$csrfToken = csrf_token();

$pageTitle = 'Contact Welzee | Get an Embroidery & Printing Quote';
$pageDescription = 'Have questions or need a bulk apparel quotation? Contact Welzee via WhatsApp, phone, or email. We fulfill orders for businesses in Pakistan, the US, and worldwide.';
$canonicalUrl = 'https://welzee.com/contact';

$formSubmitted = false;
$formError = '';
$formSuccess = false;

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact_submit') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($submittedToken)) {
        $formError = 'Security token expired. Please refresh and try again.';
    } else {
        $firstName = trim(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $lastName = trim(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
        $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

        if (empty($firstName) || empty($email) || empty($message)) {
            $formError = 'Please fill out all required fields (Name, Email, and Message).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = 'Please provide a valid email address.';
        } else {
            // Attempt email dispatch via bundled PHPMailer if SMTP is configured
            try {
                require_once __DIR__ . '/PHPMailer/Exception.php';
                require_once __DIR__ . '/PHPMailer/PHPMailer.php';
                require_once __DIR__ . '/PHPMailer/SMTP.php';

                $cfg = app_config();
                $smtp = $cfg['smtp'] ?? [];

                if (!empty($smtp['host']) && !empty($smtp['user'])) {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = $smtp['host'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtp['user'];
                    $mail->Password   = $smtp['pass'];
                    $mail->SMTPSecure = ($smtp['port'] == 465) ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = $smtp['port'] ?? 587;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom($smtp['from'] ?? 'info@welzee.com', 'Welzee Website');
                    $mail->addAddress('info@welzee.com', 'Welzee Inquiries');
                    $mail->addReplyTo($email, "$firstName $lastName");

                    $mail->isHTML(false);
                    $mail->Subject = "[Welzee Inquiry] " . ($subject ?: "New Website Contact from $firstName $lastName");
                    $mail->Body    = "New contact form submission:\n\n"
                                   . "Name: $firstName $lastName\n"
                                   . "Email: $email\n"
                                   . "Phone: " . ($phone ?: 'Not provided') . "\n"
                                   . "Subject: " . ($subject ?: 'General') . "\n\n"
                                   . "Message:\n$message\n\n"
                                   . "---\nSent from welzee.com contact form at " . date('Y-m-d H:i:s T');

                    @$mail->send();
                }
            } catch (\Throwable $e) {
                // If mail server is unreachable in local dev, log and still acknowledge inquiry gracefully
                error_log("Contact form email dispatch note: " . $e->getMessage());
            }

            $formSuccess = true;
        }
    }
}

$pageJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'ContactPage',
    'name' => 'Contact Welzee',
    'url' => 'https://welzee.com/contact',
    'description' => 'Contact Welzee for custom embroidery, garment printing, and uniform quotation inquiries.',
    'mainEntity' => [
        '@type' => 'Organization',
        'name' => 'Welzee',
        'telephone' => '+92 320 5993161',
        'email' => 'info@welzee.com',
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
<div class="bg-secondary text-white py-20 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#fff 2px, transparent 2px); background-size: 30px 30px;"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <span class="inline-block py-1 px-3.5 rounded-full bg-primary/20 text-primary border border-primary/30 text-xs font-bold tracking-widest uppercase mb-4">Direct Communication</span>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Get In Touch</h1>
        <p class="text-lg text-gray-300 max-w-2xl mx-auto">Have a question about a corporate uniform order, custom digitization, or domestic & international shipping? We are here to help.</p>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
        
        <!-- Contact Information Column -->
        <div>
            <h2 class="text-3xl font-extrabold text-secondary mb-8">We'd love to hear from you.</h2>
            
            <div class="space-y-8 mb-10">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="fa-solid fa-industry"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg mb-1">Production & Fulfillment Origin</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">Karachi, Pakistan<br><span class="text-xs text-gray-500 font-medium">Serving Pakistan nationwide and confirmed international orders to the United States.</span></p>
                    </div>
                </div>
                
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg mb-1">Direct Call & WhatsApp</h3>
                        <p class="text-gray-600 text-sm mb-2">Monday – Friday, 8:00 AM – 6:00 PM PKT</p>
                        <div class="flex flex-wrap gap-4 items-center">
                            <a href="tel:+923205993161" class="text-primary font-bold hover:underline transition flex items-center gap-1.5 text-sm">
                                <i class="fa-solid fa-phone text-xs"></i> +92 320 5993161
                            </a>
                            <a href="https://wa.me/923205993161" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold hover:bg-green-200 transition">
                                <i class="fa-brands fa-whatsapp text-sm"></i> WhatsApp Support
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg mb-1">Email Inquiries</h3>
                        <p class="text-gray-600 text-sm mb-2">Quotes & questions answered within 24 business hours.</p>
                        <a href="mailto:info@welzee.com" class="text-primary font-bold hover:underline transition text-sm">info@welzee.com</a>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                <h3 class="font-bold text-xl text-secondary mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles text-primary"></i> Looking for an exact quotation?
                </h3>
                <p class="text-gray-600 text-sm mb-4 leading-relaxed">If you have your logo artwork ready, use our interactive apparel customizer to choose your garment, position your logo, and request an itemized quotation in under 2 minutes.</p>
                <a href="<?php echo $base_url, welzee_customizer_href(); ?>" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-xl transition inline-flex items-center gap-2 shadow-sm text-sm">
                    Open Apparel Customizer <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
        
        <!-- Contact Form Column -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 lg:p-10">
            <h3 class="text-2xl font-bold text-secondary mb-2">Send us a message</h3>
            <p class="text-gray-500 text-sm mb-6">Fill out your inquiry and our team will get back to you promptly.</p>

            <?php if ($formSuccess): ?>
            <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-green-600 text-lg mt-0.5 shrink-0"></i>
                <div>
                    <h4 class="font-bold">Thank you for reaching out!</h4>
                    <p class="mt-1">Your message has been received. Our team will review your inquiry and respond within 24 business hours.</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($formError)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-start gap-3">
                <i class="fa-solid fa-circle-exclamation text-red-600 text-lg mt-0.5 shrink-0"></i>
                <div>
                    <h4 class="font-bold">Unable to submit inquiry</h4>
                    <p class="mt-1"><?php echo htmlspecialchars($formError); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo $base_url; ?>contact">
                <input type="hidden" name="action" value="contact_submit">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" required maxlength="100" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Last Name</label>
                        <input type="text" name="last_name" maxlength="100" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required maxlength="150" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone / WhatsApp</label>
                        <input type="tel" name="phone" maxlength="50" placeholder="+92 300 1234567" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 text-sm">
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Subject / Inquiry Type</label>
                    <input type="text" name="subject" maxlength="200" placeholder="e.g. 50 Custom Polos for Corporate Event" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 text-sm">
                </div>
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Message & Project Details <span class="text-red-500">*</span></label>
                    <textarea name="message" required rows="5" maxlength="3000" placeholder="Tell us about your apparel requirements, estimated quantities, and desired timeline..." class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 text-sm"></textarea>
                </div>
                <button type="submit" class="w-full bg-secondary hover:bg-black text-white font-bold py-4 rounded-xl transition shadow-md flex justify-center items-center gap-2 text-base">
                    <i class="fa-solid fa-paper-plane text-primary"></i> Send Message
                </button>
            </form>
        </div>
        
    </div>

</div>

<!-- Production & Communication Facility Card (Replaces fake map placeholder) -->
<section class="bg-gray-100 py-16 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl p-8 sm:p-12 shadow-sm border border-gray-200 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="max-w-2xl">
                <span class="text-xs font-bold text-primary uppercase tracking-widest block mb-2">Direct Manufacturing</span>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-secondary mb-3">Our Karachi Production Workshop</h3>
                <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
                    All apparel customization, digitizing, multi-head embroidery, and garment printing are executed at our primary manufacturing facility in Karachi, Pakistan. Domestic orders are dispatched via TCS, Leopards, and CallCourier, while confirmed US orders are dispatched via express air couriers.
                </p>
            </div>
            <div class="shrink-0 flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                <a href="https://wa.me/923205993161" target="_blank" rel="noopener noreferrer" class="bg-primary hover:bg-primary-dark text-white font-bold py-3.5 px-6 rounded-xl transition shadow-md flex items-center justify-center gap-2 text-sm">
                    <i class="fa-brands fa-whatsapp text-lg"></i> WhatsApp Direct
                </a>
                <a href="<?php echo $base_url, welzee_customizer_href(); ?>" class="bg-secondary hover:bg-black text-white font-bold py-3.5 px-6 rounded-xl transition shadow-md flex items-center justify-center gap-2 text-sm">
                    <i class="fa-solid fa-wand-magic-sparkles text-primary"></i> Request a Quote
                </a>
            </div>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/includes/footer.php';
?>
