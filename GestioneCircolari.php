<?php
/*
Plugin Name:Gestione Circolari
Plugin URI: http://www.sisviluppo.info
Description: Plugin che implementa la gestione delle circolari scolastiche
Version:2.3
Author: Scimone Ignazio
Author URI: http://www.sisviluppo.info
License: GPL2
    Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : info@sisviluppo.info)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { 
  die('You are not allowed to call this page directly.'); 
}

define("Circolari_URL",plugin_dir_url(dirname (__FILE__).'/circolari.php'));
define("Circolari_DIR",dirname (__FILE__));
global $wpdb,$table_prefix;
$wpdb->table_firme_circolari = $table_prefix . "firme_circolari";
include_once(Circolari_DIR."/admin/gruppi.php");
include_once(Circolari_DIR."/admin/firme.php");
include_once(Circolari_DIR."/functions.inc.php");
include_once(Circolari_DIR."/GestioneCircolari.widget.php");
include_once(Circolari_DIR."/GestioneNavigazioneCircolari.widget.php");
$msg="";
require_once(ABSPATH . 'wp-includes/pluggable.php'); 

switch ($_REQUEST["op"]){
	case "Firma":
		global $msg;
		$msg=FirmaCircolare($_REQUEST["pid"],-1,$_REQUEST["dest"]);
		break;
	case "Adesione":
		global $msg;
		$msg=FirmaCircolare($_REQUEST["pid"],$_REQUEST["scelta"],$_REQUEST["dest"]);
		break;	
}

if ($_GET['update'] == 'true')
	$stato="<div id='setting-error-settings_updated' class='updated settings-error'> 
			<p><strong>Impostazioni salvate.</strong></p></div>";
add_action('init', 'crea_custom_circolari');
add_filter( 'post_updated_messages', 'circolari_updated_messages');
add_action( 'save_post', 'circolari_salva_dettagli');
add_action('add_meta_boxes','circolari_crea_box');
add_filter('manage_posts_columns', 'circolari_NuoveColonne');  
add_action('manage_posts_custom_column', 'circolari_NuoveColonneContenuto', 10, 2); 
add_action( 'admin_menu', 'circolari_add_menu' ); 
add_action('init', 'update_Impostazioni_Circolari');
add_action('publish_circolari','AC_OnPublishPost' );
//add_action( 'contextual_help', 'Help_Circolari', 10, 3 );
/**
* *************************************************
* 
* Disattivate le notifiche per rallentamento BackEnd
*/
//add_action( 'wp_before_admin_bar_render', 'circolari_admin_bar_render' );
add_action( 'admin_menu', 'add_circolari_menu_bubble' );
/**
* *************************************************
*/
register_uninstall_hook(__FILE__,  'circolari_uninstall' );
register_activation_hook( __FILE__,  'circolari_activate');
add_filter( 'the_content', 'vis_firma');
add_shortcode('VisCircolari', 'VisualizzaCircolari');
add_shortcode('VisCircolariHome', 'VisualizzaCircolariHome');
add_action('wp_head', 'TestataCircolari' );
add_action( 'admin_enqueue_scripts',  'Circoalri_Admin_Enqueue_Scripts' );

add_action('do_feed', 'circolari_disable_feed', 1);
add_action('do_feed_rdf', 'circolari_disable_feed', 1);
add_action('do_feed_rss', 'circolari_disable_feed', 1);
add_action('do_feed_rss2', 'circolari_disable_feed', 1);
add_action('do_feed_atom', 'circolari_disable_feed', 1);
 
function circolari_disable_feed() {
	if ( get_post_type()=='circolari') {
        wp_die( 'Non ci sono feed disponibili per le Circolari, per visualizzarle naviga la  <a href="'. get_bloginfo('url') .'">Home</a> del sito!');
    }
}
/*register_deactivation_hook( __FILE__, 'deactivate') );	

function my_posts_request_filter( $input ) {
	print_r( $input );
	return $input;
}
add_filter( 'posts_request', 'my_posts_request_filter' );

function posts_where( $where ) {
	echo $where;exit;
	$PT=substr($where,strpos($where,"post_type"),strlen($where));
	$PT=substr($PT,strpos($PT,"'"),strlen($where));
//	echo "<br />1 ".$PT;
	$PT=substr($PT,1,strpos($PT,"'",1));
//	echo "<br />2 ".$PT;
	$PT=str_replace("'", "", $PT);
//	echo "<br />3 ".$PT;
	//$where .= " AND post_type in ('circolari','post')";
	return $where;
}

add_filter( 'posts_where' , 'posts_where' );
*/
function Circoalri_Admin_Enqueue_Scripts( $hook_suffix ) {
	wp_enqueue_script('jquery');
	wp_enqueue_script( 'jquery-ui-datepicker', '', array('jquery'));
	wp_enqueue_script( 'Circolari-admin', plugins_url('js/Circolari.js', __FILE__ ), array(), null);
	wp_enqueue_style( 'jquery.ui.theme', plugins_url( 'css/jquery-ui-custom.css', __FILE__ ) );

	wp_enqueue_script( 'Circolari-DataTable', plugins_url('js/jquery.dataTables.js', __FILE__ ));
	wp_enqueue_script( 'Circolari-DataTable-Tools', plugins_url('js/dataTables.tableTools.js', __FILE__ ));

	wp_enqueue_style( 'Circolari-DataTable-theme', plugins_url( 'css/jquery.dataTables.css', __FILE__ ) );
	wp_enqueue_style( 'Circolari-DataTable-theme-Tools', plugins_url( 'css/dataTables.tableTools.css', __FILE__ ) );
}

