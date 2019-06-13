<?php
/**
 * Invitations management plugin using Italian Certified Mail System (PEC)
 * and Italian Public Digital Identity Service (SPID)
 *
 * @license GPL v3
 * @version 1.0b
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */

use \ls\menu\MenuItem;


class AgID_PEC extends \ls\pluginmanager\PluginBase
{
    private $activationURL;
    private $_imgAssetsURL;
    private $ssp;
    private $dbms;

    private $logoutRedirectURL;

    protected static $description = "Gestione inviti via PEC";
    private $PASS_PHRASE = "spidQualtrics";
    protected static $name = "AgID_PEC";
    protected $storage = "DbStorage";

    //TODO
    private $email_debug = true;


    protected $settings = array(
        "PEC_SMTP_host" => array(
            "type" => "string",
            "label" => "SMTP PEC host address"
        ),
        "PEC_SMTP_port" => array(
            "type" => "int",
            "label" => "SMTP PEC host port"
        ),
        "PEC_SMTP_username" => array(
            "type" => "string",
            "label" => "SMTP PEC username"
        ),
        "PEC_SMTP_password" => array(
            "type" => "password",
            "label" => "SMTP PEC password"
        ),
        "PEC_SMTP_secure" => array(
            "type" => "select",
            "label" => "SMTP PEC security protocol",
            "options" => array(
                "tls" => "TLS",
                "ssl" => "SSL",
                "none" => "None"
            ),
            "default" => "none"
        ),
        "PEC_address" => array(
            "type" => "string",
            "label" => "PEC address"
        ),
        "PEC_name" => array(
            "type" => "string",
            "label" => "PEC name"
        ),
        "InvitationLink_MailSubject_Template" => array(
            "type" => "string",
            "label" => "Mail Subject Template for Invitation Link",
            "help" => "You can use {{SURVEY_TITLE}}  placeholder. Overwritable in each Survey setting."
        ),
        "InvitationLink_MailBody_Template" => array(
            "type" => "text",
            "label" => "Mail Body Template for Invitation Link",
            "help" => "You can use {{SURVEY_TITLE}} placeholder and you must use {{INVITATION_LINK}} placeholder. Overwritable in each Survey setting."
        ),
        "ActivationLink_MailSubject" => array(
            "type" => "string",
            "label" => "Mail Subject for Activation Link",
            "help" => "You can use {{SURVEY_TITLE}} and {{SPID_PARTICIPANT}}  placeholder. Overwritable in each Survey setting."
        ),
        "ActivationLink_MailBody" => array(
            "type" => "text",
            "label" => "Mail Body for Activation Link",
            "help" => "You can use {{SURVEY_TITLE}}, {{SURVEY_ENTE}}, {{SPID_PARTICIPANT}}, {{SURVEY_TOKEN}} and {{SURVEY_LINK}} placeholder. Overwritable in each Survey setting."
        ),
        "simplesamlphp_path" => array(
            "type" => "string",
            "label" => "Path of spid-sp-simplesamlphp installation",
            "default" => "/var/www/spid-sp-simplesamlphp/",
            "help" => "Default is '/var/www/spid-sp-simplesamlphp' change it if needed"
        ),
        "saml_authsource" => array(
            "type" => "string",
            "label" => "SAML authentication source",
            "default" => "sp-questionari",
            "help" => "Default is 'sp-questionari' change it if needed"
        ),

        "SpidLogoutRedirectURL" => array(
            "type" => "string",
            "label" => "URL to redirect after SPID Logout",
            "help" => "Overwritable in each Survey setting."
        ),

        "DefaultSurvey" => array(
            "type" => "select",
            "options" => array(
                0 => "Limesurvey",
                1 => "Qualtrics"
            ),
            "default" => 1,
            "label" => "Survey Platform"
        ),

        "spidTestIDPEnabled" => array(
            "type" => "select",
            "options" => array(
                0 => "No",
                1 => "Yes"
            ),
            "default" => 1,
            "label" => "Enable SPID TEST IDP (idp.spid.gov.it)"
        )
    );

    /**
     * Init plugin and subscribe to event
     * @return void
     */
    public function init()
    {
        $this->subscribe("afterQuickMenuLoad");
        $this->subscribe("beforeToolsMenuRender");
        $this->subscribe("beforeActivate");
        $this->subscribe("beforeSurveySettings");
        $this->subscribe("newSurveySettings");
        $this->subscribe("afterPluginLoad");
        $this->subscribe("beforeControllerAction");
        $this->dbms = Yii::app()->db->getDriverName();
    }

