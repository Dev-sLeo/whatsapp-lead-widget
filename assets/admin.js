/* WhatsApp Lead Widget — Admin JS */
(function ($) {
  "use strict";

  // ── Color Pickers ──────────────────────────────────────────────────────────

  $(".wlw-color-picker").wpColorPicker();

  // ── Font row toggle ────────────────────────────────────────────────────────

  $("#wlw-use-site-font").on("change", function () {
    $("#wlw-custom-font-row").toggle(!this.checked);
  });

  // ── Field Management ───────────────────────────────────────────────────────

  var hiddenInput = $("#wlw-fields-json");
  var fieldsContainer = $("#wlw-fields-list");
  var initialFields = window.wlwAdmin && wlwAdmin.fields ? wlwAdmin.fields : [];

  function escAttr(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  function generateId() {
    return "field_" + Date.now() + "_" + Math.floor(Math.random() * 1000);
  }

  function createRow(field) {
    var isSystem = field.system === true || field.system === "true";
    var types = ["text", "email", "tel", "number", "select", "textarea"];
    var typeOpts = types
      .map(function (t) {
        return (
          '<option value="' +
          t +
          '"' +
          (field.type === t ? " selected" : "") +
          ">" +
          t +
          "</option>"
        );
      })
      .join("");

    var $row = $('<div class="wlw-field-row"></div>');
    $row
      .attr("data-id", field.id)
      .attr("data-system", isSystem ? "true" : "false");

    var removeBtn = isSystem
      ? ""
      : '<button type="button" class="button button-small button-link-delete wlw-remove-field">Remover</button>';

    var enabledAttr = isSystem
      ? " disabled checked"
      : field.enabled
        ? " checked"
        : "";

    $row.html(
      '<div class="wlw-field-row-header">' +
        '<span class="wlw-drag-handle dashicons dashicons-move" title="Arrastar para reordenar"></span>' +
        '<label class="wlw-toggle-wrap">' +
        '<input type="checkbox" class="field-enabled"' +
        enabledAttr +
        "> Ativo" +
        "</label>" +
        (isSystem ? '<span class="wlw-system-badge">Campo fixo</span>' : "") +
        '<div class="wlw-row-actions">' +
        '<button type="button" class="button button-small wlw-move-up" title="Mover para cima">↑</button>' +
        '<button type="button" class="button button-small wlw-move-down" title="Mover para baixo">↓</button>' +
        removeBtn +
        "</div>" +
        "</div>" +
        '<div class="wlw-field-row-body">' +
        '<div class="wlw-field-grid">' +
        "<div>" +
        "<label>Rótulo (label)</label>" +
        '<input type="text" class="field-label regular-text" value="' +
        escAttr(field.label || "") +
        '" />' +
        "</div>" +
        "<div>" +
        "<label>Tipo</label>" +
        '<select class="field-type">' +
        typeOpts +
        "</select>" +
        "</div>" +
        "<div>" +
        "<label>Placeholder</label>" +
        '<input type="text" class="field-placeholder regular-text" value="' +
        escAttr(field.placeholder || "") +
        '" />' +
        "</div>" +
        '<div class="wlw-required-wrap">' +
        "<label>" +
        '<input type="checkbox" class="field-required"' +
        (field.required ? " checked" : "") +
        (isSystem ? " disabled" : "") +
        ">" +
        " Obrigatório" +
        "</label>" +
        "</div>" +
        "</div>" +
        '<div class="wlw-options-wrap"' +
        (field.type !== "select" ? ' style="display:none"' : "") +
        ">" +
        "<label>Opções do select (uma por linha)</label>" +
        '<textarea class="field-options large-text" rows="4">' +
        escAttr(field.options || "") +
        "</textarea>" +
        "</div>" +
        "</div>",
    );

    return $row;
  }

  function syncJson() {
    var fields = [];
    fieldsContainer.find(".wlw-field-row").each(function () {
      var $row = $(this);
      fields.push({
        id: $row.attr("data-id"),
        label: $row.find(".field-label").val(),
        placeholder: $row.find(".field-placeholder").val(),
        type: $row.find(".field-type").val(),
        required: $row.find(".field-required").is(":checked"),
        enabled: $row.find(".field-enabled").is(":checked"),
        system: $row.attr("data-system") === "true",
        options: $row.find(".field-options").val(),
      });
    });
    hiddenInput.val(JSON.stringify(fields));
  }

  // Render initial fields
  initialFields.forEach(function (field) {
    fieldsContainer.append(createRow(field));
  });
  syncJson();

  // Delegated events
  fieldsContainer.on("change", ".field-type", function () {
    var $row = $(this).closest(".wlw-field-row");
    $row.find(".wlw-options-wrap").toggle($(this).val() === "select");
    syncJson();
  });

  fieldsContainer.on(
    "change input",
    ".field-label, .field-placeholder, .field-required, .field-enabled, .field-options",
    syncJson,
  );

  fieldsContainer.on("click", ".wlw-move-up", function () {
    var $row = $(this).closest(".wlw-field-row");
    var $prev = $row.prev(".wlw-field-row");
    if ($prev.length) {
      $prev.before($row);
      syncJson();
    }
  });

  fieldsContainer.on("click", ".wlw-move-down", function () {
    var $row = $(this).closest(".wlw-field-row");
    var $next = $row.next(".wlw-field-row");
    if ($next.length) {
      $next.after($row);
      syncJson();
    }
  });

  fieldsContainer.on("click", ".wlw-remove-field", function () {
    if (window.confirm("Remover este campo do formulário?")) {
      $(this).closest(".wlw-field-row").remove();
      syncJson();
    }
  });

  $("#wlw-add-field").on("click", function () {
    var newField = {
      id: generateId(),
      label: "Novo campo",
      placeholder: "",
      type: "text",
      required: false,
      enabled: true,
      system: false,
      options: "",
    };
    var $row = createRow(newField);
    fieldsContainer.append($row);
    $row.find(".field-label").focus();
    syncJson();
  });

  // Sync before form submit
  $("form").on("submit", syncJson);
})(jQuery);
