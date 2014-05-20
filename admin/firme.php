<?php 
/**
 * Gestione Circolari - Funzioni Gestione Firme
 * 
 * @package Gestione Circolari
 * @author Scimone Ignazio
 * @copyright 2011-2014
 * @ver 1.8
 */

function circolari_VisualizzaFirmate()
{
	echo'
		<div class="wrap">
			<img src="'.Circolari_URL.'/img/firma24.png" alt="Icona Firma" style="display:inline;float:left;margin-top:10px;"/>
		<h2 style="margin-left:40px;">Circolari Firmate</h2>
		</div>';
$Circolari=get_option('Pasw_Comunicazioni');
$NumCircolari =GetNumCircolariFirmate("N");
$NumPagine=intval($NumCircolari/get_option('Circolari_NumPerPag'));	
if ($NumPagine<$NumCircolari/get_option('Circolari_NumPerPag'))
	$NumPagine++;
if ($NumPagine>1){
	$mTop="0";
	if (!isset($_GET['npag'])){
		$CurPage=1;
		$OSPag=0;
	}else{
		$OSPag=($_GET['npag']-1)*get_option('Circolari_NumPerPag');
		$CurPage=$_GET['npag'];
	}
	if ($CurPage==1){
		$Dietro=" disabled";
		$Pre=1;	
	}else{
		$Dietro="";
		$Pre=$CurPage-1;			
	}
	if ($CurPage==$NumPagine){
		$Avanti=" disabled";
		$Suc=$NumPagine;
	}else{
		$Avanti="";
		$Suc=$CurPage+1;
	}
		
	echo '
	<div class="tablenav top">
		<div class="tablenav-pages">
			<span class="displaying-num">'.$NumCircolari.' circolari</span>
			<span class="pagination-links">
				<a class="first-page'.$Dietro.'" title="Vai alla prima pagina" href="'.get_bloginfo("wpurl").'/wp-admin/edit.php?post_type=circolari&page=Firmate">&laquo;</a>
				<a class="prev-page'.$Dietro.'" title="Torna alla pagina precedente." href="'.get_bloginfo("wpurl").'/wp-admin/edit.php?post_type=circolari&page=Firmate&npag='.$Pre.'">&lsaquo;</a>
			<span class="paging-input">
				<input class="current-page" id="NavPag_Circolari" title="Pagina corrente." type="text" name="paged" value="'.$CurPage.'" size="2" />				<input type="hidden" id="UrlNavPagCircolari" value="'.get_bloginfo("wpurl").'/wp-admin/edit.php?post_type=circolari&page=Firmate&npag="/>
				 di <span class="total-pages">'.$NumPagine.'</span>
			</span>
				<a class="next-page'.$Avanti.'" title="Vai alla pagina successiva" href="'.get_bloginfo("wpurl").'/wp-admin/edit.php?post_type=circolari&page=Firmate&npag='.$Suc.'">&rsaquo;</a>
				<a class="last-page'.$Avanti.'" title="Vai all&#039;ultima pagina" href="'.get_bloginfo("wpurl").'/wp-admin/edit.php?post_type=circolari&page=Firmate&npag='.$NumPagine.'">&raquo;</a>
			</span>
		</div>
	</div>';
}else{
	$mTop="20";
}
//$Posts = get_posts('post_type=circolari&posts_per_page='.get_option('Circolari_NumPerPag').'&offset='.$OSPag);
	global $wpdb;
	$Sql="SELECT *
		 	FROM ($wpdb->posts INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id)
			WHERE $wpdb->posts.post_type = 'circolari' AND 
				  $wpdb->posts.post_status = 'publish' AND 
				  $wpdb->posts.ID IN (
						SELECT $wpdb->postmeta.post_id
							FROM $wpdb->postmeta
							WHERE ($wpdb->postmeta.meta_key = '_firma' AND $wpdb->postmeta.meta_value = 'Si')
							       OR ($wpdb->postmeta.meta_key = '_sciopero' AND $wpdb->postmeta.meta_value = 'Si')
							       )
						GROUP BY post_id
						ORDER BY post_date DESC";
	$Circolari=$wpdb->get_results($Sql);
	$Posts=array();
	$CurCirc=0;
	$Cpp=get_option('Circolari_NumPerPag');
	foreach($Circolari as $Circolare){
/*	echo $Circolare->ID;
		if (Is_Circolare_per_User($Circolare->ID))
			echo "Per utente ";
		if(Is_Circolare_Firmata($Circolare->ID))
			echo "Circolare firmata ";
	echo " <br />";
*/		if (Is_Circolare_per_User($Circolare->ID) and Is_Circolare_Firmata($Circolare->ID)){
			
			if($CurCirc>=$OSPag and $CurCirc<$OSPag+$Cpp)
				$Posts[]=$Circolare;		
			$CurCirc++;
	}
		}
	echo '
	<div style="width:100%;margin-top:'.$mTop.'px;">
		<table class="widefat">
			<thead>
				<tr>
					<th style="width:5%;">N°</th>
					<th style="width:45%;">Titolo</th>
					<th style="width:15%;">Tipo</th>
					<th style="width:20%;">Firma</th>
					<th>Data</th>
				</tr>
			</thead>
			<tboby>';
	foreach($Posts as $post){
		$Adesione=get_post_meta($post->ID, "_sciopero");
		$firma=get_post_meta($post->ID, "_firma");
		$TipoCircolare="Circolare";
		$Campo_Firma="";
		if($firma[0]=="Si"){
			$Campo_Firma="Firmata".$Campo_Firma_Adesione;
		}
		if ($Adesione[0]=="Si"){			
			$TipoCircolare="Circolare Sindacale";
			switch (get_Circolare_Adesione($post->ID)){
			case 1:
				$Campo_Firma="adesione Si";
				break;
			case 2:
				$Campo_Firma="adesione No";		
				break;
			case 3:
				$Campo_Firma="adesione Presa Visione";				
				break;
			}
		}	
		setup_postdata($post);
		$dati_firma=get_Firma_Circolare($post->ID);
		echo "
				<tr>
					<td> ".GetNumeroCircolare($post->ID)."</td>
					<td>
					<a href='".get_permalink( $post->ID )."'>
					$post->post_title
					</a>
					</td>
					<td>$TipoCircolare</td>
					<td>$Campo_Firma</td>
					<td>$dati_firma->datafirma</td>
				</tr>";
	}	
	echo '
				</tbody>
			</table>
		</div>';	
}

