(function () {
  'use strict';

  // Maps dropdown — inject styles once
  (function () {
    var style = document.createElement('style');
    style.textContent = '.maps-dropdown{position:absolute;z-index:9999;background:#fff;border:1px solid #d1d5db;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.12);min-width:150px;overflow:hidden}.maps-dropdown a{display:block;padding:9px 14px;font-size:13px;color:#111;text-decoration:none;white-space:nowrap}.maps-dropdown a:hover{background:#f3f4f6}';
    document.head.appendChild(style);
  }());

  var _mapsDropdown = null;

  function closeMapsDropdown() {
    if (_mapsDropdown) { _mapsDropdown.remove(); _mapsDropdown = null; }
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('.maps-dropdown-trigger');
    if (trigger) {
      e.preventDefault();
      var addr      = trigger.dataset.address;
      var isSame    = _mapsDropdown && _mapsDropdown.dataset.triggerAddr === addr;
      closeMapsDropdown();
      if (isSame) { return; }
      var menu = document.createElement('div');
      menu.className = 'maps-dropdown';
      menu.dataset.triggerAddr = addr;
      menu.innerHTML =
        '<a href="https://maps.google.com/?q=' + addr + '" target="_blank" rel="noopener">Google Maps</a>' +
        '<a href="https://maps.apple.com/?q='  + addr + '" target="_blank" rel="noopener">Apple Maps</a>';
      document.body.appendChild(menu);
      var rect = trigger.getBoundingClientRect();
      var top  = rect.bottom + window.scrollY + 4;
      var left = rect.left   + window.scrollX;
      if (left + 160 > window.innerWidth) { left = window.innerWidth - 164; }
      menu.style.top  = top  + 'px';
      menu.style.left = left + 'px';
      _mapsDropdown = menu;
      return;
    }
    if (_mapsDropdown && !_mapsDropdown.contains(e.target)) {
      closeMapsDropdown();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeMapsDropdown(); }
  });

  // Global Copy Lead Functionality
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.copy-lead-btn');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();
    var d = btn.dataset;

    var text =
      'Company: '       + (d.company  || '—') + '\n\n' +
      'Customer Name: ' + (d.customer || '—') + '\n\n' +
      'Phone Number: '  + (d.phone    || '—') + '\n\n' +
      'Address: '       + (d.address  || '—') + '\n\n' +
      'Schedule: '      + (d.schedule || '—') + '\n\n' +
      'Note: '          + (d.notes    || '—');

    var textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.top = '-9999px';
    textArea.style.left = '-9999px';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      var successful = document.execCommand('copy');
      if (successful) {
        var orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function() { btn.textContent = orig; }, 2000);
      } else {
        throw new Error('execCommand failed');
      }
    } catch (err) {
      console.error('Fallback copy failed', err);
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
          var orig = btn.textContent;
          btn.textContent = 'Copied!';
          setTimeout(function() { btn.textContent = orig; }, 2000);
        });
      }
    } finally {
      document.body.removeChild(textArea);
    }
  });

  // Special Closing toggle
  (function () {
    var checkbox  = document.getElementById('is_special_closing');
    var section   = document.getElementById('special-closing-section');
    var amtInput  = document.getElementById('provider_retained_amount');
    if (!checkbox || !section || !amtInput) return;

    function syncState() {
      var checked = checkbox.checked;
      section.style.display  = checked ? '' : 'none';
      amtInput.required      = checked;
      if (!checked) { amtInput.value = ''; }
    }

    checkbox.addEventListener('change', syncState);
    syncState();
  }());

  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });

  var alerts = document.querySelectorAll('.alert');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity .5s';
      alert.style.opacity    = '0';
      setTimeout(function () { alert.remove(); }, 500);
    }, 4000);
  });

  // Mobile navigation toggle
  var navToggle = document.getElementById('nav-toggle');
  var sidebar   = document.getElementById('sidebar');
  var overlay   = document.getElementById('nav-overlay');
  if (navToggle && sidebar && overlay) {
    function openNav() {
      sidebar.classList.add('is-open');
      overlay.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    }
    function closeNav() {
      sidebar.classList.remove('is-open');
      overlay.classList.remove('is-open');
      document.body.style.overflow = '';
    }
    navToggle.addEventListener('click', openNav);
    overlay.addEventListener('click', closeNav);
    sidebar.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', closeNav);
    });
  }

  // Auto data-label for mobile table-to-card transformation
  document.querySelectorAll('table.table:not(.table-raw)').forEach(function (table) {
    var headers = Array.from(table.querySelectorAll('thead th')).map(function (th) {
      return th.textContent.trim();
    });
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      tr.querySelectorAll('td').forEach(function (td, i) {
        if (headers[i]) { td.setAttribute('data-label', headers[i]); }
      });
    });
  });

  // Phone formatting (XXX) XXX-XXXX
  (function () {
    function formatPhone(raw) {
      var d = raw.replace(/\D/g, '').slice(0, 10);
      if (d.length < 4)  return d;
      if (d.length < 7)  return '(' + d.slice(0,3) + ') ' + d.slice(3);
      return '(' + d.slice(0,3) + ') ' + d.slice(3,6) + '-' + d.slice(6);
    }
    document.addEventListener('input', function (e) {
      var el = e.target;
      if (el.tagName === 'INPUT' && (el.type === 'tel' || el.name === 'phone' || el.id === 'quick-phone')) {
        var pos = el.selectionStart;
        var prev = el.value;
        el.value = formatPhone(prev);
        var diff = el.value.length - prev.length;
        el.setSelectionRange(pos + diff, pos + diff);
      }
    });
  }());

  // Quick-lead modal
  (function () {
    var modal     = document.getElementById('quick-lead-modal');
    var form      = document.getElementById('quick-lead-form');
    var dateInp   = document.getElementById('quick-date');
    var dateLabel = document.getElementById('quick-modal-date-label');
    var errBox    = document.getElementById('quick-modal-error');
    var submitBtn = document.getElementById('quick-submit-btn');
    if (!modal) return;

    var CAL_PASTELS = ['#fde8e8','#fef3c7','#d1fae5','#dbeafe','#ede9fe','#fce7f3','#e0f2fe','#fff7ed','#ecfdf5','#fdf4ff'];

    function escHtml(s) {
      return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function openModal(date) {
      if (dateInp) dateInp.value = date;
      if (dateLabel) {
        var d = new Date(date + 'T00:00:00');
        dateLabel.textContent = d.toLocaleDateString('en-US', {weekday:'short', month:'short', day:'numeric'});
      }
      if (errBox) errBox.style.display = 'none';
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      var first = modal.querySelector('select, input:not([type=hidden])');
      if (first) setTimeout(function () { first.focus(); }, 80);
    }

    function closeModal() {
      modal.style.display = 'none';
      document.body.style.overflow = '';
      if (form) form.reset();
      if (errBox) errBox.style.display = 'none';
    }

    document.addEventListener('click', function (e) {
      var qa = e.target.closest('.cal-quick-add');
      if (qa) { e.preventDefault(); e.stopPropagation(); openModal(qa.dataset.date || ''); return; }
      var qab = e.target.closest('.cal-quick-add-btn');
      if (qab) { e.preventDefault(); openModal(qab.dataset.date || ''); return; }
      if (e.target === modal) { closeModal(); }
    });

    var closeBtn  = document.getElementById('quick-modal-close');
    var cancelBtn = document.getElementById('quick-modal-cancel');
    if (closeBtn)  closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
    });

    if (!form) return;

    function injectLeadCard(res) {
      var dateVal  = dateInp ? dateInp.value : '';
      var custSel  = document.getElementById('quick-customer-select');
      var coSel    = document.getElementById('quick-company-select');
      var hourSel  = document.getElementById('quick-hour');
      var ampmSel  = document.getElementById('quick-ampm');
      var custName = custSel ? custSel.options[custSel.selectedIndex].text : '';
      var coName   = (coSel && coSel.value) ? coSel.options[coSel.selectedIndex].text : '';
      var hr       = parseInt(hourSel ? hourSel.value : '8', 10);
      var ap       = ampmSel ? ampmSel.value.toLowerCase() : 'am';
      var timeStr  = hr + ':00' + ap;
      var color    = CAL_PASTELS[Math.abs(res.id) % 10];
      var base     = (window.__crmBase || '');
      var cardHtml = '<a href="' + base + '/leads/' + res.id + '" class="cal-event-card status-scheduled" style="background:' + color + ';">' +
                     '<div class="event-time">' + escHtml(timeStr) + '</div>' +
                     '<div class="event-customer">' + escHtml(custName) + '</div>' +
                     (coName ? '<div class="event-company">' + escHtml(coName) + '</div>' : '') +
                     '</a>';
      // Handle two DOM patterns:
      // 1. Week table: <td data-date="X"><div class="cal-events"> — selector finds child
      // 2. Week list / day view: <div class="cal-events" data-date="X"> — element IS the target
      document.querySelectorAll('[data-date="' + dateVal + '"]').forEach(function (el) {
        var cont = el.classList.contains('cal-events') ? el : el.querySelector('.cal-events');
        if (!cont) return;
        var empty = cont.querySelector('.cal-no-leads, .cal-day-empty');
        if (empty) empty.remove();
        cont.insertAdjacentHTML('beforeend', cardHtml);
      });
    }

    function doSubmitLead() {
      fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
      })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Add Lead'; }
        if (res.success) {
          injectLeadCard(res);
          closeModal();
        } else {
          var msgs = [];
          if (res.errors) {
            Object.values(res.errors).forEach(function (v) {
              if (Array.isArray(v)) msgs = msgs.concat(v); else msgs.push(String(v));
            });
          }
          if (errBox) {
            errBox.textContent = msgs.join(' ') || 'Could not save lead.';
            errBox.style.display = '';
          }
        }
      })
      .catch(function () {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Add Lead'; }
        if (errBox) { errBox.textContent = 'Network error. Please try again.'; errBox.style.display = ''; }
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (errBox) errBox.style.display = 'none';

      var custSel = document.getElementById('quick-customer-select');
      if (custSel && !custSel.value) {
        if (errBox) { errBox.textContent = 'Please select or create a customer first.'; errBox.style.display = ''; }
        return;
      }

      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving…'; }
      doSubmitLead();
    });
  }());

})();