    public function beforeSurveySettings()
    {
        $oEvent = $this->event;
        $oEvent->set("surveysettings.{$this->id}", array(
                "name" => get_class($this),
                "settings" => array(
                    "InvitationLink_MailSubject_Template" => array(
                        "type" => "string",
                        "label" => "Mail Subject Template for Invitation Link",
                        "help" => "You can use {{SURVEY_TITLE}}  placeholder. Overwritable in each Survey setting.",
                        "default" => $this->get("InvitationLink_MailSubject_Template", null, null, ""),
                        "current" => $this->get("InvitationLink_MailSubject_Template", "Survey", $oEvent->get("survey"))
                    ),
                    "InvitationLink_MailBody_Template" => array(
                        "type" => "text",
                        "label" => "Mail Body Template for Invitation Link",
                        "help" => "You can use {{SURVEY_TITLE}} placeholder and you must use {{INVITATION_LINK}} placeholder. Overwritable in each Survey setting.",
                        "default" => $this->get("InvitationLink_MailBody_Template", null, null, ""),
                        "current" => $this->get("InvitationLink_MailBody_Template", "Survey", $oEvent->get("survey"))
                    ),

                    "ActivationLink_MailSubject" => array(
                        "type" => "string",
                        "label" => "Mail Subject for Activation Link",
                        "help" => "You can use {{SURVEY_TITLE}} and {{SPID_PARTICIPANT}}  placeholder. Overwritable in each Survey setting.",
                        "default" => $this->get("ActivationLink_MailSubject", null, null, ""),
                        "current" => $this->get("ActivationLink_MailSubject", "Survey", $oEvent->get("survey"))
                    ),
                    "ActivationLink_MailBody" => array(
                        "type" => "text",
                        "label" => "Mail Body for Activation Link",
                        "help" => "You can use {{SURVEY_TITLE}}, {{SURVEY_ENTE}}, {{SPID_PARTICIPANT}}, {{SURVEY_TOKEN}} and {{SURVEY_LINK}} placeholder. Overwritable in each Survey setting.",
                        "default" => $this->get("ActivationLink_MailBody", null, null, ""),
                        "current" => $this->get("ActivationLink_MailBody", "Survey", $oEvent->get("survey"))
                    ),


                    "SpidLogoutRedirectURL" => array(
                        "type" => "string",
                        "label" => "URL to redirect after SPID Logout",
                        "help" => "Overwritable in each Survey setting.",
                        "default" => $this->get("SpidLogoutRedirectURL", null, null, ""),
                        "current" => $this->get("SpidLogoutRedirectURL", "Survey", $oEvent->get("survey"))
                    ),


                    "ente_ipa_code_mapping" => array(
                        "type" => "string",
                        "label" => "Participant attribute to map  \"ente.ipa_code\"",
                        "current" => $this->get("ente_ipa_code_mapping", "Survey", $oEvent->get("survey"))

                    ),
                    "ente_denominazione_mapping" => array(
                        "type" => "string",
                        "label" => "Participant Attribute to map  \"ente.denominazione\"",
                        "current" => $this->get("ente_denominazione_mapping", "Survey", $oEvent->get("survey"))
                    ),
                    "ente_cf_mapping" => array(
                        "type" => "string",
                        "label" => "Participant Attribute to map  \"ente.cf\"",
                        "current" => $this->get("ente_cf_mapping", "Survey", $oEvent->get("survey"))
                    ),
                    "ente_pec_mapping" => array(
                        "type" => "string",
                        "label" => "Participant Attribute to map  \"ente.pec\"",
                        "current" => $this->get("ente_pec_mapping", "Survey", $oEvent->get("survey"))
                    ),
                    "ente_comune_mapping" => array(
                        "type" => "string",
                        "label" => "Participant Attribute to map  \"ente.comune\"",
                        "current" => $this->get("ente_comune_mapping", "Survey", $oEvent->get("survey"))
                    ),
                    "ente_desc_categoria_ipa_mapping" => array(
                        "type" => "string",
                        "label" => "Participant Attribute to map  \"ente.desc_categoria_ipa\"",
                        "current" => $this->get("ente_desc_categoria_ipa_mapping", "Survey", $oEvent->get("survey"))
                    ),
                    "ente_tipo_ente_custom_mapping" => array(
                        "type" => "string",
                        "label" => "Participant Attribute to map  \"ente.tipo_ente_custom\"",
                        "current" => $this->get("ente_tipo_ente_custom_mapping", "Survey", $oEvent->get("survey"))
                    )

                )
            )
        );
    }

    /**
     * Save the settings
     */
    public function newSurveySettings()
    {
        $event = $this->event;
        foreach ($event->get("settings") as $name => $value) {
            /* In order use survey setting, if not set, use global, if not set use default */
            $default = $event->get($name, null, null, isset($this->settings[$name]["default"]) ? $this->settings[$name]["default"] : NULL);
            $this->set($name, $value, "Survey", $event->get("survey"), $default);
        }
    }

    /**
     * Set the alias to get the file
     */
    public function afterPluginLoad()
    {
        Yii::setPathOfAlias("AgID_PEC", dirname(__FILE__));
        $this->_imgAssetsURL = Yii::app()->assetManager->publish(dirname(__FILE__) . "/assets/spid/img");
    }

    public function beforeControllerAction()
    {
        $c = $this->getEvent()->get("controller");
        $a = $this->getEvent()->get("action");
        if ($c == "survey" && $a == "index" && $this->str_endWith(Yii::app()->getRequest()->getPathInfo(), "spidactivation")) {
            Yii::app()->getClientScript()->registerCssFile(Yii::app()->assetManager->publish(dirname(__FILE__) . "/assets/spid/css/spid-sp-access-button.min.css"));
            Yii::app()->getClientScript()->registerCssFile(Yii::app()->assetManager->publish(dirname(__FILE__) . "/assets/spid/css/spidlogin-custom.css"));
            //Yii::app()->getClientScript()->registerScriptFile(Yii::app()->assetManager->publish(dirname(__FILE__) . "/assets/spid/js/jquery.min.js"));
            Yii::app()->getClientScript()->registerScriptFile(Yii::app()->assetManager->publish(dirname(__FILE__) . "/assets/spid/js/spid-sp-access-button.min.js"));
            Yii::app()->bootstrap->registerAllScripts();
            $this->spidParticipatsActivationHandle();
            $this->getEvent()->set("run", false);
        }
    }

