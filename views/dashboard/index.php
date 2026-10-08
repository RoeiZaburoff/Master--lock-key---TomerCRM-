<?php $statusLabels = ['progress' => 'Progress', 'scheduled' => 'Scheduled', 'pending_payment' => 'Pending Payment', 'canceled' => 'Canceled', 'completed' => 'Completed', 'deposit' => 'Progress']; ?>

<!-- Page header -->
<div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
  <div>
    <h1 class="text-xl font-bold text-slate-900">Dashboard</h1>
    <p class="text-sm text-slate-500 mt-0.5"><?= date('l, F j, Y') ?></p>
  </div>
  <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-lg p-1 shadow-sm">
    <a href="?period=daily"
       class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $current_period === 'daily'   ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' ?>">Daily</a>
    <a href="?period=weekly"
       class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $current_period === 'weekly'  ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' ?>">Weekly</a>
    <a href="?period=monthly"
       class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $current_period === 'monthly' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' ?>">Monthly</a>
  </div>
</div>

<!-- Stat cards -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">

  <a href="<?= url('leads?period=' . $current_period) ?>"
     class="group block bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-150 no-underline">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Total Leads</p>
    <p class="text-3xl font-bold text-slate-900"><?= (int) $daily_jobs ?></p>
  </a>

  <a href="<?= url('leads?status=active&period=' . $current_period) ?>"
     class="group block bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-150 no-underline">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Active Leads</p>
    <p class="text-3xl font-bold text-blue-600"><?= (int) ($status_counts['progress'] ?? 0) + (int) ($status_counts['scheduled'] ?? 0) ?></p>
  </a>

  <a href="<?= url('leads?status=completed&period=' . $current_period) ?>"
     class="group block bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-150 no-underline">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Completed</p>
    <p class="text-3xl font-bold text-emerald-600"><?= (int) $count_completed ?></p>
  </a>

  <a href="<?= url('leads?status=canceled&period=' . $current_period) ?>"
     class="group block bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-150 no-underline">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Canceled</p>
    <p class="text-3xl font-bold text-red-600"><?= (int) $count_canceled ?></p>
  </a>

  <a href="<?= url('leads?status=completed&period=' . $current_period) ?>"
     class="group block bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-150 no-underline">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3"><?= ucfirst($current_period) ?> Net Profit</p>
    <p class="text-3xl font-bold <?= $period_net_profit < 0 ? 'text-red-600' : 'text-emerald-600' ?>"><?= money($period_net_profit) ?></p>
  </a>

  <a href="<?= url('leads?status=pending_payment') ?>"
     class="group block bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-150 no-underline">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Pending Payments</p>
    <p class="text-3xl font-bold text-amber-600"><?= money($pending_payment) ?></p>
  </a>

</div>

<!-- Quick actions -->
<div class="flex items-center gap-3 mb-6 flex-wrap">
  <a href="<?= url('leads/create') ?>"
     class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
    + New Lead
  </a>
  <a href="<?= url('customers/create') ?>"
     class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
    + New Customer
  </a>
  <a href="<?= url('calendar') ?>"
     class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
    View Calendar
  </a>
</div>

