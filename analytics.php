<?php
require 'auth.php';
// restrict to admin if desired
if (function_exists('require_role')) {
    // uncomment to require admin role
    // require_role('admin');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Analytics Dashboard</title>
  <link rel="stylesheet" href="css/styles.css?v=<?= time() ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="container">
  <h2>Analytics Dashboard</h2>

  <section style="margin-bottom:28px;">
    <h3>Supply List (Top items by quantity)</h3>
    <div class="chart-wrapper"><canvas id="supplyChart"></canvas></div>
  </section>

  <section style="margin-bottom:28px;">
    <h3>Stock Card (select item)</h3>
    <div style="display:flex;gap:12px;align-items:center;">
      <select id="itemSelect" style="padding:8px;border-radius:6px;border:1px solid #ddd;">
        <option value="">-- Select item --</option>
      </select>
      <div id="selectedInfo" style="color:#666;font-weight:600"></div>
    </div>
    <div class="chart-wrapper" style="margin-top:12px;"><canvas id="stockCardChart"></canvas></div>
  </section>

  <section>
    <h3>Low Stock Items</h3>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px;">
      <div style="color:#666;font-weight:600">Items at or below reorder point</div>
      <div>
        <button id="exportLowCsv" class="btn-export">Export CSV</button>
      </div>
    </div>
    <div id="lowStock"></div>
  </section>

</div>

<script>
fetch('analytics_data.php')
  .then(r => r.json())
  .then(json => {
    // Supply chart (bar)
    const supply = json.supply_list || [];
    const supplyLabels = supply.map(i => i.stock_number + ' - ' + i.item_name);
    const supplyData = supply.map(i => i.quantity);

    const supplyCtx = document.getElementById('supplyChart').getContext('2d');
    // compute a sensible max to avoid chart expanding too much
    const maxSupply = supplyData.length ? Math.max(...supplyData) : 0;
    const supplySuggestedMax = Math.max(5, Math.ceil(maxSupply * 1.15));
    const supplyGradient = supplyCtx.createLinearGradient(0, 0, 0, 300);
    supplyGradient.addColorStop(0, 'rgba(58,123,200,0.85)');
    supplyGradient.addColorStop(1, 'rgba(58,123,200,0.25)');

    new Chart(supplyCtx, {
      type: 'bar',
      data: {
        labels: supplyLabels,
        datasets: [{ label: 'Quantity', data: supplyData, backgroundColor: supplyGradient, borderColor: 'rgba(58,123,200,0.9)', borderWidth: 1 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { maxRotation: 45, minRotation: 0 } },
          y: { beginAtZero: true, suggestedMax: supplySuggestedMax }
        }
      }
    });

    // Stock Card: when an item is selected, fetch its monthly history and render
    const itemSelect = document.getElementById('itemSelect');
    const selectedInfo = document.getElementById('selectedInfo');
    const stockCtx = document.getElementById('stockCardChart').getContext('2d');
    let stockChart = null;

    function renderStockChart(labels, data) {
      const maxVal = data.length ? Math.max(...data) : 0;
      const suggestedMax = Math.max(5, Math.ceil(maxVal * 1.15));
      const grad = stockCtx.createLinearGradient(0, 0, 0, 300);
      grad.addColorStop(0, 'rgba(58,123,200,0.18)');
      grad.addColorStop(1, 'rgba(58,123,200,0.02)');

      if (stockChart) stockChart.destroy();
      stockChart = new Chart(stockCtx, {
        type: 'line',
        data: { labels: labels, datasets: [{ label: 'Quantity', data: data, borderColor: '#0038a8', backgroundColor: grad, fill: true, tension: 0.35, pointRadius: 4 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, suggestedMax: suggestedMax } } }
      });
    }

    // populate item select
    // reuse the `supply` variable defined above (avoid redeclaring `const supply`)
    supply.forEach(it => {
      const opt = document.createElement('option');
      opt.value = it.item_id;
      opt.textContent = `${it.stock_number} — ${it.item_name}`;
      itemSelect.appendChild(opt);
    });

    // when selection changes, fetch history
    itemSelect.addEventListener('change', () => {
      const id = itemSelect.value;
      if (!id) {
        selectedInfo.textContent = '';
        if (stockChart) { stockChart.destroy(); stockChart = null; }
        return;
      }
      const selected = supply.find(s => s.item_id == id);
      selectedInfo.textContent = `${selected.stock_number} — ${selected.item_name}`;
      fetch(`analytics_data.php?item_id=${encodeURIComponent(id)}`)
        .then(r => r.json())
        .then(d => {
          renderStockChart(d.labels || [], d.data || []);
        })
        .catch(err => console.error(err));
    });

    // Optionally select the first item by default
    if (supply.length > 0) {
      itemSelect.value = supply[0].item_id;
      itemSelect.dispatchEvent(new Event('change'));
    }

    // Low stock list (render as table and wire export)
    const low = json.low_stock || [];
    const lowDiv = document.getElementById('lowStock');
    if (low.length === 0) {
      lowDiv.innerHTML = '<p>No low-stock items.</p>';
    } else {
      const table = document.createElement('table');
      table.className = 'analytics-table';
      const thead = document.createElement('thead');
      thead.innerHTML = '<tr><th>Stock #</th><th>Item Name</th><th>Quantity</th><th>Reorder Point</th></tr>';
      table.appendChild(thead);
      const tbody = document.createElement('tbody');
      low.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${item.stock_number}</td><td>${item.item_name}</td><td>${item.quantity}</td><td>${item.reorder_point}</td>`;
        tbody.appendChild(tr);
      });
      table.appendChild(tbody);
      lowDiv.appendChild(table);
    }

    // Export button: download CSV from server endpoint
    const exportBtn = document.getElementById('exportLowCsv');
    if (exportBtn) {
      exportBtn.addEventListener('click', () => {
        // navigate to the CSV download endpoint; browser will handle the file save
        window.location.href = 'analytics_export.php?format=csv';
      });
    }

  }).catch(err => {
    console.error(err);
    alert('Error loading analytics data — check console for details.');
  });
</script>

</body>
</html>
