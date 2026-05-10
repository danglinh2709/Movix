/**
 * ============================================================
 * Premium OTT VIP Pricing Page JavaScript
 * Handles billing toggle, FAQ accordion, and checkout modal
 * ============================================================
 */
(function () {
  'use strict';

  // ============================================================
  // UTILITY FUNCTIONS
  // ============================================================
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  // ============================================================
  // PRICING DATA
  // ============================================================
  const PRICING = {
    monthly: {
      basic: { price: 5.99, period: '/month' },
      standard: { price: 9.99, period: '/month' },
      premium: { price: 14.99, period: '/month' }
    },
    yearly: {
      basic: { price: 4.79, period: '/month', annual: '$57.48/year' },
      standard: { price: 7.99, period: '/month', annual: '$95.88/year' },
      premium: { price: 11.99, period: '/month', annual: '$143.88/year' }
    }
  };

  // ============================================================
  // STATE
  // ============================================================
  let state = {
    billingCycle: 'monthly',
    selectedPlan: localStorageGet('mu_selected_plan', null)
  };

  function localStorageGet(key, fallback) {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function localStorageSet(key, val) {
    try {
      localStorage.setItem(key, JSON.stringify(val));
    } catch (e) {}
  }

  // ============================================================
  // ELEMENTS
  // ============================================================
  let elements = {};

  function initElements() {
    elements = {
      toggleSwitch: $('.mu-pricing-toggle-switch'),
      toggleBadge: $('.mu-pricing-toggle-badge'),
      cards: $$('.mu-pricing-card'),
      cardBtns: $$('.mu-pricing-card-btn'),
      faqItems: $$('.mu-pricing-faq-item'),
      modal: $('#mu-pricing-modal'),
      modalBackdrop: $('.mu-pricing-modal-backdrop'),
      modalCancel: $('#mu-pricing-modal-cancel'),
      modalConfirm: $('#mu-pricing-modal-confirm'),
      modalTitle: $('.mu-pricing-modal-title'),
      modalDesc: $('.mu-pricing-modal-desc'),
      modalPlan: $('.mu-pricing-modal-plan')
    };
  }

  // ============================================================
  // BILLING TOGGLE
  // ============================================================
  function initBillingToggle() {
    if (!elements.toggleSwitch) return;

    elements.toggleSwitch.addEventListener('click', () => {
      state.billingCycle = state.billingCycle === 'monthly' ? 'yearly' : 'monthly';
      updateBillingUI();
      updatePricingUI();
    });

    // Initial state
    if (state.billingCycle === 'yearly') {
      elements.toggleSwitch.classList.add('is-active');
      if (elements.toggleBadge) elements.toggleBadge.classList.add('is-visible');
    }
  }

  function updateBillingUI() {
    if (!elements.toggleSwitch || !elements.toggleBadge) return;

    elements.toggleSwitch.classList.toggle('is-active', state.billingCycle === 'yearly');
    elements.toggleBadge.classList.toggle('is-visible', state.billingCycle === 'yearly');
  }

  function updatePricingUI() {
    const prices = PRICING[state.billingCycle];
    
    $$('.mu-pricing-card-amount').forEach(el => {
      const card = el.closest('.mu-pricing-card');
      if (!card) return;
      
      let plan = 'basic';
      if (card.classList.contains('mu-pricing-card--standard')) plan = 'standard';
      if (card.classList.contains('mu-pricing-card--premium')) plan = 'premium';
      
      const data = prices[plan];
      el.textContent = data.price.toFixed(2);
      
      // Update period
      const periodEl = card.querySelector('.mu-pricing-card-period');
      if (periodEl) periodEl.textContent = data.period;
      
      // Update annual text
      const annualEl = card.querySelector('.mu-pricing-card-annual');
      if (annualEl) {
        if (data.annual) {
          annualEl.style.display = 'block';
          annualEl.innerHTML = `Billed <strong>${data.annual}</strong>`;
        } else {
          annualEl.style.display = 'none';
        }
      }
    });
  }

  // ============================================================
  // PLAN SELECTION
  // ============================================================
  function initPlanButtons() {
    $$('.mu-pricing-card-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        
        const card = btn.closest('.mu-pricing-card');
        if (!card) return;
        
        let plan = 'basic';
        if (card.classList.contains('mu-pricing-card--standard')) plan = 'standard';
        if (card.classList.contains('mu-pricing-card--premium')) plan = 'premium';
        
        const prices = PRICING[state.billingCycle];
        const planData = prices[plan];
        
        // Store selected plan
        state.selectedPlan = {
          plan: plan,
          cycle: state.billingCycle,
          price: planData.price,
          period: planData.period,
          annual: planData.annual || null,
          selectedAt: Date.now()
        };
        localStorageSet('mu_selected_plan', state.selectedPlan);
        
        // Update button states
        $$('.mu-pricing-card-btn').forEach(b => {
          b.classList.remove('mu-pricing-card-btn--selected');
          b.textContent = getButtonText(b);
        });
        btn.classList.add('mu-pricing-card-btn--selected');
        btn.textContent = 'Selected ✓';
        
        // Open checkout modal
        openCheckoutModal(plan, planData);
      });
    });
    
    // Restore selected state
    if (state.selectedPlan) {
      const card = $(`.mu-pricing-card[data-plan="${state.selectedPlan.plan}"]`);
      if (card) {
        const btn = card.querySelector('.mu-pricing-card-btn');
        if (btn) {
          btn.classList.add('mu-pricing-card-btn--selected');
          btn.textContent = 'Selected ✓';
        }
      }
    }
  }

  function getButtonText(btn) {
    if (btn.classList.contains('mu-pricing-card-btn--outline')) return 'Get Basic';
    if (btn.classList.contains('mu-pricing-card-btn--primary')) return 'Get Standard';
    if (btn.classList.contains('mu-pricing-card-btn--gold')) return 'Get Premium';
    return 'Get Started';
  }

  // ============================================================
  // CHECKOUT MODAL
  // ============================================================
  function openCheckoutModal(plan, planData) {
    if (!elements.modal) return;
    
    const planNames = { basic: 'Basic', standard: 'Standard', premium: 'Premium' };
    const planNamesText = planNames[plan] || 'Selected';
    
    if (elements.modalTitle) {
      elements.modalTitle.textContent = 'Start Your Subscription';
    }
    
    if (elements.modalDesc) {
      elements.modalDesc.textContent = 'Checkout functionality is coming soon. Your selected plan has been saved and you will be redirected when checkout is available.';
    }
    
    if (elements.modalPlan) {
      elements.modalPlan.innerHTML = `
        <span class="mu-pricing-modal-plan-name">${planNamesText} Plan</span>
        <span class="mu-pricing-modal-plan-price">$${planData.price.toFixed(2)}${planData.period}</span>
      `;
    }
    
    elements.modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    if (!elements.modal) return;
    elements.modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  // ============================================================
  // FAQ ACCORDION
  // ============================================================
  function initFAQ() {
    $$('.mu-pricing-faq-question').forEach(question => {
      question.addEventListener('click', () => {
        const item = question.closest('.mu-pricing-faq-item');
        if (!item) return;
        
        // Close other items
        $$('.mu-pricing-faq-item.is-open').forEach(otherItem => {
          if (otherItem !== item) {
            otherItem.classList.remove('is-open');
          }
        });
        
        // Toggle current
        item.classList.toggle('is-open');
      });
    });
  }

  // ============================================================
  // PAYMENT LOGO TOOLTIPS
  // ============================================================
  function initPaymentLogos() {
    $$('.mu-pricing-secure-logo').forEach(logo => {
      logo.addEventListener('click', () => {
        const tooltip = logo.querySelector('.mu-pricing-secure-logo-tooltip');
        if (tooltip) {
          // Show tooltip message
          const msg = tooltip.textContent;
          showToast(msg);
        }
      });
    });
  }

  // ============================================================
  // TOAST NOTIFICATION
  // ============================================================
  let toastTimeout;

  function showToast(message) {
    let toast = $('.mu-pricing-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'mu-pricing-toast';
      toast.innerHTML = '<span class="mu-pricing-toast-msg"></span>';
      document.body.appendChild(toast);
    }
    
    const msgEl = toast.querySelector('.mu-pricing-toast-msg');
    if (msgEl) msgEl.textContent = message;
    
    toast.classList.add('is-visible');
    
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
      toast.classList.remove('is-visible');
    }, 3000);
  }

  // ============================================================
  // EVENT LISTENERS
  // ============================================================
  function setupEventListeners() {
    // Modal close on backdrop
    elements.modalBackdrop?.addEventListener('click', closeModal);
    
    // Modal cancel button
    elements.modalCancel?.addEventListener('click', closeModal);
    
    // Modal confirm button
    elements.modalConfirm?.addEventListener('click', () => {
      // Store selection and close
      showToast('Plan saved! Checkout coming soon.');
      closeModal();
    });
    
    // Close modal on Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeModal();
      }
    });
  }

  // ============================================================
  // INITIALIZE
  // ============================================================
  function init() {
    initElements();
    initBillingToggle();
    initPlanButtons();
    initFAQ();
    initPaymentLogos();
    setupEventListeners();
    
    // Initial pricing update
    updatePricingUI();
  }

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
