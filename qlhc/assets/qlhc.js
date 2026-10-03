/* Module Quản lý hành chính — tương tác phía trình duyệt (không cần thư viện ngoài) */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---------- Menu trên điện thoại ---------- */
  var tg = $('[data-nav-toggle]'), nav = $('[data-nav]');
  if (tg && nav) {
    tg.addEventListener('click', function () { nav.classList.toggle('open'); });
    document.addEventListener('click', function (e) {
      if (nav.classList.contains('open') && !nav.contains(e.target) && e.target !== tg) nav.classList.remove('open');
    });
  }

  $$('[data-print]').forEach(function (b) {
    b.addEventListener('click', function () {
      $$('details').forEach(function (d) { d.open = true; });
      window.print();
    });
  });

  /* ---------- Segmented radio ---------- */
  $$('input[data-seg-radio], input[data-he-switch]').forEach(function (r) {
    r.addEventListener('change', function () {
      $$('input[name="' + r.name + '"]').forEach(function (x) { x.closest('label').classList.toggle('on', x.checked); });
    });
  });

  /* ---------- Tra cứu: lọc nhanh ---------- */
  var filter = $('[data-filter]');
  if (filter) {
    var sections = $$(filter.getAttribute('data-filter'));
    var norm = function (s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd'); };
    filter.addEventListener('input', function () {
      var q = norm(filter.value.trim());
      sections.forEach(function (sec) {
        var any = false;
        var rows = $$('tbody tr', sec);
        rows.forEach(function (tr) {
          var hit = !q || norm(tr.textContent).indexOf(q) >= 0;
          tr.classList.toggle('tt-hidden', !hit);
          if (hit) any = true;
        });
        $$('.tt-body > p', sec).forEach(function (p) { if (q && norm(p.textContent).indexOf(q) >= 0) any = true; });
        if (!rows.length && !q) any = true;
        if (q && norm($('summary', sec).textContent).indexOf(q) >= 0) {
          any = true;
          rows.forEach(function (tr) { tr.classList.remove('tt-hidden'); });
        }
        sec.classList.toggle('tt-hidden', !any);
        if (q && any) sec.open = true;
      });
    });
  }
  var ea = $('[data-expand-all]'), ca = $('[data-collapse-all]');
  if (ea) ea.addEventListener('click', function () { $$('details.tt-section').forEach(function (d) { d.open = true; }); });
  if (ca) ca.addEventListener('click', function () { $$('details.tt-section').forEach(function (d) { d.open = false; }); });

  /* ---------- Lọc loại văn bản theo hệ (HC / Đảng) ---------- */
  function heHienTai(form) {
    var r = $('input[name="he"]:checked', form);
    return r ? r.value : 'hc';
  }
  function locTheoHe(form) {
    var he = heHienTai(form);
    var sel = $('[data-loai]', form);
    if (sel) {
      var cur = sel.options[sel.selectedIndex];
      $$('option', sel).forEach(function (o) { o.hidden = o.dataset.he !== he; o.disabled = o.dataset.he !== he; });
      if (!cur || cur.dataset.he !== he) {
        var first = $$('option', sel).filter(function (o) { return o.dataset.he === he; })[0];
        if (first) sel.value = first.value;
      }
    }
    var nk = $('[data-nguoi-ky]', form);
    if (nk) {
      $$('option', nk).forEach(function (o) { if (o.value) { o.hidden = o.dataset.he !== he; } });
    }
  }

  /* ---------- Biên tập dự thảo ---------- */
  var frm = $('#frm-du-thao');
  if (frm) {
    var preview = $('[data-preview]'), status = $('[data-preview-status]');
    var loai = $('[data-loai]', frm);
    var mauSel = $('[data-mau]', frm);
    var MAU = {};
    try { MAU = JSON.parse(($('#mau-noi-dung') || {}).textContent || '{}'); } catch (e) { MAU = {}; }

    var fit = function () {
      var a4 = $('.a4', preview);
      if (!a4) return;
      a4.style.zoom = 1;
      var w = preview.clientWidth - 32;
      var z = Math.min(1, w / a4.offsetWidth);
      a4.style.zoom = z.toFixed(3);
    };

    var capNhatGiaoDien = function () {
      var o = loai && loai.options[loai.selectedIndex];
      var laCV = o && o.dataset.cv === '1';
      var ten = o ? (o.dataset.ten || '').toLowerCase() : '';
      var kg = $('[data-kinh-gui]', frm);
      if (kg) kg.style.display = (laCV || ten === 'tờ trình') ? '' : 'none';
      var lb = $('[data-ty-label]', frm);
      if (lb) lb.textContent = laCV ? 'Trích yếu (V/v)' : 'Trích yếu';
      if (mauSel) {
        var ds = MAU[o ? o.dataset.ten : ''] || {};
        mauSel.innerHTML = '<option value="">' + (Object.keys(ds).length ? '— Chọn mẫu soạn sẵn cho ' + (o ? o.dataset.ten : '') + ' —' : '— Chưa có mẫu cho loại này —') + '</option>';
        Object.keys(ds).forEach(function (k) {
          var op = document.createElement('option');
          op.value = k; op.textContent = k; mauSel.appendChild(op);
        });
      }
    };

    var timer = null, seq = 0;
    var taiXemTruoc = function () {
      if (!preview) return;
      clearTimeout(timer);
      timer = setTimeout(function () {
        var fd = new FormData(frm);
        fd.set('action', 'xem_truoc');
        var my = ++seq;
        if (status) status.textContent = 'Đang cập nhật…';
        fetch(frm.dataset.previewUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
          .then(function (html) {
            if (my !== seq) return;
            preview.innerHTML = html;
            fit();
            if (status) status.textContent = 'Đã cập nhật';
          })
          .catch(function () { if (status) status.textContent = 'Không cập nhật được (phiên có thể đã hết hạn)'; });
      }, 450);
    };

    $$('input[data-he-switch]', frm).forEach(function (r) {
      r.addEventListener('change', function () { locTheoHe(frm); capNhatGiaoDien(); taiXemTruoc(); });
    });
    if (loai) loai.addEventListener('change', function () { capNhatGiaoDien(); taiXemTruoc(); });

    var nk = $('[data-nguoi-ky]', frm);
    if (nk) nk.addEventListener('change', function () {
      var o = nk.options[nk.selectedIndex];
      if (!o || !o.value) return;
      frm.elements.quyen_han.value = o.dataset.qh || '';
      frm.elements.thay_mat.value = o.dataset.tm || '';
      frm.elements.chuc_vu.value = o.dataset.cv || '';
      frm.elements.ho_ten.value = o.dataset.ht || '';
      taiXemTruoc();
    });

    if (mauSel) mauSel.addEventListener('change', function () {
      var o = loai.options[loai.selectedIndex];
      var m = (MAU[o.dataset.ten] || {})[mauSel.value];
      if (!m) return;
      var dangCo = frm.elements.noi_dung.value.trim() !== '';
      if (dangCo && !confirm('Thay nội dung hiện tại bằng mẫu "' + mauSel.value + '"?')) { mauSel.value = ''; return; }
      ['trich_yeu', 'noi_dung', 'noi_nhan', 'kinh_gui'].forEach(function (k) {
        if (m[k] !== undefined && frm.elements[k]) frm.elements[k].value = m[k];
      });
      mauSel.value = '';
      taiXemTruoc();
    });

    frm.addEventListener('input', taiXemTruoc);
    window.addEventListener('resize', fit);
    locTheoHe(frm);
    capNhatGiaoDien();
    fit();

    // Cảnh báo khi rời trang chưa lưu
    var dirty = false;
    frm.addEventListener('input', function () { dirty = true; });
    frm.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }

  /* ---------- Sổ văn bản đi: xem trước số tiếp theo ---------- */
  var soForm = $('form[data-so-tiep]');
  if (soForm && $('[data-loai]', soForm)) {
    var out = $('[data-so-preview]', soForm);
    var lay = function () {
      var p = new URLSearchParams({
        so_tiep: 1,
        loai_id: soForm.elements.loai_id.value,
        don_vi_id: soForm.elements.don_vi_soan_id ? soForm.elements.don_vi_soan_id.value : 0,
        ngay: soForm.elements.ngay_ban_hanh.value
      });
      fetch('van-ban-di.php?' + p.toString(), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (j) { if (out && j.ok) out.textContent = 'Số sẽ cấp: ' + j.text; })
        .catch(function () {});
    };
    $$('input[data-he-switch]', soForm).forEach(function (r) { r.addEventListener('change', function () { locTheoHe(soForm); lay(); }); });
    ['loai_id', 'don_vi_soan_id', 'ngay_ban_hanh'].forEach(function (n) {
      if (soForm.elements[n]) soForm.elements[n].addEventListener('change', lay);
    });
    locTheoHe(soForm);
    lay();
  }

  /* ---------- Kéo thả file ---------- */
  $$('[data-dropzone]').forEach(function (z) {
    var inp = $('input[type=file]', z), name = $('[data-file-name]', z);
    var show = function () { if (inp.files.length && name) name.textContent = inp.files[0].name; };
    inp.addEventListener('change', show);
    ['dragenter', 'dragover'].forEach(function (ev) { z.addEventListener(ev, function (e) { e.preventDefault(); z.classList.add('drag'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { z.addEventListener(ev, function (e) { e.preventDefault(); z.classList.remove('drag'); }); });
    z.addEventListener('drop', function (e) {
      if (e.dataTransfer && e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; show(); }
    });
  });
})();