    public function beforeActivate()
    {
        if (!$this->api->tableExists($this, "enti")) {
            $this->api->createTable(
                $this,
                "enti",
                array(
                    "ipa_code varchar(50) NOT NULL",
                    "denominazione varchar(255) NOT NULL",
                    "cf text",
                    "pec text",
                    "comune text",
                    "desc_categoria_ipa text",
                    "tipo_ente_custom text",
                    "qualtrics_link text",
                    "CONSTRAINT pk_enti PRIMARY KEY (ipa_code)"
                )
            );
        }
        if (!$this->api->tableExists($this, "enti")) {
            $this->api->createTable(
                $this,
                "enti",
                array(
                    "ipa_code varchar(50) NOT NULL",
                    "denominazione varchar(255) NOT NULL",
                    "cf text",
                    "pec text",
                    "comune text",
                    "desc_categoria_ipa text",
                    "tipo_ente_custom text",
                    "qualtrics_link text",
                    "CONSTRAINT pk_enti PRIMARY KEY (ipa_code)"
                )
            );
        }



        if (!$this->api->tableExists($this, "spid_participants")) {
            $this->api->createTable(
                $this,
                "spid_participants",
                array(
                    "spidcode varchar(14) NOT NULL",
                    "fiscalnumber varchar(22)",
                    "name varchar(100)  NOT NULL",
                    "familyname varchar(100) NOT NULL",
                    "mobilephone varchar(50)",
                    "email varchar(255) NOT NULL",
                    "CONSTRAINT pk_participants PRIMARY KEY (spidcode)"
                )
            );
        }
        if (!$this->api->tableExists($this, "invitations")) {
            $this->api->createTable(
                $this,
                "invitations",
                array(
                    "id " . (($this->dbms == "pgsql") ? "SERIAL" : "INT NOT NULL AUTO_INCREMENT"),
                    "ipa_code varchar(50) NOT NULL",
                    "survey_id int NOT NULL",
                    "token_id " . (($this->dbms == "pgsql") ? "int" : "int(11)") . " NOT NULL",
                    "token text",
                    "pec varchar(75) NOT NULL",
                    "mail_subject text",
                    "mail_text text",
                    "nonce varchar(32) NOT NULL",
                    "stato_invio int NOT NULL DEFAULT 0",
                    "data_ultimo_invio " . (($this->dbms == "pgsql") ? "timestamp" : "datetime"),
                    "CONSTRAINT pk_invitations PRIMARY KEY (id)",
                    "CONSTRAINT idx_invitations_ipa_code_survey_id_pec UNIQUE (ipa_code, survey_id, pec)"
                )
            );
        }
        if (!$this->api->tableExists($this, "invitations_participants")) {
            $this->api->createTable(
                $this,
                "invitations_participants",
                array(
                    "invitation_id INT NOT NULL",
                    "spidcode varchar(14) NOT NULL",
                    "activation_date " . (($this->dbms == "pgsql") ? "timestamp" : "datetime") . " NOT NULL",
                    "CONSTRAINT pk_invitations_participants PRIMARY KEY (invitation_id, spidcode)",
                )
            );
        }
        IPA_Data_Helper::update_ipa_data($this->dbms);
    }

    /**
     * Append menus to top admin menu bar
     * @return void
     */
    public function beforeToolsMenuRender()
    {
        $surveyId = $this->getEvent()->get("surveyId");
        if (Permission::model()->hasSurveyPermission($surveyId, "tokens", "create")) {
            // Append menu
            $event = $this->getEvent();
            $event->append("menuItems", array(
                new MenuItem(array(
                    "tooltip" => gT("Gestione inviti PEC"),
                    "label" => gT("Gestione inviti PEC"),
                    "href" => $this->api->createUrl(
                        "admin/pluginhelper",
                        array(
                            "sa" => "sidebody",
                            "surveyId" => $surveyId,
                            "plugin" => "AgID_PEC",
                            "method" => "actionIndex"  // Method name in our plugin
                        )
                    ),
                    "iconClass" => "icon-emailtemplates"
                ))
            ));
        }
    }

    /**
     * Append menus to side admin menu bar
     * @return void
     */
    public function afterQuickMenuLoad()
    {
        $surveyId = $this->getEvent()->get("aData")["surveyid"];
        if (Permission::model()->hasSurveyPermission($surveyId, "tokens", "create")) {
            $event = $this->getEvent();
            $event->append("quickMenuItems", array(
                array(
                    "name" => "gestione-inviti-pec",
                    "href" => $this->api->createUrl(
                        "admin/pluginhelper",
                        array(
                            "sa" => "sidebody",
                            "surveyId" => $surveyId,
                            "plugin" => "AgID_PEC",
                            "method" => "actionIndex"  // Method name in our plugin
                        )
                    ),
                    "tooltip" => gT("Gestione inviti PEC"),
                    "iconClass" => "icon-emailtemplates",
                    "openInNewTab" => false
                )
            ));
        }
    }

    public function actionIndex($surveyId)
    {
        $request = Yii::app()->request;
        if ($surveyId instanceof LSHttpRequest) {
            $surveyId = $request->getParam("surveyId");
        }
        if ((string)(int)$surveyId !== (string)$surveyId) {
            throw new CHttpException(404, gT("Invalid survey id"));
        }
        $survey = Survey::model()->findByPk($surveyId);
        if (empty($survey)) {
            throw new CHttpException(404, gT("Survey not found"));
        }
        if (!Permission::model()->hasSurveyPermission($surveyId, "tokens", "create")) {
            Yii::app()->session["flashmessage"] = gT("You do not have permission to access this page.");
            Yii::app()->getController()->redirect(array("/admin/survey/sa/view/surveyid/{$surveyId}"));
        }
        $data = array();
        $data["surveyId"] = $surveyId;
        $data["model"] = new Ente("search");
        $data["view"] = "listEnti";
        return $this->renderPartial("index", $data, true);
    }

