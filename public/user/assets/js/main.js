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

  /** Sidebar toggle */
  if (select(".toggle-sidebar-btn")) {
    on("click", ".toggle-sidebar-btn", function () {
      select("body").classList.toggle("toggle-sidebar");
    });
  }

  /** Search bar toggle (mobile) */
  if (select(".search-bar-toggle")) {
    on("click", ".search-bar-toggle", function () {
      select(".search-bar").classList.toggle("search-bar-show");
    });
  }

  /** Back to top */
  const backtotop = select(".back-to-top");
  if (backtotop) {
    const toggleBacktotop = () => {
      backtotop.classList.toggle("active", window.scrollY > 100);
    };
    window.addEventListener("load", toggleBacktotop);
    onscroll(document, toggleBacktotop);
  }

  /** Bootstrap tooltips */
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

  /** Bootstrap validation */
  document.querySelectorAll(".needs-validation").forEach(form => {
    form.addEventListener("submit", function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add("was-validated");
    }, false);
  });

  /** Simple DataTables */
  const datatables = select(".datatable", true);
  datatables.forEach(dt => {
    new simpleDatatables.DataTable(dt, {
      perPageSelect: [5, 10, 15, ["All", -1]],
    });
  });

  /** Cart badge updater */
  window.updateCartBadge = function (count) {
    const badge = select(".cart-count-badge");
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? "inline-block" : "none";
  };

  /** Add-to-cart animation feedback */
  document.querySelectorAll(".btn-add-cart").forEach(btn => {
    btn.addEventListener("click", function () {
      const original = this.innerHTML;
      this.innerHTML = '<i class="bi bi-check-lg me-1"></i> Added!';
      this.style.background = "#007a2b";
      setTimeout(() => {
        this.innerHTML = original;
        this.style.background = "";
      }, 1200);
    });
  });

  /** Scroll active category pill into view */
  const activePill = document.querySelector('.category-pills .pill.active');
  if (activePill) {
    activePill.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }

})();

document.addEventListener('DOMContentLoaded', function () {
  const sidebar = document.getElementById('sidebar-nav');
  if (!sidebar) return;
  const activeLink = sidebar.querySelector('.nav-link:not(.collapsed)');
  if (activeLink) {
    activeLink.scrollIntoView({ block: 'center', behavior: 'instant' });
  }
});