function search_filter($query) {

if (get_post_type()=='newsletter' ) {
    	      $query->set('post_type', array( 'post', 'circolari' ) );
}
	return $query;
}

add_action('pre_get_posts','search_filter');

function VisualizzaCircolari(){
	require_once ( dirname (__FILE__) . '/admin/frontend.php' );
	return $ret;
}

function VisualizzaCircolariHome(){
require_once ( dirname (__FILE__) . '/admin/frontendhome.php' );
return $ret;
}
      
function vis_firma( $content ){
	$PostID= get_the_ID();
	if (post_password_required( $PostID ))
		return $content;

	$Campo_Firma="";
	if (get_post_type( $PostID) !="circolari")
		return $content;
	if (!is_user_logged_in())
		return $content;
	if (!Is_Circolare_Da_Firmare($PostID) or !Is_Circolare_per_User($PostID))
		return $content;
	if (strlen(stristr($_SERVER["HTTP_REFERER"],"wp-admin/edit.php?post_type=circolari&page=Firma"))>0)
		return "<br />
		<button style=' outline: none;
 cursor: pointer;
 text-align: center;
 text-decoration: none;
 font: bold 12px Arial, Helvetica, sans-serif;
 color: #fff;
 padding: 10px 20px;
 border: solid 1px #0076a3;
 background: #0095cd;' onclick='javascript:history.back()'>Torna alla Firma</button>".$content;
	else{
		$Adesione=get_post_meta($PostID, "_sciopero");
		if (Is_Circolare_per_User($PostID)){
			$TipoCircolare="Circolare";
			$Campo_Firma_Adesione="";
			if ($Adesione[0]=="Si"){			
				$TipoCircolare="Circolare con Adesione";
				switch (get_Circolare_Adesione($PostID)){
				case 1:
					$Campo_Firma_Adesione=": adesione Si";
					break;
				case 2:
					$Campo_Firma_Adesione=": adesione No";		
					break;
				case 3:
					$Campo_Firma_Adesione=": adesione Presa Visione";				
					break;
				}
			}	
			$firma=get_post_meta($PostID, "_firma");
			$BaseUrl=admin_url()."edit.php";
			if($firma[0]=="Si" or $Adesione[0]=="Si"){
				if (Is_Circolare_Firmata($PostID)){
					$Campo_Firma="Firmata".$Campo_Firma_Adesione;
				}
				else{
					if ($Adesione[0]=="Si"){			
					$Campo_Firma='<form action=""  method="get" style="display:inline;">
						<input type="hidden" name="op" value="Adesione" />
						<input type="hidden" name="pid" value="'.$PostID.'" />
						<input type="radio" name="scelta" class="s1-'.$PostID.'" value="1"/>Si 
						<input type="radio" name="scelta" class="s2-'.$PostID.'" value="2"/>No 
						<input type="radio" name="scelta" class="s3-'.$PostID.'" value="3" checked="checked"/>Presa Visione
						<input type="submit" name="inviaadesione" class="button inviaadesione" id="'.$PostID.'" value="Firma" rel="'.get_the_title($PostID).'"/>
					</form>';

					}else
						$Campo_Firma='<a href="?op=Firma&pid='.$PostID.'">Firma Circolare</a>';
				}
				$dati_firma=get_Firma_Circolare($PostID);
			}	
		}
		return $content." <br /><div style='border: solid 1px #0076a3; background: #c6d7f2;padding: 5px;'>".$Campo_Firma."</div>";
	}
}
function circolari_activate() {
	global $wpdb;
	if(get_option('Circolari_Visibilita_Pubblica')== ''||!get_option('Circolari_Visibilita_Pubblica')){
		add_option('Circolari_Visibilita_Pubblica', '0');
	}
	if(get_option('Circolari_Categoria')== ''||!get_option('Circolari_Categoria')){
		add_option('Circolari_Categoria', '0');
	}
	if(get_option('Circolari_GGScadenza')== ''||!get_option('Circolari_GGScadenza')){
		add_option('Circolari_GGScadenza', '30');
	}
	if(get_option('Circolari_NrCircHome')== ''||!get_option('Circolari_NrCircHome')){
		add_option('Circolari_NrCircHome', '0');
	}
	$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->table_firme_circolari." (
  			post_ID  bigint(20) NOT NULL,
  			user_ID bigint(20) NOT NULL,
  			datafirma timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  			ip varchar(16) DEFAULT NULL,
			adesione smallint(6) NOT NULL DEFAULT '-1',
  			PRIMARY KEY (post_ID,user_ID));";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
	