    public function listInvitations($surveyId)
    {
        $request = Yii::app()->request;
        if ($surveyId instanceof LSHttpRequest) {
            $surveyId = $request->getParam("surveyId");
        }
        if ((string)(int)$surveyId !== (string)$surveyId) {
            throw new CHttpException(404, gT("Invalid survey id"));
        }
        $survey = Survey::model()->findByPk($surveyId);
        if (empty($survey)) {
            throw new CHttpException(404, gT("Survey not found"));
        }
        if (!Permission::model()->hasSurveyPermission($surveyId, "tokens", "create")) {
            Yii::app()->session["flashmessage"] = gT("You do not have permission to access this page.");
            Yii::app()->getController()->redirect(array("/admin/survey/sa/view/surveyid/{$surveyId}"));
        }

        $ipaCode = $request->getParam("ipaCode");
        $ente = Ente::model()->findByPk($ipaCode);
        if (empty($ente)) {
            throw new CHttpException(404, "Codice IPA non esistente");
        }
        $data = array();
        $data["surveyId"] = $surveyId;
        $data["ipaCode"] = $ipaCode;
        $data["enteName"] = $ente->denominazione;
        $data["model"] = new Invitation("search");
        $data["view"] = "listInvitations";
        return $this->renderPartial("index", $data, true);
    }


    public function addInvitation($surveyId)
    {
        $request = Yii::app()->request;
        if ($surveyId instanceof LSHttpRequest) {
            $surveyId = $request->getParam("surveyId");
        }
        if ((string)(int)$surveyId !== (string)$surveyId) {
            throw new CHttpException(404, gT("Invalid survey id"));
        }
        $survey = Survey::model()->findByPk($surveyId);
        if (empty($survey)) {
            throw new CHttpException(404, gT("Survey not found"));
        }
        if (!Permission::model()->hasSurveyPermission($surveyId, "tokens", "create")) {
            Yii::app()->session["flashmessage"] = gT("You do not have permission to access this page.");
            Yii::app()->getController()->redirect(array("/admin/survey/sa/view/surveyid/{$surveyId}"));
        }

        // Check to see if a token table exists for this survey
        $tokenExists = tableExists("{{tokens_" . $surveyId . "}}");
        if (!$tokenExists) {
            // If no tokens table exists
            Token::createTable($surveyId);
            LimeExpressionManager::SetDirtyFlag();  // LimeExpressionManager needs to know about the new token table
        }

        $ipaCode = $request->getParam("ipaCode");
        $ente = Ente::model()->findByPk($ipaCode);
        if (empty($ente)) {
            throw new CHttpException(404, "Codice IPA non esistente");
        }

        $data = array();
        $data["surveyId"] = $surveyId;
        $data["ipaCode"] = $ipaCode;
        $data["enteName"] = $ente->denominazione;
        $data["pec"] = $ente->pec;
        $data["subject"] = $this->getOverwritableSetting("InvitationLink_MailSubject_Template", $surveyId);
        $data["text"] = $this->getOverwritableSetting("InvitationLink_MailBody_Template", $surveyId);
        $data["view"] = "addInvitation";

        if ($request->getPost("action") == "new_invitation") {
            $pec = $request->getPost("pec");
            $text = $request->getPost("text");
            $subject = $request->getPost("subject");
            $sameInvitation = Invitation::model()->findAllByAttributes(array("ipa_code" => $ipaCode, "survey_id" => $surveyId, "pec" => $pec));
            $isValid = true;
            if (empty($text) || strpos($text, "{{INVITATION_LINK}}") === false) {
                Yii::app()->user->setFlash("error", gT("Nel testo del messaggio manca il tag {{INVITATION_LINK}}."));
                $isValid = false;
            }
            if (!filter_var($pec, FILTER_VALIDATE_EMAIL)) {
                Yii::app()->user->setFlash("error", gT("Indirizzo PEC non valido."));
                $isValid = false;
            }
            if (count($sameInvitation) != 0) {
                Yii::app()->user->setFlash("error", gT("Esiste già un altro invito con lo stesso indirizzo PEC."));
                $isValid = false;
            }
            if (!$isValid) {
                $data["pec"] = $pec;
                $data["subject"] = $subject;
                $data["text"] = $text;
                return $this->renderPartial("index", $data, true);
            } else {

                $invitation = new Invitation;
                $sameEnteSurveyInvitation = Invitation::model()->findByAttributes(array("ipa_code" => $ipaCode, "survey_id" => $surveyId));

                if (empty($sameEnteSurveyInvitation)) {
                    $token = Token::create($surveyId);
                    $attrs = $this->tokenPrepareAttribute($surveyId, $ente, true);
                    $token->setAttributes($attrs, true);
                    $token->save();

                    $invitation->token_id = $token->tid;
                    $invitation->token = $token->token;


                } else {

                    $invitation->token_id = $sameEnteSurveyInvitation->token_id;
                    $invitation->token = $sameEnteSurveyInvitation->token;


                }

                $invitation->ipa_code = $ipaCode;
                $invitation->survey_id = $surveyId;
                $invitation->pec = $pec;
                $invitation->mail_subject = $subject;
                $invitation->mail_text = $text;
                $invitation->nonce = str_replace(array("~", "_"), array("a", "z"), Yii::app()->securityManager->generateRandomString(32));
                $invitation->save();
                Yii::app()->user->setFlash("success", gT("Invito all'indagine creato correttamente."));
                Yii::app()->getController()->redirect($this->api->createUrl(
                    "admin/pluginhelper",
                    array(
                        "sa" => "sidebody",
                        "surveyId" => $surveyId,
                        "ipaCode" => $ipaCode,
                        "plugin" => "AgID_PEC",
                        "method" => "listInvitations"  // Method name in our plugin
                    )
                ));
            }
        } else {
            return $this->renderPartial("index", $data, true);
        }
    }

