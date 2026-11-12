// assets/js/customizer.js

class CanvasManager {
    constructor(canvasId) {
        this.canvas = new fabric.Canvas(canvasId, {
            preserveObjectStacking: true,
            selection: true
        });
        this.currentProduct = 'tshirt';
        this.currentProductUrl = 'assets/images/products/tshirt.png';
        this.currentColorHex = '#ffffff';
        this.designMethod = 'embroidery'; // Default
        this.baseImage = null;
        this.overlayImage = null; // For color tinting

        // Define print area bounding boxes based on product (x, y, width, height) roughly
        this.bounds = {
            'tshirt': { x: 120, y: 100, w: 260, h: 320 },
            'polo': { x: 140, y: 120, w: 220, h: 280 },
            'hoodie': { x: 130, y: 150, w: 240, h: 220 },
            'varsity': { x: 140, y: 140, w: 220, h: 240 },
            'cap': { x: 150, y: 80, w: 200, h: 100 },
            'visor': { x: 150, y: 60, w: 200, h: 80 },
            'bag': { x: 100, y: 150, w: 300, h: 300 }
        };

        this.initSizes();
        this.bindEvents();
        this.resizeCanvas();

        window.addEventListener('resize', () => this.resizeCanvas());
    }

    resizeCanvas() {
        const outer = document.getElementById('canvas-wrapper');
        const width = outer.clientWidth - 32; // padding
        const height = Math.min(width * 1.25, 600); // aspect ratio

        this.canvas.setWidth(width);
        this.canvas.setHeight(height);

        this.loadProductImage();
    }

    loadProductImage() {
        document.getElementById('canvas-loader').classList.remove('hidden');

        fabric.Image.fromURL(this.currentProductUrl, (img) => {
            // Scale image to fit canvas comfortably
            const scale = Math.min(
                this.canvas.width / img.width,
                this.canvas.height / img.height
            ) * 0.9;

            img.set({
                originX: 'center',
                originY: 'center',
                left: this.canvas.width / 2,
                top: this.canvas.height / 2,
                scaleX: scale,
                scaleY: scale,
                selectable: false,
                evented: false
            });

            // Remove white background so tinting only applies to garment
            // const removeWhite = new fabric.Image.filters.RemoveColor({
            //     distance: 0.05,
            //     color: '#ffffff'
            // });
            // img.filters.push(removeWhite);

            // Handle Tinting
            if (this.currentColorHex !== '#ffffff') {
                const filter = new fabric.Image.filters.BlendColor({
                    color: this.currentColorHex,
                    mode: 'multiply',
                    alpha: 0.85
                });
                img.filters.push(filter);
            }
            img.applyFilters();

            // Remove existing background
            const objects = this.canvas.getObjects();
            if (this.baseImage) this.canvas.remove(this.baseImage);

            this.baseImage = img;
            this.canvas.add(this.baseImage);
            this.baseImage.sendToBack();

            // Draw Print Area guide (optional, visual only)
            // this.drawPrintAreaHelper(scale);

            this.canvas.renderAll();
            document.getElementById('canvas-loader').classList.add('hidden');
        }, { crossOrigin: 'anonymous' });
    }

    setProduct(type, url) {
        this.currentProduct = type;
        this.currentProductUrl = url;

        // Update UI Button states (with null checks)
        document.querySelectorAll('.product-btn').forEach(btn => {
            btn.classList.remove('border-brand-500', 'bg-brand-50');
            btn.classList.add('border-transparent', 'bg-gray-50');
            const icon = btn.querySelector('.btn-icon');
            if (icon) icon.classList.replace('text-brand-600', 'text-gray-600');
            const text = btn.querySelector('.btn-text');
            if (text) text.classList.replace('text-gray-800', 'text-gray-600');
        });

        const activeBtn = document.getElementById('btn-prod-' + type);
        activeBtn.classList.remove('border-transparent', 'bg-gray-50');
        activeBtn.classList.add('border-brand-500', 'bg-brand-50');
        const activeIcon = activeBtn.querySelector('.btn-icon');
        if (activeIcon) activeIcon.classList.replace('text-gray-600', 'text-brand-600');
        const activeText = activeBtn.querySelector('.btn-text');
        if (activeText) activeText.classList.replace('text-gray-600', 'text-gray-800');

        this.loadProductImage();
    }

    formatPrice(usdAmount) {
        const store = window.Welzee || window.Eilotu;
        if (store && store.currency === 'PKR') {
            const pkr = Math.round(usdAmount * store.exchangeRate);
            return 'Rs. ' + pkr.toLocaleString();
        }
        return '$' + usdAmount.toFixed(2);
    }

