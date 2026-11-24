<?php
// custom_order_3d.php
// 3D Apparel Customizer for Welzee (Three.js r162 + Fabric.js live decal projection).
// Standalone alternative to custom_order.php; existing 2D page remains untouched.

header('X-Robots-Tag: noindex, nofollow');
require_once __DIR__ . '/config/bootstrap.php';
$csrfToken = csrf_token();

$pageTitle = 'Design in 3D: Custom Apparel Studio | Welzee';
$pageDescription = 'Experience Welzee\'s interactive 3D apparel studio. Rotate garments in real-time, customize fabrics, and place custom embroidery or prints with instant 3D preview.';

$extraHead = '
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="' . app_base_url() . 'assets/css/3d.css?v=1.1.0">
    <!-- Native ES Module Import Map for Self-Hosted Three.js r162 (Minified) -->
    <script type="importmap">
    {
      "imports": {
        "three": "' . app_base_url() . 'assets/js/3d/lib/three-r162/three.module.min.js",
        "three/addons/": "' . app_base_url() . 'assets/js/3d/lib/three-r162/addons/"
      }
    }
    </script>
';
include __DIR__ . '/includes/header.php';
?>

<div class="customizer-3d-container max-w-full mx-auto w-full px-4 sm:px-6 lg:px-8 py-10">

    <div class="customizer-3d-header mb-8 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-brand-50 border border-brand-200 rounded-full text-xs font-semibold text-brand-700 mb-3">
            <i class="fa-solid fa-cube"></i> Interactive 3D Studio (Beta)
        </div>
        <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 mb-2">Design Your Custom Apparel in 3D</h1>
        <p class="text-lg text-gray-500 max-w-2xl mx-auto">Rotate and inspect your garment in full 3D, customize embroidery or print placement, and request a personalized wholesale quote.</p>
        <p class="mt-3 text-sm text-gray-500">
            Prefer the simple designer?
            <a href="<?php echo $base_url; ?>custom_order" class="js-classic-link font-semibold text-brand-700 underline hover:text-brand-800">Classic view</a>
        </p>
    </div>

    <!-- Fallback Notice for unsupported browsers (Hidden by default) -->
    <div id="webglFallbackAlert" class="hidden mb-8 max-w-4xl mx-auto">
        <div class="webgl-fallback-card">
            <i class="fa-solid fa-triangle-exclamation text-3xl text-amber-500 mb-3"></i>
            <h3 class="text-xl font-bold text-gray-900 mb-2">3D Hardware Acceleration Unavailable</h3>
            <p class="text-sm text-gray-600 mb-4 max-w-lg mx-auto">Your current browser or device does not have WebGL enabled or has data-saving active. You can use our classic 2D apparel designer with full functionality.</p>
            <a href="<?php echo $base_url; ?>custom_order" class="js-classic-link inline-flex items-center gap-2 px-6 py-3 bg-brand-600 text-white font-bold rounded-xl hover:bg-brand-700 transition">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Switch to Standard 2D Customizer
            </a>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">

        <!-- 3D Viewport & Artwork Studio (Left) -->
        <div class="w-full lg:w-7/12 flex flex-col items-center">
            
            <!-- 3D Canvas Viewport -->
            <div class="viewport-3d-wrapper relative overflow-hidden" id="viewport3d">
                <!-- Static Instant Poster for Initial LCP -->
                <div id="posterContainer" class="absolute inset-0 flex items-center justify-center transition-opacity duration-500 z-10 pointer-events-none">
                    <div class="relative w-full h-full flex items-center justify-center p-4">
                        <img id="viewportPoster"
                             src="<?php echo $base_url; ?>assets/images/products/tshirt.png"
                             alt="Custom Apparel Preview"
                             width="480" height="480"
                             class="max-h-[85%] max-w-[85%] object-contain"
                             fetchpriority="high" />
                        <div id="posterColorOverlay" class="absolute inset-0 mix-blend-multiply opacity-20 pointer-events-none transition-colors duration-200"></div>
                    </div>
                </div>

                <!-- Manual 3D start: Save-Data visitors, or visitors who chose the classic view before -->
                <div id="lowSpecOverlay" class="hidden absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/50 backdrop-blur-xs transition-opacity duration-300">
                    <button type="button" id="btnExplore3D" class="px-6 py-3 bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm rounded-full shadow-lg flex items-center space-x-2 transition transform active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-cube text-base"></i>
                        <span id="btnExplore3DLabel">Explore in 3D</span>
                    </button>
                    <span id="overlayNote" class="text-xs text-gray-600 mt-2 font-medium">Interactive 360° Studio</span>
                    <a id="overlayClassicLink" href="<?php echo $base_url; ?>custom_order" class="js-classic-link hidden mt-3 text-sm font-semibold text-brand-700 underline hover:text-brand-800">Open the classic view</a>
                </div>

                <!-- Slow / failed 3D: non-blocking way out to the classic customizer -->
                <div id="slow3dBanner" role="status" class="hidden absolute left-3 right-3 bottom-3 z-30 flex items-center justify-between gap-3 bg-white/95 border border-amber-200 shadow-md rounded-xl px-4 py-2.5 text-sm">
                    <span class="text-gray-700"><i class="fa-solid fa-hourglass-half text-amber-500 mr-1"></i> Taking long?
                        <a href="<?php echo $base_url; ?>custom_order" class="js-classic-link font-semibold text-brand-700 underline hover:text-brand-800">Switch to the classic view</a>
                    </span>
                    <button type="button" id="slow3dDismiss" class="text-gray-400 hover:text-gray-600 px-1" aria-label="Dismiss">&times;</button>
                </div>

                <!-- Loading overlay -->
                <div id="viewport-loader" class="hidden absolute inset-0 bg-white/85 z-20 flex flex-col items-center justify-center rounded-2xl transition duration-300">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-brand-500 mb-3"></i>
                    <span class="text-sm font-semibold text-gray-700">Loading 3D Studio...</span>
                    <span class="text-xs text-gray-400 mt-1">Preparing interactive fabric view</span>
                </div>

                <!-- Interaction Hint -->
                <div class="viewport-hint hidden sm:flex opacity-0 transition-opacity duration-300" id="viewportHint">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <span>Drag to rotate • Scroll to zoom • Double-click to reset</span>
                </div>

                <!-- Viewport Overlay Quick Action -->
                <div class="absolute top-4 right-4 z-10 flex gap-2">
                    <button type="button" onclick="window.apparelViewer && window.apparelViewer.resetCamera()" title="Reset Camera View"
                        class="w-9 h-9 bg-white/90 backdrop-blur-md rounded-full border border-gray-200 shadow-sm flex items-center justify-center text-gray-600 hover:text-brand-600 hover:bg-white transition">
                        <i class="fa-solid fa-crosshairs text-xs"></i>
                    </button>
                    <button type="button" onclick="window.apparelViewer && window.apparelViewer.toggleAutoRotate()" title="Toggle Auto-Rotation"
                        class="w-9 h-9 bg-white/90 backdrop-blur-md rounded-full border border-gray-200 shadow-sm flex items-center justify-center text-gray-600 hover:text-brand-600 hover:bg-white transition">
                        <i class="fa-solid fa-play text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Visible 2D Artwork Editor (Guides text and logo placement onto 3D garment) -->
            <div class="artwork-editor-wrapper">
                <div class="flex items-center justify-between mb-3 border-b border-gray-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-palette text-brand-600"></i>
                        <span class="text-sm font-bold text-gray-800">Print & Embroidery Area Placement</span>
                    </div>
                    <span id="artworkProductBadge" class="text-[11px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded">TSHIRT</span>
                </div>
                <!-- Print side switch (buttons built by order-state.js from the product's print zones) -->
                <div class="side-switch" id="sideSwitch" role="group" aria-label="Print side"></div>
                <p class="text-xs text-gray-500 mb-3">Choose a side, then add text or upload a logo inside the print area below. Any edit reflects instantly on the 3D model above.</p>

                <div class="artwork-canvas-container" id="artworkContainer">
                    <div class="artwork-guide-boundary">
                        <span>Print Area</span>
                    </div>
                    <canvas id="apparelCanvas" width="340" height="280"></canvas>
                </div>

                <!-- Canvas Controls -->
                <div class="flex flex-wrap justify-between items-center gap-2 mt-4 pt-3 border-t border-gray-100">
                    <div class="flex gap-2">
                        <button type="button" onclick="canvasManager.clearCanvas()"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm text-xs font-medium text-gray-700 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-trash-can text-gray-400"></i> Clear
                        </button>
                        <button type="button" onclick="canvasManager.deleteSelected()"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-lg shadow-sm text-xs font-medium text-red-600 hover:bg-red-50 transition">
                            <i class="fa-solid fa-eraser text-red-400"></i> Delete Selected
                        </button>
                    </div>
                    <button type="button" onclick="canvasManager.downloadDesign()"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-brand-50 border border-brand-200 rounded-lg shadow-sm text-xs font-semibold text-brand-700 hover:bg-brand-100 transition">
                        <i class="fa-solid fa-camera"></i> Save 3D Snapshot
                    </button>
                </div>
            </div>

        </div>

        <!-- Customization Sidebar (Right - Form inputs identical to 2D customizer) -->
        <div class="w-full lg:w-5/12 bg-white rounded-2xl shadow-xl border border-gray-100 p-6 lg:p-8 flex flex-col max-h-[850px] overflow-y-auto scrollbar-hide">

            <!-- Step 1: Product Selection -->
            <div class="mb-8 border-b pb-6">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">1. Choose Product</h3>
                <div class="grid grid-cols-4 lg:grid-cols-4 gap-3">
                    <button type="button" onclick="canvasManager.setProduct('tshirt', 'assets/images/products/tshirt.png')"
                        class="product-btn border-2 border-brand-500 bg-brand-50 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                        id="btn-prod-tshirt">
                        <i class="fa-solid fa-shirt text-xl text-brand-600 btn-icon"></i>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-800 btn-text">Shirt</span>
                    </button>
                    <button type="button" onclick="canvasManager.setProduct('polo', 'assets/images/products/polo.png')"
                        class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                        id="btn-prod-polo">
                        <i class="fa-solid fa-user-tie text-xl text-gray-600 btn-icon"></i>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text">Polo</span>
                    </button>
                    <button type="button" onclick="canvasManager.setProduct('hoodie', 'assets/images/products/hoodie.png')"
                        class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                        id="btn-prod-hoodie">
                        <i class="fa-solid fa-user-ninja text-xl text-gray-600 btn-icon"></i>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text">Hoodie</span>
                    </button>
                    <button type="button" onclick="canvasManager.setProduct('varsity', 'assets/images/products/varsity_jacket.png')"
                        class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                        id="btn-prod-varsity">
                        <i class="fa-solid fa-vest text-xl text-gray-600 btn-icon"></i>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text text-center leading-tight">Varsity<br>Jacket</span>
                    </button>
                    <button type="button" onclick="canvasManager.setProduct('cap', 'assets/images/products/cap.png')"
                        class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                        id="btn-prod-cap">
                        <i class="fa-brands fa-redhat text-xl text-gray-600 btn-icon"></i>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text">Cap</span>
                    </button>
                    <button type="button" onclick="canvasManager.setProduct('visor', 'assets/images/products/visor.png')"
                        class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                        id="btn-prod-visor">
                        <i class="fa-solid fa-masks-theater text-xl text-gray-600 btn-icon"></i>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text">Visor</span>
                    </button>
                    <button type="button" onclick="canvasManager.setProduct('bag', 'assets/images/products/bag.png')"
                        class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                        id="btn-prod-bag">
                        <i class="fa-solid fa-shopping-bag text-xl text-gray-600 btn-icon"></i>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text">Bag</span>
                    </button>
                </div>
            </div>

            <!-- Step 2: Base Color -->
            <div class="mb-8 border-b pb-6">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex justify-between items-center">
                    2. Garment Color
                    <span class="text-xs font-normal text-gray-500 normal-case" id="currentColorLabel">White</span>
                </h3>
                <div class="flex flex-wrap gap-3">
                    <button type="button" onclick="canvasManager.setProductColor('#ffffff', 'White')"
                        class="w-8 h-8 rounded-full shadow-sm border border-gray-300 bg-white ring-2 ring-offset-2 ring-brand-500 transition color-btn"></button>
                    <button type="button" onclick="canvasManager.setProductColor('#000000', 'Black')"
                        class="w-8 h-8 rounded-full shadow-sm border-2 border-white bg-black hover:scale-110 transition color-btn"></button>
                    <button type="button" onclick="canvasManager.setProductColor('#1e3a8a', 'Navy Blue')"
                        class="w-8 h-8 rounded-full shadow-sm border-2 border-white bg-blue-900 hover:scale-110 transition color-btn"></button>
                    <button type="button" onclick="canvasManager.setProductColor('#991b1b', 'Crimson Red')"
                        class="w-8 h-8 rounded-full shadow-sm border-2 border-white bg-red-800 hover:scale-110 transition color-btn"></button>
                    <button type="button" onclick="canvasManager.setProductColor('#14532d', 'Forest Green')"
                        class="w-8 h-8 rounded-full shadow-sm border-2 border-white bg-green-900 hover:scale-110 transition color-btn"></button>
                    <button type="button" onclick="canvasManager.setProductColor('#4b5563', 'Charcoal')"
                        class="w-8 h-8 rounded-full shadow-sm border-2 border-white bg-gray-600 hover:scale-110 transition color-btn"></button>

                    <!-- Custom Color picker -->
                    <div class="relative w-8 h-8 rounded-full shadow-sm border border-gray-300 overflow-hidden transition color-btn flex items-center justify-center bg-gradient-to-br from-red-500 via-green-500 to-blue-500 cursor-pointer"
                        title="Custom Color">
                        <input type="color" id="customColorPicker"
                            class="absolute inset-0 w-[200%] h-[200%] top-[-50%] left-[-50%] cursor-pointer opacity-0"
                            onchange="canvasManager.setCustomColor(this.value)">
                        <i class="fa-solid fa-plus text-white text-xs pointer-events-none drop-shadow-md"></i>
                    </div>
                </div>
            </div>

            <!-- Step 3: Design Details (Text & Logo) -->
            <div class="mb-8 border-b pb-6">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">3. Embroidery & Print Artwork</h3>

                <div class="bg-gray-50 p-4 rounded-xl mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Add Text</label>
                    <div class="flex gap-2">
                        <input type="text" id="textInput"
                            class="flex-grow border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm transition"
                            placeholder="e.g. Your Company Name">
                        <button type="button" onclick="canvasManager.addText()"
                            class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-900 transition shadow-sm whitespace-nowrap">Add</button>
                    </div>

                    <!-- Text Stylings (Shown when text is selected) -->
                    <div id="textControls" class="hidden mt-4 grid grid-cols-2 gap-3 p-3 bg-white rounded-lg border border-gray-200">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Color</label>
                            <input type="color" id="textColor" class="w-full h-8 rounded cursor-pointer border-0 p-0"
                                onchange="canvasManager.updateTextOptions()">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Font</label>
                            <select id="fontFamily" class="w-full border-gray-200 text-sm rounded cursor-pointer shadow-sm p-1.5"
                                onchange="canvasManager.updateTextOptions()">
                                <option value="Inter">Inter</option>
                                <option value="Arial">Arial</option>
                                <option value="Times New Roman">Times</option>
                                <option value="Courier New">Courier</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Upload Logo/Emblem Image</label>
                    <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-100 transition cursor-pointer"
                        id="dropzone">
                        <input type="file" id="uploadImage" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            accept="image/png, image/jpeg, image/jpg, image/svg+xml">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-brand-500 mb-2"></i>
                        <p class="text-sm font-medium text-gray-700">Click or drag logo here</p>
                        <p class="text-xs text-gray-500 mt-1">PNG, JPG, SVG up to 5MB</p>
                    </div>
                </div>
            </div>

            <!-- Step 4: Design Method -->
            <div class="mb-8 border-b pb-6">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">4. Decoration Method</h3>
                <div class="grid grid-cols-2 gap-4">
                    <label class="cursor-pointer relative">
                        <input type="radio" name="designMethod" value="embroidery" class="peer sr-only" checked onchange="canvasManager.setDesignMethod(this.value)">
                        <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 transition hover:bg-gray-50 h-full flex flex-col justify-center items-center gap-2 shadow-sm">
                            <i class="fa-solid fa-wand-magic-sparkles text-2xl text-gray-400 peer-checked:text-brand-600"></i>
                            <span class="font-bold text-sm text-gray-600 peer-checked:text-brand-700 block">Embroidery</span>
                            <span class="text-[10px] text-gray-400">Raised thread & satin sheen</span>
                        </div>
                    </label>
                    <label class="cursor-pointer relative">
                        <input type="radio" name="designMethod" value="print" class="peer sr-only" onchange="canvasManager.setDesignMethod(this.value)">
                        <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 transition hover:bg-gray-50 h-full flex flex-col justify-center items-center gap-2 shadow-sm">
                            <i class="fa-solid fa-print text-2xl text-gray-400 peer-checked:text-brand-600"></i>
                            <span class="font-bold text-sm text-gray-600 peer-checked:text-brand-700 block">Print</span>
                            <span class="text-[10px] text-gray-400">Smooth matte finish</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Step 5: Quantities -->
            <div class="mb-8">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">5. Sizes & Quantities</h3>
                <div class="space-y-3" id="quantityContainer">
                    <!-- Populated dynamically by order-state.js -->
                </div>
                <div class="mt-4 pt-4 border-t flex justify-between items-center text-lg font-bold text-gray-800">
                    <span>Total Quantity:</span>
                    <span id="totalQuantityDisplay">0</span>
                </div>
                <div class="mt-2 flex justify-between items-center text-lg font-bold text-gray-800">
                    <span>Indicative Estimate:</span>
                    <span id="totalPriceDisplay" class="text-primary">$0.00</span>
                </div>
                <div class="mt-1 flex justify-between items-start gap-3">
                    <span id="sideSurchargeNote" class="hidden text-xs font-semibold text-brand-700"></span>
                    <span class="text-[11px] text-gray-500 ml-auto text-right">Estimate only. Final quote confirmed by Welzee.</span>
                </div>
                <p class="text-[11px] text-gray-500 mt-2 leading-relaxed">* Indicative estimate for initial planning. Final custom quotation is confirmed based on stitch density, artwork specifications, garment blanks, and destination shipping.</p>
            </div>

            <!-- Submit Button -->
            <button type="button" onclick="apiManager.submitEnquiry()" id="submitBtn"
                class="w-full bg-brand-600 text-white py-4 rounded-xl text-lg font-bold hover:bg-brand-700 transition shadow-lg shadow-brand-500/30 flex justify-center items-center gap-2 mt-auto">
                <i class="fa-solid fa-paper-plane"></i>
                Request 3D Pricing & Order
            </button>

        </div>
    </div>

</div>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-gray-900/50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 text-center shadow-2xl transform scale-95 transition-all duration-300 opacity-0 relative"
        id="successModalInner">
        <button type="button" onclick="document.getElementById('successModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-check text-2xl text-green-600"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-2">Enquiry Sent!</h3>
        <p class="text-gray-500 mb-6">Thank you for your 3D customization request. We have received your order details and 3D preview. Our production team will contact you shortly.</p>
        <button type="button" onclick="location.reload()"
            class="w-full bg-gray-900 text-white font-medium py-2.5 rounded-lg hover:bg-gray-800 transition">Start New Design</button>
    </div>
</div>

<!-- Contact Form Modal (Triggered before final submit) -->
<div id="contactModal" class="fixed inset-0 bg-gray-900/50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">Your Contact Details</h3>
            <button type="button" onclick="apiManager.closeContactModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <p class="text-sm text-gray-500 mb-6">Please provide your details so we can send you the quotation for your custom design.</p>

        <form id="contactForm" onsubmit="event.preventDefault(); apiManager.processFinalSubmit();">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" id="contactName" required
                        class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-brand-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" id="contactEmail" required
                        class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-brand-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="tel" id="contactPhone"
                        class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-brand-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                    <textarea id="contactNotes" rows="3"
                        class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-brand-500 outline-none placeholder-gray-400"
                        placeholder="Any specific instructions for your 3D customization?"></textarea>
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" id="finalSubmitBtn"
                    class="w-full bg-brand-600 text-white font-bold py-3 rounded-xl hover:bg-brand-700 transition flex justify-center items-center gap-2">
                    <span>Confirm Request</span><i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script>window.CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;</script>
<!-- Layer A: Immediate OrderState (no Three.js dependency) -->
<script src="assets/js/3d/order-state.js?v=1.4.0"></script>
<!-- 3D API client (front + back logos) -> api/submit_order_3d.php -->
<script src="assets/js/3d/api-3d.js?v=1.0.0"></script>

<!-- Layer B: Lazy-loaded 3D Engine & Fabric.js Integration -->
<script type="module">
function isWebGLAvailable() {
    try {
        const canvas = document.createElement('canvas');
        return !!(window.WebGLRenderingContext && (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
    } catch (e) {
        return false;
    }
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src;
        s.onload = resolve;
        s.onerror = reject;
        document.head.appendChild(s);
    });
}

let studioStarted = false;
let fabricLoaded = false;
let viewerInstance = null;
let fabricCanvasInstance = null;

// Initialize Fabric.js 2D Artwork Canvas lazily
async function initFabric(viewer) {
    if (fabricLoaded) return;
    fabricLoaded = true;

    if (typeof fabric === 'undefined') {
        await loadScript('<?php echo $base_url; ?>assets/js/3d/lib/fabric-5.3.1/fabric.min.js');
    }

    const fabricCanvas = new fabric.Canvas('apparelCanvas', {
        preserveObjectStacking: true,
        selection: true,
        backgroundColor: 'transparent'
    });
    fabricCanvasInstance = fabricCanvas;

    // Connect to decal manager
    if (viewer && viewer.decalManager) {
        viewer.decalManager.bindFabricCanvas(fabricCanvas);
    }

    if (window.canvasManager) {
        window.canvasManager.attachFabricCanvas(fabricCanvas);
    }
    window.fabricCanvas = fabricCanvas;

    // Logo Uploader
    const uploadInput = document.getElementById('uploadImage');
    const dropzone = document.getElementById('dropzone');

    if (uploadInput) {
        uploadInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.match('image.*')) {
                alert('Please upload a valid image file (PNG, JPG, SVG).');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB limit.');
                return;
            }
            if (window.canvasManager && canvasManager.getExtractedLogos().length >= 6) {
                alert('You can upload up to 6 logos per order (all sides together).');
                uploadInput.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (f) => {
                const data = f.target.result;
                fabric.Image.fromURL(data, (img) => {
                    if (img.width > 160) {
                        img.scaleToWidth(160);
                    }
                    img.set({
                        left: fabricCanvas.width / 2,
                        top: fabricCanvas.height / 2,
                        originX: 'center',
                        originY: 'center',
                        cornerColor: '#22c55e',
                        cornerStrokeColor: '#16a34a',
                        borderColor: '#22c55e',
                        transparentCorners: false,
                        customType: 'design_logo'
                    });
                    img.originalLogoData = data;
                    img.originalFileName = file.name;

                    fabricCanvas.add(img);
                    fabricCanvas.setActiveObject(img);
                    fabricCanvas.renderAll();
                });
            };
            reader.readAsDataURL(file);
            uploadInput.value = '';
        });

        uploadInput.addEventListener('dragenter', () => dropzone && dropzone.classList.add('bg-brand-50', 'border-brand-300'));
        uploadInput.addEventListener('dragleave', () => dropzone && dropzone.classList.remove('bg-brand-50', 'border-brand-300'));
        uploadInput.addEventListener('drop', () => dropzone && dropzone.classList.remove('bg-brand-50', 'border-brand-300'));
    }

    // Selection handling
    function handleSelection(obj) {
        const controls = document.getElementById('textControls');
        if (obj && (obj.type === 'i-text' || obj.type === 'text')) {
            if (controls) {
                controls.classList.remove('hidden');
                const colorEl = document.getElementById('textColor');
                const fontEl = document.getElementById('fontFamily');
                if (colorEl) colorEl.value = obj.fill || '#000000';
                if (fontEl) fontEl.value = obj.fontFamily || 'Inter';
            }
        } else {
            if (controls) controls.classList.add('hidden');
        }
    }

    fabricCanvas.on('selection:created', (e) => handleSelection(e.selected[0]));
    fabricCanvas.on('selection:updated', (e) => handleSelection(e.selected[0]));
    fabricCanvas.on('selection:cleared', () => {
        const controls = document.getElementById('textControls');
        if (controls) controls.classList.add('hidden');
    });
}

