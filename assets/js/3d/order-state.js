/**
 * assets/js/3d/order-state.js
 *
 * Layer A: OrderState & Bridge Manager for Welzee 3D Customizer.
 * Loads synchronously on DOMContentLoaded. Zero Three.js dependency.
 *
 * Implements the exact CanvasManager interface required by api.js and inline onclicks:
 * - Immediate sizes/quantities UI & calculation
 * - Currency integration (CurrencyChanged, CurrencyReady)
 * - Product & color selection state
 * - Bridge to 2D Fabric artwork canvas & 3D WebGL viewer
 * - Call queueing to safely handle any early clicks before 3D loads
 * - Per-product, per-side artwork (front / back / rear) with a side switch
 */

/* ===================== PRICE ESTIMATE SETTINGS (owner can change) =====================
 * BASE_UNIT_PRICE_USD: indicative price per piece (same $15 benchmark as the 2D customizer).
 * EXTRA_SIDE_SURCHARGE_PERCENT: every decorated side AFTER the first adds this percentage
 *   of the base unit price, per piece. Example with 30: front + back = 15 + 4.50 = $19.50/pc.
 *   One decorated side = no surcharge.
 * ===================================================================================== */
const BASE_UNIT_PRICE_USD = 15;
const EXTRA_SIDE_SURCHARGE_PERCENT = 30;

// Extra Fabric properties saved with each side (logo originals are needed for the order)
const FABRIC_JSON_PROPS = ['customType', 'originalLogoData', 'originalFileName'];

// Parts that do NOT take the chosen colour (must match fixedMaterialColors in models.manifest.json).
// Shown next to the colour name so the customer knows, e.g. varsity: body = chosen colour.
const FIXED_PART_NOTES = {
    varsity: 'body colour · sleeves stay cream'
};

// Used until the 3D manifest is loaded (must match models.manifest.json printZones)
const FALLBACK_SIDES = {
    cap: [{ id: 'front', label: 'Front' }, { id: 'rear', label: 'Rear' }],
    visor: [{ id: 'front', label: 'Front' }],
    _default: [{ id: 'front', label: 'Front' }, { id: 'back', label: 'Back' }]
};

class OrderState {
    constructor() {
        this.currentProduct = 'tshirt';
        this.currentColorHex = '#ffffff';
        this.designMethod = 'embroidery';
        this.viewer = null;
        this.fabricCanvas = null;
        this.queue = [];
        this.extractedLogos = [];

        // Artwork kept while the page is open: artworkStore[product][side] = Fabric JSON
        this.activeSide = 'front';
        this.artworkStore = {};
        this._loadToken = 0;
        this._sideLoading = false;
        this._artTimer = null;

        this.colorNameMap = {
            '#ffffff': 'White',
            '#000000': 'Black',
            '#1e3a8a': 'Navy Blue',
            '#991b1b': 'Crimson Red',
            '#14532d': 'Forest Green',
            '#4b5563': 'Charcoal'
        };

        this.productImgMap = {
            tshirt: 'assets/images/products/tshirt.png',
            polo: 'assets/images/products/polo.png',
            hoodie: 'assets/images/products/hoodie.png',
            varsity: 'assets/images/products/varsity_jacket.png',
            cap: 'assets/images/products/cap.png',
            visor: 'assets/images/products/visor.png',
            bag: 'assets/images/products/bag.png'
        };

        this.initUrlParams();
        this.initSizes();
        this.bindCurrencyEvents();
        this.renderSideSwitch();
    }

    /**
     * Parses and strictly validates URL params: ?product=...&color=...
     */
    initUrlParams() {
        try {
            const params = new URLSearchParams(window.location.search);
            const p = params.get('product') || params.get('category');
            const allowed = ['tshirt', 'polo', 'hoodie', 'varsity', 'cap', 'visor', 'bag'];
            if (p && allowed.includes(p.toLowerCase())) {
                this.currentProduct = p.toLowerCase();
            }

            const c = params.get('color');
            if (c && /^#[0-9a-fA-F]{6}$/i.test(c)) {
                this.currentColorHex = c;
            }
        } catch (e) {
            // Ignore malformed search strings
        }
    }

