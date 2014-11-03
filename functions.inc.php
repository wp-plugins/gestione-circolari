<?php
/**
 * Gestione Circolari - Funzioni libreia generale
 * 
 * @package Gestione Circolari
 * @author Scimone Ignazio
 * @copyright 2011-2014
 * @ver 2.4
 */
 
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

function FormatDataItalianoBreve($Data){
	$d=explode("-",$Data);
	if (count($d)==3)
		return $d[2]."/".$d[1]."/".$d[0];
	else
		return "";
}
function FormatDataDB($Data,$incGG=0,$incMM=0,$incAA=0){
	$d=explode("/",$Data);
	$Data=$d[2]."-".$d[1]."-".$d[0];
	if ($incAA>0)
		$Data=$d[2]+$incAA."-".$d[1]."-".$d[0];
	if ($incGG>0)
		$Data=date('Y-m-d', strtotime($Data. ' + '.$incGG.' days'));
	if ($incMM>0)
		$Data=date('Y-m-d', strtotime($Data. ' + '.$incMM.' months'));
	return $Data;
}

function circ_MeseLettere($mm){
	$mesi = array('', 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio',  'Agosto', 'Settembre', 'Ottobre', 'Novembre','Dicembre');
	return $mesi[$mm];	
}

function circ_GiornoLettere($gg){
	$giorni = array('Domenica','Lunedì','Martedì', 'Mercoledì', 'Giovedì', 'Venerdì','Sabato');
	return $giorni[$gg];
}

function FormatDataItaliano($Data){
	list($anno,$mese,$giorno) = explode('-',substr($Data,0,10)); 
	return $giorno.' '.substr(circ_MeseLettere(intval($mese)),0,3).' '.$anno;
}
function GetNumeroCircolare($PostID){
	$numero=get_post_meta($PostID, "_numero");
	$numero=$numero[0];
	$anno=get_post_meta($PostID, "_anno");
	$anno=$anno[0];
	$NumeroCircolare=$numero.'/'.$anno ;
return $NumeroCircolare;
}

function Is_Circolare_Da_Firmare($IDCircolare,$Tutte=False){
	global $wpdb, $current_user;
	$ora=date("Y-m-d");
	get_currentuserinfo();
	//$destinatari=Get_Users_per_Circolare($IDCircolare,"ID");
	$DaFirmare=get_post_meta( $IDCircolare, "_firma",true);
	$PresaVisione=get_post_meta( $IDCircolare, "_sciopero",true);
	if (!$Tutte){
		$Scadenza=get_post_meta( $IDCircolare, "_scadenza",true);
		if(!$Scadenza)
			$Scadenza=date("Y-m-d");
	}
	else
		$Scadenza=$ora;
//	if (in_array($current_user->ID,$destinatari) and (($DaFirmare=="Si" or $PresaVisione=="Si") and $Scadenza>=$ora))
	if (Is_Circolare_per_User($IDCircolare) and (($DaFirmare=="Si" or $PresaVisione=="Si") and $Scadenza>=$ora))
		return TRUE;
	else
		return FALSE;
}

function Is_Circolare_Scaduta($IDCircolare){
	global $wpdb, $current_user;
	$ora=date("Y-m-d");
	get_currentuserinfo();
//	$destinatari=Get_Users_per_Circolare($IDCircolare,"ID");
	$DaFirmare=get_post_meta( $IDCircolare, "_firma",true);
	$PresaVisione=get_post_meta( $IDCircolare, "_sciopero",true);
	$Scadenza=get_post_meta( $IDCircolare, "_scadenza",true);		
	if (!$Scadenza){		
		return FALSE;	
	}
	if (Is_Circolare_per_User($IDCircolare) and (($DaFirmare=="Si" or $PresaVisione=="Si") and  strtotime($Scadenza)<strtotime($ora)))
		return TRUE;
	else
		return FALSE;
}

function Get_scadenzaCircolare($ID,$TipoRet="Data",$Giorni=False){
	$Scadenza=get_post_meta( $ID, "_scadenza",true);
	if (!$Scadenza){
		$Scadenza=date("Y-m-d");		
		if ($Giorni){
			return -1;		
		}
	}
	if ($Giorni){
//		echo "in giorni ";
		$seconds_diff = strtotime($Scadenza) - strtotime(date("Y-m-d"));
		$GGDiff=intval(floor($seconds_diff/3600/24));
		return $GGDiff;
	}
	if ($TipoRet=="Data"){
		$Pezzi=explode("-",$Scadenza);
		return $Pezzi[2]."/".$Pezzi[1]."/".$Pezzi[0];
	}else
		return $Scadenza;
}
function Is_Circolare_Firmata($IDCircolare){
	global $wpdb, $current_user;
	get_currentuserinfo();
	if (!Is_Circolare_Da_Firmare($IDCircolare,TRUE))
		return FALSE;
	$ris=$wpdb->get_results("SELECT * FROM $wpdb->table_firme_circolari WHERE post_ID=$IDCircolare AND user_ID=$current_user->ID;");
/*	echo "SELECT * FROM $wpdb->table_firme_circolari WHERE post_ID=$IDCircolare AND user_ID=$current_user->ID;";
	print_r($ris);
echo count($ris);*/
	if (count($ris)==1)
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

function GetCircolariFirmate($Tipo="N"){
	global $wpdb, $current_user,$table_prefix;
	$tabella_firme = $table_prefix . "firme_circolari";
	get_currentuserinfo();
	$IDUser=$current_user->ID;
	$Sql="SELECT $wpdb->posts.ID,$wpdb->posts.post_title, $tabella_firme.*
		  FROM $wpdb->posts inner join $tabella_firme on
		   ($wpdb->posts.ID = $tabella_firme.post_ID)
		  WHERE 
		        $wpdb->posts.post_type   ='circolari' 
	        and $wpdb->posts.post_status ='publish'
	        and $tabella_firme.user_ID=$IDUser";
	$ris= $wpdb->get_results($Sql);
/*	$ris=get_posts('post_type=circolari&numberposts=-1');
	if (empty($ris))
		return 0;	
	if ($Tipo=="N")
		$Circolari=0;
	else
		$Circolari=array();
	foreach($ris as $riga){
		if (Is_Circolare_Da_Firmare($riga->ID,True)){
			if (Is_Circolare_Firmata($riga->ID)){
				if ($Tipo=="N")
					$Circolari++;
				else
					$Circolari[]=$riga;
			}
		}
	}
	return $Circolari;
*/
	if (empty($ris)){
		if ($Tipo=="N")
			return 0;
		else
			return array();		
	}else{
		if ($Tipo=="N")
			return count($ris);
		else	
			return $ris;		
	}
}

function GetCircolariDaFirmare($Tipo="N"){
	global $wpdb, $current_user,$table_prefix;
	$tabella_firme = $table_prefix . "firme_circolari";
	get_currentuserinfo();
	$IDUser=$current_user->ID;
	$Oggi=date('Y-m-d');
	$Sql="SELECT $wpdb->posts.ID,$wpdb->posts.post_title
		  FROM $wpdb->posts inner join $wpdb->postmeta on
		   ($wpdb->posts.ID = $wpdb->postmeta.post_id)
		  WHERE ($wpdb->posts.post_type   ='circolari' and $wpdb->posts.post_status ='publish')
		    and (($wpdb->postmeta.meta_key = '_firma' and $wpdb->postmeta.meta_value = 'Si')  
	              or ($wpdb->postmeta.meta_key = '_sciopero' and $wpdb->postmeta.meta_value = 'Si'))
            and ($wpdb->posts.ID IN (Select $wpdb->postmeta.post_ID from $wpdb->postmeta Where $wpdb->postmeta.meta_key = '_scadenza' and $wpdb->postmeta.meta_value >='$Oggi'))
	        and ($wpdb->posts.ID NOT IN (Select $tabella_firme.post_ID from $tabella_firme Where $tabella_firme.user_ID=$IDUser))
            GROUP BY ID";
//    echo $Sql;
	$ris= $wpdb->get_results($Sql);
	if ($Tipo=="N")
		$Circolari=0;
	else
		$Circolari=array();
	foreach($ris as $riga){
		if (Is_Circolare_per_User($riga->ID)){
			if ($Tipo=="N"){
				$Circolari++;
			}
			else
				$Circolari[]=$riga;
		}
	}
	return $Circolari;		
		
/*	$ris=get_posts('post_type=circolari&numberposts=-1');
	if (empty($ris))
		return 0;	
	if ($Tipo=="N")
		$Circolari=0;
	else
		$Circolari=array();
	foreach($ris as $riga){
		if (Is_Circolare_Da_Firmare($riga->ID,True)){
			$Scaduta=Get_scadenzaCircolare($riga->ID,"DataDB")<date("Y-m-d")?TRUE:FALSE;
//			echo $riga->ID." ".var_dump($Scaduta);
			$Firmata=Is_Circolare_Firmata($riga->ID);
//			echo var_dump($Firmata)." <br />";
			if (!$Firmata and !$Scaduta){
				if ($Tipo=="N")
					$Circolari++;
				else
					$Circolari[]=$riga;
			}
		}
	}
	return $Circolari;
*/
}

function GetCircolariNonFirmate($Tipo="N"){
	global $wpdb, $current_user,$table_prefix;
	$tabella_firme = $table_prefix . "firme_circolari";
	get_currentuserinfo();
	$IDUser=$current_user->ID;
	$Oggi=date('Y-m-d');
	$Sql="SELECT $wpdb->posts.ID,$wpdb->posts.post_title
		  FROM $wpdb->posts inner join $wpdb->postmeta on
		   ($wpdb->posts.ID = $wpdb->postmeta.post_id)
		  WHERE ($wpdb->posts.post_type   ='circolari' and $wpdb->posts.post_status ='publish')
		    and (($wpdb->postmeta.meta_key = '_firma' and $wpdb->postmeta.meta_value = 'Si')  
	              or ($wpdb->postmeta.meta_key = '_sciopero' and $wpdb->postmeta.meta_value = 'Si'))
            and ($wpdb->posts.ID IN (Select $wpdb->postmeta.post_ID from $wpdb->postmeta Where $wpdb->postmeta.meta_key = '_scadenza' and $wpdb->postmeta.meta_value <'$Oggi'))
	        and ($wpdb->posts.ID NOT IN (Select $tabella_firme.post_ID from $tabella_firme Where $tabella_firme.user_ID=$IDUser))
            GROUP BY ID";
//    echo $Sql;
	$ris= $wpdb->get_results($Sql);
	if ($Tipo=="N")
		$Circolari=0;
	else
		$Circolari=array();
	foreach($ris as $riga){
		if (Is_Circolare_per_User($riga->ID)){
			if ($Tipo=="N"){
				$Circolari++;
			}
			else
				$Circolari[]=$riga;
		}
	}
	return $Circolari;		
/*	$ris=get_posts('post_type=circolari&numberposts=-1');
	if (empty($ris))
		return 0;	
	if ($Tipo=="N")
		$Circolari=0;
	else
		$Circolari=array();
	foreach($ris as $riga){
		if (Is_Circolare_Da_Firmare($riga->ID,True)){
			$Scaduta=Get_scadenzaCircolare($riga->ID,"DataDB")<date("Y-m-d")?TURE:FALSE;
			$Firmata=Is_Circolare_Firmata($riga->ID);
			if (!$Firmata and $Scaduta)
				if ($Tipo=="N")
					$Circolari++;
				else
					$Circolari[]=$riga;
		}
	}
	return $Circolari;
*/
}


function Get_Users_per_Circolare($IDCircolare,$Cosa="*"){
$DestTutti=get_option('Circolari_Visibilita_Pubblica');
$dest=wp_get_post_terms( $IDCircolare, 'gruppiutenti', array("fields" => "ids") ); 
$ListaUtenti=get_users();
if (in_array($DestTutti,$dest))
	$Tutti="S";
$UtentiCircolare=array();
foreach($ListaUtenti as $utente){
	if (Is_Circolare_per_User($IDCircolare,$utente->ID) or $Tutti=="S")
		if ($Cosa=="*")
			$UtentiCircolare[]=$utente;
		else
			$UtentiCircolare[]=$utente->ID;
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
	if($DestTutti===FALSE)
		$DestTutti=-1;
	$destinatari=wp_get_post_terms( $IDCircolare, 'gruppiutenti', array("fields" => "ids") ); 
	$dest=array();
	foreach($destinatari as $ele)
		$dest[]=$ele;
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
				$Ritorno.='<li style="margin-left:10px;"><a href="'.$urlCircolari.'?Anno='.$Anno["anno"].'&amp;Mese='.$Mese['mese'].'" title="link agli articoli dell\'anno '.$Anno["anno"].' Mese '.$Mese['mese'].'">'.$mesi[$Mese['mese']].'</a></li>';
			}
		}
//echo $Sql;	
$Ritorno.="</ul>";
return $Ritorno;

}
?>