    updateTotal() {
        const total = this.calculateTotal();
        const displayAmount = this.formatPrice(total);
        document.getElementById('totalPrice').innerText = displayAmount;
    }
    setProductColor(hex, name) {
        this.currentColorHex = hex;
        document.getElementById('currentColorLabel').innerText = name;

        // Update Ring state
        document.querySelectorAll('.color-btn').forEach(btn => {
            btn.classList.remove('ring-2', 'ring-offset-2', 'ring-brand-500');
        });
        if (typeof event !== 'undefined' && event && event.currentTarget) {
            event.currentTarget.classList.add('ring-2', 'ring-offset-2', 'ring-brand-500');
        }

        this.loadProductImage();
    }

    setCustomColor(hex) {
        this.currentColorHex = hex;
        document.getElementById('currentColorLabel').innerText = 'Custom (' + hex + ')';

        // Update Ring state for custom picker
        document.querySelectorAll('.color-btn').forEach(btn => {
            btn.classList.remove('ring-2', 'ring-offset-2', 'ring-brand-500');
        });
        document.getElementById('customColorPicker').parentElement.classList.add('ring-2', 'ring-offset-2', 'ring-brand-500');

        this.loadProductImage();
    }

    setDesignMethod(method) {
        this.designMethod = method;
    }

    getDesignMethod() {
        return this.designMethod;
    }

    addText() {
        const textValue = document.getElementById('textInput').value;
        if (!textValue) return;

        const text = new fabric.IText(textValue, {
            left: this.canvas.width / 2,
            top: this.canvas.height / 2,
            originX: 'center',
            originY: 'center',
            fontFamily: 'Inter',
            fill: '#000000',
            fontSize: 32,
            fontWeight: '600',
            cornerColor: '#22c55e',
            cornerStrokeColor: '#16a34a',
            borderColor: '#22c55e',
            transparentCorners: false,
            customType: 'design'
        });

        this.canvas.add(text);
        this.canvas.setActiveObject(text);
        document.getElementById('textInput').value = '';
    }

    bindEvents() {
        // Image Upload Logic
        const uploadInput = document.getElementById('uploadImage');
        uploadInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Validate type
            if (!file.type.match('image.*')) {
                alert('Please upload a valid image file (PNG, JPG, SVG).');
                return;
            }

            // Validate size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB limit.');
                return;
            }