    public function editInvitation($surveyId)
    {
        $request = Yii::app()->request;
        if ($surveyId instanceof LSHttpRequest) {
            $surveyId = $request->getParam("surveyId");
        }
        if ((string)(int)$surveyId !== (string)$surveyId) {
            throw new CHttpException(404, gT("Invalid survey id"));
        }
        $survey = Survey::model()->findByPk($surveyId);
        if (empty($survey)) {
            throw new CHttpException(404, gT("Survey not found"));
        }
        if (!Permission::model()->hasSurveyPermission($surveyId, "tokens", "create")) {
            Yii::app()->session["flashmessage"] = gT("You do not have permission to access this page.");
            Yii::app()->getController()->redirect(array("/admin/survey/sa/view/surveyid/{$surveyId}"));
        }

        $invitationId = $request->getParam("invitationId");
        $ipaCode = $request->getParam("ipaCode");
        $ente = Ente::model()->findByPk($ipaCode);
        if (empty($ente)) {
            throw new CHttpException(404, "Codice IPA non esistente");
        }
        $invitation = Invitation::model()->findByPk($invitationId);
        if (empty($invitation)) {
            throw new CHttpException(404, "ID invito non esistente");
        }
        $data = array();
        $data["surveyId"] = $surveyId;
        $data["ipaCode"] = $ipaCode;
        $data["invitationId"] = $invitationId;
        $data["enteName"] = $ente->denominazione;
        $data["stato_invio"] = $invitation->stato_invio;
        $data["pec"] = $invitation->pec;
        $data["subject"] = $invitation->mail_subject;
        $data["text"] = $invitation->mail_text;
        $data["view"] = "editInvitation";

        if ($request->getPost("action") == "edit_invitation") {
            $pec = $request->getPost("pec");
            if ($invitation->stato_invio != 0 && $pec != $invitation->pec) {
                throw new CHttpException(403, "Non è possibile modificare l'indirizzo PEC di un invito già spedito");
            }
            $subject = $request->getPost("subject");
            $text = $request->getPost("text");
            $sameInvitation = Invitation::model()->findAllByAttributes(array("ipa_code" => $ipaCode, "survey_id" => $surveyId, "pec" => $pec));
            $isValid = true;
            if (empty($text) || strpos($text, "{{INVITATION_LINK}}") === false) {
                Yii::app()->user->setFlash("error", gT("Nel testo del messaggio manca il token {{INVITATION_LINK}}."));
                $isValid = false;
            }
            if (!filter_var($pec, FILTER_VALIDATE_EMAIL)) {
                Yii::app()->user->setFlash("error", gT("Indirizzo PEC non valido."));
                $isValid = false;
            }
            if ($pec != $invitation->pec && count($sameInvitation) != 0) {
                Yii::app()->user->setFlash("error", gT("Esiste già un altro invito con lo stesso indirizzo PEC."));
                $isValid = false;
            }
            if (!$isValid) {
                $data["pec"] = $pec;
                $data["text"] = $text;
                return $this->renderPartial("index", $data, true);
            } else {
                $invitation->pec = $pec;
                $invitation->mail_subject = $subject;
                $invitation->mail_text = $text;

                $invitation->save();

                Yii::app()->user->setFlash("success", gT("Invito all'indagine modificato correttamente."));
                Yii::app()->getController()->redirect($this->api->createUrl(
                    "admin/pluginhelper",
                    array(
                        "sa" => "sidebody",
                        "surveyId" => $surveyId,
                        "ipaCode" => $ipaCode,
                        "plugin" => "AgID_PEC",
                        "method" => "listInvitations"  // Method name in our plugin
                    )
                ));
            }
        } else {
            return $this->renderPartial("index", $data, true);
        }
    }

    public function deleteInvitation($surveyId)
    {
        $request = Yii::app()->request;
        if ($surveyId instanceof LSHttpRequest) {
            $surveyId = $request->getParam("surveyId");
        }
        if ((string)(int)$surveyId !== (string)$surveyId) {
            throw new CHttpException(404, gT("Invalid survey id"));
        }
        $survey = Survey::model()->findByPk($surveyId);
        if (empty($survey)) {
            throw new CHttpException(404, gT("Survey not found"));
        }
        if (!Permission::model()->hasSurveyPermission($surveyId, "tokens", "create")) {
            Yii::app()->session["flashmessage"] = gT("You do not have permission to access this page.");
            Yii::app()->getController()->redirect(array("/admin/survey/sa/view/surveyid/{$surveyId}"));
        }
        $invitationId = $request->getParam("invitationId");
        $ipaCode = $request->getParam("ipaCode");
        $ente = Ente::model()->findByPk($ipaCode);
        if (empty($ente)) {
            throw new CHttpException(404, "Codice IPA non esistente");
        }
        $invitation = Invitation::model()->findByPk($invitationId);
        if (empty($invitation)) {
            throw new CHttpException(404, "ID invito non esistente");
        }

        if ($invitation->stato_invio != 0) {
            throw new CHttpException(403, "Non è possibile eliminare un invito già spedito");
        }
        $sameEnteSurveyInvitation = Invitation::model()->findAllByAttributes(array("ipa_code" => $ipaCode, "survey_id" => $surveyId));
        if (count($sameEnteSurveyInvitation) == 1) { //this is the last invitation so we need to delete the token
            $token = Token::model($surveyId)->findByPk($sameEnteSurveyInvitation[0]->token_id);
            $token->delete();
        }
        $invitation->delete();
        Yii::app()->user->setFlash("success", gT("Invito all'indagine eliminato correttamente."));
        Yii::app()->getController()->redirect($this->api->createUrl(
            "admin/pluginhelper",
            array(
                "sa" => "sidebody",
                "surveyId" => $surveyId,
                "ipaCode" => $ipaCode,
                "plugin" => "AgID_PEC",
                "method" => "listInvitations"  // Method name in our plugin
            )
        ));
    }

