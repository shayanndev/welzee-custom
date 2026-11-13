<?php
// includes/header.php
// Shared head/navigation. Base URL, error handling, timezone etc. come from config/bootstrap.php
require_once __DIR__ . '/../config/bootstrap.php';
$base_url = app_base_url();

// Active-tab helper (extensionless)
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if ($currentPage == 'index') $currentPage = '';

// Canonical URL (real pages only; never for error responses)
if (!isset($canonicalUrl) || empty($canonicalUrl)) {
    $canonicalUrl = '';
    if (http_response_code() === 200) {
        $__path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $__basePath = (string)parse_url($base_url, PHP_URL_PATH);
        if ($__basePath !== '' && strpos($__path, $__basePath) === 0) {
            $__path = substr($__path, strlen($__basePath));
        }
        $__path = preg_replace('#(^|/)index(\.php)?$#', '$1', ltrim($__path, '/'));
        $__path = preg_replace('#\.php$#', '', $__path);
        $canonicalUrl = $base_url . $__path;
    }
}
?>
<?php
// Resolve title, description, and social metadata
$defaultTitle = 'Custom Embroidery & Printing Services | Welzee';
$defaultDescription = 'Welzee offers custom embroidery, garment printing, and personalized apparel for customers in Pakistan and the USA. Customize T-shirts, polos, hoodies, and caps.';
$defaultOgImage = $base_url . 'assets/images/og-image.jpg';

$resolvedTitle = $pageTitle ?? $defaultTitle;
if (isset($extraHead) && preg_match('#<title>(.*?)</title>#i', $extraHead, $matches)) {
    $resolvedTitle = $matches[1];
}

$resolvedDescription = $pageDescription ?? $defaultDescription;
if (isset($extraHead) && preg_match('#<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']#i', $extraHead, $matches)) {
    $resolvedDescription = $matches[1];
}