    /**
     * Size selection UI & inputs (copied verbatim from customizer.js)
     */
    initSizes() {
        const sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        const container = document.getElementById('quantityContainer');
        if (!container) return;

        container.innerHTML = '';
        sizes.forEach(size => {
            container.innerHTML += `
                <div class="flex items-center justify-between border border-gray-200 rounded-lg p-3 bg-white hover:border-gray-300 transition shadow-sm">
                    <span class="font-bold text-gray-700 w-12 text-center bg-gray-100 py-1 rounded">${size}</span>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="canvasManager.updateQty('${size}', -1)" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition text-gray-600"><i class="fa-solid fa-minus text-xs"></i></button>
                        <input type="number" id="qty_${size}" value="0" min="0" max="999" class="w-12 text-center text-lg font-bold border-none p-0 outline-none qty-input" readonly>
                        <button type="button" onclick="canvasManager.updateQty('${size}', 1)" class="w-8 h-8 rounded-full bg-brand-100 hover:bg-brand-200 flex items-center justify-center transition text-brand-700"><i class="fa-solid fa-plus text-xs"></i></button>
                    </div>
                </div>
            `;
        });
    }

    updateQty(size, change) {
        const input = document.getElementById(`qty_${size}`);
        if (!input) return;
        let val = parseInt(input.value) + change;
        if (isNaN(val) || val < 0) val = 0;
        input.value = val;
        this.calculateTotal();
    }

    calculateTotal() {
        let qty = 0;
        document.querySelectorAll('.qty-input').forEach(input => {
            const v = parseInt(input.value);
            if (!isNaN(v)) qty += v;
        });

        const totalQtyEl = document.getElementById('totalQuantityDisplay');
        if (totalQtyEl) totalQtyEl.innerText = qty;

        // Each decorated side after the first adds EXTRA_SIDE_SURCHARGE_PERCENT of the base unit price
        const extraSides = Math.max(0, this.getDecoratedSides().length - 1);
        const unitUsd = BASE_UNIT_PRICE_USD * (1 + (extraSides * EXTRA_SIDE_SURCHARGE_PERCENT) / 100);
        const usdTotal = qty * unitUsd;

        const displayEl = document.getElementById('totalPriceDisplay') || document.getElementById('totalPrice');
        if (displayEl) {
            displayEl.innerText = this.formatPrice(usdTotal);
        }

        const noteEl = document.getElementById('sideSurchargeNote');
        if (noteEl) {
            if (extraSides > 0) {
                const what = extraSides === 1 ? 'second print side' : `${extraSides} extra print sides`;
                noteEl.innerText = `Includes ${what} (+${extraSides * EXTRA_SIDE_SURCHARGE_PERCENT}% est.)`;
                noteEl.classList.remove('hidden');
            } else {
                noteEl.classList.add('hidden');
            }
        }
        return qty;
    }

    formatPrice(usdAmount) {
        const store = window.Welzee || window.Eilotu;
        if (store && store.currency === 'PKR') {
            const pkr = Math.round(usdAmount * store.exchangeRate);
            return 'Rs. ' + pkr.toLocaleString();
        }
        return '$' + usdAmount.toFixed(2);
    }

    bindCurrencyEvents() {
        document.addEventListener('CurrencyChanged', () => this.calculateTotal());
        document.addEventListener('CurrencyReady', () => this.calculateTotal());
    }

