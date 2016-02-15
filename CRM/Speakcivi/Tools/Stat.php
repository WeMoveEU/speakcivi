<?php

class CRM_Speakcivi_Tools_Stat {

  const PATH = '/../../../stats/';

  const DELIMITER = ';';

  const ENCLOSURE = '"';

  const COL_SID = 0;

  const COL_DESC = 1;

  const COL_SEC = 2;

  public static $isActive = false;


  public static function m($filename = '', $description = '') {
    if (self::isValid($filename, $description)) {
      $d = self::setData($description);
      self::writeCsv($filename, $d);
    }
  }


  public static function buildRows($filename) {
    $data = self::getCsv($filename);
    $header = array();
    $sid1 = array();
    $sid_sec = array();
    foreach ($data as $id => $d) {
      $header[$d[self::COL_DESC]] = $d[self::COL_DESC];
      $sid1[$d[self::COL_SID]] = $d[self::COL_SID];
      $sid_sec[$d[self::COL_SID]][][$d[self::COL_DESC]] = $d[self::COL_SEC];
    }

    $c = count($header);
    $sid_sec_2 = array();
    foreach ($sid_sec as $sid => $sec) {
      for($i = 0; $i < count($sec); $i += $c) {
        for ($j = 0; $j < $c; $j++) {
          $key = key($sec[$i+$j]);
          $value = reset($sec[$i+$j]);
          $sid_sec_2[$sid][$i][$key] = $value;
        }
      }
    }

    $i = 0;
    $rows = array();
    foreach($sid_sec_2 as $sid => $tab) {

      foreach($tab as $id => $m) {
        $i++;
        $rows[$i] = array(
          'SID' => $sid
        );
        foreach($header as $h) {
          $rows[$i][$h] = $m[$h];
        }
      }
    }

    return $rows;
  }


  public static function calculate($filename, $rows) {
    $calcs = array();
    $header = self::getHeader($filename);
    unset($header['SID']); // todo poprawić to
    foreach ($rows as $k => $row) {
      $calcs[$k] = $row;
      $previous = 0;
      $totalDiff = 0;
      foreach ($header as $h) {
        $current = $row[$h];
        if ($previous) {
          $calcs[$k][$h.'-DIFF'] = $current - $previous;
          $totalDiff += $current - $previous;
        }
        $previous = $row[$h];
      }
      $calcs[$k]['TOTAL-DIFF'] = $totalDiff;
    }
    return $calcs;
  }


  public static function replaceDots($rows) {
    foreach ($rows as $k => $row) {
      foreach ($row as $item => $value) {
        $rows[$k][$item] = str_replace('.', ',', $value);
      }
    }
    return $rows;
  }


  public static function saveReport($filename, $rows) {
    $path = dirname(__FILE__).self::PATH.$filename.'.csv';
    $fp = fopen($path, 'w');
    $header = self::getHeader($filename);
    fputcsv($fp, $header, self::DELIMITER, self::ENCLOSURE);
    foreach ($rows as $row) {
      fputcsv($fp, $row, self::DELIMITER, self::ENCLOSURE);
    }
    fclose($fp);
  }


  private static function isValid($filename, $description) {
    return (self::$isActive && $filename && $description);
  }


  private static function setData($description) {
    return array(
      self::COL_SID => CRM_Core_Key::sessionID(),
      self::COL_DESC => $description,
      self::COL_SEC => microtime(true),
    );
  }


  private static function writeCsv($filename, $data) {
    $path = dirname(__FILE__).self::PATH.$filename;
    if (($fp = fopen($path, 'a')) !== FALSE) {
      fputcsv($fp, $data, self::DELIMITER, self::ENCLOSURE);
    }
    fclose($fp);
  }


  private static function getCsv($filename) {
    $data = array();
    $path = dirname(__FILE__).self::PATH.$filename;
    if (($fp = fopen($path, 'r')) !== FALSE) {
      while (($d = fgetcsv($fp, null, self::DELIMITER, self::ENCLOSURE)) !== FALSE) {
        $data[] = $d;
      }
    }
    fclose($fp);
    return $data;
  }


  private static function getHeader($filename) {
    $data = self::getCsv($filename);
    $header = array('SID' => 'SID');
    foreach ($data as $id => $d) {
      $header[$d[self::COL_DESC]] = $d[self::COL_DESC];
    }
    return $header;
  }
}
