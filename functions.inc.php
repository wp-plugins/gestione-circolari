<?php
/*
Plugin Name:Circolari
Plugin URI: http://www.sisviluppo.info
Description: Plugin che implementa le seguenti funzionalità per la gestione della scuola
	- Circolari
Version:0.1
Author: Scimone Ignazio
Author URI: http://www.sisviluppo.info
*/
 
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }


function GetNumeroCircolare($PostID){
	$numero=get_post_meta($PostID, "_numero");
	$numero=$numero[0];
	$anno=get_post_meta($PostID, "_anno");
	$anno=$anno[0];
	$NumeroCircolare=$numero.'/'.$anno ;
return $NumeroCircolare;
}

function Is_Circolare_Firmata($IDCircolare){
	global $wpdb, $current_user;
	get_currentuserinfo();
	$ris=$wpdb->get_results("SELECT * FROM $wpdb->table_firme_circolari WHERE post_ID=$IDCircolare AND user_ID=$current_user->ID;");
	if (!empty($ris))
		return TRUE;
	else
		return FALSE;	
}
function get_Circolare_Adesione($IDCircolare){
	global $wpdb, $current_user;
	get_currentuserinfo();
	$ris=$wpdb->get_results("SELECT * FROM $wpdb->table_firme_circolari WHERE post_ID=$IDCircolare AND user_ID=$current_user->ID;");
	if (!empty($ris))
		return $ris[0]->adesione;
	else
		return "";	
}

function get_Firma_Circolare($IDCircolare,$IDUser=-1){
	global $wpdb, $current_user;
	if ($IDUser==-1){
		get_currentuserinfo();
		$IDUser=$current_user->ID;
	}
	$ris=$wpdb->get_results("SELECT datafirma,ip,adesione FROM $wpdb->table_firme_circolari WHERE post_ID=$IDCircolare AND user_ID=$IDUser;");
	if (!empty($ris))
		return $ris[0];
	else
		return FALSE;	
}

function GetNumCircolariDaFirmare($Tipo="N"){
	global $wpdb;
	$ris=$wpdb->get_results("SELECT * 
	FROM ($wpdb->posts left join $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id)
	Where  $wpdb->posts.post_type='circolari' 
	   and $wpdb->posts.post_status='publish' 
	   and ($wpdb->postmeta.meta_key='_firma' and $wpdb->postmeta.meta_value ='Si')");
	if (empty($ris))
		return 0;	
	if ($Tipo=="N")
		$Circolari=0;
	else
		$Circolari=array();
	foreach($ris as $riga){
		$Vis=Is_Circolare_per_User($riga->ID);
		if (!Is_Circolare_Firmata($riga->ID) and $Vis)
			if ($Tipo=="N" and $Vis){
				$Circolari++;
			}else{
				$Circolari[]=$riga;
			}
	}
	return $Circolari;
}

function Get_Users_per_Circolare($IDCircolare){
$DestTutti=get_option('Circolari_Visibilita_Pubblica');
$dest=wp_get_post_terms( $IDCircolare, 'gruppiutenti', array("fields" => "ids") ); 
$ListaUtenti=get_users();
if (in_array($DestTutti,$dest))
	return $ListaUtenti;
$UtentiCircolare=array();
foreach($ListaUtenti as $utente){
	if (Is_Circolare_per_User($IDCircolare,$utente->ID))
		$UtentiCircolare[]=$utente;
}
return $UtentiCircolare;
}

function Is_Circolare_per_User($IDCircolare,$IDUser=-1){
	global $current_user;
if ($IDUser==-1){
	get_currentuserinfo();
	$IDUser=$current_user->ID;
}
$Vis=FALSE;
$DestTutti=get_option('Circolari_Visibilita_Pubblica');
$dest=wp_get_post_terms( $IDCircolare, 'gruppiutenti', array("fields" => "ids") ); 
if (in_array($DestTutti,$dest))
	$Vis=TRUE;
else{
	$fgs = wp_get_object_terms($IDCircolare, 'gruppiutenti');
	$GruppiSel=array();
	if(!empty($fgs)){
		foreach($fgs as $fg)
			$GruppiSel[]=$fg->term_id;
	}
	$GruppoUtente=get_user_meta($IDUser, "gruppo", true);
	if (in_array($GruppoUtente,$GruppiSel))
		$Vis=TRUE;
	}
return $Vis;
}
function FirmaCircolare($IDCircolare,$Pv=-1){
	global $wpdb, $current_user;
	get_currentuserinfo();
	if ( false === $wpdb->insert(
		$wpdb->table_firme_circolari ,array(
				'post_ID' => $IDCircolare,
				'user_ID' => $current_user->ID,
				'ip' => $_SERVER['REMOTE_ADDR'],
				'adesione' => $Pv))){
// echo "Sql==".$wpdb->last_query ."    Ultimo errore==".$wpdb->last_error;
		$err=$wpdb->last_error;
        return "La Circolare Num. ".GetNumeroCircolare($IDCircolare)." &egrave; gi&agrave; stata Firmata (msg: ".$err.")";
	}else{
		return "Circolare Num. ".GetNumeroCircolare($IDCircolare)." Firmata correttamente";
	}
}
function Get_User_Per_Gruppo($IdGruppo){
	global $wpdb;
	if ($IdGruppo==get_option('Circolari_Visibilita_Pubblica'))
		return 	$wpdb->get_var("Select count(*) FROM $wpdb->users");
	else
		return $wpdb->get_var($wpdb->prepare(
					"Select count(*) FROM $wpdb->usermeta WHERE meta_key='gruppo' AND meta_value=%d",
					$IdGruppo));
}
function Get_Numero_Firme_Per_Circolare($IDCircolare){
	global $wpdb;
	return $wpdb->get_var($wpdb->prepare(
			"Select count(*) FROM $wpdb->table_firme_circolari WHERE post_ID=%d",
			$IDCircolare));
}
?>