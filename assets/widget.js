/* WhatsApp Lead Widget — Frontend JS */
(function () {
  "use strict";

  var cfg = window.wlwData || {};

  // ── SVG Icons ──────────────────────────────────────────────────────────────

  var WA_ICON =
    '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>';

  // ── Build a single field HTML ──────────────────────────────────────────────

  function buildFieldHtml(field) {
    var inputId = "wlw-" + field.id;
    var errorId = "err-" + field.id;
    var label = escHtml(field.label || "");
    var req = field.required ? " <span>*</span>" : "";
    var ph = escHtml(field.placeholder || "");
    var html = '<div class="wlw-field">';
    html += '<label for="' + inputId + '">' + label + req + "</label>";

    if (field.type === "select") {
      html += '<select id="' + inputId + '" name="' + field.id + '">';
      html += '<option value="">Selecione...</option>';
      var opts = (field.options || "").split("\n");
      for (var i = 0; i < opts.length; i++) {
        var opt = opts[i].trim();
        if (opt)
          html +=
            '<option value="' +
            escHtml(opt) +
            '">' +
            escHtml(opt) +
            "</option>";
      }
      html += "</select>";
    } else if (field.type === "textarea") {
      html +=
        '<textarea id="' +
        inputId +
        '" name="' +
        field.id +
        '" placeholder="' +
        ph +
        '"></textarea>';
    } else {
      html +=
        '<input type="' +
        field.type +
        '" id="' +
        inputId +
        '" name="' +
        field.id +
        '"';
      if (field.type === "email") html += ' autocomplete="email"';
      if (field.id === "full_name") html += ' autocomplete="name"';
      html += ' placeholder="' + ph + '" />';
    }

    html +=
      '<div class="wlw-field-error" id="' +
      errorId +
      '">Por favor, preencha este campo corretamente.</div>';
    html += "</div>";
    return html;
  }

  // ── Build Widget HTML ──────────────────────────────────────────────────────

  function buildWidget() {
    var pos = cfg.position === "left" ? "wlw-left" : "wlw-right";
    var fields = cfg.fields || [];

    var fieldsHtml = "";
    for (var i = 0; i < fields.length; i++) {
      if (fields[i].enabled !== false) fieldsHtml += buildFieldHtml(fields[i]);
    }

    var html = [
      // Floating button
      '<div id="wlw-float-btn" class="' +
        pos +
        '" role="button" tabindex="0" aria-label="Abrir chat WhatsApp">',
      '  <div class="wlw-fab">' + WA_ICON + "</div>",
      '  <div class="wlw-tooltip">' +
        escHtml(cfg.buttonMsg || "Fale conosco no WhatsApp!") +
        "</div>",
      "</div>",

      // Popover
      '<div id="wlw-modal" class="' +
        pos +
        '" role="dialog" aria-modal="true" aria-label="Formulário WhatsApp">',
      '  <div class="wlw-modal-header">',
      '    <div class="wlw-header-icon">' + WA_ICON + "</div>",
      '    <div class="wlw-header-text">',
      "      <h3>" + escHtml(cfg.title || "Falar no WhatsApp") + "</h3>",
      "      <p>" +
        escHtml(cfg.subtitle || "Preencha para iniciar a conversa") +
        "</p>",
      "    </div>",
      '    <button class="wlw-close-btn" id="wlw-close" aria-label="Fechar">✕</button>',
      "  </div>",
      '  <div class="wlw-modal-body">',
      '    <div id="wlw-global-error"></div>',
      fieldsHtml,
      // Honeypot
      '<div class="wlw-honeypot" aria-hidden="true">',
      '  <input type="text" id="wlw-website" name="wlw_website" tabindex="-1" autocomplete="off" />',
      "</div>",
      '    <button id="wlw-submit" class="wlw-submit-btn" type="button">',
      '      <span class="wlw-btn-icon">' + WA_ICON + "</span>",
      '      <span class="wlw-btn-text">Iniciar conversa no WhatsApp</span>',
      '      <div class="wlw-spinner"></div>',
      "    </button>",
      "  </div>",
      "</div>",
    ].join("");

    var root = document.getElementById("wlw-root");
    if (root) root.innerHTML = html;
  }

  // ── Helpers ────────────────────────────────────────────────────────────────

  function escHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function $(id) {
    return document.getElementById(id);
  }

  function openModal() {
    $("wlw-modal").classList.add("wlw-open");
    setTimeout(function () {
      var fields = cfg.fields || [];
      if (fields.length) {
        var first = $("wlw-" + fields[0].id);
        if (first) first.focus();
      }
    }, 200);
  }

  function closeModal() {
    $("wlw-modal").classList.remove("wlw-open");
  }

  function showError(id, show) {
    var el = $(id);
    if (el) el.classList.toggle("wlw-visible", show);
    var input = el && el.previousElementSibling;
    if (
      input &&
      (input.tagName === "INPUT" ||
        input.tagName === "SELECT" ||
        input.tagName === "TEXTAREA")
    )
      input.classList.toggle("wlw-error", show);
  }

  function isValidEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  }

  function applyPhoneMask(input) {
    input.addEventListener("input", function () {
      var v = input.value.replace(/\D/g, "").substring(0, 11);
      if (v.length <= 10) {
        v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
      } else {
        v = v.replace(/^(\d{2})(\d{5})(\d{0,4})/, "($1) $2-$3");
      }
      input.value = v;
    });
  }

  // ── Form Submission ────────────────────────────────────────────────────────

  function submitForm() {
    var fields = cfg.fields || [];
    var valid = true;

    for (var i = 0; i < fields.length; i++) {
      var field = fields[i];
      if (field.enabled === false) continue;
      var inputId = "wlw-" + field.id;
      var errorId = "err-" + field.id;
      var el = $(inputId);
      if (!el) continue;

      var val = (el.value || "").trim();

      if (field.required && !val) {
        showError(errorId, true);
        valid = false;
      } else if (field.type === "email" && val && !isValidEmail(val)) {
        showError(errorId, true);
        valid = false;
      } else {
        showError(errorId, false);
      }
    }

    if (!valid) return;

    var btn = $("wlw-submit");
    btn.classList.add("wlw-loading");

    var body = new FormData();
    body.append("action", "wlw_submit_lead");
    body.append("nonce", cfg.nonce);

    for (var j = 0; j < fields.length; j++) {
      var f = fields[j];
      if (f.enabled === false) continue;
      var el2 = $("wlw-" + f.id);
      if (el2) body.append(f.id, el2.value || "");
    }

    body.append("wlw_website", $("wlw-website") ? $("wlw-website").value : "");

    function doFetch(token) {
      if (token) body.append("recaptcha_token", token);
      fetch(cfg.ajaxUrl, { method: "POST", body: body })
        .then(function (r) {
          return r.json();
        })
        .then(function (res) {
          btn.classList.remove("wlw-loading");
          if (res.success) {
            window.open(res.data.whatsapp_url, "_blank");
            closeModal();
            // Reset fields
            for (var k = 0; k < fields.length; k++) {
              var el3 = $("wlw-" + fields[k].id);
              if (el3) el3.value = "";
            }
            var ge = $("wlw-global-error");
            ge.textContent = "";
            ge.classList.remove("wlw-visible");
            for (var m = 0; m < fields.length; m++) {
              showError("err-" + fields[m].id, false);
            }
          } else {
            var ge = $("wlw-global-error");
            ge.textContent =
              res.data.message || "Ocorreu um erro. Tente novamente.";
            ge.classList.add("wlw-visible");
          }
        })
        .catch(function () {
          btn.classList.remove("wlw-loading");
          var ge = $("wlw-global-error");
          ge.textContent =
            "Erro de conexão. Verifique sua internet e tente novamente.";
          ge.classList.add("wlw-visible");
        });
    }

    if (cfg.recaptchaSiteKey && typeof grecaptcha !== "undefined") {
      grecaptcha.ready(function () {
        grecaptcha
          .execute(cfg.recaptchaSiteKey, { action: "wlw_submit" })
          .then(function (token) {
            doFetch(token);
          })
          .catch(function () {
            doFetch("");
          });
      });
    } else {
      doFetch("");
    }
  }

  // ── Init ───────────────────────────────────────────────────────────────────

  function init() {
    buildWidget();

    var floatBtn = $("wlw-float-btn");
    if (!floatBtn) return;

    var closeBtn = $("wlw-close");
    var submitBtn = $("wlw-submit");

    floatBtn.addEventListener("click", openModal);
    floatBtn.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") openModal();
    });

    closeBtn.addEventListener("click", closeModal);

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeModal();
    });

    submitBtn.addEventListener("click", submitForm);

    // Phone mask + Enter key for text/email/tel inputs
    var fields = cfg.fields || [];
    for (var i = 0; i < fields.length; i++) {
      var field = fields[i];
      if (field.enabled === false) continue;
      var el = $("wlw-" + field.id);
      if (!el) continue;
      if (field.type === "tel") applyPhoneMask(el);
      if (field.type !== "select" && field.type !== "textarea") {
        (function (input) {
          input.addEventListener("keydown", function (e) {
            if (e.key === "Enter") submitForm();
          });
        })(el);
      }
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
