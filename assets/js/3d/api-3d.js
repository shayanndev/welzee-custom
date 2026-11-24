// assets/js/3d/api-3d.js
// Copy of the ApiManager from assets/js/api.js for the 3D customizer.
// Differences: posts to api/submit_order_3d.php and sends every logo with its print
// side plus the list of decorated print sides. Same element IDs, modals, success
// animation and CSRF handling as the original.

// Must match MAX_REQUEST_BYTES in api/submit_order_3d.php
const API3D_MAX_REQUEST_BYTES = 30 * 1024 * 1024;
const API3D_MAX_LOGOS = 6;

class ApiManager {
    constructor() {
        this.submitBtn = document.getElementById('submitBtn');
        this.contactModal = document.getElementById('contactModal');
        this.successModal = document.getElementById('successModal');
        this.contactForm = document.getElementById('contactForm');
    }

    submitEnquiry() {
        // Validation Checks
        const quantities = canvasManager.getQuantities();
        if (Object.keys(quantities).length === 0) {
            alert('Please select at least one quantity for any size to proceed.');
            return;
        }

        // Show Contact Modal
        this.contactModal.classList.remove('hidden');
    }

    closeContactModal() {
        this.contactModal.classList.add('hidden');
    }

    async processFinalSubmit() {
        const finalBtn = document.getElementById('finalSubmitBtn');
        finalBtn.disabled = true;
        finalBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

        try {
            // Retrieve all data
            const productType = canvasManager.currentProduct;
            const baseColor = canvasManager.currentColorHex;
            const designMethod = canvasManager.getDesignMethod();
            const quantities = canvasManager.getQuantities();
            const totalQuantity = canvasManager.calculateTotal();

            // Persist the side being edited, then collect every side's logos
            if (typeof canvasManager.saveActiveSide === 'function') canvasManager.saveActiveSide();
            const uploadedLogos = canvasManager.getExtractedLogos();   // [{side, name, data}]
            const printSides = typeof canvasManager.getDecoratedSides === 'function'
                ? canvasManager.getDecoratedSides()
                : [];

            if (uploadedLogos.length > API3D_MAX_LOGOS) {
                alert(`Please use at most ${API3D_MAX_LOGOS} logos per order (all sides together). You currently have ${uploadedLogos.length}.`);
                return;
            }

            // Get Image Data (Heavy Process): one JPEG with front (+ back) views
            const designImage = canvasManager.getDesignData();

            // Get Form Data
            const contactData = {
                name: document.getElementById('contactName').value,
                email: document.getElementById('contactEmail').value,
                phone: document.getElementById('contactPhone').value,
                notes: document.getElementById('contactNotes').value,
            };

            // Prepare Payload
            const payload = {
                product: productType,
                color: baseColor,
                designMethod: designMethod,
                quantities: quantities,
                total: totalQuantity,
                contact: contactData,
                designBase64: designImage,
                logos: uploadedLogos,
                printSides: printSides
            };

            const body = JSON.stringify(payload);
            if (body.length > API3D_MAX_REQUEST_BYTES) {
                const mb = (body.length / (1024 * 1024)).toFixed(1);
                const maxMb = Math.round(API3D_MAX_REQUEST_BYTES / (1024 * 1024));
                alert(`Your logo files are too large together (${mb} MB). The total upload must stay under ${maxMb} MB. Please use smaller logo files (for example PNG under 2 MB each) and try again.`);
                return;
            }

            // Send via Fetch API to save in database first
            const response = await fetch('api/submit_order_3d.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF_TOKEN || ''
                },
                body: body
            });

            let result = null;
            try {
                result = await response.json();
            } catch (parseError) {
                result = null;
            }

            if (response.status === 413 && !(result && result.message)) {
                alert('Your upload is too large. Please use smaller logo files and try again.');
                return;
            }
            if (!response.ok && !(result && result.message)) {
                throw new Error("HTTP Status " + response.status);
            }
            if (!result) {
                throw new Error("Invalid server response");
            }

            if (result.status === 'success') {
                this.closeContactModal();
                this.successModal.classList.remove('hidden');

                // Trigger success animation
                requestAnimationFrame(() => {
                    document.getElementById('successModalInner').classList.remove('scale-95', 'opacity-0');
                    document.getElementById('successModalInner').classList.add('scale-100', 'opacity-100');
                });

            } else {
                alert('Error submitting enquiry: ' + (result.message || 'Unknown error occurred.'));
            }

        } catch (error) {
            console.error('Submission Error:', error);
            alert('A network error occurred while submitting your order. Please try again.');
        } finally {
            finalBtn.disabled = false;
            finalBtn.innerHTML = '<span>Confirm Request</span><i class="fa-solid fa-arrow-right"></i>';
        }
    }
}

// Global exposure
let apiManager;
document.addEventListener('DOMContentLoaded', () => {
    apiManager = new ApiManager();
});
