<?php
/**
 * Plugin Name: Digi Forms
 * Description: Herramienta que permite implementar formularios personalizados usando shortcodes y guardar sus llenados en la base de datos de WP. Shortcode disponible: [digi_contact_form].
 * Author: Digiproduct
 * Version: 1.0.1
 * Author URI: https://www.digiproduct.com
 * PHP Version: 5.6
 * 
 * @category Form
 * @package  DIGI
 * @author   Digiproduct <salomon.cruz@digiproduct.com>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.txt
 * @link     https://www.digiproduct.com
 */

// Cuando el plugin se active se crea la tabla del mismo si no existe
 register_activation_hook(__FILE__, 'Digi_Contact_init');


/**
 * Realiza las acciones necesarias para configurar el plugin cuando se activa
 *
 * @return void
 */
 function Digi_Contact_init()
 {
    global $wpdb;   // Este objeto global nos permite trabajar con la BD de WP
    // Crea la tabla si no existe
    $tabla_contacto = $wpdb->prefix . 'contacto';
    $charset_collate = $wpdb-> get_charset_collate();
    //Prepara la consulta para crear la tabla del formulario de contacto
    $query = "CREATE TABLE IF NOT EXISTS $tabla_contacto (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nombre varchar(50) NOT NULL,
        empresa varchar(100),
        correo varchar(100) NOT NULL,
        mensaje text,
        aceptacion smallint(4) NOT NULL,
        ip varchar(300) NOT NULL,
        created_at datetime NOT NULL,
        UNIQUE (id)
    ) $charset_collate";
