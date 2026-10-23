/*
 * Dynamic item rows for the Purchase and Billing forms.
 * cfg = { products:[{id,name,unit,price,stock}], rows:[...], priceReadonly:bool, showStock:bool }
 * The server recalculates everything again, so this is only for display.
 */
function initLines(cfg) {
  var tbody = document.getElementById('lines');
  var byId = {};
  cfg.products.forEach(function (p) { byId[p.id] = p; });

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function fmt(n) { return (Math.round(n * 100) / 100).toFixed(2); }
  function val(id) { var el = document.getElementById(id); return el ? (parseFloat(el.value) || 0) : 0; }
  function setText(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }

  function addRow(row) {
    row = row || {};
    var opts = '<option value="">-- Select product --</option>' + cfg.products.map(function (p) {
      return '<option value="' + p.id + '">' + esc(p.name) + ' (' + esc(p.unit) + ')</option>';
    }).join('');
    var tr = document.createElement('tr');
    tr.innerHTML =
      '<td><select name="product_id[]" class="form-select form-select-sm">' + opts + '</select><div class="form-text stock"></div></td>' +
      '<td style="width:120px"><input name="quantity[]" type="number" step="0.01" min="0.01" class="form-control form-control-sm qty" value="' + esc(row.quantity || 1) + '"></td>' +
      '<td style="width:140px"><input name="price[]" type="number" step="0.01" min="0" class="form-control form-control-sm price"' +
        (cfg.priceReadonly ? ' readonly tabindex="-1"' : '') + ' value="' + esc(row.price || '') + '"></td>' +
      '<td style="width:120px" class="text-end line-total">0.00</td>' +
      '<td style="width:50px"><button type="button" class="btn btn-sm btn-outline-danger rm">&times;</button></td>';
    tbody.appendChild(tr);
    tr.querySelector('select').value = row.product_id || '';
    if (row.product_id && cfg.priceReadonly) fillFromProduct(tr);
    recalc();
  }

  function fillFromProduct(tr) {
    var p = byId[tr.querySelector('select').value];
    var price = tr.querySelector('.price');
    if (p) {
      if (cfg.priceReadonly || !price.value) price.value = p.price;
      tr.querySelector('.stock').textContent = cfg.showStock ? 'In stock: ' + p.stock + ' ' + p.unit : '';
    } else {
      tr.querySelector('.stock').textContent = '';
    }
  }

  function recalc() {
    var sub = 0;
    tbody.querySelectorAll('tr').forEach(function (tr) {
      var line = (parseFloat(tr.querySelector('.qty').value) || 0) * (parseFloat(tr.querySelector('.price').value) || 0);
      tr.querySelector('.line-total').textContent = fmt(line);
      sub += line;
    });
    setText('sub', fmt(sub));
    if (document.getElementById('discount')) {
      var disc = Math.min(Math.max(val('discount'), 0), sub);
      var tax = (sub - disc) * val('tax_percent') / 100;
      setText('disc', '-' + fmt(disc));
      setText('tax', fmt(tax));
      setText('grand', fmt(sub - disc + tax));
    }
  }

  tbody.addEventListener('change', function (e) {
    if (e.target.tagName === 'SELECT') { fillFromProduct(e.target.closest('tr')); recalc(); }
  });
  tbody.addEventListener('input', recalc);
  tbody.addEventListener('click', function (e) {
    if (e.target.classList.contains('rm')) {
      if (tbody.children.length > 1) { e.target.closest('tr').remove(); recalc(); }
    }
  });
  ['discount', 'tax_percent'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', recalc);
  });
  document.getElementById('addRow').addEventListener('click', function () { addRow(); });

  (cfg.rows && cfg.rows.length ? cfg.rows : [{}]).forEach(addRow);
}
