<?php
/*
Plugin Name:Circolari
Plugin URI: http://www.sisviluppo.info
Description: Plugin che implementa le seguenti funzionalità per la gestione della scuola
Version:0.01
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
include_once(Circolari_DIR."/circolari.widget.php");
$msg="";

switch ($_REQUEST["op"]){
	case "Firma":
		global $msg;
		$msg=FirmaCircolare($_REQUEST["pid"],-1);
		break;
	case "Adesione":
		global $msg;
		$msg=FirmaCircolare($_REQUEST["pid"],$_REQUEST["scelta"]);
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
add_action( 'contextual_help', 'Help_Circolari', 10, 3 );
add_action( 'wp_before_admin_bar_render', 'circolari_admin_bar_render' );
add_action( 'admin_menu', 'add_circolari_menu_bubble' );
register_uninstall_hook(__FILE__,  'circolari_uninstall' );
register_activation_hook( __FILE__,  'circolari_activate');
//register_deactivation_hook( __FILE__, 'deactivate') );	

function circolari_activate() {
	global $wpdb;
	if(get_option('Circolari_Visibilita_Pubblica')== ''||!get_option('Circolari_Visibilita_Pubblica')){
		add_option('Circolari_Visibilita_Pubblica', '0');
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
  global $menu;
  $NumCircolari=GetNumCircolariDaFirmare("N");
  if ($NumCircolari==0)
	return;
  $i=0;
  foreach($menu as $m){
  	if ($menu[$i][0]=="Circolari"){
		$menu[$i][0] .= "<span class='update-plugins count-1'><span class='update-count'>$NumCircolari</span></span>";
		return;
	}
	$i++;
 }
}

function circolari_add_menu(){
   add_submenu_page( 'edit.php?post_type=circolari', 'Parametri',  'Parametri', 'manage_options', 'circolari', 'circolari_MenuPagine');
    add_submenu_page( 'edit.php?post_type=circolari', 'Firma',  'Firma', 'read', 'Firma', 'circolari_GestioneFirme');
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
	global $wpdb;
	$DatiPost=get_post( $IDPost,  ARRAY_A);
	$DatiPost["ID"]= 0; 
	$DatiPost["post_type"]= "newsletter"; 
	//$IDNewPost=wp_insert_post( $DatiPost );
	$PostsAllegati = get_posts(array('post_parent' => '783'));
	print_r($PostsAllegati);
	exit;
	foreach($PostsAllegati as $PostsAllegato){
		$PostsAllegato["ID"]= 0; 
		$PostsAllegato["post_parent"]= 800;//$IDNewPost;
		$IDNewPostAllegato=wp_insert_post( $PostsAllegato );
	}
	
	echo "ID del nuovo Post ".$IDNewPost;
}
function circolari_Parametri(){
	
	$DestTutti  =  get_option('Circolari_Visibilita_Pubblica');
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
		header('Location: '.get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=circolari'); 
	}
}

// Nuova Colonna Gestione  
function circolari_NuoveColonne($defaults) {  
	if ($_GET['post_type']=="circolari"){
		$defaults['numero'] = 'Numero';  
//	    $defaults['destinatari'] = 'Destinatari';
		$defaults['firme'] = 'Firme';    
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
	            echo $Linkfirma.'  
<a href="'.admin_url().'edit.php?post_type=circolari&page=circolari&op=email&post_id='.$post_ID.'">Invia per eMail</a>';  
	     } 
		 if ($column_name == 'numero'){
		 	$numero=get_post_meta($post_ID, "_numero");
			$anno=get_post_meta($post_ID, "_anno");
			echo $numero[0].'/'.$anno[0];
		 }
		 if ($column_name == 'firme'){
		 	$dest=wp_get_post_terms( $post_ID, 'gruppiutenti', array("fields" => "ids") ); 
		 	$NU=0;
			foreach($dest as $IdGruppo)
				$NU+=Get_User_Per_Gruppo($IdGruppo);
			echo Get_Numero_Firme_Per_Circolare($post_ID)."/$NU";			
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
   'not_found_in_trash' => __( 'Nessuna Circoalre trovata nel cestino' ),
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
	global $wp_admin_bar;
	$NumCircolari=GetNumCircolariDaFirmare("N");
	if ($NumCircolari>0)
		$VisNumCircolari=' <span style="background-color:red;">&nbsp;'.$NumCircolari.'&nbsp;</span>';
	else
		$VisNumCircolari="";
	$wp_admin_bar->add_menu( array(
		'id' => 'fc', // link ID, defaults to a sanitized title value
		'title' => 'Circolari '.$VisNumCircolari, // link title
		'href' => 'edit.php?post_type=circolari&page=Firma', // name of file
		'meta' => array(  'title' => 'Circolari da Firmare' )));
}

function Help_Circolari( $contextual_help, $screen_id, $screen ) { 
	if ( !(stripos($screen->id,'circolari' )===FALSE)) {

		$contextual_help = '<h2>Products</h2>
		<p>Products show the details of the items that we sell on the website. You can see a list of them on this page in reverse chronological order - the latest one we added is first.</p> 
		<p>You can view/edit the details of each product by clicking on its name, or you can perform bulk actions using the dropdown menu and selecting multiple items.</p>';

	} elseif ( 'edit-product' == $screen->id ) {

		$contextual_help = '<h2>Editing products</h2>
		<p>This page allows you to view/modify product details. Please make sure to fill out the available boxes with the appropriate details (product image, price, brand) and <strong>not</strong> add these details to the product description.</p>';

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
			$Circolari=get_option('Pasw_Comunicazioni');
			wp_set_post_categories( $post_id, array($Circolari) );
			$term_list = wp_get_post_terms($post_id, 'gruppiutenti', array("fields" => "names"));
			if (count($term_list)==0) {
				$DestTutti=get_option('Circolari_Visibilita_Pubblica');
				wp_set_object_terms( $post_id, (int)$DestTutti,"gruppiutenti",FALSE );
			}
			update_post_meta( $post_id, '_numero', $_POST["numero"]);
			update_post_meta( $post_id, '_anno', $_POST["anno"]);
			update_post_meta( $post_id, '_firma', $_POST["firma"]);
			update_post_meta( $post_id, '_sciopero', $_POST["sciopero"]);

		}
}
function circolari_crea_box(){
  add_meta_box('prog', 'Progressivo', 'circolari_crea_box_progressivo', 'circolari', 'advanced', 'high');
  add_meta_box('firma', 'Richiesta Firma', 'circolari_crea_box_firma', 'circolari', 'advanced', 'high');
  add_meta_box('sciopero', 'Circolare comunicazione Sciopero', 'circolari_crea_box_firma_sciopero', 'circolari', 'advanced', 'high');
}

function NewNumCircolare(){
	global $wpdb,$table_prefix;

	$Sql='SELECT wp_posts.ID
			FROM '.$wpdb->posts. '
			INNER JOIN '.$wpdb->postmeta. ' ON '.$wpdb->posts. '.ID = '.$wpdb->postmeta. '.post_id
			WHERE post_type = %s 
		     AND post_status = %s 
			 AND meta_key = %s 
			 AND meta_value = %d;';
//echo $wpdb->prepare($Sql,"circolari","publish","_anno",2013);
	$ris=$wpdb->get_results($wpdb->prepare($Sql,"circolari","publish","_anno",2013),'ARRAY_N');
	$p_ids=array();
	foreach($ris as $r){
		$p_ids[]=$r[0];
	}
	$psel=implode(",",$p_ids);
	$Sql='SELECT max(meta_value * 1)
		 	FROM '.$wpdb->posts. '
			INNER JOIN '.$wpdb->postmeta. ' ON '.$wpdb->posts. '.ID = '.$wpdb->postmeta. '.post_id
			WHERE ID in ('.$psel.') and meta_key="_numero";';
//echo $Sql;
	return $wpdb->get_var($Sql)+1;
}
function circolari_crea_box_progressivo( $post ){
$numero=get_post_meta($post->ID, "_numero");
$anno=get_post_meta($post->ID, "_anno");
$anno=$anno[0];
$numero=$numero[0];
if ($anno=="" or !$anno)
	$anno=date("Y");
if ($numero=="" or !$numero)
	$numero=NewNumCircolare();
echo '<label>Numero/Anno</label>
	<input type="text" name="numero" value="'.$numero.'" size="10" style="text-align:right"/>/ <input type="text" name="anno" value="'.$anno.'" size="4"/>' ;
}

function circolari_crea_box_firma( $post ){
$firma=get_post_meta($post->ID, "_firma");
if($firma[0]=="Si")
	$firma='checked="checked"';
 echo "<label>E' richiesta la firma alla circolare</label>
	<input type='checkbox' name='firma' value='Si' $firma />" ;
}
function circolari_crea_box_firma_sciopero( $post ){
$sciopero=get_post_meta($post->ID, "_sciopero");
if($sciopero[0]=="Si")
	$sciopero='checked="checked"';
 echo "<label>La circolare si riferisce ad uno sciopero, bisogna indirare l'adesione o la presa visione</label>
	<input type='checkbox' name='sciopero' value='Si' $sciopero />" ;
}

function circolari_VisualizzaFirme($post_id,$Tipo=0){
global $GestioneScuola;
$numero=get_post_meta($post_id, "_numero");
$anno=get_post_meta($post_id, "_anno");
$circolare=get_post($post_id);
// Inizio interfaccia
echo' 
<div class="wrap">
	      <img src="'.Circolari_URL.'/img/atti32.png" alt="Icona Atti" style="display:inline;float:left;margin-top:10px;"/>
		  
<h2 style="margin-left:40px;">Circolare n°'.$numero[0].'/'.$anno[0].'<br /><strong>'.$circolare->post_title.'</strong></h2>
<div id="col-container">
	<div class="col-wrap">';
$globale=get_post_meta($post_id, '_visibilita_generale');
$fgs = wp_get_object_terms($post_id, 'gruppiutenti');
if(!empty($fgs)){
	foreach($fgs as $fg){
		$Elenco.="<em>".$fg->name."</em> - ";
	}
}
echo'	
<div class="col-wrap">
		<h3>Visibilit&aacute;</h3>
			<p>'.$Elenco.'</p>
</div><!-- /col-wrap -->';
$utenti=Get_Users_per_Circolare($post_id);
if ($Tipo==1)
	$sottrai=1;
else	
	$sottrai=0;
echo '
<div style="width:90%;margin-top:20px;">
	<table class="widefat">
		<caption>Elenco Firme</caption>
		<thead>
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
		</thead>
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