/**
 * BarbaBook - tdesksolutions.com.br
 * Máscara de telefone, data e hora
 */

(function () {
  'use strict';

  var today = new Date().toISOString().slice(0, 10);

  // Data: mínima = hoje; ao clicar/focar, abrir o calendário nativo
  var dateInputs = document.querySelectorAll('input[type="date"]');
  dateInputs.forEach(function (el) {
    if (!el.getAttribute('min')) el.setAttribute('min', today);
    el.addEventListener('click', function () {
      try { if (typeof this.showPicker === 'function') this.showPicker(); } catch (e) {}
    });
    el.addEventListener('focus', function () {
      try { if (typeof this.showPicker === 'function') this.showPicker(); } catch (e) {}
    });
  });

  // Horário: step 15 min; ao clicar/focar, abrir o seletor de hora nativo
  var timeInputs = document.querySelectorAll('input[type="time"]');
  timeInputs.forEach(function (el) {
    if (!el.getAttribute('step')) el.setAttribute('step', '900');
    el.addEventListener('click', function () {
      try { if (typeof this.showPicker === 'function') this.showPicker(); } catch (e) {}
    });
    el.addEventListener('focus', function () {
      try { if (typeof this.showPicker === 'function') this.showPicker(); } catch (e) {}
    });
  });

  // Máscara de telefone: (XX) XXXX-XXXX fixo ou (XX) XXXXX-XXXX celular
  function formatarTelefone(val) {
    var digits = val.replace(/\D/g, '').slice(0, 11);
    if (digits.length <= 2) return digits.length ? '(' + digits : '';
    if (digits.length <= 6) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
    if (digits.length <= 10) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 6) + '-' + digits.slice(6);
    return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 7) + '-' + digits.slice(7, 11);
  }

  var phoneInputs = document.querySelectorAll('input[name="cliente_telefone"], input[name="consulta_telefone"]');
  phoneInputs.forEach(function (input) {
    input.setAttribute('inputmode', 'numeric');
    input.setAttribute('autocomplete', 'tel');
    input.setAttribute('placeholder', '(00) 00000-0000');

    input.addEventListener('input', function () {
      var start = this.selectionStart;
      var oldLen = this.value.length;
      var formatted = formatarTelefone(this.value);
      this.value = formatted;
      var newLen = this.value.length;
      var newStart = Math.max(0, start + (newLen - oldLen));
      if (newStart > formatted.length) newStart = formatted.length;
      this.setSelectionRange(newStart, newStart);
    });

    input.addEventListener('focus', function () {
      if (this.value.replace(/\D/g, '').length === 0) this.value = '';
    });

    // Formatar valor inicial se vier preenchido (ex.: após erro de validação)
    if (input.value && /^\d+$/.test(input.value.replace(/\D/g, ''))) {
      input.value = formatarTelefone(input.value);
    }
  });
})();