function add_circolari_menu_bubble() {
  global $menu,$DaFirmare;
//	$NumCircolari=GetCircolariDaFirmare("N");
  $DaFirmare=GetCircolariDaFirmare("N");
  if ($DaFirmare==0)
	return;
  $i=0;
  foreach($menu as $m){
  	if ($menu[$i][0]=="Circolari"){
		$menu[$i][0] .= "<span class='update-plugins count-1'><span class='update-count'>$DaFirmare</span></span>";
		return;
	}
	$i++;
 }
}

function circolari_add_menu(){
   add_submenu_page( 'edit.php?post_type=circolari', 'Parametri',  'Parametri', 'edit_published_posts', 'circolari', 'circolari_MenuPagine');
   $pageFirma=add_submenu_page( 'edit.php?post_type=circolari', 'Firma',  'Firma', 'read', 'Firma', 'circolari_GestioneFirme');
   add_action( 'admin_head-'. $pageFirma, 'TestataCircolari' );
   $pageFirmate=add_submenu_page( 'edit.php?post_type=circolari', 'Firmate',  'Firmate', 'read', 'Firmate', 'circolari_VisualizzaFirmate');
   add_action( 'admin_head-'. $pageFirmate, 'TestataCircolari' );
   $pagenFirmate=add_submenu_page( 'edit.php?post_type=circolari', 'Scadute e non Firmate', 'Scadute e non Firmate', 'read', 'non_Firmate', 'circolari_VisualizzaNonFirmate');
   add_action( 'admin_head-'. $pagenFirmate, 'TestataCircolari' );
   $utility=add_submenu_page( 'edit.php?post_type=circolari', 'Utility',  'Utility', 'edit_published_posts', 'Utility', 'circolari_Utility');
   add_action( 'admin_head-'. $utility, 'TestataCircolari' );
}

function circolari_Utility($Stato=""){
echo '<div class="wrap">
	<img src="'.Circolari_URL.'/img/utility.png" alt="Icona Permessi" style="display:inline;float:left;margin-top:5px;"/>
		<h2 style="margin-left:40px;">Utility Circolari</h2>';
if ($Stato!="") 
	echo '<div id="message" class="updated"><p>'.str_replace("%%br%%","<br />",$Stato).'</p></div>
      <meta http-equiv="refresh" content="5;url=admin.php?page=utility"/>';
echo '		</div> 
		<p></p>
		<div class="widefat" style="padding:10px;">
				<p style="text-align:center;font-size:1.5em;font-weight: bold;">
				Verifica procedura
				</p>
				<p style="text-align:left;font-size:1em;font-style: italic;">
Questa procedura esegue un test generale della procedura e riporta eventuali anomalie nei dati e nelle impostazioni.</spam><br />Operazioni eseguite:</p>
<p style="font-size:1em;font-style: italic;margin-left:10px;font-weight: bold;">
Verifica data scadenza firma <spam style="text-align:center;font-size:1.5em;font-weight: bold;"> <a href="edit.php?post_type=circolari&page=Utility&action=versca">Verifica</a></spam>
</p>';
		$lista="";
		if (isset($_GET['action']) && $_GET['action']=="versca"){
			$GGscadenza=get_option("Circolari_GGScadenza");
			$Posts = get_posts('post_type=circolari&numberposts=-1');
			foreach($Posts as $post){
				$Adesione=get_post_meta($post->ID, "_sciopero",TRUE);
				$firma=get_post_meta($post->ID, "_firma",TRUE);
				$scadenza=get_post_meta($post->ID, "_scadenza",TRUE);
				if ((!empty($Adesione) or !empty($firma)) and empty($scadenza)){
	 				$lista.="			<li>$post->ID $post->post_title $post->post_date_gmt";
	 				if (isset($_GET['opt']) && $_GET['opt']=="aggsca"){
	 					$Scadenza=date('Y-m-d', strtotime(substr($post->post_date_gmt,0,10). " + $GGscadenza days"));
	 					if (update_post_meta($post->ID,"_scadenza",$Scadenza ))
	 						$lista.= ' <img src="'.Circolari_URL.'/img/verificato.png" alt="Icona Permessi" style="display:inline;margin-left:5px;"/>';
	 				}else
	 				$lista.=" la data verrà aggiornata a:".date('Y-m-d', strtotime(substr($post->post_date_gmt,0,10). " + $GGscadenza days"));
				$lista.= "</li>";					
				}
			}
			if (!empty($lista)){
				echo "<h4>Circolari da firmare senza data di scadenza</h4>
		<ul>
				$lista
		</ul>";
		if (isset($_GET['opt']) && $_GET['opt']=="aggsca")
			echo "Aggiornamento effettuato";
		else
			echo '<p style="text-align:left;font-size:1em;font-style: italic;">Aggiorna la Data entro cui firmare = aggiunngendo '.$GGscadenza.' giorni alla data di pubblicazione <spam style="text-align:center;font-size:1.5em;font-weight: bold;"> <a href="edit.php?post_type=circolari&page=Utility&action=versca&opt=aggsca">Aggiorna</a></spam>
</p>
</div>';			
		}else
			echo "<h4>Tutte le circolari da firmare hanno la data di scadenza</h4>";
	}
}
function TestataCircolari() {
?>
<script type='text/javascript'>
jQuery.noConflict();
(function($) {
	$(function() {
		$('.inviaadesione').click(function(){
			switch ($("input[type=radio][name=scelta]:checked").val()){
					case "1":
						s="Si";
						break;
					case "2":
						s="No";
						break;
					case "3":
						s="Presa Visione";
						break;
				}
			var answer = confirm("Circolare "+$(this).attr('rel') +"\nConfermi la scelta:\n\n   " + s +"\n\nAllo sciopero?")
			if (answer){
				return true;
			}
			else{
				return false;
			}					
		});
 });
})(jQuery);
</script>	
<?php
}

