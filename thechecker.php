<?php
/*
Plugin Name: Integrate Contact Form 7 with TheChecker.co
Plugin URI: http://jaworowi.cz/
Description: Integration with TheChecker.co
Version: 1.0
Author: Jakub Jaworowicz
Author URI: http://jaworowi.cz/
*/

add_action( 'wpcf7_init', 'jcz7_checkerco_add_form_tag_checkertest' );

function jcz7_checkerco_add_form_tag_checkertest() {
	wpcf7_add_form_tag(
		array( 'emailchecker', 'emailchecker*'),
		'jcz7_checkerco_text_form_tag_handler_checker', array( 'name-attr' => true ) );
}

function jcz7_checkerco_text_form_tag_handler_checker( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-text' );

	if ( in_array( 'email', array( 'email') ) ) {
		$class .= ' wpcf7-validates-as-' . 'email';
	}

	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
	}

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['maxlength'] = $tag->get_maxlength_option();
	$atts['minlength'] = $tag->get_minlength_option();

	if ( $atts['maxlength'] && $atts['minlength']
	&& $atts['maxlength'] < $atts['minlength'] ) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

	$atts['autocomplete'] = $tag->get_option( 'autocomplete',
		'[-0-9a-zA-Z]+', true );

	if ( $tag->has_option( 'readonly' ) ) {
		$atts['readonly'] = 'readonly';
	}

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = wpcf7_get_hangover( $tag->name, $value );

	$atts['value'] = $value;

	if ( wpcf7_support_html5() ) {
		$atts['type'] = 'email';
	}
	
	$atts['type'] = 'email';
	$atts['name'] = $tag->name;

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_emailchecker', 'jcz7_checkerco_text_validation_filter_checker', 10, 2 );
add_filter( 'wpcf7_validate_emailchecker*', 'jcz7_checkerco_text_validation_filter_checker', 10, 2 );

function jcz7_checkerco_text_validation_filter_checker( $result, $tag ) {
	$name = $tag->name;

	$value = isset( $_POST[$name] )
		? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
		: '';

	if ( 'emailchecker' == 'email' ) {
		if ( $tag->is_required() && '' == $value ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
		} elseif ( '' != $value && ! wpcf7_is_email( $value ) ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_email' ) );
		}
	}

	if ( '' !== $value ) {
		$maxlength = $tag->get_maxlength_option();
		$minlength = $tag->get_minlength_option();

		if ( $maxlength && $minlength && $maxlength < $minlength ) {
			$maxlength = $minlength = null;
		}

		$code_units = wpcf7_count_code_units( stripslashes( $value ) );

		if ( false !== $code_units ) {
			if ( $maxlength && $maxlength < $code_units ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_too_long' ) );
			} elseif ( $minlength && $code_units < $minlength ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_too_short' ) );
			}
		}
	}

	return $result;
}


/* Messages */

add_filter( 'wpcf7_messages', 'jcz7_checkerco_text_messages_checkemail' );

function jcz7_checkerco_text_messages_checkemail( $messages ) {
	$messages = array_merge( $messages, array(
		'invalid_email' => array(
			'description' =>
				__( "Email address that the sender entered is invalid", 'contact-form-7' ),
			'default' =>
				__( "The e-mail address entered is invalid.", 'contact-form-7' ),
		),

		'invalid_url' => array(
			'description' =>
				__( "URL that the sender entered is invalid", 'contact-form-7' ),
			'default' =>
				__( "The URL is invalid.", 'contact-form-7' ),
		),

		'invalid_tel' => array(
			'description' =>
				__( "Telephone number that the sender entered is invalid", 'contact-form-7' ),
			'default' =>
				__( "The telephone number is invalid.", 'contact-form-7' ),
		),
	) );

	return $messages;
}


/* Tag generator */

add_action( 'wpcf7_admin_init', 'jcz7_checkerco_add_tag_generator_checkemail', 15 );

function jcz7_checkerco_add_tag_generator_checkemail() {
	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add( 'emailchecker', __( 'emailchecker', 'contact-form-7' ),
		'jcz7_checkerco_tag_generator_checkertext' );
}