    public function sendInvitation($surveyId)
    {
        $request = Yii::app()->request;
        if ($surveyId instanceof LSHttpRequest) {
            $surveyId = $request->getParam("surveyId");
        }
        if ((string)(int)$surveyId !== (string)$surveyId) {
            throw new CHttpException(404, gT("Invalid survey id"));
        }
        $survey = Survey::model()->findByPk($surveyId);
        if (empty($survey)) {
            throw new CHttpException(404, gT("Survey not found"));
        }
        if (!Permission::model()->hasSurveyPermission($surveyId, "tokens", "create")) {
            Yii::app()->session["flashmessage"] = gT("You do not have permission to access this page.");
            Yii::app()->getController()->redirect(array("/admin/survey/sa/view/surveyid/{$surveyId}"));
        }
        $invitationId = $request->getParam("invitationId");
        $ipaCode = $request->getParam("ipaCode");
        $ente = Ente::model()->findByPk($ipaCode);
        if (empty($ente)) {
            throw new CHttpException(404, "Codice IPA non esistente");
        }
        $invitation = Invitation::model()->findByPk($invitationId);
        if (empty($invitation)) {
            throw new CHttpException(404, "ID invito non esistente");
        }
        $invitation_link = Yii::app()->getController()->createAbsoluteUrl("survey/index/spidactivation");
        $invitation_link .= "?iid=" . urlencode($invitationId) . "&n=" . urlencode($invitation->nonce);

        date_default_timezone_set("Europe/Rome"); // SMTP needs accurate times, and the PHP time zone MUST be set
        require_once(APPPATH . "/third_party/phpmailer/PHPMailerAutoload.php");
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = $this->get("PEC_SMTP_host");
        $mail->Port = $this->get("PEC_SMTP_port");
        $mail->SMTPSecure = $this->get("PEC_SMTP_secure");
        $mail->SMTPAuth = true;
        $mail->Username = $this->get("PEC_SMTP_username");
        $mail->Password = $this->get("PEC_SMTP_password");
        $mail->setFrom($this->get("PEC_address"), $this->get("PEC_name"));
        $mail->Subject = str_replace("{{SURVEY_TITLE}}", $survey->defaultlanguage->surveyls_title, $invitation->mail_subject);
        $mail->Body = str_replace(array(
            "{{INVITATION_LINK}}",
            "{{SURVEY_TITLE}}"),
            array(
                $invitation_link,
                $survey->defaultlanguage->surveyls_title),
            $invitation->mail_text
        );
        $mail->CharSe = "UTF-8";


        $mail->addAddress($invitation->pec);

        //TODO remove

        if ($this->email_debug) {

            $mail->smtpConnect([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

        }


        if (!$mail->send()) {
            Yii::app()->user->setFlash("error", gT("Errore nella spedizione dell'invito all'indagine. Controllare le impostazioni SMTP."));
        } else {
            $invitation->data_ultimo_invio = date("Y-m-d H:i:s");
            $invitation->stato_invio = 1;
            $invitation->save();
            Yii::app()->user->setFlash("success", gT("Invito all'indagine spedito correttamente."));
        }
        Yii::app()->getController()->redirect($this->api->createUrl(
            "admin/pluginhelper",
            array(
                "sa" => "sidebody",
                "surveyId" => $surveyId,
                "ipaCode" => $ipaCode,
                "plugin" => "AgID_PEC",
                "method" => "listInvitations"  // Method name in our plugin
            )
        ));
    }

    public function listSPIDParticipants($surveyId)
    {
        $request = Yii::app()->request;
        if ($surveyId instanceof LSHttpRequest) {
            $surveyId = $request->getParam("surveyId");
        }
        if ((string)(int)$surveyId !== (string)$surveyId) {
            throw new CHttpException(404, gT("Invalid survey id"));
        }
        $survey = Survey::model()->findByPk($surveyId);
        if (empty($survey)) {
            throw new CHttpException(404, gT("Survey not found"));
        }
        if (!Permission::model()->hasSurveyPermission($surveyId, "tokens", "create")) {
            Yii::app()->session["flashmessage"] = gT("You do not have permission to access this page.");
            Yii::app()->getController()->redirect(array("/admin/survey/sa/view/surveyid/{$surveyId}"));
        }
        $invitationId = $request->getParam("invitationId");
        $ipaCode = $request->getParam("ipaCode");
        $ente = Ente::model()->findByPk($ipaCode);
        if (empty($ente)) {
            throw new CHttpException(404, "Codice IPA non esistente");
        }
        $invitation = Invitation::model()->findByPk($invitationId);
        if (empty($invitation)) {
            throw new CHttpException(404, "ID invito non esistente");
        }
        $data = array();
        $data["surveyId"] = $surveyId;
        $data["ipaCode"] = $ipaCode;
        $data["invitationId"] = $invitationId;
        $data["pec"] = Invitation::model()->findByPk($invitationId)->pec;
        $data["enteName"] = $ente->denominazione;
        $data["model"] = new SPIDParticipant("search");
        $data["view"] = "listSPIDParticipants";
        return $this->renderPartial("index", $data, true);
    }

    protected function get_saml_instance()
    {
        if ($this->ssp == null) {
            $simplesamlphp_path = $this->get("simplesamlphp_path", null, null, $this->settings["simplesamlphp_path"]);

            // To avoid __autoload conflicts, remove limesurvey autoloads temporarily
            $autoload_functions = spl_autoload_functions();
            foreach ($autoload_functions as $function) {
                spl_autoload_unregister($function);
            }

            require_once($simplesamlphp_path . "/lib/_autoload.php");
            $saml_authsource = $this->get("saml_authsource", null, null, $this->settings["saml_authsource"]);
            $this->ssp = new SimpleSAML_Auth_Simple($saml_authsource);

            // To avoid __autoload conflicts, restore the limesurvey autoloads
            foreach ($autoload_functions as $function) {
                spl_autoload_register($function);
            }
        }
        return $this->ssp;
    }

    private function spidParticipatsActivationHandle()
    {
        $invitationId = $req = Yii::app()->getRequest()->getParam("iid");
        $nonce = $req = Yii::app()->getRequest()->getParam("n");
        $invitation = Invitation::model()->findByPk($invitationId);


        if (empty($invitation)) {
            throw new CHttpException(404, gT("Invitation not found"));
        }

        $survey = Survey::model()->findByPk($invitation->survey_id);
        if (empty($survey)) {
            throw new CHttpException(404, gT("Survey not found"));
        }

        $this->checkSPIDAuth($survey);

        //check nonce
        if (empty($nonce) || $invitation->nonce != $nonce) {
            throw new CHttpException(403, gT("Invitation not valid"));
        }
        Yii::app()->controller->layout = "bare";
        if (Yii::app()->getRequest()->getParam("activate", 0) == 1) {
            $spidParticipant = SPIDParticipant::model()->findByPk($_SESSION["spidAttribute"]["spidCode"][0]);
            if (empty($spidParticipant)) {
                $spidParticipant = new SPIDParticipant();
            }
            $spidParticipant->spidcode = isset($_SESSION["spidAttribute"]["spidCode"][0]) ? $_SESSION["spidAttribute"]["spidCode"][0] : "";
            $spidParticipant->fiscalnumber = isset($_SESSION["spidAttribute"]["fiscalNumber"][0]) ? $_SESSION["spidAttribute"]["fiscalNumber"][0] : "";
            $spidParticipant->name = isset($_SESSION["spidAttribute"]["name"][0]) ? $_SESSION["spidAttribute"]["name"][0] : "";
            $spidParticipant->familyname = isset($_SESSION["spidAttribute"]["familyName"][0]) ? $_SESSION["spidAttribute"]["familyName"][0] : "";

            //from request
            $spidParticipant->mobilephone = Yii::app()->getRequest()->getParam("mobilePhone", isset($_SESSION["spidAttribute"]["mobilePhone"][0]) ? $_SESSION["spidAttribute"]["mobilePhone"][0] : "");
            $spidParticipant->email = Yii::app()->getRequest()->getParam("email", isset($_SESSION["spidAttribute"]["email"][0]) ? $_SESSION["spidAttribute"]["email"][0] : "");

            if (empty($spidParticipant->email)) {
                throw new CHttpException(404, gT("eMail is mandatary"));
            }

            $spidParticipant->save();
            $invited = InvitedSPIDParticipants::model()->findByPk(array("invitation_id" => $invitation->id, "spidcode" => $spidParticipant->spidcode));

            if (empty($invited)) {
                $invited = new InvitedSPIDParticipants();
                $invited->invitation_id = $invitation->id;
                $invited->spidcode = $spidParticipant->spidcode;
                $invited->activation_date = date("Y-m-d H:i:s");
                $invited->save();
            }

            //Send Survey Access Link
            $mailSubject = $this->getOverwritableSetting("ActivationLink_MailSubject", $invitation->survey_id);
            $mailBody = $this->getOverwritableSetting("ActivationLink_MailBody", $invitation->survey_id);

            $mailSubject = str_replace(
                array("{{SURVEY_TITLE}}", "{{SPID_PARTICIPANT}}"),
                array($survey->getLocalizedTitle(), $spidParticipant->name . " " . $spidParticipant->familyname),
                $mailSubject
            );

            $ente = Ente::model()->findByPk($invitation->ipa_code);

            $token = TokenDynamic::model($invitation->survey_id)->findByPk($invitation->token_id);


            $plaform = $this->get("DefaultSurvey", null, null, $this->settings["DefaultSurvey"]);
            $is_qualtrics = $plaform == 1;


            $survey_url = $is_qualtrics ? $this->get_qualtrics_link($ente->qualtrics_link, $spidParticipant) :
                $this->api->createUrl("/" . $invitation->survey_id, array("lang" => $survey->language));


            $mailBody = str_replace(
                array(
                    "{{SURVEY_TITLE}}",
                    "{{SURVEY_ENTE}}",
                    "{{SPID_PARTICIPANT}}",
                    "{{SURVEY_TOKEN}}",
                    "{{SURVEY_LINK}}"

                ),
                array(
                    $survey->getLocalizedTitle(),
                    $ente->denominazione,
                    $spidParticipant->name . " " . $spidParticipant->familyname,
                    $token->token,
                    $survey_url,

                ),
                $mailBody
            );

            $sFrom = Yii::app()->getConfig("siteadminname") . " <" . Yii::app()->getConfig("siteadminemail") . ">";
            $mailSend = SendEmailMessage($mailBody, $mailSubject, $spidParticipant->email, $sFrom, "AgID Survey");

            if (!$mailSend) {
                throw new CHttpException(404, gT("Fail send mail"));

            }

            $renderData = array();
            $renderData["imgAssetsURL"] = $this->_imgAssetsURL;
            $renderData["spidParticipant"] = $spidParticipant;
            $renderData["surveyTitle"] = $survey->getLocalizedTitle();
            $renderData["surveyId"] = $survey->sid;
            $renderData["logoutURL"] = $this->activationURL . "&logout";

            Yii::app()->controller->render("AgID_PEC.views.spidParticipantActivated", $renderData);
            Yii::app()->end();

        } else {

            $renderData = array();
            $renderData["returnURL"] = $this->activationURL;
            $renderData["iid"] = $invitationId;
            $renderData["nonce"] = $nonce;
            $renderData["imgAssetsURL"] = $this->_imgAssetsURL;
            $renderData["surveyTitle"] = $survey->getLocalizedTitle();
            $renderData["surveyId"] = $survey->sid;

            Yii::app()->controller->render("AgID_PEC.views.spidParticipantActivation", $renderData);
            Yii::app()->end();
        }
    }


    private function get_qualtrics_link($ente_link, $usr_data)
    {

        return $ente_link . "&sid=" . base64_encode($usr_data->spidcode . " " . $usr_data->familyname . " " . $usr_data->name);
    }

    private function str_endWith($haystack, $needle)
    {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }

    private function checkSPIDAuth(&$oSurvey)
    {
        $ssp = $this->get_saml_instance();
        $this->activationURL = SimpleSAML\Utils\HTTP::getSelfURL();
        if (!$ssp->isAuthenticated()) {
            setcookie("spid_participant", "", time() - 3600, "/");
            $returnURL = $this->cleanURL($this->activationURL, array("idp"));
            if (isset($_REQUEST["idp"])) {
                $ssp->requireAuth(array(
                    "saml:idp" => $_REQUEST["idp"],
                    "ReturnTo" => $returnURL
                ));
                $session = SimpleSAML_Session::getSessionFromRequest();
                $session->cleanup();
            } else {
                $this->renderSpidLogin($returnURL, $oSurvey);
            }
        }
        if (isset($_REQUEST["logout"])) {
            setcookie("spid_participant", "", time() - 3600, "/");

            $this->logoutRedirectURL = $this->getOverwritableSetting('SpidLogoutRedirectURL', $oSurvey->sid);

            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                session_destroy();
                header("Location: " . $this->logoutRedirectURL);
            });
            set_exception_handler(function ($exception) {
                session_destroy();
                header("Location: " . $this->logoutRedirectURL);
            });

            $ssp->logout();
            restore_error_handler();
            restore_exception_handler();
        }
        if (!isset($_SESSION["spidAttribute"])) {
            $_SESSION["spidAttribute"] = $ssp->getAttributes();
            setcookie("spid_participant", $ssp->getAttributes()["name"][0] . " " . $ssp->getAttributes()["familyName"][0], null, "/");
        }

    }