<!-- Follow-up Estimates widget -->
<?php if (!empty($follow_up_estimates)): ?>
<div class="bg-white rounded-xl border border-amber-200 shadow-sm overflow-hidden mb-6">
  <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-amber-100 flex-wrap">
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center justify-center w-2 h-2 rounded-full bg-red-500"></span>
      <h2 class="text-sm font-semibold text-slate-900">Open Estimates Requiring Follow-up</h2>
      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700"><?= count($follow_up_estimates) ?></span>
    </div>
    <a href="<?= url('leads?status=estimate') ?>"
       class="text-xs font-medium text-amber-700 hover:text-amber-900 no-underline transition-colors">View all estimates &rarr;</a>
  </div>
  <div class="divide-y divide-slate-100">
    <?php foreach ($follow_up_estimates as $est): ?>
    <div class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-amber-50/40 transition-colors flex-wrap">
      <div class="flex items-center gap-3 min-w-0">
        <div class="min-w-0">
          <p class="text-sm font-medium text-slate-900 truncate"><?= h($est['customer_name']) ?></p>
          <?php if (!empty($est['customer_phone'])): ?>
          <p class="text-xs text-slate-400"><?= h(format_phone($est['customer_phone'])) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="flex items-center gap-4 shrink-0 flex-wrap">
        <div class="text-right">
          <?php if (!empty($est['estimate_amount'])): ?>
          <p class="text-sm font-semibold text-slate-900"><?= money((float) $est['estimate_amount']) ?></p>
          <?php endif; ?>
          <?php $_fup_days = (int) floor((strtotime($est['follow_up_date']) - strtotime('today')) / 86400); ?>
          <p class="text-xs <?= $_fup_days < 0 ? 'text-red-600 font-semibold' : 'text-amber-600' ?>">
            <?php if ($_fup_days < 0): ?>
              <?= abs($_fup_days) ?> day<?= abs($_fup_days) !== 1 ? 's' : '' ?> overdue
            <?php else: ?>
              Follow-up: <?= h($est['follow_up_date']) ?>
            <?php endif; ?>
          </p>
        </div>
        <a href="<?= url('leads/' . (int) $est['job_id']) ?>"
           class="inline-flex items-center px-3 py-1.5 rounded-lg border border-amber-200 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-semibold transition-colors duration-150 no-underline">
          View
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Schedule card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

  <!-- Card header -->
  <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-slate-100 flex-wrap">
    <h2 id="schedule-title" class="text-sm font-semibold text-slate-900">
      Today&#8217;s Schedule &#8212; <?= date('F j, Y') ?>
    </h2>
    <div class="flex items-center gap-2 flex-wrap">
      <input type="date" id="schedule-date" value="<?= date('Y-m-d') ?>"
             class="px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-700 bg-white
                    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    transition cursor-pointer">
      <a href="<?= url('leads?status=scheduled') ?>"
         class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white
                hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        All Scheduled
      </a>
    </div>
  </div>

  <!-- Map container — class="schedule-map" required by JS -->
  <div id="schedule-map" class="schedule-map"></div>

  <!-- Schedule table — id required by JS -->
  <div id="schedule-table-wrap">
    <?php if (empty($today_leads)): ?>
      <div class="empty-state">No leads scheduled for today.</div>
    <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Time</th>
          <th>Customer</th>
          <th>Phone</th>
          <th>Address</th>
          <th>Company</th>
          <th>Status</th>
          <th>Total</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($today_leads as $job):
          $fullAddr = implode(', ', array_filter([$job['address_line1'], $job['city'], $job['state'], $job['zip'] ?? '']));
        ?>
        <tr data-address="<?= h($fullAddr) ?>" data-label="<?= h($job['customer_name']) ?>">
          <td><?= !empty($job['scheduled_time']) ? date('g:i A', strtotime($job['scheduled_time'])) : '—' ?></td>
          <td><?= h($job['customer_name']) ?></td>
          <td style="white-space:nowrap;"><a href="tel:<?= h(phone_digits($job['customer_phone'] ?? '')) ?>"><?= h(format_phone($job['customer_phone'] ?? '')) ?></a></td>
          <td><?= $fullAddr ? address_maps_link($fullAddr) : '—' ?></td>
          <td><?= !empty($job['company_name']) ? h($job['company_name']) : '—' ?></td>
          <td>
            <span class="badge badge-<?= h($job['status']) ?>"><?= h($statusLabels[$job['status']] ?? ucfirst($job['status'])) ?></span>
            <?php if (!empty($job['deposit_received'])): ?><span class="badge badge-deposit">Deposit</span><?php endif; ?>
          </td>
          <td><?= $job['job_total'] > 0 ? money($job['job_total']) : '—' ?></td>
          <td><a href="<?= url('leads/' . $job['job_id']) ?>" class="btn btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

</div>