    /**
     * Product Selection (called from product tabs, URL, or programmatically)
     */
    setProduct(productId, imgUrl) {
        const prevProduct = this.currentProduct;
        const productChanged = prevProduct !== productId;

        // Keep the current side's artwork for the product we are leaving
        if (productChanged) this.saveActiveSide();

        this.currentProduct = productId;
        if (productChanged) this.activeSide = 'front';

        // Update UI Button states matching customizer.js exactly
        document.querySelectorAll('.product-btn').forEach(btn => {
            btn.classList.remove('border-brand-500', 'bg-brand-50');
            btn.classList.add('border-transparent', 'bg-gray-50');
            btn.setAttribute('aria-pressed', 'false');
            const icon = btn.querySelector('.btn-icon');
            if (icon) icon.classList.replace('text-brand-600', 'text-gray-600');
            const text = btn.querySelector('.btn-text');
            if (text) text.classList.replace('text-gray-800', 'text-gray-600');
        });

        const activeBtn = document.getElementById('btn-prod-' + productId);
        if (activeBtn) {
            activeBtn.classList.remove('border-transparent', 'bg-gray-50');
            activeBtn.classList.add('border-brand-500', 'bg-brand-50');
            activeBtn.setAttribute('aria-pressed', 'true');
            const activeIcon = activeBtn.querySelector('.btn-icon');
            if (activeIcon) activeIcon.classList.replace('text-gray-600', 'text-brand-600');
            const activeText = activeBtn.querySelector('.btn-text');
            if (activeText) activeText.classList.replace('text-gray-600', 'text-gray-800');
        }

        // Update artwork product badge chip
        const badge = document.getElementById('artworkProductBadge');
        if (badge) {
            badge.innerText = productId.toUpperCase();
        }

        // Update static poster immediately
        const poster = document.getElementById('viewportPoster');
        if (poster) {
            const path = imgUrl || this.productImgMap[productId] || `assets/images/products/${productId}.png`;
            poster.src = path;
        }

        this.updateFixedPartsNote();

        // Side switch + restore this product's artwork (editor = front, other sides -> 3D textures)
        this.renderSideSwitch();
        if (productChanged && this.fabricCanvas) {
            const dm = this.viewer?.decalManager;
            if (dm) {
                dm.setActiveSide(this.activeSide);
                dm.clearAllSides();
            }
            this.loadSideIntoEditor(this.activeSide);
            this.renderInactiveSides();
        }
        this.calculateTotal();

        // Inform 3D viewer if loaded, then face the active side
        const load = () => {
            if (!this.viewer) return;
            Promise.resolve(this.viewer.loadProduct(productId)).then(() => {
                if (this.currentProduct === productId) this.viewer.rotateToSide?.(this.activeSide);
            });
        };
        if (this.viewer) load(); else this.queue.push(load);

        // Update guide aspect ratio in 2D artwork canvas
        this.updateArtworkGuideAspect(productId);
    }

    /* ---------------------------- Print sides ---------------------------- */

    /** [{id,label}] of the print sides the product has (manifest when loaded, else fallback). */
    getSidesFor(productId) {
        const fromManifest = this.viewer?.getZoneList ? this.viewer.getZoneList(productId) : [];
        if (fromManifest && fromManifest.length) return fromManifest;
        return FALLBACK_SIDES[productId] || FALLBACK_SIDES._default;
    }

