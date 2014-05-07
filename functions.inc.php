<?php
/**
 * Gestione Circolari - Funzioni libreia generale
 * 
 * @package Gestione Circolari
 * @author Scimone Ignazio
 * @copyright 2011-2014
 * @ver 1.4
 */
 
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

function FormatDataItaliano($Data){
	$mesi = array('', 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio',  'Agosto', 'Settembre', 'Ottobre', 'Novembre','Dicembre');
	$giorni = array('Domenica','Lunedì','Martedì', 'Mercoledì', 'Giovedì', 'Venerdì','Sabato');
	list($anno,$mese,$giorno) = explode('-',substr($Data,0,10)); 
	return $giorno.' '.substr($mesi[intval($mese)],0,3).' '.$anno;
}
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

function get_Circolari_Gruppi(){
	global $wpdb,$table_prefix;
	$Gruppi=array();
	if (get_option('Circolari_UsaGroups')=="si"){
		$Sql="Select group_id, name From ".$table_prefix."groups_group Where group_id>1";
		$Records=$wpdb->get_results($Sql,ARRAY_A);
		foreach( $Records as $Record)
			$Gruppi[]=array("Id"=>$Record["group_id"],
						  "Nome"=>$Record["name"]);
	}else{
		$Records =get_terms('gruppiutenti',array('orderby'=> 'name','hide_empty'=> false));
		foreach( $Records as $Record)
			$Gruppi[]=array("Id"=>$Record->term_id,
					      "Nome"=>$Record->name);
	}
	return $Gruppi;
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
function Circolari_ElencoAnniMesi($urlCircolari){

global $wpdb,$table_prefix;
$Circolari=get_option('Circolari_Categoria');
$PaginaCircolari=get_option('Circolari_Categoria');
$Ritorno="<ul>
";
//echo $tipo."  ".$Categoria."  ".$Anno;
$mesi = array("","Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre","Novembre", "Dicembre");

	$Sql='SELECT year('.$table_prefix.'posts.post_date) as anno  
		FROM '.$table_prefix.'posts JOIN '.$table_prefix.'term_relationships ON '.$table_prefix.'posts.ID = '.$table_prefix.'term_relationships.object_id
                                    JOIN '.$table_prefix.'term_taxonomy ON '.$table_prefix.'term_taxonomy.term_taxonomy_id = '.$table_prefix.'term_relationships.term_taxonomy_id
		WHERE post_type IN ("post","circolari") and post_status="publish" and '.$table_prefix.'term_taxonomy.term_id='.$Circolari.' 
		group by year('.$table_prefix.'posts.post_date)
		order by year('.$table_prefix.'posts.post_date) DESC;';


	$Anni=$wpdb->get_results($Sql,ARRAY_A );

		foreach( $Anni as $Anno){
			$SqlMese='
SELECT month('.$table_prefix.'posts.post_date) as mese  
FROM '.$table_prefix.'posts JOIN '.$table_prefix.'term_relationships ON '.$table_prefix.'posts.ID = '.$table_prefix.'term_relationships.object_id
                            JOIN '.$table_prefix.'term_taxonomy ON '.$table_prefix.'term_taxonomy.term_taxonomy_id = '.$table_prefix.'term_relationships.term_taxonomy_id
WHERE post_type IN ("post","circolari") and post_status="publish" 
	and '.$table_prefix.'term_taxonomy.term_id='.$Circolari.'
	and year('.$table_prefix.'posts.post_date)='.$Anno["anno"].' 
group by month('.$table_prefix.'posts.post_date)
order by month('.$table_prefix.'posts.post_date) DESC;';

			$Ritorno.='<li><a href="'.$urlCircolari.'?Anno='.$Anno["anno"].'" title="link agli articoli dell\'anno '.$Anno["anno"].'">'.$Anno["anno"].'</a></li>';
	
			$Mesi=$wpdb->get_results($SqlMese,ARRAY_A );
			foreach( $Mesi as $Mese){
				$Ritorno.='<li style="margin-left:10px;"><a href="'.$urlCircolari.'?Anno='.$Anno["anno"].'&Mese='.$Mese['mese'].'" title="link agli articoli dell\'anno '.$Anno["anno"].' Mese '.$Mese['mese'].'">'.$mesi[$Mese['mese']].'</a></li>';
			}
		}
/*
}else{

	$Sql='SELECT year('.$table_prefix.'posts.post_date) as anno  , month('.$table_prefix.'posts.post_date) as mese

		FROM '.$table_prefix.'posts JOIN '.$table_prefix.'term_relationships ON '.$table_prefix.'posts.ID = '.$table_prefix.'term_relationships.object_id

                   JOIN '.$table_prefix.'term_taxonomy ON '.$table_prefix.'term_taxonomy.term_taxonomy_id = '.$table_prefix.'term_relationships.term_taxonomy_id

		WHERE post_type="post" and post_status="publish" and '.$table_prefix.'term_taxonomy.term_id='.$Categoria.' And year('.$table_prefix.'posts.post_date)='.$Anno.' 

		group by year('.$table_prefix.'posts.post_date), month('.$table_prefix.'posts.post_date)

		order by year('.$table_prefix.'posts.post_date) DESC, month('.$table_prefix.'posts.post_date) DESC;';

	$AnniMesi=$wpdb->get_results($Sql,ARRAY_A );

		foreach( $AnniMesi as $AnnoMese){

			$Mese=$AnnoMese["mese"];

			$Ritorno.='<li><a href="'.home_url().'/'.$AnnoMese["anno"].'/'.$Mese.'/?catid='.$Categoria.'" title="link agli articoli di '.$mesi[$Mese-1].' del '.$AnnoMese["anno"].'">'.$mesi[$AnnoMese["mese"]-1].' '.$AnnoMese["anno"].'</a></li>

				';

		}

}
*/
//echo $Sql;	
$Ritorno.="</ul>";
return $Ritorno;

}
?>