            const reader = new FileReader();
            reader.onload = (f) => {
                const data = f.target.result;
                fabric.Image.fromURL(data, (img) => {
                    // Scale down if too large
                    if (img.width > 200) {
                        img.scaleToWidth(200);
                    }
                    img.set({
                        left: this.canvas.width / 2,
                        top: this.canvas.height / 2,
                        originX: 'center',
                        originY: 'center',
                        cornerColor: '#22c55e',
                        cornerStrokeColor: '#16a34a',
                        borderColor: '#22c55e',
                        transparentCorners: false,
                        customType: 'design_logo' // Important for extraction later
                    });

                    // Store raw data attached to object for backend transmission
                    img.originalLogoData = data;
                    img.originalFileName = file.name;

                    this.canvas.add(img);
                    this.canvas.setActiveObject(img);
                });
            };
            reader.readAsDataURL(file);
            // Reset input
            uploadInput.value = '';
        });

        // Object Selection to show controls
        this.canvas.on('selection:created', (e) => this.handleSelection(e.selected[0]));
        this.canvas.on('selection:updated', (e) => this.handleSelection(e.selected[0]));
        this.canvas.on('selection:cleared', () => {
            document.getElementById('textControls').classList.add('hidden');
        });

        // Drag over effects
        const dropzone = document.getElementById('dropzone');
        uploadInput.addEventListener('dragenter', () => dropzone.classList.add('bg-brand-50', 'border-brand-300'));
        uploadInput.addEventListener('dragleave', () => dropzone.classList.remove('bg-brand-50', 'border-brand-300'));
        uploadInput.addEventListener('drop', () => dropzone.classList.remove('bg-brand-50', 'border-brand-300'));
    }

    handleSelection(obj) {
        if (obj && (obj.type === 'i-text' || obj.type === 'text')) {
            document.getElementById('textControls').classList.remove('hidden');
            document.getElementById('textColor').value = obj.fill || '#000000';
            document.getElementById('fontFamily').value = obj.fontFamily || 'Inter';
        } else {
            document.getElementById('textControls').classList.add('hidden');
        }
    }

    updateTextOptions() {
        const obj = this.canvas.getActiveObject();
        if (obj && (obj.type === 'i-text' || obj.type === 'text')) {
            obj.set({
                fill: document.getElementById('textColor').value,
                fontFamily: document.getElementById('fontFamily').value
            });
            this.canvas.renderAll();
        }
    }

    deleteSelected() {
        const activeObjects = this.canvas.getActiveObjects();
        if (activeObjects.length) {
            this.canvas.discardActiveObject();
            activeObjects.forEach((object) => {
                if (object !== this.baseImage) {
                    this.canvas.remove(object);
                }
            });
        }
    }

    clearCanvas() {
        if (confirm("Are you sure you want to clear all design elements?")) {
            const objects = this.canvas.getObjects();
            objects.forEach(obj => {
                if (obj !== this.baseImage) {
                    this.canvas.remove(obj);
                }
            });
        }
    }

    downloadDesign() {
        this.canvas.discardActiveObject();
        this.canvas.renderAll();
        const dataURL = this.canvas.toDataURL({ format: 'png', quality: 1 });
        const link = document.createElement('a');
        link.href = dataURL;
        link.download = `custom_${this.currentProduct}_design.png`;
        link.click();
    }

    // Size Selection Logic
    initSizes() {
        const sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        const container = document.getElementById('quantityContainer');
        container.innerHTML = '';

        sizes.forEach(size => {
            container.innerHTML += `
                <div class="flex items-center justify-between border border-gray-200 rounded-lg p-3 bg-white hover:border-gray-300 transition shadow-sm">
                    <span class="font-bold text-gray-700 w-12 text-center bg-gray-100 py-1 rounded">${size}</span>
                    <div class="flex items-center gap-3">
                        <button onclick="canvasManager.updateQty('${size}', -1)" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition text-gray-600"><i class="fa-solid fa-minus text-xs"></i></button>
                        <input type="number" id="qty_${size}" value="0" min="0" max="999" class="w-12 text-center text-lg font-bold border-none p-0 outline-none qty-input" readonly>
                        <button onclick="canvasManager.updateQty('${size}', 1)" class="w-8 h-8 rounded-full bg-brand-100 hover:bg-brand-200 flex items-center justify-center transition text-brand-700"><i class="fa-solid fa-plus text-xs"></i></button>
                    </div>
                </div>
            `;
        });
    }

    updateQty(size, change) {
        const input = document.getElementById(`qty_${size}`);
        let val = parseInt(input.value) + change;
        if (val < 0) val = 0;
        input.value = val;

        this.calculateTotal();
    }

    calculateTotal() {
        let qty = 0;
        document.querySelectorAll('.qty-input').forEach(input => {
            qty += parseInt(input.value);
        });
        document.getElementById('totalQuantityDisplay').innerText = qty;
        
        // Base arbitrary price per item metric ($15 ea)
        let usdTotal = qty * 15;
        
        let displayEl = document.getElementById('totalPriceDisplay');
        if (displayEl) {
            const store = window.Welzee || window.Eilotu;
            if (store && store.currency === 'PKR') {
                const pkr = Math.round(usdTotal * store.exchangeRate);
                displayEl.innerText = 'Rs. ' + pkr.toLocaleString();
            } else {
                displayEl.innerText = '$' + usdTotal.toFixed(2);
            }
        }
        return qty;
    }

    // Methods for Data Extraction
    getDesignData() {
        this.canvas.discardActiveObject();
        // Temporarily force a light grey background so neither white nor black shirts disappear
        const originalBg = this.canvas.backgroundColor;
        this.canvas.backgroundColor = '#f3f4f6';
        this.canvas.renderAll();
        
        const dataURL = this.canvas.toDataURL({ format: 'jpeg', quality: 0.8 });
        
        // Restore original transparent background
        this.canvas.backgroundColor = originalBg;
        this.canvas.renderAll();
        
        return dataURL;
    }

    getExtractedLogos() {
        const logos = [];
        this.canvas.getObjects().forEach(obj => {
            if (obj.customType === 'design_logo' && obj.originalLogoData) {
                logos.push({
                    name: obj.originalFileName,
                    data: obj.originalLogoData
                });
            }
        });
        return logos;
    }

    getQuantities() {
        const q = {};
        const sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        sizes.forEach(size => {
            const val = parseInt(document.getElementById(`qty_${size}`).value);
            if (val > 0) q[size] = val;
        });
        return q;
    }
}

// Initialize when DOM Ready
let canvasManager;
document.addEventListener('DOMContentLoaded', () => {
    canvasManager = new CanvasManager('apparelCanvas');
});

// React to global UI currency changes seamlessly
document.addEventListener('CurrencyChanged', () => {
    if (canvasManager) canvasManager.calculateTotal(); 
});
document.addEventListener('CurrencyReady', () => {
    if (canvasManager) canvasManager.calculateTotal(); 
});
