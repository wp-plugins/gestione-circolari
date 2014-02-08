<?php 
/**
 * Gestione Circolari - Funzioni Gestione Firme
 * 
 * @package Gestione Circolari
 * @author Scimone Ignazio
 * @copyright 2011-2014
 * @ver 0.1
 */
 
function circolari_GestioneFirme()
{
global $msg;
echo'
		<div class="wrap">
			<img src="'.Circolari_URL.'/img/atti32.png" alt="Icona Firma" style="display:inline;float:left;margin-top:10px;"/>
		<h2 style="margin-left:40px;">Circolari da Firmare</h2>
		</div>';
if($msg!="") 
	echo '<div id="message" class="updated"><p>'.$msg.'</p></div>';
		VisualizzaTabellaCircolari();		
}

function VisualizzaTabellaCircolari(){
$Circolari=get_option('Pasw_Comunicazioni');	
$Posts = get_posts('post_type=circolari');
echo '
<div style="width:100%;margin-top:20px;">
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
	if (Is_Circolare_per_User($post->ID)){
		$TipoCircolare="Circolare";
		$Campo_Firma_Adesione="";
		if ($Adesione[0]=="Si"){			
			$TipoCircolare="Circolare con Adesione";
			switch (get_Circolare_Adesione($post->ID)){
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
		$firma=get_post_meta($post->ID, "_firma");
		$BaseUrl=admin_url()."edit.php";
		if($firma[0]=="Si"){
			if (Is_Circolare_Firmata($post->ID)){
				$Campo_Firma="Firmata".$Campo_Firma_Adesione;
			}
			else{
				if ($Adesione[0]=="Si"){			
					$Campo_Firma='<form action="'.$BaseUrl.'" id="adesione" method="get" style="display:inline;">
						<input type="hidden" name="post_type" value="circolari" />
						<input type="hidden" name="page" value="Firma" />
						<input type="hidden" name="op" value="Adesione" />
						<input type="hidden" name="pid" value="'.$post->ID.'" />
						<input type="radio" name="scelta" value="1"/>Si 
						<input type="radio" name="scelta" value="2"/>No 
						<input type="radio" name="scelta" value="3"/>Presa Visione
						<input type="submit" name="invia" id="invia" class="button" value="Firma"/>
					</form>';
				}else
					$Campo_Firma='<a href="'.$BaseUrl.'?post_type=circolari&page=Firma&op=Firma&pid='.$post->ID.'">Firma Circolare</a>';
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
	}
}
echo '
			</tbody>
		</table>
	</div>';
}
?>