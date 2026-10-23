/* Conciliación de Despachos — interacciones */

(function () {
    'use strict';

    window.CD = {
        showProgress: function (mensaje) {
            var el = document.getElementById('cd-progress');
            if (!el) return;
            var span = el.querySelector('span');
            if (span && mensaje) span.textContent = mensaje;
            el.classList.add('show');
        },
        hideProgress: function () {
            var el = document.getElementById('cd-progress');
            if (el) el.classList.remove('show');
        },
        bindFileInputs: function () {
            document.querySelectorAll('.cd-file-input').forEach(function (input) {
                input.addEventListener('change', function () {
                    var card = input.closest('.cd-upload-card');
                    if (!card) return;
                    var label = card.querySelector('.cd-file-name');
                    if (label) {
                        label.textContent = this.files && this.files[0]
                            ? this.files[0].name
                            : 'Ningún archivo seleccionado';
                    }
                    if (this.files && this.files[0]) {
                        var btn = card.querySelector('button[type="submit"]');
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fa-solid fa-upload"></i> Cargar archivo';
                        }
                    }
                });
            });
        },
        bindForms: function () {
            document.querySelectorAll('form.cd-needs-progress').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var msg = form.getAttribute('data-progress-msg') || 'Procesando...';
                    CD.showProgress(msg);
                    var btn = form.querySelector('button[type="submit"].cd-submit');
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
                    }
                });
            });
        },
        bindTabs: function () {
            document.querySelectorAll('.cd-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var target = tab.getAttribute('data-tab');
                    document.querySelectorAll('.cd-tab').forEach(function (t) {
                        t.classList.toggle('active', t === tab);
                    });
                    document.querySelectorAll('.cd-tab-panel').forEach(function (panel) {
                        panel.style.display = panel.id === 'tab-' + target ? '' : 'none';
                    });
                });
            });
        },
        bindFilters: function () {
            var inputs = document.querySelectorAll('.cd-filter-input');
            if (!inputs.length) return;

            inputs.forEach(function (input) {
                input.addEventListener('input', aplicarFiltros);
                input.addEventListener('change', aplicarFiltros);
            });

            var limpiar = document.getElementById('cd-limpiar-filtros');
            if (limpiar) {
                limpiar.addEventListener('click', function () {
                    inputs.forEach(function (i) { i.value = ''; });
                    aplicarFiltros();
                });
            }
        },
        detail: function (btn) {
            var data = btn.getAttribute('data-detail');
            if (!data) return;
            try {
                var obj = JSON.parse(data);
                var html = '<div style="text-align:left;font-size:.85rem">';
                Object.keys(obj).forEach(function (k) {
                    var v = obj[k];
                    if (Array.isArray(v)) v = v.join(', ');
                    if (v === null || v === undefined || v === '') v = '—';
                    html += '<p style="margin:.25rem 0"><strong>' + escapeHtml(k) + ':</strong> ' + escapeHtml(String(v)) + '</p>';
                });
                html += '</div>';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Detalle del registro', html: html, width: 560 });
                } else {
                    alert(html.replace(/<[^>]+>/g, ' '));
                }
            } catch (e) {
                console.error(e);
            }
        },
    };

    function aplicarFiltros() {
        var filtroParte = (val('f-part') || '').toLowerCase();
        var filtroLote = (val('f-lote') || '').toLowerCase();
        var filtroEstado = (val('f-estado') || '').toLowerCase();
        var filtroTrailer = (val('f-trailer') || '').toLowerCase();
        var filtroConsign = (val('f-consign') || '').toLowerCase();
        var filtroTipo = (val('f-tipo') || '').toLowerCase();
        var filtroDespacho = (val('f-despacho') || '').toLowerCase();

        document.querySelectorAll('tbody.cd-filter-body').forEach(function (tbody) {
            tbody.querySelectorAll('tr').forEach(function (tr) {
                var visible =
                    coincide(tr.getAttribute('data-material'), filtroParte) &&
                    coincide(tr.getAttribute('data-lote'), filtroLote) &&
                    coincide(tr.getAttribute('data-estado'), filtroEstado) &&
                    coincide(tr.getAttribute('data-trailer'), filtroTrailer) &&
                    coincide(tr.getAttribute('data-consignatario'), filtroConsign) &&
                    coincide(tr.getAttribute('data-tipos'), filtroTipo) &&
                    coincide(tr.getAttribute('data-despacho'), filtroDespacho);
                tr.style.display = visible ? '' : 'none';
            });
        });
    }

    function coincide(haystack, needle) {
        if (!needle) return true;
        return (haystack || '').toLowerCase().indexOf(needle) !== -1;
    }

    function val(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function () {
        CD.bindFileInputs();
        CD.bindForms();
        CD.bindTabs();
        CD.bindFilters();
    });
})();