<!-- Past Due Jobs -->
<?php if (!empty($past_due_jobs)): ?>
<div class="bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden mt-6">
  <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-red-100 flex-wrap">
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center justify-center w-2 h-2 rounded-full bg-red-500"></span>
      <h2 class="text-sm font-semibold text-slate-900">Past Due Jobs</h2>
      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700"><?= count($past_due_jobs) ?></span>
    </div>
    <a href="<?= url('leads?status=scheduled') ?>"
       class="text-xs font-medium text-red-700 hover:text-red-900 no-underline transition-colors">View all scheduled &rarr;</a>
  </div>
  <div class="overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th>Date</th>
          <th>Time</th>
          <th>Customer</th>
          <th>Phone</th>
          <th>Address</th>
          <th>Company</th>
          <th>Status</th>
          <th>Total</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($past_due_jobs as $pdj):
          $pdAddr = implode(', ', array_filter([$pdj['address_line1'] ?? '', $pdj['city'] ?? '', $pdj['state'] ?? '']));
        ?>
        <tr>
          <td><?= h($pdj['scheduled_date']) ?></td>
          <td><?= !empty($pdj['scheduled_time']) ? date('g:i A', strtotime($pdj['scheduled_time'])) : '—' ?></td>
          <td><?= h($pdj['customer_name']) ?></td>
          <td style="white-space:nowrap;"><a href="tel:<?= h(phone_digits($pdj['customer_phone'] ?? '')) ?>"><?= h(format_phone($pdj['customer_phone'] ?? '')) ?></a></td>
          <td><?= $pdAddr ? address_maps_link($pdAddr) : '—' ?></td>
          <td><?= !empty($pdj['company_name']) ? h($pdj['company_name']) : '—' ?></td>
          <td><span class="badge badge-<?= h($pdj['status']) ?>"><?= h($statusLabels[$pdj['status']] ?? ucfirst($pdj['status'])) ?></span></td>
          <td><?= $pdj['job_total'] > 0 ? money($pdj['job_total']) : '—' ?></td>
          <td><a href="<?= url('leads/' . (int) $pdj['job_id']) ?>" class="btn btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