$resolvedOgType = $pageOgType ?? 'website';
$resolvedOgImage = $pageOgImage ?? $defaultOgImage;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($resolvedTitle); ?></title>
    <meta name="description" content="<?php echo e($resolvedDescription); ?>">
    <?php if ($canonicalUrl !== ''): ?>
    <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">
    <?php endif; ?>

    <!-- Open Graph / Social Media -->
    <meta property="og:site_name" content="Welzee">
    <meta property="og:type" content="<?php echo e($resolvedOgType); ?>">
    <meta property="og:title" content="<?php echo e($resolvedTitle); ?>">
    <meta property="og:description" content="<?php echo e($resolvedDescription); ?>">
    <?php if ($canonicalUrl !== ''): ?>
    <meta property="og:url" content="<?php echo e($canonicalUrl); ?>">
    <?php endif; ?>
    <meta property="og:image" content="<?php echo e($resolvedOgImage); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($resolvedTitle); ?>">
    <meta name="twitter:description" content="<?php echo e($resolvedDescription); ?>">
    <meta name="twitter:image" content="<?php echo e($resolvedOgImage); ?>">

    <!-- Multi-size Favicons & Touch Icons -->
    <link rel="icon" type="image/x-icon" href="<?php echo $base_url; ?>favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $base_url; ?>assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>apple-touch-icon.png">

    <!-- Schema.org JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Organization",
          "@id": "https://welzee.com/#organization",
          "name": "Welzee",
          "url": "https://welzee.com/",
          "logo": {
            "@type": "ImageObject",
            "@id": "https://welzee.com/#logo",
            "url": "https://welzee.com/assets/images/favicon.png",
            "caption": "Welzee"
          },
          "email": "info@welzee.com",
          "telephone": "+92 320 5993161",
          "address": {
            "@type": "PostalAddress",
            "addressCountry": "PK"
          },
          "areaServed": [
            { "@type": "Country", "name": "Pakistan" },
            { "@type": "Country", "name": "United States" }
          ]
        },
        {
          "@type": "WebSite",
          "@id": "https://welzee.com/#website",
          "url": "https://welzee.com/",
          "name": "Welzee",
          "description": "Custom embroidery and garment printing in Pakistan and USA",
          "publisher": { "@id": "https://welzee.com/#organization" },
          "inLanguage": "en"
        }
      ]
    }
    </script>
    <?php if (isset($pageJsonLd) && !empty($pageJsonLd)): ?>
    <script type="application/ld+json">
    <?php echo is_string($pageJsonLd) ? $pageJsonLd : json_encode($pageJsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    <?php endif; ?>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    
    <!-- CSS Theme Variables & Fallbacks -->
    <style>
        :root {
            --color-primary: #22c55e;
            --color-primary-dark: #16a34a;
            --color-primary-light: #dcfce7;
            --color-secondary: #0f172a;
            --color-secondary-light: #1e293b;
            --color-secondary-dark: #090d16;
            --bg-body: #f8fafc;
            --bg-white: #ffffff;
        }
        body {
            background-color: var(--bg-body);
        }
    </style>

    <!-- Dynamic Tailwind config based on CSS Variables with fallbacks -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: 'var(--color-primary, #22c55e)',
                        'primary-dark': 'var(--color-primary-dark, #16a34a)',
                        'primary-light': 'var(--color-primary-light, #dcfce7)',
                        secondary: 'var(--color-secondary, #0f172a)',
                        'secondary-light': 'var(--color-secondary-light, #1e293b)',
                        'secondary-dark': 'var(--color-secondary-dark, #090d16)',
                        brand: {
                            50: 'var(--color-primary-light, #dcfce7)',
                            100: 'var(--color-primary-light, #dcfce7)',
                            500: 'var(--color-primary, #22c55e)',
                            600: 'var(--color-primary-dark, #16a34a)',
                            700: 'var(--color-primary-dark, #16a34a)'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Extra head elements explicitly passed from the invoking page -->
    <?php if (isset($extraHead)) echo $extraHead; ?>

    <!-- Global Currency State & Locater -->
    <script>
        window.Welzee = window.Welzee || {};
        window.Eilotu = window.Welzee; // Compatibility alias
        
        function initializeCurrency() {
            // 1. Check explicit URL parameter (?currency=PKR or ?currency=USD) - essential for GMC landing pages
            const urlParams = new URLSearchParams(window.location.search);
            const queryCur = (urlParams.get('currency') || urlParams.get('cur') || '').toUpperCase();
            
            let selectedCurrency = null;
            if (queryCur === 'PKR' || queryCur === 'USD') {
                selectedCurrency = queryCur;
                try { localStorage.setItem('welzee_currency', selectedCurrency); } catch(e){}
            }
            
            // 2. Check saved user preference
            if (!selectedCurrency) {
                try { selectedCurrency = localStorage.getItem('welzee_currency'); } catch(e){}
            }
            
            // 3. Fallback based on timezone (instant, offline, reliable)
            if (!selectedCurrency || (selectedCurrency !== 'PKR' && selectedCurrency !== 'USD')) {
                try {
                    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
                    selectedCurrency = (tz.includes('Karachi') || tz.includes('Pakistan')) ? 'PKR' : 'USD';
                } catch (e) {
                    selectedCurrency = 'PKR';
                }
                try { localStorage.setItem('welzee_currency', selectedCurrency); } catch(e){}
            }
            
            // Set global state
            window.Welzee.currency = selectedCurrency;
            window.Welzee.exchangeRate = 280; // Configurable indicative rate: 1 USD ~ 280 PKR
            
            // Update UI selector if present
            const selector = document.getElementById('currencySelector');
            if (selector) selector.value = selectedCurrency;

            document.dispatchEvent(new Event('CurrencyReady'));
        }

        function changeCurrency(newCurrency) {
            try { localStorage.setItem('welzee_currency', newCurrency); } catch(e){}
            window.Welzee.currency = newCurrency;
            document.dispatchEvent(new Event('CurrencyChanged'));
        }

        // Initialize immediately
        initializeCurrency();
    </script>
</head>
<body class="text-gray-800 antialiased min-h-screen flex flex-col bg-gray-50">

    <!-- Topbar (Contact / Info Strip) -->
    <div class="bg-secondary text-gray-300 text-xs py-2 px-4 sm:px-6 lg:px-8 flex justify-between items-center border-b border-white/10 z-50 relative">
        <div class="flex items-center gap-4">
            <span class="hidden sm:inline-block"><i class="fa-solid fa-location-dot text-primary mr-1"></i> Dispatched from Pakistan • Nationwide & USA Delivery</span>
            <span class="inline-block sm:hidden"><i class="fa-solid fa-location-dot text-primary mr-1"></i> Dispatched from Pakistan</span>
        </div>
        <div class="flex items-center gap-4 font-medium">
            <!-- Currency Switcher -->
            <div class="flex items-center border-r border-gray-600 pr-4">
                <i class="fa-solid fa-earth-americas text-primary mr-2"></i>
                <select id="currencySelector" onchange="changeCurrency(this.value)" class="bg-transparent text-gray-300 font-bold focus:outline-none cursor-pointer hover:text-white transition">
                    <option value="PKR" class="text-secondary">PKR (Rs)</option>
                    <option value="USD" class="text-secondary">USD ($)</option>
                </select>
            </div>
            <a href="tel:+923205993161" class="hover:text-primary transition"><i class="fa-solid fa-phone mr-1"></i> +92 320 5993161</a>
        </div>
    </div>

    <!-- Main Header / Navbar -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            <!-- Logo area -->
            <a href="<?php echo $base_url; ?>" class="flex items-center gap-3 group w-1/4">
                <div class="w-12 h-12 bg-primary text-white flex justify-center items-center rounded-xl shadow-lg shadow-primary/30 group-hover:bg-primary-dark transition duration-300">
                    <i class="fa-solid fa-shirt text-2xl"></i>
                </div>
                <div class="flex flex-col">
                    <span class="font-extrabold text-3xl tracking-tighter text-secondary leading-none">Welzee</span>
                    <span class="text-[10px] text-gray-500 font-bold tracking-widest uppercase mt-1">Embroidery & Print</span>
                </div>
            </a>
            
            <!-- Desktop Navigation (Centered) -->
            <nav class="hidden lg:flex flex-grow justify-center items-center gap-8 text-[15px] font-semibold text-secondary relative h-full">
                <a href="<?php echo $base_url; ?>" class="hover:text-primary transition flex items-center h-full <?php echo ($currentPage == '') ? 'text-primary border-b-2 border-primary' : ''; ?>">Home</a>
                <a href="<?php echo $base_url; ?>services" class="hover:text-primary transition flex items-center h-full <?php echo ($currentPage == 'services') ? 'text-primary border-b-2 border-primary' : ''; ?>">Services</a>
                <a href="<?php echo $base_url; ?>about" class="hover:text-primary transition flex items-center h-full <?php echo ($currentPage == 'about') ? 'text-primary border-b-2 border-primary' : ''; ?>">About Us</a>
                <a href="<?php echo $base_url; ?>blog" class="hover:text-primary transition flex items-center h-full <?php echo ($currentPage == 'blog') ? 'text-primary border-b-2 border-primary' : ''; ?>">Blog</a>
                <a href="<?php echo $base_url; ?>contact" class="hover:text-primary transition flex items-center h-full <?php echo ($currentPage == 'contact') ? 'text-primary border-b-2 border-primary' : ''; ?>">Contact</a>
            </nav>

            <!-- Call to Action Container -->
            <div class="hidden lg:flex items-center justify-end w-1/4">
                <a href="<?php echo $base_url; ?>custom_order" class="bg-primary hover:bg-primary-dark text-white px-6 py-2.5 rounded-full transition shadow-lg shadow-primary/30 flex items-center gap-2 transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> 
                    <span>Customizer</span>
                </a>
            </div>
            
            <!-- Mobile Menu Button -->
            <div class="flex items-center gap-4 lg:hidden">
                <a href="<?php echo $base_url; ?>custom_order" class="bg-primary text-white px-3 py-1.5 rounded text-sm font-semibold shadow-md">
                    Design
                </a>
                <button class="text-secondary hover:text-primary transition text-2xl focus:outline-none" id="mobileMenuBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
            
        </div>

        <!-- Mobile Navigation Menu (Hidden by default) -->
        <div class="lg:hidden hidden bg-white border-t border-gray-100 absolute w-full shadow-lg" id="mobileMenu">
            <div class="flex flex-col px-4 pt-2 pb-6 space-y-1">
                <a href="<?php echo $base_url; ?>" class="block px-3 py-3 rounded-md text-base font-medium text-secondary hover:bg-gray-50 hover:text-primary">Home</a>
                <a href="<?php echo $base_url; ?>services" class="block px-3 py-3 rounded-md text-base font-medium text-secondary hover:bg-gray-50 hover:text-primary">Services</a>
                <a href="<?php echo $base_url; ?>about" class="block px-3 py-3 rounded-md text-base font-medium text-secondary hover:bg-gray-50 hover:text-primary">About Us</a>
                <a href="<?php echo $base_url; ?>blog" class="block px-3 py-3 rounded-md text-base font-medium text-secondary hover:bg-gray-50 hover:text-primary">Blog</a>
                <a href="<?php echo $base_url; ?>contact" class="block px-3 py-3 rounded-md text-base font-medium text-secondary hover:bg-gray-50 hover:text-primary">Contact</a>
            </div>
        </div>
    </header>

    <script>
        // Simple Mobile Menu Toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            var menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        });
    </script>

    <!-- Main content begins after header (flex-grow pushes footer to bottom) -->
    <main class="flex-grow w-full">
