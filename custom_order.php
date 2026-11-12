<?php
// custom_order.php
require_once __DIR__ . '/config/bootstrap.php';
$csrfToken = csrf_token(); // starts the secure session and issues the CSRF token for the order API

$pageTitle = 'Design Your Own Custom Apparel Online | Welzee Customizer';
$pageDescription = 'Use Welzee\'s interactive apparel customizer to design custom embroidered or printed T-shirts, polos, hoodies, and caps. Request a fast wholesale quote.';

$extraHead = '
    <!-- Fabric.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <style>
        .canvas-container {
            margin: 0 auto;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            overflow: hidden;
            background-color: var(--bg-white, #ffffff);
        }
    </style>
';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-full mx-auto w-full px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-8 text-center">
            <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 mb-2">Design Your Custom Apparel</h1>
            <p class="text-lg text-gray-500 max-w-2xl mx-auto">Select a product, add your custom text and logo for
                embroidery, and request a personalized quote today.</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">

            <!-- Canvas Area (Left) -->
            <div class="w-full lg:w-7/12 flex flex-col items-center">
                <div class="bg-gray-200 rounded-xl p-4 w-full flex justify-center items-center relative"
                    id="canvas-wrapper">
                    <!-- Loading overlay -->
                    <div id="canvas-loader"
                        class="absolute inset-0 bg-white/80 z-10 flex flex-col items-center justify-center rounded-xl">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-brand-500 mb-2"></i>
                        <span class="text-sm font-medium text-gray-600">Loading editor...</span>
                    </div>
                    <canvas id="apparelCanvas"></canvas>
                </div>

                <!-- Canvas Controls -->
                <div class="flex gap-4 mt-6">
                    <button onclick="canvasManager.clearCanvas()"
                        class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        <i class="fa-solid fa-trash-can"></i> Clear Design
                    </button>
                    <button onclick="canvasManager.deleteSelected()"
                        class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-red-600 hover:bg-red-50 transition">
                        <i class="fa-solid fa-eraser"></i> Delete Selected
                    </button>
                    <button onclick="canvasManager.downloadDesign()"
                        class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-brand-600 hover:bg-brand-50 transition">
                        <i class="fa-solid fa-download"></i> Save Draft Locally
                    </button>
                </div>
            </div>

            <!-- Customization Sidebar (Right) -->
            <div
                class="w-full lg:w-5/12 bg-white rounded-2xl shadow-xl border border-gray-100 p-6 lg:p-8 flex flex-col max-h-[800px] overflow-y-auto scrollbar-hide">

                <!-- Step 1: Product Selection -->
                <div class="mb-8 border-b pb-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">1. Choose Product</h3>
                    <div class="grid grid-cols-4 lg:grid-cols-4 gap-3">
                        <button onclick="canvasManager.setProduct('tshirt', 'assets/images/products/tshirt.png')"
                            class="product-btn border-2 border-brand-500 bg-brand-50 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                            id="btn-prod-tshirt">
                            <i class="fa-solid fa-shirt text-xl text-brand-600 btn-icon"></i>
                            <span class="text-[10px] sm:text-xs font-semibold text-gray-800 btn-text">Shirt</span>
                        </button>
                        <button onclick="canvasManager.setProduct('polo', 'assets/images/products/polo.png')"
                            class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                            id="btn-prod-polo">
                            <i class="fa-solid fa-user-tie text-xl text-gray-600 btn-icon"></i>
                            <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text">Polo</span>
                        </button>
                        <button onclick="canvasManager.setProduct('hoodie', 'assets/images/products/hoodie.png')"
                            class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                            id="btn-prod-hoodie">
                            <!-- Using generic icons for illustration -->
                            <i class="fa-solid fa-user-ninja text-xl text-gray-600 btn-icon"></i>
                            <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text">Hoodie</span>
                        </button>
                        <button
                            onclick="canvasManager.setProduct('varsity', 'assets/images/products/varsity_jacket.png')"
                            class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                            id="btn-prod-varsity">
                            <i class="fa-solid fa-vest text-xl text-gray-600 btn-icon"></i>
                            <span
                                class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text text-center leading-tight">Varsity<br>Jacket</span>
                        </button>
                        <button onclick="canvasManager.setProduct('cap', 'assets/images/products/cap.png')"
                            class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                            id="btn-prod-cap">
                            <i class="fa-brands fa-redhat text-xl text-gray-600 btn-icon"></i>
                            <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text">Cap</span>
                        </button>
                        <button onclick="canvasManager.setProduct('visor', 'assets/images/products/visor.png')"
                            class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                            id="btn-prod-visor">
                            <i class="fa-solid fa-masks-theater text-xl text-gray-600 btn-icon"></i>
                            <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text">Visor</span>
                        </button>
                        <button onclick="canvasManager.setProduct('bag', 'assets/images/products/bag.png')"
                            class="product-btn border-2 border-transparent hover:border-gray-200 bg-gray-50 hover:bg-gray-100 rounded-lg p-2.5 flex flex-col items-center gap-2 transition"
                            id="btn-prod-bag">
                            <i class="fa-solid fa-shopping-bag text-xl text-gray-600 btn-icon"></i>
                            <span class="text-[10px] sm:text-xs font-semibold text-gray-600 btn-text">Bag</span>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Base Color -->
                <div class="mb-8 border-b pb-6">
                    <h3
                        class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex justify-between items-center">
                        2. Garment Color
                        <span class="text-xs font-normal text-gray-500 normal-case" id="currentColorLabel">White</span>
                    </h3>
                    <div class="flex flex-wrap gap-3">
                        <button onclick="canvasManager.setProductColor('#ffffff', 'White')"
                            class="w-8 h-8 rounded-full shadow-sm border border-gray-300 bg-white ring-2 ring-offset-2 ring-brand-500 transition color-btn"></button>
                        <button onclick="canvasManager.setProductColor('#000000', 'Black')"
                            class="w-8 h-8 rounded-full shadow-sm border-2 border-white bg-black hover:scale-110 transition color-btn"></button>
                        <button onclick="canvasManager.setProductColor('#1e3a8a', 'Navy Blue')"
                            class="w-8 h-8 rounded-full shadow-sm border-2 border-white bg-blue-900 hover:scale-110 transition color-btn"></button>
                        <button onclick="canvasManager.setProductColor('#991b1b', 'Crimson Red')"
                            class="w-8 h-8 rounded-full shadow-sm border-2 border-white bg-red-800 hover:scale-110 transition color-btn"></button>
                        <button onclick="canvasManager.setProductColor('#14532d', 'Forest Green')"
                            class="w-8 h-8 rounded-full shadow-sm border-2 border-white bg-green-900 hover:scale-110 transition color-btn"></button>
                        <button onclick="canvasManager.setProductColor('#4b5563', 'Charcoal')"
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
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">3. Embroidery Design</h3>

                    <div class="bg-gray-50 p-4 rounded-xl mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Add Text</label>
                        <div class="flex gap-2">
                            <input type="text" id="textInput"
                                class="flex-grow border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm transition"
                                placeholder="e.g. Your Company Name">
                            <button onclick="canvasManager.addText()"
                                class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-900 transition shadow-sm whitespace-nowrap">Add</button>
                        </div>

                        <!-- Text Stylings (Hidden by default, shown when text selected) -->
                        <div id="textControls"
                            class="hidden mt-4 grid grid-cols-2 gap-3 p-3 bg-white rounded-lg border border-gray-200">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Color</label>
                                <input type="color" id="textColor"
                                    class="w-full h-8 rounded cursor-pointer border-0 p-0"
                                    onchange="canvasManager.updateTextOptions()">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Font</label>
                                <select id="fontFamily"
                                    class="w-full border-gray-200 text-sm rounded cursor-pointer shadow-sm p-1.5"
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
                            <input type="file" id="uploadImage"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                accept="image/png, image/jpeg, image/jpg, image/svg+xml">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-brand-500 mb-2"></i>
                            <p class="text-sm font-medium text-gray-700">Click or drag logo here</p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, SVG up to 5MB</p>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Design Method -->
                <div class="mb-8 border-b pb-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">4. Design Method</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="cursor-pointer relative">
                            <input type="radio" name="designMethod" value="embroidery" class="peer sr-only" checked onchange="canvasManager.setDesignMethod(this.value)">
                            <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 transition hover:bg-gray-50 h-full flex flex-col justify-center items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-wand-magic-sparkles text-2xl text-gray-400 peer-checked:text-brand-600"></i>
                                <span class="font-bold text-sm text-gray-600 peer-checked:text-brand-700 block">Embroidery</span>
                            </div>
                        </label>
                        <label class="cursor-pointer relative">
                            <input type="radio" name="designMethod" value="print" class="peer sr-only" onchange="canvasManager.setDesignMethod(this.value)">
                            <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 transition hover:bg-gray-50 h-full flex flex-col justify-center items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-print text-2xl text-gray-400 peer-checked:text-brand-600"></i>
                                <span class="font-bold text-sm text-gray-600 peer-checked:text-brand-700 block">Print</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 5: Quantities -->
                <div class="mb-8">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">5. Sizes & Quantities</h3>
                    <div class="space-y-3" id="quantityContainer">
                        <!-- Populated dynamically by JS -->
                    </div>
                    <div class="mt-4 pt-4 border-t flex justify-between items-center text-lg font-bold text-gray-800">
                        <span>Total Quantity:</span>
                        <span id="totalQuantityDisplay">0</span>
                    </div>
                    <div class="mt-2 flex justify-between items-center text-lg font-bold text-gray-800">
                        <span>Indicative Estimate:</span>
                        <span id="totalPriceDisplay" class="text-primary">$0.00</span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-2 leading-relaxed">* Indicative estimate for initial planning. Final custom quotation is confirmed based on stitch density, artwork specifications, garment blanks, and destination shipping.</p>
                </div>

                <!-- Submit Button -->
                <button onclick="apiManager.submitEnquiry()" id="submitBtn"
                    class="w-full bg-brand-600 text-white py-4 rounded-xl text-lg font-bold hover:bg-brand-700 transition shadow-lg shadow-brand-500/30 flex justify-center items-center gap-2 mt-auto">
                    <i class="fa-solid fa-paper-plane"></i>
                    Request Pricing & Order
                </button>

            </div>
        </div>
    </main>

    <!-- Success Modal -->
    <div id="successModal"
        class="fixed inset-0 bg-gray-900/50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 text-center shadow-2xl transform scale-95 transition-all duration-300 opacity-0 relative"
            id="successModalInner">
            <button onclick="document.getElementById('successModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-check text-2xl text-green-600"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Enquiry Sent!</h3>
            <p class="text-gray-500 mb-6">Thank you for your request. We've received your design and details. Our team
                will contact you shortly with pricing and timeline.</p>
            <button onclick="location.reload()"
                class="w-full bg-gray-900 text-white font-medium py-2.5 rounded-lg hover:bg-gray-800 transition">Start
                New Design</button>
        </div>
    </div>

    <!-- Contact Form Modal (Triggered before final submit) -->
    <div id="contactModal"
        class="fixed inset-0 bg-gray-900/50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Your Contact Details</h3>
                <button onclick="apiManager.closeContactModal()" class="text-gray-400 hover:text-gray-600"><i
                        class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <p class="text-sm text-gray-500 mb-6">Please provide your details so we can send you the quotation for your
                custom design.</p>

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
                            placeholder="Any specific embroidery instructions?"></textarea>
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
    <script src="assets/js/customizer.js"></script>
    <script src="assets/js/api.js"></script>

</div> <!-- End customizer max-w container -->

<?php
include __DIR__ . '/includes/footer.php';
?>