(function () {
  var dateInput    = document.getElementById('schedule-date');
  var schedTitle   = document.getElementById('schedule-title');
  var tableWrap    = document.getElementById('schedule-table-wrap');
  var mapEl        = document.getElementById('schedule-map');
  if (!dateInput || !tableWrap) return;

  var dashMap      = null;
  var dashGeocoder = null;
  var markers      = [];
  var STATUS_LABELS = { progress: 'Progress', scheduled: 'Scheduled', pending_payment: 'Pending Payment', canceled: 'Canceled', completed: 'Completed', deposit: 'Progress' };
  var LEADS_URL    = '<?= h(rtrim(url('leads'), '/')) ?>';
  var SCHEDULE_URL = '<?= h(url('dashboard/schedule')) ?>';

  window.dashboardMapInit = function () {
    if (!mapEl) return;
    console.log('[Map] dashboardMapInit called');
    try {
      mapEl.classList.add('is-ready');
      console.log('[Map] container height:', mapEl.offsetHeight);
      if (mapEl.offsetHeight === 0) { mapEl.style.height = '400px'; }
      dashGeocoder = new google.maps.Geocoder();
      dashMap      = new google.maps.Map(mapEl, {
        center: { lat: 39.5, lng: -98.35 },
        zoom: 4,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
      });
      google.maps.event.addListenerOnce(dashMap, 'idle', function () {
        google.maps.event.trigger(dashMap, 'resize');
        setTimeout(function () {
          dashMap.setCenter(dashMap.getCenter());
          console.log('[Map] Reflow triggered');
        }, 150);
      });
      console.log('[Map] Map initialized');
      geocodeCurrentLeads();
    } catch (e) {
      console.error('[Map] Init error:', e);
    }
  };

  function clearMarkers() {
    markers.forEach(function (m) { m.setMap(null); });
    markers = [];
  }

  function geocodeCurrentLeads() {
    if (!dashMap || !dashGeocoder) return;
    clearMarkers();
    var rows = tableWrap.querySelectorAll('tbody tr[data-address]');
    if (!rows.length) return;
    var bounds = new google.maps.LatLngBounds();
    var done = 0, total = rows.length;
    rows.forEach(function (row) {
      var addr  = row.getAttribute('data-address');
      var label = row.getAttribute('data-label') || '';
      dashGeocoder.geocode({ address: addr, region: 'us' }, function (results, status) {
        done++;
        console.log('[Geocode] ' + addr + ' → ' + status);
        if (status === 'OK' && results[0]) {
          var pos    = results[0].geometry.location;
          var marker = new google.maps.Marker({ map: dashMap, position: pos, title: label });
          var info   = new google.maps.InfoWindow({
            content: '<div style="font-size:13px;line-height:1.5"><strong>' + esc(label) + '</strong><br>' + esc(addr) + '</div>'
          });
          marker.addListener('click', function () { info.open(dashMap, marker); });
          markers.push(marker);
          bounds.extend(pos);
        }
        if (done === total && markers.length) {
          dashMap.fitBounds(bounds);
          if (markers.length === 1) dashMap.setZoom(14);
        }
      });
    });
  }

  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function fmtPhone(p) {
    if (!p) return '\u2014';
    var d = p.replace(/\D/g, '');
    if (d.length === 11 && d[0] === '1') d = d.slice(1);
    if (d.length === 10) return '(' + d.slice(0, 3) + ') ' + d.slice(3, 6) + '-' + d.slice(6);
    return p;
  }

  function fmtAddr(addr, enc) {
    if (!addr) return '\u2014';
    return '<a href="javascript:void(0)" class="maps-dropdown-trigger" data-address="' + esc(enc) + '">' + esc(addr) + '</a>';
  }

  function fmtTime(t) {
    if (!t) return '\u2014';
    var p = t.split(':'), h = parseInt(p[0], 10), m = p[1];
    return (h % 12 || 12) + ':' + m + ' ' + (h >= 12 ? 'PM' : 'AM');
  }

  function fmtMoney(v) {
    var n = parseFloat(v);
    return n > 0 ? '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '\u2014';
  }

  function fmtTitle(dateStr) {
    var d   = new Date(dateStr + 'T00:00:00');
    var tod = new Date();
    tod.setHours(0, 0, 0, 0);
    var fmt = d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    return (d.getTime() === tod.getTime() ? 'Today\u2019s Schedule' : 'Schedule') + ' \u2014 ' + fmt;
  }

  function renderTable(leads) {
    if (!leads || !leads.length) {
      tableWrap.innerHTML = '<div class="empty-state">No leads scheduled for this date.</div>';
      return;
    }
    var html = '<table class="table"><thead><tr>'
      + '<th>Time</th><th>Customer</th><th>Phone</th>'
      + '<th>Address</th><th>Company</th><th>Status</th><th>Total</th><th></th>'
      + '</tr></thead><tbody>';
    leads.forEach(function (j) {
      var addr     = [j.address_line1, j.city, j.state].filter(Boolean).join(', ');
      var fullAddr = [j.address_line1, j.city, j.state, j.zip].filter(Boolean).join(', ');
      var sl       = STATUS_LABELS[j.status] || (j.status.charAt(0).toUpperCase() + j.status.slice(1));
      var dep      = j.deposit_received ? ' <span class="badge badge-deposit">Deposit</span>' : '';
      var phoneNum = j.customer_phone || '';
      html += '<tr data-address="' + esc(fullAddr) + '" data-label="' + esc(j.customer_name) + '">'
        + '<td>' + esc(fmtTime(j.scheduled_time)) + '</td>'
        + '<td>' + esc(j.customer_name) + '</td>'
        + '<td style="white-space:nowrap"><a href="tel:' + esc(phoneNum.replace(/\D/g, '')) + '">' + esc(fmtPhone(phoneNum)) + '</a></td>'
        + '<td>' + fmtAddr(addr, encodeURIComponent(fullAddr)) + '</td>'
        + '<td>' + esc(j.company_name || '\u2014') + '</td>'
        + '<td><span class="badge badge-' + esc(j.status) + '">' + esc(sl) + '</span>' + dep + '</td>'
        + '<td>' + fmtMoney(j.job_total) + '</td>'
        + '<td><a href="' + LEADS_URL + '/' + j.job_id + '" class="btn btn-sm">View</a></td>'
        + '</tr>';
    });
    html += '</tbody></table>';
    tableWrap.innerHTML = html;
    applyMobileLabels(tableWrap.querySelector('table'));
    geocodeCurrentLeads();
  }

  function applyMobileLabels(table) {
    if (!table) return;
    var hdrs = Array.from(table.querySelectorAll('thead th')).map(function (th) { return th.textContent.trim(); });
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      tr.querySelectorAll('td').forEach(function (td, i) {
        if (hdrs[i]) td.setAttribute('data-label', hdrs[i]);
      });
    });
  }

  dateInput.addEventListener('change', function () {
    var date = this.value;
    schedTitle.textContent = fmtTitle(date);
    tableWrap.innerHTML = '<div class="empty-state">Loading\u2026</div>';
    clearMarkers();
    fetch(SCHEDULE_URL + '?date=' + encodeURIComponent(date), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) { console.log('[AJAX] schedule response:', data); renderTable(data); })
      .catch(function () {
        tableWrap.innerHTML = '<div class="empty-state">Failed to load schedule.</div>';
      });
  });
})();
</script>
