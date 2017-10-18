jQuery(function($) {

  /* get cookie */
  var member = decodeURIComponent(getCookie('member'));
  var jso = JSON.parse(member || null) || {};

  /* map of keys: key from cookies : name from form */
  var fields = {
    "firstname" : "first_name",
    "lastname" : "last_name",
    "email" : "email-5",
    "postcode" : "postal_code"
  };
  $.each(fields, function(k, f) {
    var el = $("input[name*="+f+"]");
    if (!el.val() && k in jso) {
     el.val(jso[k]);
    }
  });

  /* set country */
  var countries = {
    "fr" : 1076,
    "de" : 1082,
    "gr" : 1085,
    "it" : 1107,
    "pl" : 1172,
    "pt" : 1173,
    "ro" : 1176,
    "es" : 1198,
    "gb" : 1226,
    "uk" : 1226
  };

  if ("country" in jso && countries[jso.country]) {
    var ccc = {
      1 : "country-1",
      2 : "country_id-5"
    };
    $.each(ccc, function(k, f) {
      $("#" + f).val(countries[jso.country]).change(function(e){$("#select2-chosen-1").text($("#" + f + " option:selected").text());}).trigger("change");
    });
  }
});

function getCookie(name) {
  var value = "; " + document.cookie;
  var parts = value.split("; " + name + "=");
  if (parts.length == 2) return parts.pop().split(";").shift();
  return '';
}
