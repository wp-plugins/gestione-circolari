<?php 
/**
 * Gestione Circolari - Funzioni Gestione Firme
 * 
 * @package Gestione Circolari
 * @author Scimone Ignazio
 * @copyright 2011-2014
 * @ver 2.2.1
 */

function circolari_VisualizzaFirmate()
{
	echo'
		<div class="wrap">
			<img src="'.Circolari_URL.'/img/firma24.png" alt="Icona Firma" style="display:inline;float:left;margin-top:10px;"/>
		<h2 style="margin-left:40px;">Circolari Firmate</h2>
		</div>';
	$Posts=GetCircolariFirmate("D");
	echo '
	<div>
		<table id="TabellaCircolari" class="widefat"  cellspacing="0" width="99%">
			<thead>
				<tr>
					<th style="width:5%;">N°</th>
					<th style="width55%;">Titolo</th>
					<th style="width:15%;">Tipo</th>
					<th style="width:10%;">Firma</th>
					<th style="width:15%;"  id="ColOrd" sorted="4">Data Firma</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th style="width:5%;">N°</th>
					<th style="width:55%;">Titolo</th>
					<th style="width:15%;">Tipo</th>
					<th style="width:10%;">Firma</th>
					<th style="width:15%;">Data Firma</th>
				</tr>
			</tfoot>
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
//		setup_postdata($post);
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

function circolari_VisualizzaNonFirmate()
{
	echo'
		<div class="wrap">
			<img src="'.Circolari_URL.'/img/firma24.png" alt="Icona Firma" style="display:inline;float:left;margin-top:10px;"/>
		<h2 style="margin-left:40px;">Circolari non Firmate</h2>
		</div>';
	$Posts=GetCircolariNonFirmate("D");
	echo '
	<div>
		<table id="TabellaCircolari" class="widefat"  cellspacing="0" width="99%">
			<thead>
				<tr>
					<th style="width:5%;">N°</th>
					<th style="width:70%;">Titolo</th>
					<th style="width:15%;">Tipo</th>
					<th style="width:10%;"  id="ColOrd" sorted="3">Scadenza</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th style="width:5%;">N°</th>
					<th style="width:70%;">Titolo</th>
					<th style="width:15%;">Tipo</th>
					<th style="width:10%;">Scadenza</th>
				</tr>
			</tfoot>
			<tboby>';
	foreach($Posts as $post){
		$Adesione=get_post_meta($post->ID, "_sciopero");
		$firma=get_post_meta($post->ID, "_firma");
		if($firma[0]=="Si")
			$Campo_Firma="Firmare";
		if ($Adesione[0]=="Si")
			$Campo_Firma="Circolare Sindacale";
		echo "
				<tr>
					<td> ".GetNumeroCircolare($post->ID)."</td>
					<td>
					<a href='".get_permalink( $post->ID )."'>
					$post->post_title
					</a>
					</td>
					<td>$Campo_Firma</td>
					<td>".Get_scadenzaCircolare( $post->ID )."</td>
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
	$Posts=GetCircolariDaFirmare("D");
	
	echo '
	<div>
		<table id="TabellaCircolari" class="widefat"  cellspacing="0" width="99%">
			<thead>
				<tr>
					<th style="width:5%;">N°</th>
					<th style="width:60%;">Titolo</th>
					<th style="width:15%;" id="ColOrd" sorted="-2">Scadenza</th>
					<th style="width:20%;">Firma</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th style="width:5%;">N°</th>
					<th style="width:60%;">Titolo</th>
					<th style="width:15%;">Scadenza</th>
					<th style="width:20%;">Firma</th>
				</tr>
			</tfoot>
			<tboby>';

	foreach($Posts as $post){
		$Adesione=get_post_meta($post->ID, "_sciopero");
			$firma=get_post_meta($post->ID, "_firma");
			$BaseUrl=admin_url()."edit.php";
			$Scadenza=Get_scadenzaCircolare($post->ID,"DataDB");
			$seconds_diff = strtotime($Scadenza) - strtotime(date("Y-m-d"));
			$GGDiff=floor($seconds_diff/3600/24);
			switch ($GGDiff){
				case ($GGDiff <3):
					$BGC="color: Red;";
					break;
				case ($GGDiff >2 And $GGDiff <7):
					$BGC="color: #FFA500;";
					break;
				case ($GGDiff >6  And $GGDiff <15):
					$BGC="color: #71E600;";
					break;
				default:
					$BGC="color: Blue;";
					break;	
			}
			//setup_postdata($post);
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
						<td><spam style='$BGC'>$Scadenza ($GGDiff gg)</spam></td>
						<td>$Campo_Firma</td>
					</tr>";
			}
	echo '
				</tbody>
			</table>
		</div>';
}
?>