function circolari_GestioneFirme()
{
global $msg;
echo'
		<div class="wrap">
			<img src="'.Circolari_URL.'/img/firma24.png" alt="Icona Firma" style="display:inline;float:left;margin-top:10px;"/>
		<h2 style="margin-left:40px;">Circolari da Firmare</h2>
		</div>';
if($msg!="") 
	echo '<div id="message" class="updated"><p>'.$msg.'</p></div>';
		VisualizzaTabellaCircolari();		
}

function VisualizzaTabellaCircolari(){
$Circolari=get_option('Pasw_Comunicazioni');
$NumCircolari =GetNumCircolariDaFirmare("N");
$NumPagine=intval($NumCircolari/get_option('Circolari_NumPerPag'));	
if ($NumPagine<$NumCircolari/get_option('Circolari_NumPerPag'))
	$NumPagine++;
$OSPag=0;
if ($NumPagine>1){
	$mTop="0";
	if (!isset($_GET['npag'])){
		$CurPage=1;
		$OSPag=0;
	}else{
		$OSPag=($_GET['npag']-1)*get_option('Circolari_NumPerPag');
		$CurPage=$_GET['npag'];
	}
	if ($CurPage==1){
		$Dietro=" disabled";
		$Pre=1;	
	}else{
		$Dietro="";
		$Pre=$CurPage-1;			
	}
	if ($CurPage==$NumPagine){
		$Avanti=" disabled";
		$Suc=$NumPagine;
	}else{
		$Avanti="";
		$Suc=$CurPage+1;
	}

	echo '
	<div class="tablenav top">
		<div class="tablenav-pages">
			<span class="displaying-num">'.$NumCircolari.' circolari</span>
			<span class="pagination-links">
				<a class="first-page'.$Dietro.'" title="Vai alla prima pagina" href="'.get_bloginfo("wpurl").'/wp-admin/edit.php?post_type=circolari&page=Firma">&laquo;</a>
				<a class="prev-page'.$Dietro.'" title="Torna alla pagina precedente." href="'.get_bloginfo("wpurl").'/wp-admin/edit.php?post_type=circolari&page=Firma&npag='.$Pre.'">&lsaquo;</a>
			<span class="paging-input">
				<input class="current-page" id="NavPag_Circolari" title="Pagina corrente." type="text" name="paged" value="'.$CurPage.'" size="2" />
				<input type="hidden" id="UrlNavPagCircolari" value="'.get_bloginfo("wpurl").'/wp-admin/edit.php?post_type=circolari&page=Firma&npag="/>
				 di <span class="total-pages">'.$NumPagine.'</span>
			</span>
				<a class="next-page'.$Avanti.'" title="Vai alla pagina successiva" href="'.get_bloginfo("wpurl").'/wp-admin/edit.php?post_type=circolari&page=Firma&npag='.$Suc.'">&rsaquo;</a>
				<a class="last-page'.$Avanti.'" title="Vai all&#039;ultima pagina" href="'.get_bloginfo("wpurl").'/wp-admin/edit.php?post_type=circolari&page=Firma&npag='.$NumPagine.'">&raquo;</a>
			</span>
		</div>
	</div>';
}else{
	$mTop="20";
}
	$Cpp=get_option('Circolari_NumPerPag');
	global $wpdb;
	$Sql="SELECT *
		 	FROM ($wpdb->posts INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id)
			WHERE $wpdb->posts.post_type = 'circolari' AND 
				  $wpdb->posts.post_status = 'publish' AND 
				  $wpdb->posts.ID IN (
						SELECT $wpdb->postmeta.post_id
							FROM $wpdb->postmeta
							WHERE ($wpdb->postmeta.meta_key = '_firma' AND $wpdb->postmeta.meta_value = 'Si')
							       OR ($wpdb->postmeta.meta_key = '_sciopero' AND $wpdb->postmeta.meta_value = 'Si')
							       )
						GROUP BY post_id";
	$Circolari=$wpdb->get_results($Sql);
	$Posts=array();
	$CurCirc=0;
	$Cpp=get_option('Circolari_NumPerPag');
	foreach($Circolari as $Circolare)
		if (Is_Circolare_per_User($Circolare->ID) and !Is_Circolare_Firmata($Circolare->ID)){
			if($CurCirc>=$OSPag and $CurCirc<$OSPag+$Cpp)
				$Posts[]=$Circolare;		
			$CurCirc++;
		}
//	$Posts=$wpdb->get_results($Sql);
//$Posts = get_posts($args);
echo '
<div style="width:100%;margin-top:'.$mTop.'px;">
	<table class="widefat">
		<thead>
			<tr>
				<th style="width:5%;">N°</th>
				<th style="width:40%;">Titolo</th>
				<th style="width:15%;">Tipo</th>
				<th style="width:25%;">Firma</th>
			</tr>
		</thead>
		<tboby>';
foreach($Posts as $post){
	$Adesione=get_post_meta($post->ID, "_sciopero");
	if (Is_Circolare_per_User($post->ID) And !Is_Circolare_Firmata($post->ID)){
		$TipoCircolare="Circolare";
		$firma=get_post_meta($post->ID, "_firma");
		$BaseUrl=admin_url()."edit.php";
		setup_postdata($post);
		if($firma[0]=="Si")
			$Campo_Firma='<a href="'.$BaseUrl.'?post_type=circolari&page=Firma&op=Firma&pid='.$post->ID.'">Firma Circolare</a>';
		if ($Adesione[0]=="Si")			
			$Campo_Firma='<form action="'.$BaseUrl.'"  method="get" style="display:inline;">
				<input type="hidden" name="post_type" value="circolari" />
				<input type="hidden" name="page" value="Firma" />
				<input type="hidden" name="op" value="Adesione" />
				<input type="hidden" name="pid" value="'.$post->ID.'" />
				<input type="radio" name="scelta" class="s1-'.$post->ID.'" value="1"/>Si 
				<input type="radio" name="scelta" class="s2-'.$post->ID.'" value="2"/>No 
				<input type="radio" name="scelta" class="s3-'.$post->ID.'" value="3" checked="checked"/>Presa Visione
				<input type="submit" name="inviaadesione" class="button inviaadesione" id="'.$post->ID.'" value="Firma" rel="'.$post->post_title.'"/>
			</form>';
		echo "
				<tr>
					<td> ".GetNumeroCircolare($post->ID)."</td>
					<td>
					<a href='".get_permalink( $post->ID )."'>
					$post->post_title
					</a>
					</td>
					<td>$TipoCircolare</td>
					<td>$Campo_Firma</td>
				</tr>";
		}
	}	
echo '
			</tbody>
		</table>
	</div>';
}
?>