// Global hook if user clicks text/upload before fabric loads
window.triggerFabricLoad = function(callback) {
    if (!studioStarted) {
        start3DStudio().then(() => {
            initFabric(viewerInstance).then(() => {
                if (callback) callback();
            });
        });
    } else {
        initFabric(viewerInstance).then(() => {
            if (callback) callback();
        });
    }
};

window.start3DStudio = () => start3DStudio();

// ---------- Classic-view preference (per browser; storage can be blocked) ----------
const VIEW_PREF_KEY = 'welzee_view_pref';
function getViewPref() { try { return window.localStorage.getItem(VIEW_PREF_KEY); } catch (e) { return null; } }
function setViewPref(v) {
    try { if (v) window.localStorage.setItem(VIEW_PREF_KEY, v); else window.localStorage.removeItem(VIEW_PREF_KEY); } catch (e) { /* ignore */ }
}
document.querySelectorAll('.js-classic-link').forEach((a) => a.addEventListener('click', () => setViewPref('classic')));

// ---------- Slow-load safeguard ----------
const SLOW_3D_MS = 6000;
const slowBanner = document.getElementById('slow3dBanner');
let firstFrameDrawn = false;
function showClassicBanner(reason) {
    if (!slowBanner || firstFrameDrawn && reason === 'slow') return;
    slowBanner.dataset.reason = reason;
    slowBanner.classList.remove('hidden');
}
const slowDismiss = document.getElementById('slow3dDismiss');
if (slowDismiss) slowDismiss.addEventListener('click', () => slowBanner && slowBanner.classList.add('hidden'));
// A model that fails to load falls back to a placeholder shape: offer the classic view
document.getElementById('viewport3d').addEventListener('welzee:3d-error', () => showClassicBanner('error'));

