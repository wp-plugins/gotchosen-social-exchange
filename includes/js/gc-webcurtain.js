(function(e, t, n, r, i, s, o) {
  e["GotChosenObject"] = i;
  e[i] = e[i] ||
  function() {
    (e[i].q = e[i].q || []).push(arguments)
  }, e[i].l = 1 * new Date;
  s = t.createElement(n), o = t.getElementsByTagName(n)[0];
  s.async = 1;
  s.src = r;
  o.parentNode.insertBefore(s, o)
})(window, document, "script", "//gotchosen.com/thirdparty/gc.js", "gc");
/**
 * Need to convert the value passed by wp_localize_script into a js bool.
 */
(function() {
  var gc_intg_compatabilty = false;
  if (gc_intg_plugin.compat == '1') {
    gc_intg_compatabilty = true;
  }
  gc("webcurtain", gc_intg_plugin.gcid, gc_intg_compatabilty);
})();
