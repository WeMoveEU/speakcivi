#!/usr/bin/env bash

ENDPOINT=$1
DB=$2
USR=$3
PSW=$4

TEST_DATETIME=$(date +%Y-%m-%d\ %H:%M:%S)
TEST_HASH=$(date +%s)

echo "1. Sign petition at ${TEST_DATETIME}"
curl $ENDPOINT -d "{
\"action_type\":\"petition\",
\"action_technical_type\":\"you.wemove.eu:petition\",
\"create_dt\":\"${TEST_DATETIME}\",
\"action_name\":\"${TEST_HASH}\",
\"external_id\":\"10007\",
\"cons_hash\":{\"firstname\":\"Tomasz\",\"lastname\":\"Pietrzkowski\",\"emails\":[{\"email\":\"scardinius@chords.pl\"}],\"addresses\":[{\"zip\":\"[pl] 01-111\"}]}
}"

PETITION_QUERY="SELECT count(id) AS count_of_petition1
FROM civicrm_activity
WHERE activity_type_id = 32 AND subject = '${TEST_HASH}'"

mysql $DB -u $USR -p$PSW -e "$PETITION_QUERY"
echo -e "\n"