async function start3DStudio() {
    if (studioStarted) return;
    studioStarted = true;

    if (!isWebGLAvailable()) {
        const alertEl = document.getElementById('webglFallbackAlert');
        if (alertEl) alertEl.classList.remove('hidden');
        showClassicBanner('error');
        return;
    }

    // Show loading overlay
    const loader = document.getElementById('viewport-loader');
    if (loader) loader.classList.remove('hidden');

    const watchdog = setTimeout(() => showClassicBanner('slow'), SLOW_3D_MS);

    try {
        // Dynamic import of 3D viewer
        const { ApparelViewer } = await import('<?php echo $base_url; ?>assets/js/3d/viewer.js?v=1.4.0');

        const vpEl = document.getElementById('viewport3d');
        vpEl.addEventListener('welzee:3d-ready', () => {
            firstFrameDrawn = true;
            clearTimeout(watchdog);
            if (slowBanner && slowBanner.dataset.reason === 'slow') slowBanner.classList.add('hidden');

            // First 3D frame rendered: cross-fade poster
            const poster = document.getElementById('posterContainer');
            if (poster) {
                poster.classList.add('opacity-0');
                setTimeout(() => poster.classList.add('hidden'), 500);
            }
            if (loader) {
                loader.classList.add('opacity-0');
                setTimeout(() => loader.classList.add('hidden'), 300);
            }
            const hint = document.getElementById('viewportHint');
            if (hint) {
                hint.classList.remove('opacity-0');
            }

            // Lazily load Fabric.js only after 3D first frame
            initFabric(viewerInstance);
        }, { once: true });

        const viewer = new ApparelViewer('viewport3d', '<?php echo $base_url; ?>assets/js/3d/models.manifest.json?v=1.5.0');
        viewerInstance = viewer;
        window.apparelViewer = viewer;

        if (viewer.initPromise) {
            await viewer.initPromise;
        }

        if (window.canvasManager) {
            window.canvasManager.attachViewer(viewer);
        }

    } catch (err) {
        console.error('Failed to initialize 3D studio:', err);
        clearTimeout(watchdog);
        const alertEl = document.getElementById('webglFallbackAlert');
        if (alertEl) alertEl.classList.remove('hidden');
        if (loader) loader.classList.add('hidden');
        showClassicBanner('error');
    }
}

