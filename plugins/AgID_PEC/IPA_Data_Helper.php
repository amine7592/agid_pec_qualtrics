<?php
if (!defined("BASEPATH"))
    exit("No direct script access allowed");
/**
 * Invitations management plugin using Italian Certified Mail System (PEC)
 *
 * @author Davide Porrovecchio <davide.porrovecchio@agid.gov.it>
 * @version 0.1
 */

/*
 * IPA data helper
*/

class IPA_Data_Helper
{
  

  public static function update_ipa_data($dbms) {
    
    $datafile = dirname(__FILE__) . "/data/amministrazioni.txt";
    if (!file_exists($datafile)) {
        return false;
    }
    
    $pdo = Yii::app()->db->getPdoInstance();
    $tablePrefix = Yii::app()->db->tablePrefix;
    ini_set("auto_detect_line_endings", true);
    if (($handle = fopen($datafile, "r")) !== false) {
      $header = fgetcsv($handle, 0, "\t");
      if ($dbms == "pgsql") {
        $sql_stmt = "";
        $sql_stmt .=" UPDATE " . $tablePrefix . "agid_pec_enti SET denominazione=:ente,cf=:cf,pec=:pec, comune=:comune,desc_categoria_ipa=:desc_categoria_ipa,tipo_ente_custom=:tipo_ente_custom WHERE ipa_code=:ipa_code;";
        $sql_stmt .=" INSERT INTO " . $tablePrefix . "agid_pec_enti (ipa_code, denominazione, cf,pec, comune, desc_categoria_ipa, tipo_ente_custom,qualtrics_link) SELECT :ipa_code, :ente, :cf,:pec, :comune, :desc_categoria_ipa, :tipo_ente_custom WHERE NOT EXISTS (SELECT 1 FROM " . $tablePrefix . "agid_pec_enti WHERE ipa_code=:ipa_code);";
      } else {
        $sql_stmt = "REPLACE INTO `" . $tablePrefix . "agid_pec_enti` VALUES (:ipa_code, :ente, :cf,:pec, :comune, :desc_categoria_ipa, :tipo_ente_custom,:qualtrics_link)";
      }
      $stmt = $pdo->prepare($sql_stmt);
        while (($data = fgetcsv($handle, 0, "\t")) !== false) {
          $ente = array();
          for($i = 0; $i < count($header); $i++) {
            switch ($header[$i]) {
              case "cod_amm":
                  $ente[":ipa_code"] = $data[$i];
                  break;
              case "des_amm":
                  $ente[":ente"] = $data[$i];
                  break;
              case "Cf":
                  $ente[":cf"] = $data[$i];
                  break;
              case "Comune":
                  $ente[":comune"] = $data[$i];
                  break;
              case "tipologia_istat":
                  $ente[":desc_categoria_ipa"] = $data[$i];
                  break;
            }
          }
          $ente[":pec"] = "";
          $ente[":tipo_ente_custom"] = "";
          $ente[":qualtrics_link"] = "";
          $stmt->execute($ente);
        }
      fclose($handle);
      
      
      //Apply correttivo_enti.csv
      if (($handle = fopen(dirname(__FILE__) . "/data/correttivo_enti.csv", "r")) !== false) {
            $header = fgetcsv($handle, 0, ";");
            if ($dbms == "pgsql") {
                $sql_stmt = "";
                $sql_stmt .= " UPDATE " . $tablePrefix . "agid_pec_enti SET denominazione=:ente,cf=:cf,pec=:pec, comune=:comune,desc_categoria_ipa=:desc_categoria_ipa,tipo_ente_custom=:tipo_ente_custom WHERE ipa_code=:ipa_code;";
                $sql_stmt .= " INSERT INTO " . $tablePrefix . "agid_pec_enti (ipa_code, denominazione, cf,pec, comune, desc_categoria_ipa, tipo_ente_custom,qualtrics_link) SELECT :ipa_code, :ente, :cf,:pec, :comune, :desc_categoria_ipa, :tipo_ente_custom WHERE NOT EXISTS (SELECT 1 FROM " . $tablePrefix . "agid_pec_enti WHERE ipa_code=:ipa_code);";
            } else {
                $sql_stmt = "REPLACE INTO `" . $tablePrefix . "agid_pec_enti` VALUES (:ipa_code, :ente, :cf,:pec, :comune, :desc_categoria_ipa, :tipo_ente_custom,:qualtrics_link)";
            }
            $stmt = $pdo->prepare($sql_stmt);
            while (($data = fgetcsv($handle, 0, ";")) !== false) {
                $ente = array();
                for ($i = 0; $i < count($header); $i ++) {
                    switch ($header[$i]) {
                        case "cod_amm":
                            $ente[":ipa_code"] = $data[$i];
                            break;
                        case "denominazione":
                            $ente[":ente"] = $data[$i];
                            break;
                        case "cf":
                            $ente[":cf"] = $data[$i];
                            break;
                        case "comune":
                            $ente[":comune"] = $data[$i];
                            break;
                        case "descr_categoria_ipa":
                            $ente[":desc_categoria_ipa"] = $data[$i];
                            break;
                        case "pec":
                            $ente[":pec"] = $data[$i];
                            break;
                        case "tipo_ente_custom":
                            $ente[":tipo_ente_custom"] = $data[$i];
                            break;
                        case "qualtrics_link":
                            $ente[":qualtrics_link"] = $data[$i];
                            break;

                    }
                }
                $stmt->execute($ente);
            }
            fclose($handle);
        } else {
            return false;
        }
      
      return true;
    } else {
      return false;
    }
  }
}
