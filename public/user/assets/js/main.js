/**
 * QuickCart – User Page JS
 */
(function () {
  "use strict";

  const select = (el, all = false) => {
    el = el.trim();
    return all ? [...document.querySelectorAll(el)] : document.querySelector(el);
  };

  const on = (type, el, listener, all = false) => {
    if (all) {
      select(el, all).forEach(e => e.addEventListener(type, listener));
    } else {
      const elem = select(el, all);
      if (elem) elem.addEventListener(type, listener);
    }
  };

  const onscroll = (el, listener) => {
    el.addEventListener("scroll", listener);
  };

  if (select(".toggle-sidebar-btn")) {
    on("click", ".toggle-sidebar-btn", function () {
      select("body").classList.toggle("toggle-sidebar");
    });
  }

  if (select(".search-bar-toggle")) {
    on("click", ".search-bar-toggle", function () {
      select(".search-bar").classList.toggle("search-bar-show");
    });
  }

  const backtotop = select(".back-to-top");
  if (backtotop) {
    const toggleBacktotop = () => {
      backtotop.classList.toggle("active", window.scrollY > 100);
    };
    window.addEventListener("load", toggleBacktotop);
    onscroll(document, toggleBacktotop);
  }

  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

  document.querySelectorAll(".needs-validation").forEach(form => {
    form.addEventListener("submit", function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add("was-validated");
    }, false);
  });

  const datatables = select(".datatable", true);
  datatables.forEach(dt => {
    new simpleDatatables.DataTable(dt, {
      perPageSelect: [5, 10, 15, ["All", -1]],
    });
  });

  window.updateCartBadge = function (count) {
    const badge = select(".cart-count-badge");
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? "inline-block" : "none";
  };

  const activePill = document.querySelector('.category-pills .pill.active');
  if (activePill) {
    activePill.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }

  function showCartToast() {
    let toast = document.getElementById('cart-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'cart-toast';
      toast.style.cssText = `
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        background: #005d21; color: #fff; padding: 12px 20px;
        border-radius: 8px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,.2);
        transition: opacity .3s;
      `;
      document.body.appendChild(toast);
    }
    toast.textContent = '✓ Added to cart!';
    toast.style.opacity = '1';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.style.opacity = '0', 2000);
  }

  document.querySelectorAll('form[action*="cartController.php"]').forEach(function (form) {
    if (!form.querySelector('[name="addToCart"]')) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const btn = form.querySelector('.btn-add-cart');
      const scrollY = window.scrollY;
      if (btn) {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Added!';
        btn.style.background = "#007a2b";
        setTimeout(() => {
          btn.innerHTML = original;
          btn.style.background = "";
        }, 1200);
      }
      fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: (() => {
          const fd = new FormData(form);
          fd.append('addToCart', '1');
          return fd;
        })()
      })
        .then(response => {
          if (!response.ok) throw new Error('Network error');
          return response.json();
        })
        .then(data => {
          if (!data.success) throw new Error('Cart error');
          window.scrollTo(0, scrollY);
          showCartToast();
          window.updateCartBadge(data.cartCount);
        })
        .catch(err => console.error('Add to cart failed:', err));
    });
  });

})();

// ─────────────────────────────────────────────
// Address edit modal
// ─────────────────────────────────────────────
function openEditModal(addr) {
  document.getElementById('edit_addressId').value = addr.addressId;
  document.getElementById('edit_label').value = addr.label ?? '';
  document.getElementById('edit_recipientName').value = addr.recipientName;
  document.getElementById('edit_phoneNumber').value = addr.phoneNumber;
  document.getElementById('edit_street').value = addr.street;
  document.getElementById('edit_barangay').value = addr.barangay;
  document.getElementById('edit_city').value = addr.city;
  document.getElementById('edit_province').value = addr.province ?? '';
  document.getElementById('edit_zipCode').value = addr.zipCode ?? '';
  document.getElementById('edit_isDefault').checked = addr.isDefault == 1;
  new bootstrap.Modal(document.getElementById('editAddressModal')).show();
}

// ─────────────────────────────────────────────
// Payment method — toggle add-form fields
// ─────────────────────────────────────────────
function toggleAddFields(method) {
  document.getElementById('add_ewallet_fields').classList.add('d-none');
  document.getElementById('add_card_fields').classList.add('d-none');
  document.getElementById('add_bank_fields').classList.add('d-none');
  if (method === 'gcash' || method === 'maya') {
    document.getElementById('add_ewallet_fields').classList.remove('d-none');
  } else if (method === 'credit_card') {
    document.getElementById('add_card_fields').classList.remove('d-none');
  } else if (method === 'bank_transfer') {
    document.getElementById('add_bank_fields').classList.remove('d-none');
  }
}

