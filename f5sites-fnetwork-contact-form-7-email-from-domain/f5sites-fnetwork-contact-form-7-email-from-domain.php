<?php
/*
LIXO
Plugin Name: f5sites-fnetwork-contact-form-7-email-from-domain
Author: Francisco Matelli Matulovic
Author uri: www.franciscomat.com
Plugin uri: www.f5sites.com/f5sites-fnetwork-contact-form-7-email-from-domain
Description: Replace original email from DATABASE settings and change it just before contact form send email
Version: 0.1
*/

add_action('wpcf7_before_send_mail', 'f5sites_fnetwork_contact_form_7_email_from_domain', 10, 2);

function f5sites_fnetwork_contact_form_7_email_from_domain($contact_form) {
	apply_filters( 'wp_mail_from', "wordpress2@francisco.com" );
	#$arg = array("recipient"=>"wordpress@".$_SERVER["HTTP_HOST"]);
	#$arg = array( "sender" => "wordpress2@f5sites.com" );
	#$properties = $contact_form->get_properties();

	#$properties['sender'] = "wordpress2@f5sites.com";
	#$properties['mail'] = wpcf7_sanitize_mail(
	#	$args['mail'], $properties['mail'] );
	#sender
	#recipient
	/*$arg = array( 
			"active" => true,
			"subject" =>  "[your-subject]",
			"sender" => "[your-name] ",
			"recipient" => "AAAAAAAAAAAfmatelli@gmail.com",
			"body" => "De: [your-name] <[your-email]> Assunto: [your-subject] Corpo da Mensagem: [your-message] -- Email enviado pelo formulário de contato",
			"additional_headers" => "",
			"attachments" => "",
			"use_html" => false,
			"exclude_blank"=> false
			);
	$contact_form->set_properties( 'mail', $arg);*/
	#$properties = $contact_form->get_properties();

	#$contact_form->set_properties($properties);
	#var_dump($arg);die;
	#var_dump($contact_form->prop( 'mail' ));
	
	/*$skip_mail = $this->skip_mail || ! empty( $contact_form->skip_mail );
	$skip_mail = apply_filters( 'wpcf7_skip_mail', $skip_mail, $contact_form );

	if ( $skip_mail ) {
		return true;
	}

	$result = WPCF7_Mail::send( $contact_form->prop( 'mail' ), 'mail' );

	if ( $result ) {
		$additional_mail = array();

		if ( ( $mail_2 = $contact_form->prop( 'mail_2' ) ) && $mail_2['active'] ) {
			$additional_mail['mail_2'] = $mail_2;
		}

		$additional_mail = apply_filters( 'wpcf7_additional_mail',
			$additional_mail, $contact_form );

		foreach ( $additional_mail as $name => $template ) {
			WPCF7_Mail::send( $template, $name );
		}

		return true;
	}

	return false;
	die;*/
}
