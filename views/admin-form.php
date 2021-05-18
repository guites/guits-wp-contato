<?php
// make sure the users have the access permision
if (!current_user_can('edit_others_posts')) {
  wp_die('Usuário não autorizado.');
}
$dropdown_html = "
<label for='guits_page_select'>Escolha a página</label><br/>
<select required id='guits_page_select' name='guits[page_select]'>
<option value=''>Selecione uma página</option>
";
$wp_pages = get_pages();
$formulario_page = $this->guits_get_formulario_page();
foreach($wp_pages as $page) {
  $selected = $formulario_page == $page->ID ? "selected" : "";
  $dropdown_html .= "<option value='".$page->ID."' ".$selected.">".$page->post_title."</option>";
}
$dropdown_html .= "<option value='nenhuma'>Nenhuma</option>";
$dropdown_html .= "</select><br/><br/>";
$use_recaptcha = $guits_recaptcha->checkStatus();
$recaptcha_checkbox = "
<p>Marque para utilizar reCAPTCHA nos envios marcados como SPAM:</p>
<input type='checkbox' id='guits_recaptcha_option' name='guits[recaptcha_option]' ";
if ($use_recaptcha) {
 $recaptcha_checkbox .=  "checked";
}
$recaptcha_checkbox .=  "
/>
<label for='guits_recaptcha_option'>Utilizar reCAPTCHA</label><br/><br/>
<small>Crie suas chaves clicando <a href='https://www.google.com/recaptcha/admin' title='Site do reCAPTCHA'>aqui</a>.</small><br/><br/>
<label for='guits_recaptcha_secret_key'>Digite sua chave secreta do reCAPTCHA.</label><br/>
<input type='text' id='guits_recaptcha_secret_key' value='".$guits_recaptcha->getSecretKey()."' name='guits[recaptcha_secret_key]' placeholder='ch4v3d0m3ur3c4pth4' /><br/>
<label for='guits_recaptcha_site_key'>Digite sua chave pública do reCAPTCHA.</label><br/>
<input type='text' id='guits_recaptcha_site_key' value='".$guits_recaptcha->getSiteKey()."' name='guits[recaptcha_site_key]' placeholder='ch4v3publ1c4d0r3c4pth4' /><br/>
<small>chaves obrigatórias caso optar pelo uso do reCAPTCHA</small><br/>
";

$guits_select_page_nonce = wp_create_nonce( 'guits_select_page_form_nonce' );
?>
<h1>Guits Form Contato - Configurações.</h1>
<hr/>
<div class='guits-form-wrapper'>
<form action="<?php echo esc_url( admin_url ( 'admin-post.php' ) ); ?>" method="post" id="guits_select_page_form">
<fieldset>
<legend><strong>Defina as configurações do formulário</strong></legend>
<label for='guits_akismet_api_key'>Coloque sua chave da API Akismet</label><br/>
<small>Crie sua chave clicando <a href='https://akismet.com/signup/?plan=developer' title='Site do AKISMET'>aqui</a>.</small><br/>
<input type='text' name='guits_akismet_api_key' placeholder='m1nh4ch4v34k1sm3t' id='guits_akismet_api_key' value='<?php echo $guits_akismet->getApiKey(); ?>' required /><br/><br/>
<?php echo $dropdown_html; ?>
<?php echo $recaptcha_checkbox; ?>
<input type="hidden" name="action" value="guits_form_response">
<input type="hidden" name="guits_select_page_nonce" value="<?php echo $guits_select_page_nonce ?>" />
<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Definir página"></p>
</fieldset>
</form>
<br/><br/>
<br/><br/>
</div>