    /** Builds the Front | Back (Front | Rear for caps) switch above the artwork editor. */
    renderSideSwitch() {
        const wrap = document.getElementById('sideSwitch');
        if (!wrap) return;
        const sides = this.getSidesFor(this.currentProduct);
        if (!sides.some(s => s.id === this.activeSide)) this.activeSide = sides[0]?.id || 'front';

        wrap.innerHTML = '';
        sides.forEach((s) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'side-switch-btn';
            btn.dataset.side = s.id;
            btn.addEventListener('click', () => this.setActiveSide(s.id));
            const label = document.createElement('span');
            label.textContent = s.label;
            const dot = document.createElement('span');
            dot.className = 'side-switch-dot';
            dot.setAttribute('aria-hidden', 'true');
            btn.append(label, dot);
            wrap.appendChild(btn);
        });
        wrap.classList.toggle('single', sides.length < 2);
        this.updateSideSwitchState();
    }

    /** Highlights the active side, marks sides that have artwork, updates the badge. */
    updateSideSwitchState() {
        const wrap = document.getElementById('sideSwitch');
        const decorated = this.getDecoratedSides();
        if (wrap) {
            wrap.querySelectorAll('.side-switch-btn').forEach((btn) => {
                const active = btn.dataset.side === this.activeSide;
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                btn.classList.toggle('has-art', decorated.includes(btn.dataset.side));
            });
        }
        const badge = document.getElementById('artworkProductBadge');
        if (badge) {
            const side = this.getSidesFor(this.currentProduct).find(s => s.id === this.activeSide);
            badge.innerText = this.currentProduct.toUpperCase() + (side ? ' · ' + side.label.toUpperCase() : '');
        }
    }

    /** Selects the side the editor works on and turns the 3D garment to it. */
    setActiveSide(sideId) {
        const sides = this.getSidesFor(this.currentProduct).map(s => s.id);
        if (!sides.includes(sideId)) return;

        if (sideId !== this.activeSide) {
            this.saveActiveSide();
            this.activeSide = sideId;
            const dm = this.viewer?.decalManager;
            if (dm) dm.setActiveSide(sideId);   // flushes the old side's last edit first
            if (this.fabricCanvas) this.loadSideIntoEditor(sideId);
        }
        this.updateSideSwitchState();
        this.calculateTotal();

        if (this.viewer) {
            this.viewer.rotateToSide?.(sideId);
        } else {
            this.queue.push(() => this.viewer && this.viewer.rotateToSide?.(this.activeSide));
        }
    }

    /** Saves the editor's objects as the active side's artwork. */
    saveActiveSide() {
        if (!this.fabricCanvas || this._sideLoading) return;
        const json = this.fabricCanvas.toJSON(FABRIC_JSON_PROPS);
        if (!this.artworkStore[this.currentProduct]) this.artworkStore[this.currentProduct] = {};
        this.artworkStore[this.currentProduct][this.activeSide] = (json.objects && json.objects.length) ? json : null;
    }

    getStoredSide(productId, sideId) {
        return this.artworkStore[productId]?.[sideId] || null;
    }

    /** Replaces the editor contents with the saved artwork of `sideId` (current product). */
    loadSideIntoEditor(sideId) {
        const fc = this.fabricCanvas;
        if (!fc) return;
        const token = ++this._loadToken;

        fc.discardActiveObject();
        const old = fc.getObjects().slice();
        if (old.length) fc.remove(...old);
        fc.renderAll();

        const json = this.getStoredSide(this.currentProduct, sideId);
        if (!json || !json.objects || !json.objects.length || typeof fabric === 'undefined') {
            this._sideLoading = false;
            this.onArtworkChanged();
            return;
        }

        this._sideLoading = true;
        fabric.util.enlivenObjects(json.objects, (objs) => {
            if (token !== this._loadToken) return; // a newer switch took over
            objs.forEach(o => fc.add(o));
            fc.renderAll();
            this._sideLoading = false;
            this.onArtworkChanged();
        });
    }

    /** Draws each NON-active side's saved artwork into its 3D texture (off-screen Fabric). */
    renderInactiveSides() {
        const dm = this.viewer?.decalManager;
        if (!dm || typeof fabric === 'undefined' || !this.fabricCanvas) return;
        const product = this.currentProduct;
        const w = this.fabricCanvas.getWidth();
        const h = this.fabricCanvas.getHeight();
        const zoom = 2;

        this.getSidesFor(product).forEach(({ id }) => {
            if (id === this.activeSide) return;
            const json = this.getStoredSide(product, id);
            if (!json) { dm.setSideArtwork(id, null); return; }

            const el = document.createElement('canvas');
            const sc = new fabric.StaticCanvas(el, { enableRetinaScaling: false, renderOnAddRemove: false });
            sc.setDimensions({ width: w * zoom, height: h * zoom });
            sc.setZoom(zoom);
            fabric.util.enlivenObjects(json.objects, (objs) => {
                if (this.currentProduct === product && this.activeSide !== id) {
                    objs.forEach(o => sc.add(o));
                    sc.renderAll();
                    dm.setSideArtwork(id, sc.getElement());
                }
                sc.dispose();
            });
        });
    }

    /** Sides of the current product that have at least one design object. */
    getDecoratedSides() {
        return this.getSidesFor(this.currentProduct)
            .map(s => s.id)
            .filter((id) => {
                if (id === this.activeSide && this.fabricCanvas && !this._sideLoading) {
                    return this.fabricCanvas.getObjects().length > 0;
                }
                const json = this.getStoredSide(this.currentProduct, id);
                return !!(json && json.objects && json.objects.length);
            });
    }

    /** Called when objects are added/removed: refresh price + side dots (batched). */
    onArtworkChanged() {
        if (this._artTimer) clearTimeout(this._artTimer);
        this._artTimer = setTimeout(() => {
            this._artTimer = null;
            this.calculateTotal();
            this.updateSideSwitchState();
        }, 60);
    }

    updateArtworkGuideAspect(productId) {
        const guide = document.getElementById('printAreaGuide');
        if (!guide) return;

        const aspects = {
            tshirt: 'aspect-[4/5]',
            polo: 'aspect-[4/5]',
            hoodie: 'aspect-square',
            varsity: 'aspect-[4/5]',
            cap: 'aspect-[16/9]',
            visor: 'aspect-[2/1]',
            bag: 'aspect-[4/5]'
        };

        guide.className = guide.className.replace(/aspect-\[[^\]]+\]|aspect-square/g, '');
        guide.classList.add(aspects[productId] || 'aspect-[4/5]');
    }

    getProduct() {
        return this.currentProduct;
    }

    /**
     * Color Selection (called from preset swatches)
     */
    setProductColor(hex, name) {
        if (!/^#[0-9a-fA-F]{6}$/i.test(hex)) return;
        this.currentColorHex = hex;

        const label = document.getElementById('currentColorLabel');
        if (label) {
            label.innerText = name || this.colorNameMap[hex.toLowerCase()] || hex;
        }
        this.updateFixedPartsNote();

        // Update active swatch ring
        document.querySelectorAll('.color-btn').forEach(swatch => {
            swatch.classList.remove('ring-2', 'ring-offset-2', 'ring-brand-500');
        });

        // Reset custom color picker container style if standard color chosen
        const customPicker = document.getElementById('customColorPicker');
        if (customPicker && customPicker.parentElement) {
            customPicker.parentElement.style.backgroundColor = '';
            customPicker.parentElement.style.backgroundImage = '';
        }

        const activeSwatch = document.querySelector(`[onclick*="${hex}"]`);
        if (activeSwatch) {
            activeSwatch.classList.add('ring-2', 'ring-offset-2', 'ring-brand-500');
        }

        // Update poster color overlay
        const posterTint = document.getElementById('posterColorOverlay');
        if (posterTint) {
            posterTint.style.backgroundColor = hex;
        }

        if (this.viewer) {
            this.viewer.setProductColor(hex);
        } else {
            this.queue.push(() => this.viewer && this.viewer.setProductColor(hex));
        }
    }

    /** Small note after the colour name for products with fixed-colour parts (varsity sleeves). */
    updateFixedPartsNote() {
        const label = document.getElementById('currentColorLabel');
        if (!label) return;
        // Lives inside the label (the heading is a two-item flex row); setting the colour
        // name via innerText removes it, so it is re-added here each time.
        let note = label.querySelector('#fixedPartsNote');
        const text = FIXED_PART_NOTES[this.currentProduct] || '';
        if (!text) {
            if (note) note.remove();
            return;
        }
        if (!note) {
            note = document.createElement('span');
            note.id = 'fixedPartsNote';
            note.style.cssText = 'margin-left:4px;color:#9ca3af;';
            label.appendChild(note);
        }
        note.textContent = '(' + text + ')';
    }

    /**
     * Custom Color Selection (called from color input picker)
     */
    setCustomColor(hex) {
        if (!/^#[0-9a-fA-F]{6}$/i.test(hex)) return;
        this.currentColorHex = hex;

        const label = document.getElementById('currentColorLabel');
        if (label) {
            label.innerText = 'Custom (' + hex + ')';
        }
        this.updateFixedPartsNote();

        // Update ring state for custom picker
        document.querySelectorAll('.color-btn').forEach(btn => {
            btn.classList.remove('ring-2', 'ring-offset-2', 'ring-brand-500');
        });

        const picker = document.getElementById('customColorPicker');
        if (picker && picker.parentElement) {
            picker.parentElement.classList.add('ring-2', 'ring-offset-2', 'ring-brand-500');
            picker.parentElement.style.backgroundColor = hex;
            picker.parentElement.style.backgroundImage = 'none';
        }

        const posterTint = document.getElementById('posterColorOverlay');
        if (posterTint) {
            posterTint.style.backgroundColor = hex;
        }

        if (this.viewer) {
            this.viewer.setProductColor(hex);
        } else {
            this.queue.push(() => this.viewer && this.viewer.setProductColor(hex));
        }
    }

    setDesignMethod(method) {
        this.designMethod = method;
        if (this.viewer) {
            this.viewer.setDesignMethod(method);
        } else {
            this.queue.push(() => this.viewer && this.viewer.setDesignMethod(method));
        }
    }

    getDesignMethod() {
        return this.designMethod;
    }

    // 2D Fabric.js Text and Logo methods
    addText() {
        const input = document.getElementById('textInput');
        if (!input || !input.value) return;

        // If fabric is not loaded yet (e.g. user clicks add text early), trigger 3D/Fabric load
        if (typeof window.triggerFabricLoad === 'function' && !this.fabricCanvas) {
            window.triggerFabricLoad(() => this.addText());
            return;
        }

        if (this.fabricCanvas && typeof fabric !== 'undefined') {
            const text = new fabric.IText(input.value, {
                left: this.fabricCanvas.width / 2,
                top: this.fabricCanvas.height / 2,
                originX: 'center',
                originY: 'center',
                fontFamily: 'Inter',
                fill: '#111827',
                fontSize: 28,
                fontWeight: '600',
                cornerColor: '#22c55e',
                cornerStrokeColor: '#16a34a',
                borderColor: '#22c55e',
                transparentCorners: false,
                customType: 'design'
            });
            this.fabricCanvas.add(text);
            this.fabricCanvas.setActiveObject(text);
            this.fabricCanvas.renderAll();
            input.value = '';
        }
    }

    updateTextOptions() {
        if (!this.fabricCanvas) return;
        const obj = this.fabricCanvas.getActiveObject();
        if (obj && (obj.type === 'i-text' || obj.type === 'text')) {
            const colorEl = document.getElementById('textColor');
            const fontEl = document.getElementById('fontFamily');
            if (colorEl) obj.set('fill', colorEl.value);
            if (fontEl) obj.set('fontFamily', fontEl.value);
            this.fabricCanvas.renderAll();
        }
    }

    deleteSelected() {
        if (!this.fabricCanvas) return;
        const active = this.fabricCanvas.getActiveObjects();
        if (active && active.length) {
            this.fabricCanvas.discardActiveObject();
            active.forEach(obj => this.fabricCanvas.remove(obj));
            this.fabricCanvas.renderAll();
        }
    }

    /** Clears the SELECTED side only. Customers always get the confirmation. */
    clearCanvas(skipConfirm = false) {
        const side = this.getSidesFor(this.currentProduct).find(s => s.id === this.activeSide);
        const sideName = side ? side.label.toLowerCase() : 'this';
        if (skipConfirm || typeof window === 'undefined' || !window.confirm ||
            confirm(`Are you sure you want to clear all design elements on the ${sideName} side?`)) {
            if (this.fabricCanvas) {
                this.fabricCanvas.discardActiveObject();
                const objs = this.fabricCanvas.getObjects().slice();
                if (objs.length) this.fabricCanvas.remove(...objs);
                this.fabricCanvas.renderAll();
            }
            if (this.artworkStore[this.currentProduct]) {
                this.artworkStore[this.currentProduct][this.activeSide] = null;
            }
            this.onArtworkChanged();
        }
    }

    downloadDesign() {
        const snap = this.getDesignData();
        if (!snap) return;
        const a = document.createElement('a');
        a.href = snap;
        a.download = `welzee_3d_${this.currentProduct}_design.jpg`;
        a.click();
    }

    /**
     * ONE JPEG for the order: front and (if decorated) back side by side on the light
     * grey studio background, labelled, kept under ~1 MB.
     */
    getDesignData() {
        if (this.viewer && this.viewer.firstFrameRendered && this.viewer.getSideSnapshots) {
            try {
                this.viewer.decalManager?.flushActive();
                const sides = this.getSidesFor(this.currentProduct);
                const decorated = this.getDecoratedSides();
                const first = sides[0] || { id: 'front', label: 'Front' };
                const show = [first, ...sides.slice(1).filter(s => decorated.includes(s.id))];

                const PW = 800, PH = 900;
                const shots = this.viewer.getSideSnapshots(show.map(s => s.id), PW, PH);
                const out = document.createElement('canvas');
                out.width = PW * show.length;
                out.height = PH;
                const ctx = out.getContext('2d');
                ctx.fillStyle = '#f3f4f6';
                ctx.fillRect(0, 0, out.width, out.height);
                show.forEach((s, i) => {
                    if (shots[s.id]) ctx.drawImage(shots[s.id], i * PW, 0);
                    ctx.fillStyle = '#6b7280';
                    ctx.font = '600 26px Inter, Arial, sans-serif';
                    ctx.fillText(s.label.toUpperCase(), i * PW + 28, 48);
                    if (i > 0) {
                        ctx.fillStyle = '#e5e7eb';
                        ctx.fillRect(i * PW - 1, 40, 2, PH - 80);
                    }
                });
                return this.encodeJpegUnder(out, 1000 * 1000);
            } catch (e) {
                console.warn('[Welzee OrderState] Side snapshot failed, using single view', e);
                return this.viewer.getDesignSnapshot({ quality: 0.85 });
            }
        }

        // Fallback: composite poster image with fabric canvas
        const canvas = document.createElement('canvas');
        canvas.width = 1200;
        canvas.height = 960;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#f3f4f6';
        ctx.fillRect(0, 0, 1200, 960);

        const posterEl = document.getElementById('viewportPoster');
        if (posterEl && posterEl.complete && posterEl.naturalWidth > 0) {
            const aspect = posterEl.naturalWidth / posterEl.naturalHeight;
            const h = 700;
            const w = h * aspect;
            ctx.drawImage(posterEl, (1200 - w) / 2, (960 - h) / 2, w, h);
        }

        if (this.fabricCanvas) {
            const fabEl = this.fabricCanvas.getElement();
            if (fabEl) {
                ctx.drawImage(fabEl, 450, 320, 300, 300);
            }
        }

        return this.encodeJpegUnder(canvas, 1000 * 1000);
    }

    /** JPEG at quality 0.85, stepping down only if the file would exceed maxBytes. */
    encodeJpegUnder(canvas, maxBytes) {
        let url = '';
        for (const q of [0.85, 0.78, 0.7, 0.6, 0.5]) {
            url = canvas.toDataURL('image/jpeg', q);
            const bytes = Math.floor((url.length - url.indexOf(',') - 1) * 3 / 4);
            if (bytes <= maxBytes) break;
        }
        return url;
    }

    /**
     * Original logo files of the current product, all sides: [{ side, name, data }].
     * (Same shape as before plus `side`, so older callers keep working.)
     */
    getExtractedLogos() {
        const logos = [];
        const push = (side, obj) => {
            if (obj && obj.customType === 'design_logo' && obj.originalLogoData) {
                logos.push({ side, name: obj.originalFileName || 'logo.png', data: obj.originalLogoData });
            }
        };
        this.getSidesFor(this.currentProduct).forEach(({ id }) => {
            if (id === this.activeSide && this.fabricCanvas && !this._sideLoading) {
                this.fabricCanvas.getObjects().forEach(obj => push(id, obj));
            } else {
                const json = this.getStoredSide(this.currentProduct, id);
                (json?.objects || []).forEach(obj => push(id, obj));
            }
        });
        return logos;
    }

    getQuantities() {
        const q = {};
        const sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        sizes.forEach(size => {
            const input = document.getElementById(`qty_${size}`);
            if (input) {
                const val = parseInt(input.value);
                if (!isNaN(val) && val > 0) q[size] = val;
            }
        });
        return q;
    }

    /**
     * Attaches the Three.js ApparelViewer when ready and replays queued commands.
     */
    attachViewer(viewer) {
        this.viewer = viewer;
        if (viewer.decalManager) viewer.decalManager.setActiveSide(this.activeSide);
        this.renderSideSwitch(); // sides now come from the manifest
        // Replay any early actions
        while (this.queue.length > 0) {
            const action = this.queue.shift();
            try { action(); } catch (e) { console.error('Error replaying 3D action', e); }
        }
    }

    /**
     * Attaches the Fabric.js canvas instance.
     */
    attachFabricCanvas(fabricCanvas) {
        this.fabricCanvas = fabricCanvas;
        const changed = () => { if (!this._sideLoading) this.onArtworkChanged(); };
        fabricCanvas.on('object:added', changed);
        fabricCanvas.on('object:removed', changed);
        if (this.viewer?.decalManager) this.viewer.decalManager.setActiveSide(this.activeSide);
        // Restore anything saved before the editor existed (normally nothing)
        if (this.getStoredSide(this.currentProduct, this.activeSide)) this.loadSideIntoEditor(this.activeSide);
        this.renderInactiveSides();
    }
}

// Global exposure immediately on load
let canvasManager;
document.addEventListener('DOMContentLoaded', () => {
    canvasManager = new OrderState();
    window.canvasManager = canvasManager;

    // Apply URL preselection if specified
    if (canvasManager.currentProduct !== 'tshirt') {
        canvasManager.setProduct(canvasManager.currentProduct);
    }
    if (canvasManager.currentColorHex !== '#ffffff') {
        canvasManager.setProductColor(canvasManager.currentColorHex);
    }

    // Startup self-check: verify all inline canvasManager handler calls exist
    try {
        const inlineElements = document.querySelectorAll('*');
        const checkedMethods = new Set();
        const missingMethods = [];
        const handlerAttrs = ['onclick', 'onchange', 'oninput'];

        inlineElements.forEach(el => {
            handlerAttrs.forEach(attr => {
                const val = el.getAttribute(attr);
                if (val && val.includes('canvasManager.')) {
                    const matches = val.matchAll(/canvasManager\.([a-zA-Z0-9_]+)\s*\(/g);
                    for (const match of matches) {
                        const method = match[1];
                        if (!checkedMethods.has(method)) {
                            checkedMethods.add(method);
                            if (typeof canvasManager[method] !== 'function') {
                                missingMethods.push(method);
                            }
                        }
                    }
                }
            });
        });

        if (missingMethods.length > 0) {
            console.error('[Welzee OrderState] Missing required canvasManager method(s):', missingMethods);
        }
    } catch (checkErr) {
        // Non-blocking self-check
    }
});
