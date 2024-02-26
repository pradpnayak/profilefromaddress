<?php

require_once 'profilefromaddress.civix.php';

use CRM_Profilefromaddress_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function profilefromaddress_civicrm_config(&$config): void {
  _profilefromaddress_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function profilefromaddress_civicrm_install(): void {
  _profilefromaddress_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function profilefromaddress_civicrm_enable(): void {
  _profilefromaddress_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm
 */
function profilefromaddress_civicrm_buildForm($formName, &$form) {
  if ('CRM_UF_Form_Group' == $formName
    && (($form->getVar('_action') & CRM_Core_Action::UPDATE)
      || ($form->getVar('_action') & CRM_Core_Action::ADD)
    )
  ) {
    $form->add('select', 'profilefromaddress[from_email_address]', ts('From'),
      ['' => ts('- select -')] + CRM_Core_BAO_Email::getFromEmail(),
      FALSE, ['class' => 'crm-select2 huge']
    );
    $form->add('select', 'profilefromaddress[msg_template_id]', ts('Msg Template'),
      ['' => ts('- select -')] + CRM_Core_BAO_MessageTemplate::getMessageTemplates(FALSE),
      FALSE, ['class' => 'crm-select2 huge']
    );
    CRM_Core_Region::instance('page-body')->add([
      'template' => 'CRM/ProfileFromAddress/Form/Group.tpl',
    ]);

    if ($form->getVar('_id')) {
      $defaults['profilefromaddress'] = (_profilefromaddress_civicrm_getSettings(
        $form->getVar('_id')
      )) ?? [];
      $form->setDefaults($defaults);
    }
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postProcess
 */
function profilefromaddress_civicrm_postProcess($formName, &$form) {
  if ('CRM_UF_Form_Group' == $formName
    && (($form->getVar('_action') & CRM_Core_Action::UPDATE)
      || ($form->getVar('_action') & CRM_Core_Action::ADD)
    )
  ) {
    $params = $form->controller->exportValues($form->getVar('_name'));
    if (!empty($params['notify']) && !empty($params['profilefromaddress'])) {
      $settings = array_filter($params['profilefromaddress']);
      if (!empty($settings)) {
        _profilefromaddress_civicrm_setSettings(
          $form->getVar('_id'), $settings
        );
      }
    }
  }
}

/**
 * Implements hook_civicrm_alterMailParams().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterMailParams
 */
function profilefromaddress_civicrm_alterMailParams(&$params, $context) {
  if (!empty($params['workflow']) && in_array($params['workflow'], ['uf_notify'])
    && !empty($params['tplParams']) && !empty($params['tplParams']['profileID'])
  ) {
    $values = _profilefromaddress_civicrm_getSettings($params['tplParams']['profileID']);
    if (!empty($values)) {
      if ($context == 'messageTemplate') {
        Civi::$statics['profilefromaddress']['profileID'] = $params['tplParams']['profileID'];
      }
      else if ($context == 'singleEmail' && !empty($values['from_email_address'])) {
        // Update From
        $from = CRM_Utils_Mail::formatFromAddress($values['from_email_address']);
        if (!empty($from)) {
          $params['from'] = $from;
        }
      }
    }
  }
}

/**
 * Implements hook_civicrm_alterMailContent().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterMailContent
 */
function profilefromaddress_civicrm_alterMailContent(&$content) {
  if (!empty($content['workflow'])
    && in_array($content['workflow'], ['uf_notify'])
    && !empty(Civi::$statics['profilefromaddress']['profileID'])
  ) {
    $values = _profilefromaddress_civicrm_getSettings(Civi::$statics['profilefromaddress']['profileID']);
    if (!empty($values['msg_template_id'])) {
      $messageTemplate = \Civi\Api4\MessageTemplate::get(FALSE)
        ->addSelect('msg_subject', 'msg_text', 'msg_html')
        ->addWhere('id', '=', $values['msg_template_id'])
        ->addWhere('is_active', '=', TRUE)
        ->execute()
        ->first();
      if (!empty($messageTemplate)) {
        $content['subject'] = $messageTemplate['msg_subject'];
        $content['text'] = $messageTemplate['msg_text'];
        $content['html'] = $messageTemplate['msg_html'];
      }
    }
    unset(Civi::$statics['profilefromaddress']);
  }
}

/**
 * Get Profile from address settings.
 *
 * @param int $profileId
 *
 * @return null|array
 */
function _profilefromaddress_civicrm_getSettings($profileId) {
  return \Civi::settings()->get('profilefromaddress_' . $profileId);
}

/**
 * Set Profile from address settings.
 *
 * @param int $profileId
 * @param array $settings
 *
 */
function _profilefromaddress_civicrm_setSettings($profileId, $settings) {
  \Civi::settings()->set('profilefromaddress_' . $profileId, $settings);
}
