<?php
/*
Plugin Name: WhatsApp Chat WP
Plugin URI: https://caporalmktdigital.com.br/plataformas/plugin-whatsapp-chat-wp/
Description: Inicie uma conversa no whatsapp direto de seu site.
Author: Alexandre Caporal
Author URI: https://caporalmktdigital.com.br/
Version: 2.0
License: GPLv2
*/

if ( ! defined( 'ABSPATH' ) )
	exit;

add_action( 'plugins_loaded', 'whatsapp_load_textdomain' );
function whatsapp_load_textdomain() {
load_plugin_textdomain( 'wac', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function whatsapp_chat_menu() {
	add_options_page('WhatsApp Chat Settings', 'WhatsApp Chat', 'administrator', 'whatsapp-chat-settings', 'whatsapp_chat_settings_page');
}
add_action('admin_menu', 'whatsapp_chat_menu');

function whatsapp_chat_settings_page() { ?>
<div class="wrap">
<h2>WhatsApp Chat WP</h2>
<h3><?php esc_html_e('Inicie uma conversa no whatsapp direto de seu site.', 'wac'); ?></h3>
<form method="post" action="options.php">
    <?php
		settings_fields( 'whatsapp-chat-settings' );
		do_settings_sections( 'whatsapp-chat-settings' );
	?>
	
    <table class="form-table">
        <tr valign="top">
			<th scope="row"><label for="whatsapp_chat_page"><?php esc_html_e('Número do whatsapp', 'wac'); ?></label></th>
			<td>
				<input type="text" size="30" name="whatsapp_chat_page" value="<?php echo esc_attr( get_option('whatsapp_chat_page') ); ?>" /> <small>Ex. +5512999999999<br /><?php esc_html_e('Note que é preciso preencher o número no modelo internacional +código-do-pais (55 para Brasil) DDD de sua cidade e número completo.', 'wac'); ?></small>
			</td>
        </tr>
		<tr valign="top">
			<th scope="row"><label for="whatsapp_chat_msg"><?php esc_html_e('Mensagem para iniciar conversa', 'wac'); ?></label></th>
			<td>
				<input type="text" size="60" name="whatsapp_chat_msg" value="<?php echo esc_attr( get_option('whatsapp_chat_msg') ); ?>" /> <small><?php esc_html_e('Ex. Olá, gostaria de saber mais sobre seu serviço/produto.', 'wac'); ?></small>
			</td>
		</tr>
        <tr valign="top">
			<th scope="row"><label for="whatsapp_chat_button"><?php esc_html_e('Edite botão', 'wac'); ?></label></th>
			<td>
				<input type="text" size="30" name="whatsapp_chat_button" value="<?php echo esc_attr( get_option('whatsapp_chat_button') ); ?>" /> <small><?php esc_html_e('Edite seu botão. Ex: Converse no WhatsApp', 'wac'); ?></small>
			</td>
        </tr>
		<tr valign="top">
			<th scope="row"><label for="whatsapp_chat_hide_button"><?php esc_html_e('Esconder Chat', 'wac'); ?></label></th>
			<td>
				<input type="checkbox" name="whatsapp_chat_hide_button" value="true" <?php echo ( get_option('whatsapp_chat_hide_button') == true ) ? ' checked="checked" />' : ' />'; ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="whatsapp_chat_left_side"><?php esc_html_e('Chat do lado esquerdo', 'wac'); ?></label></th>
			<td>
				<input type="checkbox" name="whatsapp_chat_left_side" value="true" <?php echo ( get_option('whatsapp_chat_left_side') == true ) ? ' checked="checked" />' : ' />'; ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="whatsapp_chat_down"><?php esc_html_e('Chat no canto Inferior', 'wac'); ?></label></th>
			<td>
				<input type="checkbox" name="whatsapp_chat_down" value="true" <?php echo ( get_option('whatsapp_chat_down') == true ) ? ' checked="checked" />' : ' />'; ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="whatsapp_chat_powered_by"><?php esc_html_e('Discreto link "desenvolvido por"', 'wac'); ?></label></th>
			<td>
				<input type="checkbox" name="whatsapp_chat_powered_by" value="true" <?php echo ( get_option('whatsapp_chat_powered_by') == true ) ? ' checked="checked" />' : ' />'; ?>
			</td>
		</tr>
    </table>
    <?php submit_button(); ?>
</form>
<h3><?php esc_html_e('Shortcode', 'wac'); ?></h3>
<p><?php esc_html_e('Para adicionar um botão do WhatsApp em qualquer lugar do seu site é muito fácil, basta usar como o modelo abaixo:', 'wac'); ?></p>
<p><?php esc_html_e('[whatsapp phone="0000000000" blank="true"]Texto do Botão[/whatsapp]', 'wac'); ?></p>
<p><?php esc_html_e('Você pode adicionar o número que quiser, podendo ter vários botões para diferentes números.', 'wac'); ?></p>
<p><strong><?php esc_html_e('Instruções', 'wac'); ?></strong>
<p><?php esc_html_e('Digite o telefone no modelo internacional, com o código do país + ddd + número de telefone, exemplo 551100000000', 'wac'); ?><p>
<p><?php esc_html_e('A tag blank indica se a página deve abrir em outra aba ou não, por padrão tem o valor "false" que abre na mesma página, nesse caso não é necessário incluir a tag. Caso queira abrir em outra aba, utilizar como no exemplo com valor "true".', 'wac'); ?></p>
<h3><?php esc_html_e('Gostou do plugin?', 'wac'); ?></h3>
<p><?php esc_html_e('Não deixe de avaliar.', 'wac'); ?> <a href="https://wordpress.org/support/plugin/wp-whatsapp-chat/reviews/?rate=5#new-post"><?php esc_html_e('Avaliar agora', 'wac'); ?></a></p>
<p><?php esc_html_e('Se esse plugin te ajudou, contribua para manutenção e desenvolvimentos futuros:', 'wac'); ?> <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="9E6HS3D48FS28">
<input type="image" src="https://www.paypalobjects.com/pt_BR/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - A maneira fácil e segura de enviar pagamentos online!">
<img alt="" border="0" src="https://www.paypalobjects.com/pt_BR/i/scr/pixel.gif" width="1" height="1">
</form>
</p>
<h3><?php esc_html_e('Precisa de suporte?', 'wac'); ?></h3>
<p><?php esc_html_e('Por favor entre em contato através do', 'wac'); ?> <a href="https://wordpress.org/support/plugin/wp-whatsapp-chat/"><?php esc_html_e('fórum de suporte no Wordpress', 'wac'); ?></a>.
<?php esc_html_e('Não atendemos suporte via WhatsApp', 'wac'); ?></p>
<h3><?php esc_html_e('Quem somos', 'wac'); ?></h3>
<p><?php esc_html_e('Plugin desenvolvido por', 'wac'); ?> <a href="https://caporalmktdigital.com.br/"><img width="55" style="vertical-align:middle" src="<?php echo plugins_url( 'images/caporalmktdigital.png', __FILE__ ) ?>" alt="Agência de planejamento estratégico digital" title="Caporal Mkt Digital"></a> <?php esc_html_e('especialistas em performance, novas ferramentas e estratégias digitais para PME, empreendedores e profissionais liberais.', 'wac'); ?></p>
</div>
<?php }

function whatsapp_chat_settings() {
	register_setting( 'whatsapp-chat-settings', 'whatsapp_chat_page' );
	register_setting( 'whatsapp-chat-settings', 'whatsapp_chat_msg' );
	register_setting( 'whatsapp-chat-settings', 'whatsapp_chat_hide_button' );
	register_setting( 'whatsapp-chat-settings', 'whatsapp_chat_left_side' );
	register_setting( 'whatsapp-chat-settings', 'whatsapp_chat_down' );
	register_setting( 'whatsapp-chat-settings', 'whatsapp_chat_button' );
	register_setting( 'whatsapp-chat-settings', 'whatsapp_chat_powered_by' );
}
add_action( 'admin_init', 'whatsapp_chat_settings' );

function whatsapp_chat_deactivation() {
    delete_option( 'whatsapp_chat_page' );
    delete_option( 'whatsapp_chat_msg' );
    delete_option( 'whatsapp_chat_hide_button' );
    delete_option( 'whatsapp_chat_left_side' );
    delete_option( 'whatsapp_chat_down' );
    delete_option( 'whatsapp_chat_button' );
    delete_option( 'whatsapp_chat_powered_by' );
}
register_deactivation_hook( __FILE__, 'whatsapp_chat_deactivation' );

function whatsapp_chat_dependencies() {
	wp_register_script( 'whatsapp-chat-index', '', true );
	wp_enqueue_script( 'whatsapp-chat-index' );
	wp_register_style( 'whatsapp-chat-style', plugins_url('css/style.css', __FILE__) );
	wp_enqueue_style( 'whatsapp-chat-style' );
}
add_action( 'wp_enqueue_scripts', 'whatsapp_chat_dependencies' );

function whatsapp_chat_admin_scripts() {
    if (isset($_GET['page']) && $_GET['page'] == 'whatsapp-chat-settings') {
        wp_enqueue_media();
        wp_register_script('whatsapp-chat-admin-js',"");
        wp_enqueue_script('whatsapp-chat-admin-js');
    }
}
add_action('admin_enqueue_scripts', 'whatsapp_chat_admin_scripts');

function shortcode_whatsapp_chat( $atts, $content = null ) {
    extract(shortcode_atts(array(
        'phone'      => '#',
		'blank'     => 'false'
    ), $atts));

	$blank_link = '';

	if ( $blank == 'true' )
		$blank_link = "target=\"_blank\"";

	$out = "<a class=\"whatsapp\" href=\"https://api.whatsapp.com/send?phone=" .$phone. "\"" .$blank_link."><span>" .do_shortcode($content). "</span></a>";

    return $out;
}
add_shortcode('whatsapp', 'shortcode_whatsapp_chat');

function whatsapp_chat() { ?>

<?php if (get_option('whatsapp_chat_hide_button') != true) {
?>

<div id="wacht<?php if (get_option('whatsapp_chat_left_side') == true) { ?>-leftside<?php } ?><?php if (get_option('whatsapp_chat_down') == true) { ?>-baixo<?php } ?>">
<link rel="stylesheet" href="https://d1azc1qln24ryf.cloudfront.net/114779/Socicon/style-cf.css?libdco">
<a href="https://<?php if (wp_is_mobile() ) {echo "api";} else {echo "web";}?>.whatsapp.com/send?phone=+5511958843715&text=Ola" onclick="gtag('event', 'WhatsApp', {'event_action': 'whatsapp_chat', 'event_category': 'Chat', 'event_label': 'Chat_WhatsApp'});" target="_blank"><?php echo esc_attr( get_option('whatsapp_chat_button')); ?></a><?php if (get_option('whatsapp_chat_powered_by') == true) { ?><a href="https://caporalmktdigital.com.br/" class="agencia"><?php esc_html_e('plugin whatsapp é desenvolvido por Caporal Mkt Digital, especialista em estratégias digitais', 'wac'); ?></a><?php } ?>
</div>
<?php }//hide_button ?>
<?php
}
add_action( 'wp_footer', 'whatsapp_chat', 10 );