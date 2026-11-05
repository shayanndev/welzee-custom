// assets/js/api.js

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
            
            // Get Image Data (Heavy Process)
            const designImage = canvasManager.getDesignData();
            const uploadedLogos = canvasManager.getExtractedLogos();

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
                logos: uploadedLogos
            };

            // Send via Fetch API to save in database first
            const response = await fetch('api/submit_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if(!response.ok) {
                throw new Error("HTTP Status " + response.status);
            }

            const result = await response.json();

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
