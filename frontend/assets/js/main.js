// Sistema de Rifas - Frontend JavaScript

// Utility Functions
const formatNumber = (number) => {
    return number.toString().padStart(4, '0');
};

const validatePhone = (phone) => {
    // Remove any non-digit characters
    const cleanPhone = phone.replace(/\D/g, '');
    // Check if it has 9-15 digits
    return /^\d{9,15}$/.test(cleanPhone);
};

const validateEmail = (email) => {
    return email === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
};

// Modal Management
class ModalManager {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        this.form = this.modal.querySelector('form');
        this.submitButton = this.form.querySelector('button[type="submit"]');
        this.spinner = document.getElementById('submitSpinner');
        
        // Bind methods
        this.open = this.open.bind(this);
        this.close = this.close.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
        
        // Setup event listeners
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Close on outside click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.close();
            }
        });

        // Form validation
        this.form.addEventListener('submit', this.handleSubmit);

        // Real-time validation
        const phoneInput = this.form.querySelector('#phone');
        const emailInput = this.form.querySelector('#email');

        phoneInput.addEventListener('input', () => {
            this.validateField(phoneInput, validatePhone(phoneInput.value));
        });

        emailInput.addEventListener('input', () => {
            this.validateField(emailInput, validateEmail(emailInput.value));
        });
    }

    validateField(input, isValid) {
        if (isValid) {
            input.classList.remove('border-red-500');
            input.classList.add('border-green-500');
        } else {
            input.classList.remove('border-green-500');
            input.classList.add('border-red-500');
        }
    }

    open(raffleId) {
        this.form.querySelector('#raffle_id').value = raffleId;
        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.modal.classList.add('hidden');
        this.modal.classList.remove('flex');
        this.form.reset();
        document.body.style.overflow = 'auto';

        // Reset validation styles
        this.form.querySelectorAll('input').forEach(input => {
            input.classList.remove('border-red-500', 'border-green-500');
        });
    }

    async handleSubmit(event) {
        event.preventDefault();

        const formData = new FormData(this.form);
        const phone = formData.get('phone');
        const email = formData.get('email');

        // Validate phone
        if (!validatePhone(phone)) {
            alert('Por favor, ingresa un número de teléfono válido');
            return;
        }

        // Validate email if provided
        if (email && !validateEmail(email)) {
            alert('Por favor, ingresa un email válido');
            return;
        }

        // Disable button and show spinner
        this.submitButton.disabled = true;
        this.spinner.classList.remove('hidden');

        try {
            const response = await fetch('reserve.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert('¡Reserva exitosa! Serás redirigido a WhatsApp para confirmar.');
                window.location.href = result.whatsapp_url;
                this.close();
            } else {
                alert(result.error || 'Error al procesar la reserva');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al procesar la reserva');
        } finally {
            // Re-enable button and hide spinner
            this.submitButton.disabled = false;
            this.spinner.classList.add('hidden');
        }
    }
}

// Raffle Card Animation
class RaffleCard {
    constructor(element) {
        this.card = element;
        this.setupHoverEffect();
    }

    setupHoverEffect() {
        this.card.addEventListener('mouseenter', () => {
            this.card.style.transform = 'translateY(-5px)';
            this.card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
        });

        this.card.addEventListener('mouseleave', () => {
            this.card.style.transform = 'translateY(0)';
            this.card.style.boxShadow = '';
        });
    }
}

// Smooth Scroll
const setupSmoothScroll = () => {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize modal
    const reservationModal = new ModalManager('reservationModal');
    window.openReservationModal = reservationModal.open;
    window.closeReservationModal = reservationModal.close;

    // Initialize raffle cards
    document.querySelectorAll('.raffle-card').forEach(card => {
        new RaffleCard(card);
    });

    // Setup smooth scroll
    setupSmoothScroll();
});

// Export for potential module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ModalManager,
        RaffleCard,
        formatNumber,
        validatePhone,
        validateEmail
    };
}