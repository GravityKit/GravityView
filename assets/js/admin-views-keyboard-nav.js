/* Keyboard navigation and reorder controls for GravityView View editor */
(function($){
  var DBG = function(){
    if (window.gvDebugKeyboardNav) {
      var args = Array.prototype.slice.call(arguments);
      args.unshift('[GV KB]');
      try { console.log.apply(console, args); } catch(e) {}
    }
  };
  function enableKeyboardClass(){ if (!$('body').hasClass('gv-using-keyboard')) { $('body').addClass('gv-using-keyboard'); DBG('enable keyboard class'); } }
  function disableKeyboardClass(){ $('body').removeClass('gv-using-keyboard'); DBG('disable keyboard class'); }

  function getContainer($field){ return $field.closest('.active-drop-field, .active-drop-widget, .active-drop-search'); }
  function updateButtons($field){
    var $container = getContainer($field);
    var $siblings = $container.children('.gv-fields');
    var index = $siblings.index($field);
    var $up = $field.find('.gv-move-up');
    var $down = $field.find('.gv-move-down');
    var atTop = index <= 0;
    var atBottom = index === $siblings.length - 1;
    $up.attr('aria-hidden', atTop ? 'true' : 'false').toggle(!atTop);
    $down.attr('aria-hidden', atBottom ? 'true' : 'false').toggle(!atBottom);
    DBG('updateButtons', {index:index, len:$siblings.length, atTop:atTop, atBottom:atBottom});
  }
  function announce($field){
    try {
      var $container = getContainer($field);
      var $siblings = $container.children('.gv-fields');
      var index = $siblings.index($field);
      var $status = $('#gv-reorder-status');
      if ($status.length === 0) {
        $status = $('<div/>', { id: 'gv-reorder-status', 'class': 'screen-reader-text', 'aria-live': 'polite', role: 'status' }).appendTo(document.body);
      }
      $status.text('Moved to position ' + (index+1) + ' of ' + $siblings.length + '.');
    } catch(e) {}
  }
  function ensureFocus($el){
    if (!$el || !$el.length) return false;
    var attempts = 0;
    var maxAttempts = 5;
    var tryFocus = function(){
      attempts++;
      if (!$el.is(':visible')) { if (attempts<maxAttempts) return setTimeout(tryFocus, 0); return; }
      try { $el[0].focus({ preventScroll: true }); } catch(e){ $el.trigger('focus'); }
      var ok = document.activeElement === $el[0];
      DBG('ensureFocus attempt', attempts, ok, $el[0]);
      if (!ok && attempts < maxAttempts) { setTimeout(tryFocus, 0); }
    };
    tryFocus();
    return true;
  }

  function focusContainer($field){
    if (!$field || !$field.length) return;
    try {
      $field.attr('tabindex','-1');
      $field[0].focus({ preventScroll: true });
      DBG('focused container');
      setTimeout(function(){ $field.removeAttr('tabindex'); }, 250);
    } catch(e){}
  }
  function setUnsaved(){ try { if (window.viewConfiguration && typeof viewConfiguration.setUnsavedChanges==='function'){ viewConfiguration.setUnsavedChanges(true); } } catch(e){} }
  function focusControl($field, preferred){
    var sel = preferred === 'up' ? '.gv-move-up' : '.gv-move-down';
    var $btn = $field.find(sel).filter(':visible');
    if (!$btn.length){
      sel = preferred === 'up' ? '.gv-move-down' : '.gv-move-up';
      $btn = $field.find(sel).filter(':visible');
    }
    if ($btn.length){ $btn.trigger('focus'); return true; }
    return false;
  }

  function move($field, delta, preferred, $usedBtn){
    if (!$field || !$field.length) return;
    var $container = getContainer($field);
    if (!$container.length) return;
    var $siblings = $container.children('.gv-fields');
    var index = $siblings.index($field);
    var newIndex = index + (delta < 0 ? -1 : 1);
    if (newIndex < 0 || newIndex >= $siblings.length) return;
    var $focused = $(document.activeElement);
    var hadFocusInside = $.contains($field[0], $focused[0]);
    DBG('move start', {delta:delta, preferred:preferred});
    if (delta < 0) { $field.prev('.gv-fields').before($field); } else { $field.next('.gv-fields').after($field); }
    setUnsaved();
    // Defer updates and focus to next tick to ensure DOM settled
    setTimeout(function(){
      enableKeyboardClass();
      // Ensure :focus-within for visibility of reorder controls
      focusContainer($field);
      updateButtons($field);
      announce($field);
      // Prefer re-focusing the exact button used if still visible
      if ($usedBtn && $usedBtn.length && $usedBtn.is(':visible')) {
        DBG('refocus used button');
        ensureFocus($usedBtn);
      } else if (!focusControl($field, preferred)) {
        DBG('used button hidden; fallback');
        if (hadFocusInside && $focused && $focused.length && $.contains($field[0], $focused[0])) { ensureFocus($focused); }
        else { var $t = $field.find('.gv-move-up:visible, .gv-move-down:visible, button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])').filter(':visible').first(); if ($t.length) ensureFocus($t); }
      }
      DBG('activeElement', document.activeElement);
    }, 0);
  }

  $(function(){
    // Keyboard indicator
    $(window).on('keydown.gvKeyboardNav', function(e){ var k=e.key||e.keyCode; if (k==='Tab'||k===9||k==='ArrowUp'||k===38||k==='ArrowDown'||k===40){ enableKeyboardClass(); } });
    $(window).on('mousedown.gvKeyboardNav touchstart.gvKeyboardNav', disableKeyboardClass);

    // Delegate events
    $(document.body)
      .on('click', '.gv-move-up', function(e){
        var $btn=$(this);
        if ($btn.data('gv-skip-click')) { $btn.removeData('gv-skip-click'); e.preventDefault(); e.stopPropagation(); return; }
        e.preventDefault(); e.stopPropagation(); enableKeyboardClass();
        move($btn.closest('.gv-fields'), -1, 'up', $btn);
      })
      .on('click', '.gv-move-down', function(e){
        var $btn=$(this);
        if ($btn.data('gv-skip-click')) { $btn.removeData('gv-skip-click'); e.preventDefault(); e.stopPropagation(); return; }
        e.preventDefault(); e.stopPropagation(); enableKeyboardClass();
        move($btn.closest('.gv-fields'), 1, 'down', $btn);
      })
      .on('keydown', '.gv-field-reorder button', function(e){
        var $btn=$(this);
        var isUp = $btn.hasClass('gv-move-up');
        var isDown = $btn.hasClass('gv-move-down');
        if (e.key===' ' || e.keyCode===32 || e.key==='Enter' || e.keyCode===13) {
          // Prevent native click firing later; run our move now
          e.preventDefault(); e.stopPropagation(); enableKeyboardClass();
          $btn.data('gv-skip-click', true);
          setTimeout(function(){ $btn.removeData('gv-skip-click'); }, 250);
          if (isUp) { move($btn.closest('.gv-fields'), -1, 'up', $btn); }
          else { move($btn.closest('.gv-fields'), 1, 'down', $btn); }
          return;
        }
        if (e.key==='ArrowUp'||e.keyCode===38){ e.preventDefault(); enableKeyboardClass(); move($btn.closest('.gv-fields'), -1, 'up', $btn); }
        else if (e.key==='ArrowDown'||e.keyCode===40){ e.preventDefault(); enableKeyboardClass(); move($btn.closest('.gv-fields'), 1, 'down', $btn); }
      })
      .on('keydown', '.gv-fields', function(e){ if (e.key==='ArrowUp'||e.keyCode===38){ e.preventDefault(); move($(this), -1, 'up'); } else if (e.key==='ArrowDown'||e.keyCode===40){ e.preventDefault(); move($(this), 1, 'down'); } })
      .on('focusin', '.gv-fields', function(){ updateButtons($(this)); });
  });
})(jQuery);
