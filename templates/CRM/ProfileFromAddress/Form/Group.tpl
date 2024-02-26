<table id='profilefromaddress-table' style="display:none;">
  <tr class="crm-uf-advancesetting-form-block-">
    <td class="label">{$form.profilefromaddress.from_email_address.label}</td>
    <td>{$form.profilefromaddress.from_email_address.html}</td>
  </tr>
  <tr class="crm-uf-advancesetting-form-block-">
    <td class="label">{$form.profilefromaddress.msg_template_id.label}</td>
    <td>{$form.profilefromaddress.msg_template_id.html}</td>
  </tr>
</table>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $('table#profilefromaddress-table tr').insertAfter('tr.crm-uf-advancesetting-form-block-notify');
  });
</script>
{/literal}