    private function renderSpidLogin($returnURL, &$oSurvey)
    {
        $renderData = array();
        $renderData["returnURL"] = $returnURL;
        $renderData["imgAssetsURL"] = $this->_imgAssetsURL;
        $renderData["surveyTitle"] = $oSurvey->getLocalizedTitle();
        $renderData["surveyId"] = $oSurvey->sid;
        $spidTestIDPEnabled = $this->get("spidTestIDPEnabled", null, null, $this->settings["spidTestIDPEnabled"]);
        $renderData["testIDPEnabled"] = ($spidTestIDPEnabled == 1);

        Yii::app()->controller->layout = "bare";
        Yii::app()->controller->render("AgID_PEC.views.spidlogin", $renderData);
        Yii::app()->end();
    }

    private function cleanURL($url, $removePars = array())
    {
        $pURL = parse_url(html_entity_decode($url));
        $url = $pURL["scheme"] . "://" . $pURL["host"] . (isset($pURL["port"]) ? ":" . $pURL["port"] : "") . $pURL["path"];
        $params = "";
        if (isset($pURL["query"])) {
            $queryPart;
            parse_str($pURL["query"], $queryPart);
            foreach ($queryPart as $pName => $pVal) {
                if (!in_array($pName, $removePars)) $params .= "&" . $pName . "=" . $pVal;
            }
        }
        return (empty($params) ? $url : $url . "?" . $params);
    }


