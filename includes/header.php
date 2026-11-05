<?php
if(session_status() === PHP_SESSION_NONE) session_start();
// includes/header.php
// Ensures the absolute path for assets and internal links is correctly mapped in any environment
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, 'localhost:') === 0 || strpos($host, '127.0.0.1:') === 0) {
    // Dynamic subfolder detection for local development (supports /welzee/, /eilotu/, /customshirtsgen/, or root)
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $segments = array_values(array_filter(explode('/', trim($scriptDir, '/'))));
    $folder = !empty($segments[0]) ? $segments[0] : '';
    $base_path = $folder !== '' ? '/' . $folder . '/' : '/';
    $base_url = "$protocol://$host$base_path";
} else {
    // Live production domain (root-level)
    $base_url = "$protocol://$host/";
}

// Clean up current page for active tab checking without .php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if ($currentPage == 'index') $currentPage = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Embroidery & Printing Services | Welzee</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon Icon -->
    <link rel="icon" href="<?php echo $base_url; ?>assets/images/favicon.png" type="image/png">
    
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
        // Set up the global scope Welzee store (with backward compatibility)
        window.Welzee = window.Welzee || {};
        window.Eilotu = window.Welzee; // Compatibility alias
        
        async function initializeCurrency() {
            let savedCurrency = localStorage.getItem('welzee_currency') || localStorage.getItem('eilotu_currency');
            
            if (!savedCurrency) {
                try {
                    // Fetch location to define default currency.
                    const response = await fetch('https://ipapi.co/json/');
                    const data = await response.json();
                    savedCurrency = (data.country_code === 'PK') ? 'PKR' : 'USD';
                } catch (e) {
                    // Fallback to PKR if API fails
                    savedCurrency = 'PKR';
                }
                localStorage.setItem('welzee_currency', savedCurrency);
            }
            
            // Set global state
            window.Welzee.currency = savedCurrency;
            window.Welzee.exchangeRate = 280; // 1 USD = 280 PKR (adjustable)
            
            // Update UI
            const selector = document.getElementById('currencySelector');
            if (selector) selector.value = savedCurrency;

            // Trigger event indicating currency is ready for any listening components
            document.dispatchEvent(new Event('CurrencyReady'));
        }

        function changeCurrency(newCurrency) {
            localStorage.setItem('welzee_currency', newCurrency);
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
            <span class="hidden sm:inline-block"><i class="fa-solid fa-truck-fast text-primary mr-1"></i> Free Shipping worldwide</span>
            <span class="inline-block sm:hidden"><i class="fa-solid fa-truck-fast text-primary"></i> Free Shipping</span>
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
