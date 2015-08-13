## setup
clone this into your extension folder, enable this civicrm extension. and voila.

## api endpoint provided
it creates a new route/url http://yourdomain.org/civicrm/bsd That should behave like an API from BSD

I'm a bit confused by the doc, can't find anything about cons_action


      Mechanize.new.post(ENV['API_URL']+'/cons_action', {action_name: internal_name, action_type: rthing.action_type, action_technical_type: "#{ENV['DOMAIN']}:#{rthing.action_type}", external_id: id, create_dt: rthing.created_at, cons_hash: rthing.bsd_cons_hash}.to_json)


    bsd_cons_hash (rsign)
    {firstname: firstname, lastname: lastname, emails: [{ email: email}], addresses: [{zip: postcode}]}