function jcz7_checkerco_tag_generator_checkertext( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = $args['id'];

	if ( ! in_array( $type, array( 'emailchecker') ) ) {
		$type = 'email';
	}

	if ( 'emailchecker' == $type ) {
		$description = __( "Generate a form-tag for a single-line email address input field. For more details, see %s.", 'contact-form-7' );
	}

	$desc_link = wpcf7_link( __( 'https://contactform7.com/text-fields/', 'contact-form-7' ), __( 'Text Fields', 'contact-form-7' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
		<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Default value', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
	<label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html( __( 'Use this text as the placeholder of the field', 'contact-form-7' ) ); ?></label></td>
	</tr>

<?php if ( in_array( $type, array('emailchecker') ) ) : ?>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Akismet', 'contact-form-7' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Akismet', 'contact-form-7' ) ); ?></legend>

<?php if ( 'emailchecker' == $type ) : ?>
		<label>
			<input type="checkbox" name="akismet:author_email" class="option" />
			<?php echo esc_html( __( "This field requires author's email address", 'contact-form-7' ) ); ?>
		</label>
<?php endif; ?>

		</fieldset>
	</td>
	</tr>
<?php endif; ?>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" value="thechecker-input" readonly="readonly" /></td>
	</tr>

</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
	</div>

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}
add_action( 'admin_menu', 'JCZ_HF_add_admin_menu' );
add_action( 'admin_init', 'JCZ_HF_settings_init' );


function JCZ_HF_add_admin_menu(  ) { 

	add_submenu_page( 'themes.php', 'TheChecker.co CF7', 'TheChecker.co CF7', 'manage_options', 'jcz_thechecker', 'JCZ_HF_options_page' );

}


function JCZ_HF_settings_init(  ) { 

	register_setting( 'pluginPage', 'JCZ_HF_settings' );

	add_settings_section(
		'JCZ_HF_pluginPage_section', 
		__( '<a href="http://jaworowi.cz/thechecker-co-wordpress-10432.php">How Integrate? [PL]</a>', 'JCZ_HF' ), 
		'JCZ_HF_settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'JCZ_HF_textarea_field_0', 
		__( 'Public API Key', 'JCZ_HF' ), 
		'JCZ_HF_textarea_field_0_render', 
		'pluginPage', 
		'JCZ_HF_pluginPage_section' 
	);

}


function JCZ_HF_textarea_field_0_render(  ) { 

	$options = get_option( 'JCZ_HF_settings' );
	?>
	<input name='JCZ_HF_settings[JCZ_HF_textarea_field_0]' value="<?php echo $options['JCZ_HF_textarea_field_0']; ?>"> 
	<?php

}


function JCZ_HF_settings_section_callback(  ) { 
	
	echo __( '<a href="https://thechecker.co/?coupon=JAWOROWICZ" target="_blank">Get the API key and 1000 free creditc/email check</a><br>', 'JCZ_HF' );
	echo __( '<strong>Enter Public Widget Code from - <a href="https://thechecker.co/javascript-widget?coupon=JAWOROWICZ" target="_blank">this page</a> - Copy and paste fragment on red color.</strong></br>', 'JCZ_HF' );
	echo '</br><img width="100%" style="max-width: 793px;" src="' . plugins_url( 'pr_pk.PNG', __FILE__ ) . '" > ';

}

function jcz_header() {
	$options = get_option( 'JCZ_HF_settings' );
	$checker_public_api = $options['JCZ_HF_textarea_field_0'];
    if( $checker_public_api ){
     wp_enqueue_script('thechecker', '//thechecker.co/widget.js?k='.$checker_public_api.'', array(), null );
}
}
add_action( 'wp_head', 'jcz_header' );

function JCZ_HF_options_page(  ) { 

	?>
	<form action='options.php' method='post'>

		<h2>TheChecker.co CF7 by <a href="http://jaworowi.cz/">Jaworowi.cz</a></a></h2>

		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>

	</form>
	<?php

}