function circolari_MenuPagine(){
	switch ($_REQUEST["op"]){
		case "Firme":
			circolari_VisualizzaFirme($_REQUEST["post_id"]);
			break;
		case "Adesioni":
			circolari_VisualizzaFirme($_REQUEST["post_id"],1);
			break;
		case "email":
			circolari_SpostainNewsletter($_REQUEST["post_id"]);
			break;
		default:
			circolari_Parametri();	
	}
}
function circolari_uninstall() {
	global $wpdb;
// Eliminazione Tabelle data Base
	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->table_firme_circolari);
	$Circolari = get_posts( "post_type=circolari" );
	foreach ( $Circolari as $Circolare )
		set_post_type( $Circolare );	
}

function circolari_SpostainNewsletter($IDPost){
$DatiPost=get_post( $IDPost,  ARRAY_A);
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => null,
			'post_status' => null,
			'post_parent' => $IDPost); 
		$attachments = get_posts($args);
		$LinkAllegati="";
		if ($attachments) {
			$LinkAllegati.="<p>Allegati
			<ul>";
			foreach ($attachments as $attachment) {
				$LinkAllegati.="		<li><a href='$attachment->guid'>$attachment->post_title</a></li>";
			}
			$LinkAllegati.="</p>
			</ul>";	
		}
$my_post = array(
  		'post_title'    => $DatiPost['post_title'],
  		'post_content'  => "<p>Ciao [USER-NAME]</p>
<p>in data odierna è stata inserita la seguente circolare nel sito [SITE-NAME]</p>
<p>[POST-EXCERPT]</p>
<p>[POST-CONTENT]</p>".$LinkAllegati,
  		'post_status'   => 'publish',
  		'comment_status'   => 'closed',
  		'ping_status' => 'closed',
  		'post_author' => $DatiPost['post_author'],
  		'post_name' => $DatiPost['post_name'],
  		'post_type' => 'newsletter');
