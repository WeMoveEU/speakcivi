jQuery(function($) {

  /* get cookie */
  var member = decodeURIComponent(getCookie('member'));
  var jso = JSON.parse(member);

  /* map of keys: key from cookies : name from form */
  var fields = {
    "firstname" : "first_name",
    "lastname" : "last_name",
    "email" : "email-5",
    "postcode" : "postal_code"
  };
  $.each(fields, function(k, f) {
    var el = $("input[name*="+f+"]");
    if (!el.val()) {
     el.val(jso[k]);
    }
  });
});

function getCookie(name) {
  var value = "; " + document.cookie;
  var parts = value.split("; " + name + "=");
  if (parts.length == 2) return parts.pop().split(";").shift();
}
