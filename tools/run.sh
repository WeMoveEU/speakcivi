#!/usr/bin/env bash

ENDPOINT=$1
DB=$2
USR=$3

TEST_DATETIME=$(date +%Y-%m-%d\ %H:%M:%S)
TEST_HASH=$(date +%s)

read -s -p "db password: " PSW

echo ""
echo "1. Sign petition, current member at ${TEST_DATETIME}"
curl $ENDPOINT -d "{
\"action_type\":\"petition\",
\"action_technical_type\":\"you.wemove.eu:petition\",
\"create_dt\":\"${TEST_DATETIME}\",
\"action_name\":\"${TEST_HASH}\",
\"external_id\":\"10007\",
\"cons_hash\":{\"firstname\":\"Tomasz\",\"lastname\":\"Pietrzkowski\",\"emails\":[{\"email\":\"scardinius@chords.pl\"}],\"addresses\":[{\"zip\":\"[pl] 01-111\"}]},
\"comment\":\"comment from bash script\",
\"source\":{\"source\":\"source-script\",\"medium\":\"medium-script\",\"campaign\":\"campaign-script\"},
\"metadata\":{\"tracking_codes\":{\"source\":\"source-tracking\",\"medium\":\"medium-tracking\",\"campaign\":\"campaign-tracking\",\"content\":\"content-tracking\"}}
}"

PETITION_QUERY="SELECT ac.contact_id, ac.activity_id, a.activity_date_time, a.subject, a.campaign_id, s.source_27, s.media_28, s.campaign_26
FROM civicrm_activity a
  JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
  JOIN civicrm_value_action_source_4 s ON s.entity_id = a.id
WHERE activity_type_id = 32 AND subject = '${TEST_HASH}'"

mysql $DB -u $USR -p$PSW -e "$PETITION_QUERY"
echo -e "\n"


echo "2. Sign petition, new member at ${TEST_DATETIME}"
TEST_HASH2=$(date +%s)
curl $ENDPOINT -d "{
\"action_type\":\"petition\",
\"action_technical_type\":\"you.wemove.eu:petition\",
\"create_dt\":\"${TEST_DATETIME}\",
\"action_name\":\"${TEST_HASH2}\",
\"external_id\":\"10007\",
\"cons_hash\":{\"firstname\":\"Test\",\"lastname\":\"Testowski\",\"emails\":[{\"email\":\"testowski@wemove.eu\"}],\"addresses\":[{\"zip\":\"[pl] 01-111\"}]},
\"comment\":\"comment from bash script, new user\",
\"source\":{\"source\":\"source-script\",\"medium\":\"medium-script\",\"campaign\":\"campaign-script\"},
\"metadata\":{\"tracking_codes\":{\"source\":\"source-tracking\",\"medium\":\"medium-tracking\",\"campaign\":\"campaign-tracking\",\"content\":\"content-tracking\"}}
}"

QUERY="SELECT ac.contact_id, ac.activity_id, a.activity_date_time, a.subject, a.campaign_id, s.source_27, s.media_28, s.campaign_26
FROM civicrm_activity a
  JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
  JOIN civicrm_value_action_source_4 s ON s.entity_id = a.id
WHERE activity_type_id = 32 AND subject = '${TEST_HASH2}'"

mysql $DB -u $USR -p$PSW -e "$QUERY"
echo -e "\n"


OLDDATE="$(date +%Y-%m-%d\ %H:%M:%S -d '10 days ago')"
echo "3. Sign petition, current member at ${OLDDATE}"
TEST_HASH3=$(date +%s)
curl $ENDPOINT -d "{
\"action_type\":\"petition\",
\"action_technical_type\":\"you.wemove.eu:petition\",
\"create_dt\":\"${OLDDATE}\",
\"action_name\":\"${TEST_HASH3}\",
\"external_id\":\"10007\",
\"cons_hash\":{\"firstname\":\"Tomasz\",\"lastname\":\"Pietrzkowski\",\"emails\":[{\"email\":\"scardinius@chords.pl\"}],\"addresses\":[{\"zip\":\"[pl] 01-111\"}]},
\"comment\":\"comment from bash script\",
\"source\":{\"source\":\"source-script\",\"medium\":\"medium-script\",\"campaign\":\"campaign-script\"},
\"metadata\":{\"tracking_codes\":{\"source\":\"source-tracking\",\"medium\":\"medium-tracking\",\"campaign\":\"campaign-tracking\",\"content\":\"content-tracking\"}}
}"

QUERY="SELECT ac.contact_id, ac.activity_id, a.activity_date_time, a.subject, a.campaign_id, s.source_27, s.media_28, s.campaign_26
FROM civicrm_activity a
  JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
  JOIN civicrm_value_action_source_4 s ON s.entity_id = a.id
WHERE activity_type_id = 32 AND subject = '${TEST_HASH3}'"