$post_id =wp_insert_post( $my_post,$errore );
echo '<div class="wrap">
	  	<img src="'.Circolari_URL.'/img/mail.png" alt="Icona Send email" style="display:inline;float:left;margin-top:10px;"/>
	  	<h2 style="margin-left:40px;">Crea NewsLetter 
	  	<a href="'.home_url().'/wp-admin/edit.php?post_type=circolari" class="add-new-h2 tornaindietro">Torna indietro</a></h2>';

	if($post_id>0){
		$recipients=Array();
		$recipients['list'][] = 1;
		$recipients['list'][] = 2;
		add_post_meta ( $post_id, "_easymail_recipients", $recipients );	
		add_post_meta ( $post_id, "_placeholder_easymail_post",  $IDPost);	
		add_post_meta ( $post_id, "_placeholder_post_imgsize", 'thumbnail' );	
		add_post_meta ( $post_id, "_placeholder_newsletter_imgsize", 'thumbnail' );	
		add_post_meta ( $post_id, "_easymail_theme", 'campaignmonitor_elegant.html' );	
		echo "<p style='font-weight: bold;font-size: medium;color:green;'>NewsLetter Creata correttamente</p> 
		<p style='font-weight: bold;font-style: italic;font-size: medium;'>Adesso dovete completare le operazioni di invio seguendo pochi e semplici passi:<ul style='list-style: circle outside;margin-left:20px;'>
			<li>Selezionare la gestione delle NewsLetter</li>
			<li>Entrare in modifica nella circolare appena creata (l'ultima, quella in cima alla lista)</li>
			<li>Selezionate i destinatari</li>
			<li>Memorizzare le modifiche</li>
			<li>Dall'elenco delle NewsLetter, sulla riga corrente cliccare su <em>Richiesto: Crea la lista dei destinatari</em></li>
		</ul>
		</p>";
		add_post_meta ( $IDPost, "_sendNewsLetter",date("d/m/y g:i O"));
	}else{
		echo "<p  style='font-weight: bold;font-size: medium;color:red;'>NewsLetter Non Creata correttamente, errore riportato:</p>";
				print_r($errore);			
	}
}
function circolari_Parametri(){
	$DestTutti  =  get_option('Circolari_Visibilita_Pubblica');
	$GiorniScadenza  =  get_option('Circolari_GGScadenza');
	$NrCircolariHome = get_option('Circolari_NrCircHome');
echo'
<div class="wrap">
	  	<img src="'.Circolari_URL.'/img/opzioni32.png" alt="Icona configurazione" style="display:inline;float:left;margin-top:10px;"/>
	  	<h2 style="margin-left:40px;">Configurazione Circolari</h2>
	  <form name="Circolari_cnf" action="'.get_bloginfo('wpurl').'/wp-admin/index.php" method="post">
	  <table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="pubblica">Gruppo Pubblico Circolari</label></th>
			<td><select name="pubblica" id="pubblica" >';
			$bloggroups =get_terms('gruppiutenti',array('orderby'=> 'name','hide_empty'=> false));
			foreach ($bloggroups as $gruppo) {
		        echo '<option value="'.$gruppo->term_id.'" ';
				//$e.= "Memorizzato ".$DestTutti." Id ".$gruppo->term_id."<br />";
				if($DestTutti==$gruppo->term_id) 
					echo 'selected="selected"';
				echo '>'.$gruppo->name.'</option>';	
			}
echo'</select></td>				
		</tr>
		<tr valign="top">
			<th scope="row"><label for="categoria">Categoria Circolari</label></th>
			<td>';
			wp_dropdown_categories('orderby=name&hide_empty=0&name=Categoria&id=categoria&selected='.get_option('Circolari_Categoria'));
echo'			</td>				
		</tr>
		<tr valign="top">
			<th scope="row"><label for="GGScadenza">N. giorni entro cui firmare di default</label></th>
			<td>
				<input type="text" name="GGScadenza" id="GGScadenza" size="3" maxlength="3" value="'.$GiorniScadenza.'" />
			</td>				
		</tr>
		<tr valign="top">
			<th scope="row"><label for="NrCircHome">N. di circolari da visionare in home</label></th>
			<td>
				<input type="text" name="NrCircHome" id="NrCircHome" size="3" maxlength="3" value="'.$NrCircolariHome.'" />
			</td>				
		</tr>
	</table>
	    <p class="submit">
	        <input type="submit" name="Circolari_submit_button" value="Salva Modifiche" />
	    </p> 
	    </form>
	    </div>';
}