// ─────────────────────────────────────────────
// Payment method — open edit modal
// ─────────────────────────────────────────────
function openEditPaymentModal(pm) {
  const methodLabels = {
    cod: 'Cash on Delivery', gcash: 'GCash', maya: 'Maya',
    credit_card: 'Credit Card', bank_transfer: 'Bank Transfer'
  };
  document.getElementById('edit_paymentMethodId').value = pm.paymentMethodId;
  document.getElementById('edit_method_hidden').value = pm.method;
  document.getElementById('edit_method_display').value = methodLabels[pm.method] ?? pm.method;
  document.getElementById('edit_label').value = pm.label ?? '';
  document.getElementById('edit_isDefault').checked = pm.isDefault == 1;
  document.getElementById('edit_ewallet_fields').classList.add('d-none');
  document.getElementById('edit_card_fields').classList.add('d-none');
  document.getElementById('edit_bank_fields').classList.add('d-none');
  if (pm.method === 'gcash' || pm.method === 'maya') {
    document.getElementById('edit_ewallet_fields').classList.remove('d-none');
    document.getElementById('edit_accountName').value = pm.accountName ?? '';
    document.getElementById('edit_accountNumber').value = pm.accountNumber ?? '';
  } else if (pm.method === 'credit_card') {
    document.getElementById('edit_card_fields').classList.remove('d-none');
    document.getElementById('edit_cardholderName').value = pm.cardholderName ?? '';
    document.getElementById('edit_cardNumber').value = pm.cardNumber ?? '';
    document.getElementById('edit_expiryMonth').value = pm.expiryMonth ?? '';
    document.getElementById('edit_expiryYear').value = pm.expiryYear ?? '';
    document.getElementById('edit_cardBrand').value = pm.cardBrand ?? '';
  } else if (pm.method === 'bank_transfer') {
    document.getElementById('edit_bank_fields').classList.remove('d-none');
    document.getElementById('edit_bankName').value = pm.bankName ?? '';
    document.getElementById('edit_bankAccountName').value = pm.bankAccountName ?? '';
    document.getElementById('edit_bankAccountNumber').value = pm.bankAccountNumber ?? '';
  }
  new bootstrap.Modal(document.getElementById('editPaymentModal')).show();
}

// ─────────────────────────────────────────────
// Reviews — shared helpers
// ─────────────────────────────────────────────
const PRODUCT_UPLOAD = '../uploads/products/';
const REVIEW_UPLOAD = '../uploads/reviews/';
const PLACEHOLDER = 'assets/img/product-placeholder.png';

function reviewStars(rating, size = 16) {
  let s = '';
  for (let i = 1; i <= 5; i++)
    s += `<span style="color:${i <= rating ? '#f5a623' : '#ccc'};font-size:${size}px;">★</span>`;
  return s;
}

