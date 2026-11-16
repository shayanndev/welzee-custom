<?php
// faq.php - Frequently Asked Questions
require_once __DIR__ . '/config/bootstrap.php';
$base_url = app_base_url();

$pageTitle = 'Frequently Asked Questions About Custom Embroidery | Welzee';
$pageDescription = 'Find answers about custom embroidery, garment printing, artwork, pricing, production, and shipping from Pakistan to domestic and international customers.';
$canonicalUrl = 'https://welzee.com/faq';

// Define FAQ data for structured rendering and Schema.org FAQPage generation
$faqSections = [
    [
        'category' => 'Ordering & Quotations',
        'icon' => 'fa-clipboard-list',
        'questions' => [
            [
                'q' => 'How does the custom apparel quotation and ordering process work?',
                'a' => 'Our ordering process is quotation-based:
<ol class="list-decimal pl-5 mt-2 space-y-1.5">
    <li><strong>Submit Your Requirements:</strong> Use our <a href="' . $base_url . 'custom_order" class="text-primary font-semibold hover:underline">online customizer</a> or <a href="' . $base_url . 'contact" class="text-primary font-semibold hover:underline">contact form</a> to upload your logo and specify your garment type, quantities, and placement preferences.</li>
    <li><strong>Receive Digital Mockup & Quote:</strong> Our team reviews your artwork and emails an itemized quotation with an estimated production schedule and digital visual preview.</li>
    <li><strong>Review & Approve:</strong> You verify the thread colors, sizing, stitch placement, and quote details.</li>
    <li><strong>Production & Dispatch:</strong> Once approved and deposit cleared, your apparel is stitched or printed at our Karachi workshop and dispatched with door-to-door tracking.</li>
</ol>'
            ],
            [
                'q' => 'How quickly will I receive my quote after submitting an inquiry?',
                'a' => 'We aim to review artwork submissions and respond with an itemized quote and initial guidance within <strong>24 business hours</strong> (Monday through Friday). If your project involves intricate artwork or multi-garment corporate uniform packages, we may contact you on WhatsApp or email to clarify stitch specs before sending the final pricing.'
            ],
            [
                'q' => 'Can I order mixed garment sizes or colors in a single order?',
                'a' => 'Yes. As long as the embroidered or printed logo design, size, and thread colors remain identical, you can combine different garment sizes (e.g., S, M, L, XL, XXL) and garment body colors within your order to reach volume tier pricing.'
            ],
            [
                'q' => 'Can I supply my own blank garments for embroidery or printing (CMT)?',
                'a' => 'Yes. In addition to supplying high-grade wholesale blank garments, we offer contract Cut, Make & Trim (CMT) garment embellishment services for brands, schools, and corporate clients in Pakistan who provide their own blanks. A fabric sample or test piece is recommended beforehand to ensure stitch tension and needle compatibility.'
            ]
        ]
    ],
    [
        'category' => 'Artwork, Digitizing & Stitch Files',
        'icon' => 'fa-bezier-curve',
        'questions' => [
            [
                'q' => 'What artwork file formats should I provide for embroidery or printing?',
                'a' => 'For the sharpest detail and fastest processing, vector artwork formats are strongly preferred:
<ul class="list-disc pl-5 mt-2 space-y-1.5">
    <li><strong>Preferred Vector Formats:</strong> Adobe Illustrator (.AI), Encapsulated PostScript (.EPS), Scalable Vector Graphics (.SVG), or high-resolution vector PDF (.PDF).</li>
    <li><strong>Accepted High-Resolution Raster Formats:</strong> PNG or JPEG files at 300 DPI or higher with clear, sharp contrast.</li>
</ul>
If you already have a machine-ready embroidery file (.DST or .PES) digitized for your intended fabric, you can submit that directly for evaluation.'
            ],
            [
                'q' => 'What is embroidery digitizing, and why is it necessary?',
                'a' => 'Embroidery machines cannot stitch standard image files (like JPG or PNG) directly. Digitizing is the skilled engineering process of converting two-dimensional artwork into a specialized machine file (such as a .DST file). A digitizer manually maps out stitch directions, stitch types (satin, fill, running), thread underlay density, pull compensation, and needle sequences tailored to the stretch and thickness of the specific fabric being stitched.'
            ],
            [
                'q' => 'Do you charge a setup or digitizing fee for embroidery?',
                'a' => 'New logo designs require a one-time digitizing fee to cover the technician time required to engineer your stitch file. Once your logo is successfully digitized and approved, your production stitch file is permanently archived on our secure servers. <strong>You will never be charged a digitizing setup fee on future reorders</strong> using that same artwork.'
            ],
            [
                'q' => 'Can small lettering, fine gradients, or photographic logos be embroidered?',
                'a' => 'Because embroidery uses physical threads, lines have physical thickness (typically a minimum of 1 mm line thickness and 4.5 mm capital letter height for legible text). Very fine gradients, drop shadows, and photographic details do not translate cleanly into thread. In those cases, we will either propose an embroidery-friendly simplification of your logo or recommend our vibrant Direct-to-Garment (DTG) or heat transfer printing.'
            ]
        ]
    ],
    [
        'category' => 'Embroidery vs. Printing',
        'icon' => 'fa-shirt',
        'questions' => [
            [
                'q' => 'When should I choose custom embroidery over garment printing?',
                'a' => '<strong>Embroidery</strong> is ideal for corporate polo shirts, oxford dress shirts, structured caps, heavy fleece hoodies, and outerwear. It provides a premium, dimensional, textured feel that resists industrial washing up to 95°C and lasts for years. <strong>Printing (DTG / Flex)</strong> is preferable for large graphic designs (such as full-back prints), lightweight summer T-shirts, photographic art, multi-color gradients, or cost-conscious high-volume promotional runs.'
            ],
            [
                'q' => 'What garment printing methods do you utilize?',
                'a' => 'We utilize Direct-to-Garment (DTG) digital printing, high-resolution direct-to-film / flex transfers, and screen printing depending on order volume, artwork complexity, and fabric composition. For detailed technical comparisons, read our guide on <a href="' . $base_url . 'blog/dtg-vs-screen-print" class="text-primary font-semibold hover:underline">DTG vs Screen Printing for Custom T-Shirts</a>.'
            ],
            [
                'q' => 'How well do your embroidered logos and prints hold up to washing?',
                'a' => 'Our embroidery is stitched with colorfast, bleach-resistant poly-neon threads that will typically outlast the garment itself under normal domestic laundering. Our printed garments utilize premium wash-tested inks and professional heat-press curing to prevent premature peeling, fading, or cracking when washed inside-out in cold or warm water.'
            ]
        ]
    ],
    [
        'category' => 'Minimum Quantities & Samples',
        'icon' => 'fa-cubes',
        'questions' => [
            [
                'q' => 'What is the minimum order quantity (MOQ)?',
                'a' => 'To keep production viable and offset machine setup and hooping time, our standard minimum order quantity for custom embroidery is typically <strong>10 pieces per design</strong>. For direct-to-garment (DTG) printing, smaller flexible quantities are accommodated. Tiered unit discounts apply as order volume increases (e.g., 25, 50, 100, 250+ units).'
            ],
            [
                'q' => 'Can I receive a sample before committing to a large bulk order?',
                'a' => 'Yes. Every order receives a high-resolution digital visual mockup showing exact dimensions, placement, and thread/ink colors for your written sign-off. For confirmed bulk corporate orders, we can also provide a photograph of a physical sewn stitch proof on fabric scrap or produce a pre-production sample unit before running full batch production.'
            ]
        ]
    ],
    [
        'category' => 'Pricing, Currencies & Payment',
        'icon' => 'fa-money-bill-wave',
        'questions' => [
            [
                'q' => 'How are custom apparel prices calculated?',
                'a' => 'Because custom apparel is tailored to each client, quotation pricing depends on four transparent variables:
<ul class="list-disc pl-5 mt-2 space-y-1.5">
    <li><strong>Garment Blank Selection:</strong> Base price of the chosen T-shirt, polo, hoodie, or cap.</li>
    <li><strong>Decoration Complexity:</strong> Total embroidery stitch count (or print surface dimensions).</li>
    <li><strong>Decoration Placements:</strong> Number of locations (e.g., left chest only vs. left chest + right sleeve + back).</li>
    <li><strong>Total Order Volume:</strong> Larger order quantities qualify for substantial tier discounts.</li>
</ul>'
            ],
            [
                'q' => 'What payment methods do you accept for orders in Pakistan?',
                'a' => 'For clients within Pakistan, we accept Direct Online Bank Transfer (IBFT via Raast or standard interbank wire), JazzCash, and Easypaisa. Standard corporate order terms require a 50% deposit upon digital proof approval, with the remaining balance settled upon dispatch.'
            ],
            [
                'q' => 'What payment methods do you accept for US and international orders?',
                'a' => 'For international clients in the United States and abroad, invoices are payable in USD via major international credit and debit cards (Visa, MasterCard, American Express) or international commercial wire transfers. Invoices are issued with transparent DAP freight and tracking included.'
            ],
            [
                'q' => 'Why does Welzee offer both PKR and USD currencies?',
                'a' => 'Welzee is a Pakistan-based manufacturer that proudly serves both our primary domestic market in Pakistan and international corporate and individual clients in the United States and worldwide. Our currency selector allows you to view indicative pricing and receive quotation estimates in your preferred currency.'
            ]
        ]
    ],
    [
        'category' => 'Production, Shipping & Customs',
        'icon' => 'fa-truck-fast',
        'questions' => [
            [
                'q' => 'How long does standard production take?',
                'a' => 'Standard production takes <strong>5 to 7 business days</strong> following your written approval of the digital artwork proof and deposit clearance. High-volume corporate runs (500+ units) or specialized custom dyeing schedules are confirmed individually during quotation.'
            ],
            [
                'q' => 'How does domestic shipping within Pakistan work?',
                'a' => 'Domestic orders are dispatched directly from our Karachi workshop via reliable courier partners (including TCS, Leopards, and CallCourier). Delivery typically takes <strong>2 to 4 business days</strong> following completion of production, with full door-to-door tracking provided via WhatsApp and email.'
            ],
            [
                'q' => 'How does international shipping to the United States work?',
                'a' => 'All products originate and are manufactured at our facility in Pakistan. <em>Welzee does not operate a US warehouse.</em> Confirmed international orders to the US are dispatched via tracked express air couriers. Estimated airfreight transit times and shipping charges are quoted transparently in USD prior to order confirmation.'
            ],
            [
                'q' => 'Who is responsible for US customs duties, import tariffs, or taxes?',
                'a' => 'International orders are dispatched under Delivered At Place (DAP / DDU) terms. Any customs duties, import tariffs, clearance fees, or state/local taxes assessed by destination authorities (such as US Customs and Border Protection) are the responsibility of the recipient/importer. For full details, review our <a href="' . $base_url . 'shipping" class="text-primary font-semibold hover:underline">Shipping Policy</a>.'
            ]
        ]
    ],
    [
        'category' => 'Returns, Replacements & Support',
        'icon' => 'fa-shield-halved',
        'questions' => [
            [
                'q' => 'What is your policy on returns and replacements for custom apparel?',
                'a' => 'Because customized garments are permanently branded with your specific corporate logo or artwork, orders cannot be returned or refunded for buyer\'s remorse or sizing selection errors once production is approved. However, if your order contains an objective manufacturing defect, stitch flaw, garment damage, or material deviation from your signed-off digital proof, Welzee will replace or reprint the affected garments free of charge upon photographic verification within <strong>7 days of delivery</strong>.'
            ],
            [
                'q' => 'How can I get in touch with Welzee support about my project?',
                'a' => 'You can reach our team via WhatsApp and telephone at <strong>+92 320 5993161</strong> (Monday–Friday, 8:00 AM – 6:00 PM PKT), email us at <a href="mailto:info@welzee.com" class="text-primary font-semibold hover:underline">info@welzee.com</a>, or submit your project details directly through our <a href="' . $base_url . 'contact" class="text-primary font-semibold hover:underline">Contact page</a>.'
            ]
        ]
    ]
];