function update_Impostazioni_Circolari(){
    if($_POST['Circolari_submit_button'] == 'Salva Modifiche'){
	    update_option('Circolari_Visibilita_Pubblica',$_POST['pubblica'] );
	    update_option('Circolari_Categoria',$_POST['Categoria'] );
	    update_option('Circolari_GGScadenza',$_POST['GGScadenza'] );
		update_option('Circolari_NrCircHome',$_POST['NrCircHome'] );  		
		header('Location: '.get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=circolari'); 
	}
}

// Nuova Colonna Gestione  
function circolari_NuoveColonne($defaults) {  
	if ($_GET['post_type']=="circolari"){
		$defaults['numero'] = 'Numero';  
//	    $defaults['destinatari'] = 'Destinatari';
		$defaults['firme'] = 'Firme';    
		$defaults['scadenza'] = 'Firmare entro'; 
	    $defaults['gestionecircolari'] = 'Gestione';  
	}
   return $defaults;  
}  
  
// Visualizzazione nuova colonna Gestione  
function circolari_NuoveColonneContenuto($column_name, $post_ID) {  
	global $wpdb;
 	if ($_GET['post_type']=="circolari"){
		$firma=get_post_meta($post_ID, "_firma");
		$sciopero=get_post_meta($post_ID, "_sciopero");
		$Linkfirma="";
	    if ($firma[0]=="Si" )
			$Linkfirma='<a href="'.admin_url().'edit.php?post_type=circolari&page=circolari&op=Firme&post_id='.$post_ID.'">Firme</a> |';
		if($sciopero[0]=="Si")
			$Linkfirma='<a href="'.admin_url().'edit.php?post_type=circolari&page=circolari&op=Adesioni&post_id='.$post_ID.'">Adesioni</a> |';		
		if ($column_name == 'gestionecircolari') {  
	    	if ( defined( 'ALO_EM_INTERVAL_MIN' ) ){
				$DataInvio = get_post_meta( $post_ID, "_sendNewsLetter", true); 
	    		if ($DataInvio){
					$res=$wpdb->get_results("SELECT post_id FROM $wpdb->postmeta Where meta_value=$post_ID And meta_key='_placeholder_easymail_post';");
					$Linkfirma.="Inviata in data ". $DataInvio.' <a href="'.admin_url().'post.php?post='.$res[0]->post_id.'&action=edit">Modifica NewsLetter</a>';
				}else
	            	$Linkfirma.='<a href="'.admin_url().'edit.php?post_type=circolari&page=circolari&op=email&post_id='.$post_ID.'">Invia per eMail</a>';  
			}
			echo $Linkfirma;
	     } 
		 if ($column_name == 'numero'){
		 	$numero=get_post_meta($post_ID, "_numero",TRUE);
			$anno=get_post_meta($post_ID, "_anno",TRUE);
			echo $numero.'_'.$anno;
		 }
		 if ($column_name == 'scadenza'){
		 	$scadenza=FormatDataItalianoBreve(get_post_meta($post_ID, "_scadenza",TRUE));
			echo $scadenza;
		 }
		 if ($column_name == 'firme'){
		 	$dest=wp_get_post_terms( $post_ID, 'gruppiutenti', array("fields" => "ids") ); 
		 	$NU=0;
		 	$IdGruppoTutti=get_option('Circolari_Visibilita_Pubblica');
			if(in_array($IdGruppoTutti,$dest))
				echo Get_Numero_Firme_Per_Circolare($post_ID)."/".Get_User_Per_Gruppo($IdGruppoTutti);
			else{
				foreach($dest as $IdGruppo)
					if ($IdGruppoTutti!=$IdGruppo)
						$NU+=Get_User_Per_Gruppo($IdGruppo);
				echo Get_Numero_Firme_Per_Circolare($post_ID)."/$NU";			
			}
		}
	}
}  
function crea_custom_circolari() {

 register_post_type('circolari', array(
  'labels' => array(
   'name' => __( 'Circolari' ),
   'singular_name' => __( 'Circolare' ),
   'add_new' => __( 'Aggiungi Circolare' ),
   'add_new_item' => 'Aggiungi nuova Circolare',
   'edit' => __( 'Modifica' ),
   'edit_item' => __( 'Modifica Circolare' ),
   'new_item' => __( 'Nuova Circolare' ),
   'items_archive' => __( 'Circolare Aggiornata' ),
   'view' => __( 'Visualizza Circolare' ),
   'view_item' => __( 'Visualizza' ),
   'search_items' => __( 'Cerca Circolare' ),
   'not_found' => __( 'Nessuna Circolare trovata' ),
   'not_found_in_trash' => __( 'Nessuna Circolare trovata nel cestino' ),
   'parent' => __( 'Circolare superiore' )),
   'public' => true,
   'show_ui' => true,
   'show_in_admin_bar' => true,
   'menu_position' => 5,
   'capability_type' => 'post',
   'hierarchical' => false,
   'has_archive' => true,
   'menu_icon' => plugins_url( 'img/circolare.png', __FILE__ ),
//   'taxonomies' => array('category'),  
   'supports' => array('title', 'editor', 'author','excerpt')));
}
// add links/menus to the admin bar

function circolari_admin_bar_render() {
	global $wp_admin_bar,$DaFirmare;
	$DaFirmare=GetCircolariDaFirmare("N");
	if ($DaFirmare>0)
		$VisNumCircolari=' <span style="background-color:red;">&nbsp;'.$DaFirmare.'&nbsp;</span>';
	else
		$VisNumCircolari="";
	$wp_admin_bar->add_menu( array(
		'id' => 'pluginCircoalri', // link ID, defaults to a sanitized title value
		'title' => 'Circolari '.$VisNumCircolari, // link title
		'href' => site_url().'/wp-admin/edit.php?post_type=circolari&page=Firma', // name of file
		'meta' => array(  'title' => 'Circolari da Firmare' )));
}

function Help_Circolari( $contextual_help, $screen_id, $screen ) { 
	if ( !(stripos($screen->id,'circolari' )===FALSE)) {

		$contextual_help = '<h2>Prodotto</h2>';

	} elseif ( 'edit-product' == $screen->id ) {

		$contextual_help = '<h2>Modifica</h2>
';

	}
	return $contextual_help;
}

function circolari_updated_messages( $messages ) {
	global $post, $post_ID;
    $messages['circolari'] = array(
	0 => '', 
	1 => sprintf('Circolare aggiornata. <a href="%s">Visualizza Circolare</a>', esc_url( get_permalink($post_ID) ) ),
	2 => 'Circolare aggiornata',
/* translators: %s: date and time of the revision */
	3 => isset($_GET['circolari']) ? sprintf( 'Circolare ripristinata alla versione %s', wp_post_revision_title( (int) $_GET['circolari'], false ) ) : false,
	4 => sprintf( 'Circolare pubblicata. <a href="%s">Visualizza Circolare</a>', esc_url( get_permalink($post_ID) ) ),
	5 => 'Circolare memorizzata',
	6 => sprintf( 'Circolare inviata. <a target="_blank" href="%s">Anteprima Circolare</a>', esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	7 => sprintf( 'Circolare schedulata per: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Anteprima circolare</a>',date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
	8 => sprintf( 'Bozza Circolare aggiornata. <a target="_blank" href="%s">Anteprima Circolare</a>', esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
);
return $messages;
}
function circolari_salva_dettagli( $post_id ){
	global $wpdb,$table_prefix;
//	print_r($_POST);exit;
		if ( $_POST['post_type'] == 'circolari' ) {	
			$Circolari=get_option('Circolari_Categoria');
			wp_set_post_categories( $post_id, array($Circolari) );
			$term_list = wp_get_post_terms($post_id, 'gruppiutenti', array("fields" => "names"));
			if (count($term_list)==0) {
				$DestTutti=get_option('Circolari_Visibilita_Pubblica');
				wp_set_object_terms( $post_id, (int)$DestTutti,"gruppiutenti",FALSE );
			}
			if($_POST["firma"] Or $_POST["sciopero"])
				if ($_POST["scadenza"])
					update_post_meta( $post_id, '_scadenza', FormatDataDB($_POST["scadenza"]));
				else
					update_post_meta( $post_id, '_scadenza', FormatDataDB( $_POST["jj"]."/".$_POST["mm"]."/".$_POST["aa"],get_option('Circolari_GGScadenza')));
			else
				update_post_meta( $post_id, '_scadenza', "");
			update_post_meta( $post_id, '_numero', $_POST["numero"]);
			update_post_meta( $post_id, '_anno', $_POST["anno"]);
			update_post_meta( $post_id, '_firma', $_POST["firma"]);
			update_post_meta( $post_id, '_sciopero', $_POST["sciopero"]);
			update_post_meta( $post_id, '_visibilita', $_POST["visibilita"]);

		}
}
function circolari_crea_box(){
  add_meta_box('parametri', 'Parametri Circolari', 'circolari_crea_box_parametri', 'circolari', 'advanced', 'high');

/*  add_meta_box('prog', 'Progressivo', 'circolari_crea_box_progressivo', 'circolari', 'advanced', 'high');
  add_meta_box('firma', 'Richiesta Firma', 'circolari_crea_box_firma', 'circolari', 'advanced', 'high');
  add_meta_box('sciopero', 'Circolare comunicazione Sciopero', 'circolari_crea_box_firma_sciopero', 'circolari', 'advanced', 'high');
  add_meta_box('visibilita', 'Visibilit&agrave;', 'circolari_crea_box_visibilita', 'circolari', 'advanced', 'high');*/
}

function circolari_crea_box_parametri( $post ){
echo "<h4>Firmare entro</h4>";	
circolari_crea_box_data_scadenza($post);
echo "<h4>Progessivo</h4>";
circolari_crea_box_progressivo($post);
echo "<h4>Firme</h4>";
circolari_crea_box_firma($post);
circolari_crea_box_firma_sciopero($post);
echo "<h4>Visibilit&agrave;</h4>";
circolari_crea_box_visibilita($post);
}

function NewNumCircolare($numero){
	$args = array( 'numberposts' => '1','post_type'=> 'circolari','post_status' => 'publish','meta_key' => '_anno','meta_value' => $numero);
	$ultimo=wp_get_recent_posts($args);
	$ID=$ultimo[0]['ID'];
	$numero=get_post_meta($ID, "_numero");
	return $numero[0]+1;
}
function circolari_crea_box_progressivo( $post ){
$numero=get_post_meta($post->ID, "_numero");
$anno=get_post_meta($post->ID, "_anno");
$anno=$anno[0];
$numero=$numero[0];
if ($anno=="" or !$anno){	$anno=date("Y");	$canno=strval(date("y"))+1;	if (date("n")>8)		$anno=$anno."/".$canno;	else			$anno=($anno-1)."/".date("y");}
if ($numero=="" or !$numero)
	$numero=NewNumCircolare($anno);
echo '<label>Numero/Anno</label>
	<input type="text" name="numero" value="'.$numero.'" size="5" style="text-align:right"/>_<input type="text" name="anno" value="'.$anno.'" size="5"/>
	<br />' ;
}

function circolari_crea_box_visibilita( $post ){
$visibilita=get_post_meta($post->ID, "_visibilita");
if (count($visibilita)==0)
	$selp='checked="checked"';
else 
	if ($visibilita[0]=="p")
		$selp='checked="checked"';
	else	
		$seld='checked="checked"';
echo 'Pubblica <input type="radio" name="visibilita" value="p" '.$selp.'/><br />
Privata&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="visibilita" value="d" '.$seld.'/>';
//$term_list = wp_get_post_terms($post->ID, 'gruppiutenti', array("fields" => "names"));
//print_r($term_list);
}
function circolari_crea_box_firma( $post ){
$firma=get_post_meta($post->ID, "_firma");
if($firma[0]=="Si")
	$firma='checked="checked"';
 echo "<label>E' richiesta la firma</label>
	<input type='checkbox' name='firma' value='Si' $firma />
	<br />" ;
}
function circolari_crea_box_firma_sciopero( $post ){
$sciopero=get_post_meta($post->ID, "_sciopero");
if($sciopero[0]=="Si")
	$sciopero='checked="checked"';
 echo "<label>Circolare sindacale</label>
	<input type='checkbox' name='sciopero' value='Si' $sciopero />
	<br />" ;
}

function circolari_crea_box_data_scadenza( $post ){
$scadenza=FormatDataItalianoBreve(get_post_meta($post->ID, "_scadenza",TRUE));
echo "<label>Data</label> <input type='text' name='scadenza' value='".$scadenza."' size='8' style='text-align:left' id='DataScadenza'/>
	<br />" ;
}

function circolari_VisualizzaFirme($post_id,$Tipo=0){
	global $GestioneScuola;
	$numero=get_post_meta($post_id, "_numero");
	$anno=get_post_meta($post_id, "_anno");
	$circolare=get_post($post_id);
	// Inizio interfaccia
	echo' 
	<div class="wrap">
		<img src="'.Circolari_URL.'/img/firma24.png" alt="Icona Atti" style="display:inline;float:left;margin-top:10px;"/>  
		<h2 style="margin-left:40px;">Circolare n°'.$numero[0].'_'.$anno[0].'<br /><strong>'.$circolare->post_title.'</strong></h2>';
	$globale=get_post_meta($post_id, '_visibilita_generale');
	$fgs = wp_get_object_terms($post_id, 'gruppiutenti');
	if(!empty($fgs)){
		foreach($fgs as $fg){
			$Elenco.="<em>".$fg->name."</em> - ";
		}
		$Elenco=substr($Elenco,0,strlen($Elenco)-3);
	}
	echo'
		<div style="display:inline;">
			<img src="'.Circolari_URL.'img/destinatari.png" style="border:0;" alt="Icona destinatari"/>
		</div>
		<div style="display:inline;vertical-align:top;">
			<p style="font-style:italic;font-weight:bold;display:inline;margin-top:3px;">'.$Elenco.'</p>
		</div>	
	</div>
	';
	$utenti=Get_Users_per_Circolare($post_id);
	if ($Tipo==1)
		$sottrai=3;
	else	
		$sottrai=0;
	$NumUtentiFirme =count($utenti);
	echo '
	<div>
		<table  id="TabellaCircolari" class="widefat"  cellspacing="0" width="99%">
			<thead>
				<tr>
					<th style="width:'.(20-$sottrai).'%;">User login</th>
					<th style="width:'.(30-$sottrai).'%;" id="ColOrd" sorted="1">Cognome</th>
					<th style="width:'.(15-$sottrai).'%;">Gruppo</th>
					<th style="width:'.(15-$sottrai).'%;">Data Firma</th>';
	if ($Tipo==1)
		echo '
					<th style="width:12%;">Adesione</th>';				
	echo '
					<th>IP</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th style="width:'.(20-$sottrai).'%;">User login</th>
					<th style="width:'.(30-$sottrai).'%;">Cognome</th>
					<th style="width:'.(15-$sottrai).'%;">Gruppo</th>
					<th style="width:'.(15-$sottrai).'%;">Data Firma</th>';
	if ($Tipo==1)
		echo '
					<th style="width:12%;">Adesione</th>';				
	echo '
					<th>IP</th>
				</tr>
			</tfoot>
			<tboby>';
	foreach($utenti as $utente){
		$GruppoUtente=get_user_meta($utente->ID, "gruppo", true);
		$firma=get_Firma_Circolare($post_id,$utente->ID);
		$gruppiutenti=get_terms('gruppiutenti', array('hide_empty' => 0,'include'=>$GruppoUtente));
		echo '
					<tr>
						<td>'.$utente->user_login.'</td>
						<td>'.$utente->display_name.'</td>
						<td>'.$gruppiutenti[0]->name.'</td>
						<td>'.$firma->datafirma.'</td>';
		if ($Tipo==1){
			switch ($firma->adesione){
				case 1:
					$desad="Si";
					break;
				case 2:
					$desad="No";
					break;
				case 3:	
					$desad="Presa Visione";
					break;
				default:
					$desad="Non Firmata";
			}
			echo '
						<td>'.$desad.'</td>';
		}
		echo '
						<td>'.$firma->ip.'</td>
					</tr>';
	}
	echo'
				</tbody>
			</table>
	</div>
	';
}	

?>