    private function getOverwritableSetting($name, $sid)
    {
        $onSurveyOpt = $this->get($name, "Survey", $sid);
        $globalDefaultOpt = isset($this->settings[$name]["default"]) ? $this->settings[$name]["default"] : NULL;
        $globalOpt = $this->get($name, null, null, $globalDefaultOpt);
        return (empty($onSurveyOpt) ? $globalOpt : $onSurveyOpt);
    }


    private function tokenPrepareAttribute($surveyId, &$ente, $generateToken = true)
    {

        $attributes = array(
            "firstname" => $ente->denominazione,
            "lastname" => $ente->ipa_code
        );

        if ($generateToken) $attributes["token"] = Token::generateRandomToken(35);

        $aTokenFieldNames = Yii::app()->db->getSchema()->getTable("{{tokens_$surveyId}}", true);

        if (isset($aTokenFieldNames)) {

            $enteFieldsMapping = array(
                'ente_ipa_code_mapping' => 'ipa_code',
                'ente_denominazione_mapping' => 'denominazione',
                'ente_cf_mapping' => 'cf',
                'ente_pec_mapping' => 'pec',
                'ente_comune_mapping' => 'comune',
                'ente_desc_categoria_ipa_mapping' => 'desc_categoria_ipa',
                'ente_tipo_ente_custom_mapping' => 'tipo_ente_custom',
            );

            foreach ($enteFieldsMapping as $mapping_key => $ente_field) {
                $attribute = $this->get($mapping_key, "Survey", $surveyId);

                if (empty($attribute)) continue;

                $column = $aTokenFieldNames->getColumn($attribute);

                if (isset($column)) $attributes[$attribute] = $ente[$ente_field];
            }
        }

        return $attributes;

    }


    /**
     * Encrypt value to a cryptojs compatiable json encoding string
     *
     * @param mixed $passphrase
     * @param mixed $value
     * @return string
     */
    function cryptoJsAesEncrypt($passphrase, $value)
    {
        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx . $passphrase . $salt, true);
            $salted .= $dx;
        }
        $key = substr($salted, 0, 32);
        $iv = substr($salted, 32, 16);
        $encrypted_data = openssl_encrypt(json_encode($value), 'aes-256-cbc', $key, true, $iv);
        $data = array("ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt));
        return json_encode($data);
    }


}