include_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($query);

}


 //Definición de shortcode que pinta el formulario de contacto
 add_shortcode( 'digi_contact_form', 'Digi_Contact_form');

 function Digi_Contact_form(){
    global $wpdb;

     if (!empty($_POST)
     && $_POST['nombre'] != ''
     && is_email($_POST['correo'])
     && isset($_POST['aceptacion'])
     )
     {
     $tabla_contacto = $wpdb->prefix . 'contacto';
     $nombre = sanitize_text_field($_POST['nombre']);
     $empresa = sanitize_text_field($_POST['empresa']);
     $correo = $_POST['correo'];
   $mensaje = sanitize_textarea_field($_POST['mensaje']);
   $aceptacion = (int) $_POST['aceptacion'];
   $ip = Digi_Obtener_IP_usuario();
    $created_at = date('Y-m-d H:i:s');
    
      $wpdb->insert($tabla_contacto,
       array(
          'nombre' => $nombre,
       'empresa' => $empresa,
          'correo' => $correo,
        'mensaje' => $mensaje,
        'aceptacion' => $aceptacion,
        'ip' => $ip,
         'created_at' => $created_at,
        )
      );
//Suscripción a mailchimp 
    $email = $correo;
    $NAME= $nombre;
    $COMPANY = $empresa;
    $MESSAGE = $mensaje;
    $status = 'subscribed'; // "subscribed" or "unsubscribed" or "cleaned" or "pending"
    $list_id = '4412b66666'; // where to get it read above
    $api_key = 'b9328f46de6a495fd63b646cae576aa6-us12'; // where to get it read above
    $merge_fields = array('NAME' => $NAME, 'COMPANY' => $COMPANY, 'MESSAGE' => $MESSAGE);

    rudr_mailchimp_subscriber_status($email, $status, $list_id, $api_key, $merge_fields );
    //Envio a correo electronico
    //Destinatario
    $to = "salomon.cruz@digiproduct.com";
    //Asunto
    $subject = "Nuevo formulario en cipherlabstore.mx de: " . $nombre;
    //Mensaje
    $messageEmail = "Nuevo formulario de contacto en Cipherlab Store México\n" . "\nCorreo: " . $correo . "\nEmpresa: " . $empresa . "\nMensaje: " . $mensaje ."\n\nSuscripción a boletín de noticias: Si\n\nDigi Forms";
    wp_mail($to, $subject, $messageEmail);       



       echo "<p class='exito'><b>Tus datos han sido registrados.</b> Muchas gracias. Nos contáctaremos contigo pronto.<p>";
  }
  else if(!empty($_POST)
     && $_POST['nombre'] != ''
     && is_email($_POST['correo'])
  ){
     $tabla_contacto = $wpdb->prefix . 'contacto';
     $nombre = sanitize_text_field($_POST['nombre']);
     $empresa = sanitize_text_field($_POST['empresa']);
     $correo = $_POST['correo'];
   $mensaje = sanitize_textarea_field($_POST['mensaje']);
   $aceptacion = 0;
   $ip = Digi_Obtener_IP_usuario();
    $created_at = date('Y-m-d H:i:s');
    
      $wpdb->insert($tabla_contacto,
       array(
          'nombre' => $nombre,
       'empresa' => $empresa,
          'correo' => $correo,
        'mensaje' => $mensaje,
        'aceptacion' => $aceptacion,
        'ip' => $ip,
         'created_at' => $created_at,
        )
      );

    //Seccion Envio de Email

     // Se define correo de admin 
     $bcc = "contacto@cipherlabstore.mx";
      
     //Sanitizing correo 
     $correo = filter_var($correo, FILTER_SANITIZE_EMAIL);

     //Validacion de Sanitization
   if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {  
     $php_subject = "Nuevo formulario en cipherlabstore.mx de: " . $nombre;;
     
     //Par enviar un correo HTML se define los PHP headers.
     $php_headers = 'MIME-Version: 1.0' . "\r\n";
     $php_headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
     $php_headers .= 'Bcc:' . $bcc. "\r\n"; // con copia oculta al correo de admin
     
     //Se define HTML template
     $php_template = '<div style="padding:50px;">Hola ' . $nombre . ',<br/>'
     . '<p>Gracias por ponerte en contacto con nosotros.</p><br/><br/>'
     . '<strong style="color:#15487b;">Nombre:</strong>  ' . $nombre . '<br/>'
     . '<strong style="color:#15487b;">Correo:</strong>  ' . $correo . '<br/>'
     . '<strong style="color:#15487b;">Empresa:</strong>  ' . $empresa . '<br/>'
     . '<strong style="color:#15487b;">Mensaje:</strong>  ' . $mensaje . '<br/><br/>'
     . 'Esta es una confirmación de contacto.'
     . '<br/>'
     . '<p>Te contactaremos lo más pronto posible, saludos.</p>'
     . '<br/>' 
     .'<p>Cipherlabstore Team.</p>''</div>';
     $php_sendmessage = "<div style=\"background-color:#f5f5f5; color:#333;\">" . $php_template . "</div>";
     
     // Las lineas de mensajes no deberán extender las 70 (PHP regla)
     $php_sendmessage = wordwrap($php_sendmessage, 70);
     
     // Envio de email a traves de la funcion PHP WP_Mail 
     wp_mail($correo, $php_subject, $php_sendmessage, $php_headers);
     echo "<p class='exito'><b>Tus datos han sido registrados.</b> Muchas Gracias. Nos contactaremos pronto contigo.<p>";;

    //Validación de error
   } else {
     echo "<p class='error'><b>Erorr de Correo </b></p>";
   }
  }
// Se carga hoja de estilo para poner más bonito el formulario
    wp_enqueue_style('css_formularios', plugins_url('style.css', __FILE__));
    ob_start();
    ?>
    <form action="<?php get_the_permalink(); ?>" method="post" id="form_contacto" class="formularios">
    <?php wp_nonce_field('graba_formulario', 'formulario_nonce'); ?>
    <div class="form-input">
        <label for="name">Nombre:</label
      ><input type="text" placeholder="Nombre" name="nombre" id="name" required/>
    </div>
    <div class="form-input"> <label for="company">Empresa:</label
      ><input type="text" placeholder="Empresa" name="empresa" id="company" /></div>
    <div class="form-input"><label for="email">Correo electrónico:</label
      ><input type="email" placeholder="Correo electrónico" name="correo" id="mail" required/></div>
    <div class="form-input"><label for="msg">Mensaje</label
      ><textarea name="mensaje" placeholder="Mensaje" id="msg" cols="30" rows="10"></textarea></div><br>
    <div class="form-input"><input type="checkbox" id="questionOne" name="aceptacion" value="1"/>&nbsp;¿Quieres suscribirte a nuestro boletín?</div><br>
    <div class="form-input">
      <input type="submit" value="Enviar">
     </div>
</form>
    <?php
    return ob_get_clean();
 }

 add_action("admin_menu", "Digi_Formulario_menu");
 /**Agregar menu
  * Agrega el menu del plugin al formulario de Wordpress
  */

  function Digi_Formulario_menu(){
    /*
     * Para agregar icono/imágen personalizada: (plugin_dir_url( __FILE__ ) . 'assets/imgs/wp-icon-digi.png')
     */
    add_menu_page(
    "Formularios", //Titulo de la pagina
    "Digi Forms", //Titulo del menu
    "manage_options", //Capability
    "digi_forms_menu", //slug
    "digi_Formularios_Admin", //function del contenido
    "dashicons-list-view", //icono
    50); //prioridad
   
      /*
   * Agregar submenus
   */

  add_submenu_page(
    'digi_forms_menu', //parent slug
    'Contacto', //Titulo de la página
    'Formularios de contacto', //Titulo del menu
    'manage_options',
    plugin_dir_path(__FILE__).'digi_contact_form.php',
    null
  );

  }

  //Genera la vista de la pagina principal de Digi Forms
  function digi_Formularios_Admin(){
   echo "<h1>" . get_admin_page_title() . "</h1>";
   echo "<h2>Shortcodes disponibles</h2>";
   echo "<ul><li>[digi_contact_form]</li></ul>";
  }

  /**
 * Devuelve la IP del usuario que está visitando la página
 * Código fuente: https://stackoverflow.com/questions/6717926/function-to-get-user-ip-address
 *
 * @return string
 */
function Digi_Obtener_IP_usuario()
{
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
        'REMOTE_ADDR') as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    return $ip;
                }
            }
        }
    }
}

//Suscribir a Mailchimp

/**
 * Agregar en wordpress child theme functions.php
 *  */  
    function rudr_mailchimp_subscriber_status( $email, $status, $list_id, $api_key, $merge_fields = array('NAME'=> '', 'COMPANY'=> '', 'MESSAGE'=> '') ){
    $data = array(
         'apikey'        => $api_key,
         'email_address' => $email,
         'status'        => $status,
         'merge_fields'  => $merge_fields
    );
 $mch_api = curl_init(); // initialize cURL connection

    curl_setopt($mch_api, CURLOPT_URL, 'https://' . substr($api_key,strpos($api_key,'-')+1) . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($data['email_address'])));
    curl_setopt($mch_api, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic '.base64_encode( 'user:'.$api_key )));
    curl_setopt($mch_api, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
    curl_setopt($mch_api, CURLOPT_RETURNTRANSFER, true); // return the API response
    curl_setopt($mch_api, CURLOPT_CUSTOMREQUEST, 'PUT'); // method PUT
    curl_setopt($mch_api, CURLOPT_TIMEOUT, 10);
    curl_setopt($mch_api, CURLOPT_POST, true);
    curl_setopt($mch_api, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($mch_api, CURLOPT_POSTFIELDS, json_encode($data) ); // send data in json

    $result = curl_exec($mch_api);
      return $result;
    }
