/*
 * Check-in (AP-12): In Skalen mit data-clearable leert erneutes Tippen auf den gewählten Wert die Auswahl
 * (leer = nicht erhoben, E-02). Ohne JavaScript bleibt die Skala normal bedienbar.
 */
(function () {
  'use strict';
  function init() {
    document.querySelectorAll('.scale[data-clearable]').forEach(function (group) {
      var last = null;
      var checked = group.querySelector('input[type="radio"]:checked');
      if (checked) {
        last = checked.value;
      }
      group.querySelectorAll('input[type="radio"]').forEach(function (input) {
        input.addEventListener('click', function () {
          if (last === input.value) {
            input.checked = false;
            last = null;
          } else {
            last = input.value;
          }
        });
      });
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
