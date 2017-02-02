#!/usr/bin/env bash

ENDPOINT=$1
DB=$2
USR=$3

TEST_DATETIME=$(date +%Y-%m-%d\ %H:%M:%S)
TEST_HASH=$(date +%s)

read -s -p "db password: " PSW

echo "1. Sign petition, current member at ${TEST_DATETIME}"
curl $ENDPOINT -d "{
\"action_type\":\"petition\",
\"action_technical_type\":\"you.wemove.eu:petition\",
\"create_dt\":\"${TEST_DATETIME}\",
\"action_name\":\"${TEST_HASH}\",
\"external_id\":\"10007\",
\"cons_hash\":{\"firstname\":\"Tomasz\",\"lastname\":\"Pietrzkowski\",\"emails\":[{\"email\":\"scardinius@chords.pl\"}],\"addresses\":[{\"zip\":\"[pl] 01-111\"}]},
\"source\":{\"source\":\"source-script\",\"medium\":\"medium-script\",\"campaign\":\"campaign-script\"},
\"metadata\":{\"tracking_codes\":{\"source\":\"source-tracking\",\"medium\":\"medium-tracking\",\"campaign\":\"campaign-tracking\",\"content\":\"content-tracking\"}}
}"

PETITION_QUERY="SELECT a.id, a.activity_date_time, a.subject, a.campaign_id, s.source_27, s.media_28, s.campaign_26
FROM civicrm_activity a
  JOIN civicrm_value_action_source_4 s ON s.entity_id = a.id
WHERE activity_type_id = 32 AND subject = '${TEST_HASH}'"

mysql $DB -u $USR -p$PSW -e "$PETITION_QUERY"
echo -e "\n"


# delete
read -p "delete testing data? (y/n): " DROP
if [ "$DROP" = "y" ] ; then
  echo "deleting data..."
  echo "delete activity for current member..."
  mysql $DB -u $USR -p$PSW -e "DELETE FROM civicrm_activity WHERE activity_type_id = 32 AND subject = '${TEST_HASH}'"
  echo -e "\n"
fi