mysql $DB -u $USR -p$PSW -e "$QUERY"
echo -e "\n"


echo "4. Share petition, current member at ${TEST_DATETIME}"
TEST_HASH4=$(date +%s)
curl $ENDPOINT -d "{
\"action_type\":\"share\",
\"action_technical_type\":\"you.wemove.eu:petition\",
\"create_dt\":\"${TEST_DATETIME}\",
\"action_name\":\"${TEST_HASH4}\",
\"external_id\":\"10007\",
\"cons_hash\":{\"firstname\":\"Tomasz\",\"lastname\":\"Pietrzkowski\",\"emails\":[{\"email\":\"scardinius@chords.pl\"}],\"addresses\":[{\"zip\":\"[pl] 01-111\"}]},
\"comment\":\"comment from bash script\",
\"source\":{\"source\":\"source-script\",\"medium\":\"medium-script\",\"campaign\":\"campaign-script\"},
\"metadata\":{\"tracking_codes\":{\"source\":\"source-tracking\",\"medium\":\"medium-tracking\",\"campaign\":\"campaign-tracking\",\"content\":\"content-tracking\"}}
}"

QUERY="SELECT
  ac.contact_id, ac.activity_id, a.activity_date_time adt, a.subject, a.campaign_id campid, s.source_27 ass, s.media_28 asm, s.campaign_26 asca,
  sp.utm_source_37 utms, sp.utm_medium_38 utmm, sp.utm_campaign_39 utmc, sp.utm_content_40 utmca
FROM civicrm_activity a
  JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
  JOIN civicrm_value_action_source_4 s ON s.entity_id = a.id
  JOIN civicrm_value_share_params_6 sp ON sp.entity_id = a.id
WHERE activity_type_id = 54 AND subject = '${TEST_HASH4}'"

mysql $DB -u $USR -p$PSW -e "$QUERY"
echo -e "\n"


echo "5. Sign petition as new member from UK at ${TEST_DATETIME}"
TEST_HASH5=$(date +%s)
curl $ENDPOINT -d "{
\"action_type\":\"petition\",
\"action_technical_type\":\"act.wemove.eu:petition\",
\"create_dt\":\"${TEST_DATETIME}\",
\"action_name\":\"${TEST_HASH5}\",
\"external_id\":\"49\",
\"cons_hash\":{\"firstname\":\"Test-EN\",\"lastname\":\"Testowski-EN\",\"emails\":[{\"email\":\"testowski-en@wemove.eu\"}],\"addresses\":[{\"zip\":\"[uk] 1111\"}]},
\"comment\":\"comment from bash script, new user\",
\"source\":{\"source\":\"source-script\",\"medium\":\"medium-script\",\"campaign\":\"campaign-script\"},
\"metadata\":{\"tracking_codes\":{\"source\":\"source-tracking\",\"medium\":\"medium-tracking\",\"campaign\":\"campaign-tracking\",\"content\":\"content-tracking\"}}
}"

QUERY="SELECT ac.contact_id, ac.activity_id, a.activity_date_time, a.subject, a.campaign_id, s.source_27, s.media_28, s.campaign_26
FROM civicrm_activity a
  JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
  JOIN civicrm_value_action_source_4 s ON s.entity_id = a.id
WHERE activity_type_id = 32 AND subject = '${TEST_HASH5}'"

mysql $DB -u $USR -p$PSW -e "$QUERY"
echo -e "\n"


# delete
read -p "delete testing data? (y/n): " DROP
if [ "$DROP" = "y" ] ; then
  echo "delete activity for current member..."
  mysql $DB -u $USR -p$PSW -e "DELETE FROM civicrm_activity WHERE activity_type_id = 32 AND subject = '${TEST_HASH}'"
  echo "delete new contact..."
  mysql $DB -u $USR -p$PSW -e "DELETE c FROM civicrm_contact c
JOIN civicrm_activity_contact ac ON ac.contact_id = c.id
JOIN civicrm_activity a ON a.id = ac.activity_id
WHERE activity_type_id = 32 AND a.subject = '${TEST_HASH2}'"
  echo "delete old activity for current member..."
  mysql $DB -u $USR -p$PSW -e "DELETE FROM civicrm_activity WHERE activity_type_id = 32 AND subject = '${TEST_HASH3}'"
  echo "delete share activity for current member..."
  mysql $DB -u $USR -p$PSW -e "DELETE FROM civicrm_activity WHERE activity_type_id = 54 AND subject = '${TEST_HASH4}'"
  echo "delete new contact from UK..."
  mysql $DB -u $USR -p$PSW -e "DELETE c FROM civicrm_contact c
JOIN civicrm_activity_contact ac ON ac.contact_id = c.id
JOIN civicrm_activity a ON a.id = ac.activity_id
WHERE activity_type_id = 32 AND a.subject = '${TEST_HASH5}'"
fi