function formatDate(ts) {
  if (!ts) return '';
  const d = new Date(ts.replace(' ', 'T'));
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// ─────────────────────────────────────────────
// Reviews — View modal
// ─────────────────────────────────────────────
let currentReview = null;

function openReviewViewModal(r) {
  currentReview = r;

  const prodImg = document.getElementById('viewProductImg');
  if (prodImg) {
    prodImg.src = r.productImage ? PRODUCT_UPLOAD + r.productImage : PLACEHOLDER;
    prodImg.onerror = function () { this.src = PLACEHOLDER; };
  }

  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  set('viewProductName', r.productName || 'Unknown Product');
  set('viewOrderNumber', 'Order: ' + r.orderNumber);
  set('viewComment', r.comment || 'No comment left.');
  set('viewDate', 'Reviewed on ' + formatDate(r.createdAt));

  const viewStarsEl = document.getElementById('viewStars');
  if (viewStarsEl) viewStarsEl.innerHTML = reviewStars(r.rating, 20);

  const imgWrap = document.getElementById('viewImageWrap');
  const reviewImg = document.getElementById('viewReviewImg');
  if (imgWrap && reviewImg) {
    if (r.imageUrl) {
      reviewImg.src = REVIEW_UPLOAD + r.imageUrl;
      imgWrap.classList.remove('d-none');
    } else {
      imgWrap.classList.add('d-none');
    }
  }

  // Show/hide Edit button and lock notice based on canEdit
  const editBtn = document.getElementById('viewToEditBtn');
  const editNotice = document.getElementById('viewEditNotice');
  if (editBtn) editBtn.classList.toggle('d-none', !r.canEdit);
  if (editNotice) editNotice.classList.toggle('d-none', !!r.canEdit);

  new bootstrap.Modal(document.getElementById('viewModal')).show();
}

// ─────────────────────────────────────────────
// Reviews — Edit modal
// ─────────────────────────────────────────────
function openReviewEditModal(r) {
  const idEl = document.getElementById('editReviewId');
  if (!idEl) return;

  idEl.value = r.reviewId;

  const prodImg = document.getElementById('editProductImg');
  if (prodImg) {
    prodImg.src = r.productImage ? PRODUCT_UPLOAD + r.productImage : PLACEHOLDER;
    prodImg.onerror = function () { this.src = PLACEHOLDER; };
  }

  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  set('editProductName', r.productName || 'Unknown Product');
  set('editOrderNumber', 'Order: ' + r.orderNumber);

  const commentEl = document.getElementById('editComment');
  const countEl = document.getElementById('commentCharCount');
  if (commentEl) commentEl.value = r.comment || '';
  if (countEl) countEl.textContent = (r.comment || '').length;

  // Current photo
  const currentWrap = document.getElementById('editCurrentPhotoWrap');
  const currentPhoto = document.getElementById('editCurrentPhoto');
  if (currentWrap && currentPhoto) {
    if (r.imageUrl) {
      currentPhoto.src = REVIEW_UPLOAD + r.imageUrl;
      currentWrap.classList.remove('d-none');
    } else {
      currentWrap.classList.add('d-none');
    }
  }

  // Reset new-photo preview
  const previewWrap = document.getElementById('editReviewPreview');
  const fileInput = document.getElementById('editReviewImage');
  if (previewWrap) previewWrap.classList.add('d-none');
  if (fileInput) fileInput.value = '';

  // Days-left notice
  const noticeEl = document.getElementById('editDaysNotice');
  if (noticeEl) {
    if (r.daysLeft === 0) {
      noticeEl.innerHTML = `<span class="badge rounded-pill" style="font-size:11px;background:#fff3cd;color:#856404;">
        <i class="bi bi-exclamation-triangle me-1"></i>Last day to edit this review
      </span>`;
    } else {
      noticeEl.innerHTML = `<span class="badge rounded-pill" style="font-size:11px;background:#d1f0db;color:#005d21;">
        <i class="bi bi-clock me-1"></i>${r.daysLeft} day${r.daysLeft !== 1 ? 's' : ''} left to edit
      </span>`;
    }
  }

  setReviewStars(parseInt(r.rating) || 5);
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ─────────────────────────────────────────────
// Reviews — star picker (function only; events wired in DOMContentLoaded)
// ─────────────────────────────────────────────
function setReviewStars(val) {
  const ratingEl = document.getElementById('editRating');
  if (ratingEl) ratingEl.value = val;
  document.querySelectorAll('#starPicker .star-btn').forEach(s => {
    s.style.color = parseInt(s.dataset.val) <= val ? '#f5a623' : '#ccc';
  });
}

// ─────────────────────────────────────────────
// Reviews — image preview
// ─────────────────────────────────────────────
function openImagePreview(src) {
  const el = document.getElementById('imgPreviewSrc');
  const modal = document.getElementById('imgPreviewModal');
  if (el) el.src = src;
  if (modal) new bootstrap.Modal(modal).show();
}

// ─────────────────────────────────────────────
// DOMContentLoaded — all DOM-dependent init
// ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

  // ── Scroll sidebar to active link ──────────────────────────────────────
  const sidebar = document.getElementById('sidebar-nav');
  if (sidebar) {
    const activeLink = sidebar.querySelector('.nav-link:not(.collapsed)');
    if (activeLink) activeLink.scrollIntoView({ block: 'center', behavior: 'instant' });
  }

  // ── Reviews: "Edit this review" button inside view modal ───────────────
  document.getElementById('viewToEditBtn')?.addEventListener('click', () => {
    bootstrap.Modal.getInstance(document.getElementById('viewModal'))?.hide();
    setTimeout(() => openReviewEditModal(currentReview), 300);
  });

  // ── Reviews: star picker events ────────────────────────────────────────
  document.querySelectorAll('#starPicker .star-btn').forEach(s => {
    s.addEventListener('click', () => setReviewStars(parseInt(s.dataset.val)));
    s.addEventListener('mouseenter', () => {
      document.querySelectorAll('#starPicker .star-btn').forEach(x => {
        x.style.color = parseInt(x.dataset.val) <= parseInt(s.dataset.val) ? '#f5a623' : '#ccc';
      });
    });
    s.addEventListener('mouseleave', () => {
      const ratingEl = document.getElementById('editRating');
      if (ratingEl) setReviewStars(parseInt(ratingEl.value));
    });
  });

  // ── Reviews: comment char counter ──────────────────────────────────────
  document.getElementById('editComment')?.addEventListener('input', function () {
    const countEl = document.getElementById('commentCharCount');
    if (countEl) countEl.textContent = this.value.length;
  });

  // ── Star rating hints (rateOrder page) ─────────────────────────────────
  const hints = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
  document.querySelectorAll('.star-rating').forEach(function (wrap) {
    const hint = wrap.querySelector('.star-hint');
    const inputs = wrap.querySelectorAll('input[type="radio"]');
    inputs.forEach(function (inp) {
      if (inp.checked && hint) hint.textContent = hints[inp.value] || '';
      inp.addEventListener('change', function () {
        if (hint) hint.textContent = hints[this.value] || '';
      });
    });
    wrap.querySelectorAll('label').forEach(function (lbl) {
      lbl.addEventListener('mouseenter', function () {
        const val = this.getAttribute('for').split('_').pop();
        if (hint) hint.textContent = hints[val] || '';
      });
      lbl.addEventListener('mouseleave', function () {
        const checked = wrap.querySelector('input:checked');
        if (hint) hint.textContent = checked ? (hints[checked.value] || '') : '';
      });
    });
  });

  // ── Review image previews (rateOrder page) ─────────────────────────────
  document.querySelectorAll('.review-img-input').forEach(function (input) {
    input.addEventListener('change', function () {
      const previewWrap = document.getElementById(this.dataset.preview);
      const img = previewWrap?.querySelector('img');
      if (this.files && this.files[0] && img) {
        img.src = URL.createObjectURL(this.files[0]);
        previewWrap.classList.remove('d-none');
      } else if (previewWrap) {
        previewWrap.classList.add('d-none');
      }
    });
  });

  // ── Refund proof image preview ──────────────────────────────────────────
  document.getElementById('imageProof')?.addEventListener('change', function () {
    const wrap = document.getElementById('proofPreviewWrap');
    const preview = document.getElementById('proofPreview');
    if (this.files && this.files[0]) {
      preview.src = URL.createObjectURL(this.files[0]);
      wrap.classList.remove('d-none');
    } else {
      wrap.classList.add('d-none');
    }
  });

  // ── Warn before leaving with unsaved ratings ────────────────────────────
  document.getElementById('rateForm')?.addEventListener('submit', function () {
    window._formSubmitting = true;
  });

  // ── Checkout: highlight selected address card ───────────────────────────
  document.querySelectorAll('.address-radio').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll('.address-card').forEach(c =>
        c.classList.remove('border-success', 'bg-light'));
      radio.closest('label').querySelector('.address-card')
        .classList.add('border-success', 'bg-light');
    });
  });

  // ── Checkout: highlight selected payment card ───────────────────────────
  document.querySelectorAll('.payment-radio').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll('.payment-card').forEach(c =>
        c.classList.remove('border-success', 'bg-light'));
      radio.closest('label').querySelector('.payment-card')
        .classList.add('border-success', 'bg-light');
    });
  });

  // ── Checkout: Place Order button ────────────────────────────────────────
  document.getElementById('placeOrderBtn')?.addEventListener('click', () => {
    const selectedAddress = document.querySelector('.address-radio:checked');
    if (!selectedAddress) { alert('Please select a delivery address.'); return; }

    const selectedRadio = document.querySelector('.payment-radio:checked');
    const method = selectedRadio ? selectedRadio.dataset.method : 'cod';

    if (method === 'cod') {
      const form = document.getElementById('checkoutForm');
      if (!form.querySelector('[name="placeOrder"]')) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = 'placeOrder'; hidden.value = '1';
        form.appendChild(hidden);
      }
      form.submit();
      return;
    }

    const instructions = paymentInstructions[method];
    const label = methodMetaMap[method]?.label || method;
    document.getElementById('modalPaymentLabel').textContent = 'Pay via ' + label;

    const stepsList = document.getElementById('modalStepsList');
    stepsList.innerHTML = '';
    instructions?.steps?.forEach(step => {
      const li = document.createElement('li');
      li.innerHTML = step;
      stepsList.appendChild(li);
    });

    const noteEl = document.getElementById('modalNote');
    if (instructions?.note) {
      document.getElementById('modalNoteText').innerHTML = instructions.note;
      noteEl.style.display = '';
    } else {
      noteEl.style.display = 'none';
    }

    document.getElementById('modalReferenceInput').value = '';
    document.getElementById('refError').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('paymentReferenceModal')).show();
  });

  // ── Checkout: Confirm Order button inside modal ─────────────────────────
  document.getElementById('confirmOrderBtn')?.addEventListener('click', () => {
    const refInput = document.getElementById('modalReferenceInput');
    const refValue = refInput.value.trim();
    if (!refValue) {
      document.getElementById('refError').classList.remove('d-none');
      refInput.focus();
      return;
    }
    document.getElementById('refError').classList.add('d-none');
    document.getElementById('referenceNumberInput').value = refValue;

    const form = document.getElementById('checkoutForm');
    if (!form.querySelector('[name="placeOrder"]')) {
      const hidden = document.createElement('input');
      hidden.type = 'hidden'; hidden.name = 'placeOrder'; hidden.value = '1';
      form.appendChild(hidden);
    }
    form.submit();
  });

});