// ---------- When to start ----------
// Everyone: automatically after window load (poster stays the first paint / LCP).
// Exceptions: Save-Data (tap to start) and visitors who chose the classic view ("Try 3D").
const saveData = !!(navigator.connection && navigator.connection.saveData);
const prefersClassic = getViewPref() === 'classic';
const lowSpecOverlay = document.getElementById('lowSpecOverlay');
const btnExplore = document.getElementById('btnExplore3D');

if (saveData || prefersClassic) {
    if (prefersClassic) {
        const label = document.getElementById('btnExplore3DLabel');
        const note = document.getElementById('overlayNote');
        const classicLink = document.getElementById('overlayClassicLink');
        if (label) label.textContent = 'Try 3D';
        if (note) note.textContent = 'You chose the classic view last time.';
        if (classicLink) classicLink.classList.remove('hidden');
    }
    if (lowSpecOverlay) lowSpecOverlay.classList.remove('hidden');
    if (btnExplore) {
        btnExplore.addEventListener('click', () => {
            if (prefersClassic) setViewPref(null);      // "Try 3D" forgets the classic choice
            if (lowSpecOverlay) lowSpecOverlay.classList.add('hidden');
            start3DStudio();
        });
    }
    if (saveData && !prefersClassic) {
        // Save-Data: a tap anywhere on the viewport also starts 3D
        const vp = document.getElementById('viewport3d');
        if (vp) {
            vp.addEventListener('click', (ev) => {
                if (studioStarted || (ev.target.closest && ev.target.closest('a'))) return;
                if (lowSpecOverlay) lowSpecOverlay.classList.add('hidden');
                start3DStudio();
            }, { once: true });
        }
    }
} else {
    const onWindowLoad = () => {
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => start3DStudio(), { timeout: 1500 });
        } else {
            setTimeout(() => start3DStudio(), 100);
        }
    };

    if (document.readyState === 'complete') {
        onWindowLoad();
    } else {
        window.addEventListener('load', onWindowLoad);
    }
}
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>