// Build Schema.org FAQPage structured data
$schemaQuestions = [];
foreach ($faqSections as $sec) {
    foreach ($sec['questions'] as $qa) {
        $schemaQuestions[] = [
            '@type' => 'Question',
            'name' => $qa['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => strip_tags($qa['a'])
            ]
        ];
    }
}

$pageJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $schemaQuestions
];

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Header -->
<div class="bg-secondary text-white py-16 border-b-4 border-primary">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block py-1 px-3.5 rounded-full bg-primary/20 text-primary border border-primary/30 text-xs font-bold tracking-widest uppercase mb-4">Support & Guidance</span>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Frequently Asked Questions</h1>
        <p class="text-base sm:text-lg text-gray-300 max-w-2xl mx-auto">Everything you need to know about custom embroidery, garment printing, digitizing, quotation pricing, and domestic & international delivery.</p>
    </div>
</div>

<!-- Main FAQ Container -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- Category Nav Pills -->
    <div class="flex flex-wrap gap-2 justify-center mb-12">
        <?php foreach ($faqSections as $idx => $sec): ?>
        <a href="#cat-<?php echo $idx; ?>" class="px-4 py-2 rounded-full text-xs sm:text-sm font-semibold bg-white border border-gray-200 text-gray-700 hover:border-primary hover:text-primary transition shadow-sm flex items-center gap-1.5">
            <i class="fa-solid <?php echo htmlspecialchars($sec['icon']); ?> text-primary text-xs"></i>
            <span><?php echo htmlspecialchars($sec['category']); ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Accordion Sections -->
    <div class="space-y-12">
        <?php foreach ($faqSections as $idx => $sec): ?>
        <section id="cat-<?php echo $idx; ?>" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6 pb-3 border-b-2 border-primary/30">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid <?php echo htmlspecialchars($sec['icon']); ?>"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-secondary tracking-tight"><?php echo htmlspecialchars($sec['category']); ?></h2>
            </div>

            <div class="space-y-4">
                <?php foreach ($sec['questions'] as $qIdx => $qa): 
                    $itemId = "faq-{$idx}-{$qIdx}";
                ?>
                <div class="bg-white rounded-xl border border-gray-200/80 shadow-sm overflow-hidden transition duration-200 hover:border-primary/40">
                    <button type="button" 
                            class="faq-toggle w-full text-left p-5 sm:p-6 flex justify-between items-center gap-4 focus:outline-none focus:bg-gray-50 transition" 
                            aria-expanded="false" 
                            aria-controls="<?php echo $itemId; ?>"
                            onclick="toggleFaq('<?php echo $itemId; ?>', this)">
                        <span class="font-bold text-base sm:text-lg text-secondary leading-snug"><?php echo htmlspecialchars($qa['q']); ?></span>
                        <span class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center shrink-0 text-gray-500 transition-transform duration-300 faq-icon">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </span>
                    </button>
                    <div id="<?php echo $itemId; ?>" 
                         class="faq-content hidden px-5 sm:px-6 pb-6 text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                        <?php echo $qa['a']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>

    <!-- Contact & Customizer CTA Card -->
    <div class="mt-16 bg-gradient-to-br from-secondary to-secondary-light text-white p-8 sm:p-10 rounded-2xl shadow-xl text-center relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-primary/20 rounded-full blur-2xl pointer-events-none"></div>
        <h3 class="text-2xl sm:text-3xl font-extrabold mb-3">Have a specific custom project in mind?</h3>
        <p class="text-gray-300 text-sm sm:text-base max-w-xl mx-auto mb-8">Send your logo artwork and project details through our interactive customizer, or speak directly with our team on WhatsApp.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?php echo $base_url; ?>custom_order" class="bg-primary hover:bg-primary-dark text-white font-bold py-3.5 px-8 rounded-xl transition shadow-lg flex items-center justify-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Open Customizer
            </a>
            <a href="https://wa.me/923205993161" target="_blank" rel="noopener noreferrer" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold py-3.5 px-8 rounded-xl transition flex items-center justify-center gap-2">
                <i class="fa-brands fa-whatsapp text-green-400 text-lg"></i> Chat on WhatsApp
            </a>
        </div>
    </div>

</div>

<!-- Vanilla Accordion Interaction -->
<script>
function toggleFaq(id, btn) {
    const content = document.getElementById(id);
    const icon = btn.querySelector('.faq-icon');
    const isExpanded = btn.getAttribute('aria-expanded') === 'true';
    
    if (isExpanded) {
        content.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
        if (icon) icon.classList.remove('rotate-180', 'bg-primary/10', 'text-primary');
    } else {
        content.classList.remove('hidden');
        btn.setAttribute('aria-expanded', 'true');
        if (icon) icon.classList.add('rotate-180', 'bg-primary/10', 'text-primary');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
