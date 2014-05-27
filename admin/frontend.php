<?php
/**
 * Gestione Circolari
 * 
 * @package Gestione Circolari
 * @author Scimone Ignazio
 * @copyright 2011-2014
 * @since 2.0
 */

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

if (isset($_REQUEST['anno']))
	$Anno=$_REQUEST['anno'];
else
	$Anno=date("Y");
$Mese=0;
if (isset($_REQUEST['mese']))
	$Mese=$_REQUEST['mese'];
$ret=Lista_Circolari($Anno,$Mese);

function Lista_Circolari($Anno,$Mese){
$Contenuto="";
$IdCircolari=get_option('Circolari_Categoria');
$mesecorrente = date('n');
if (isset($_REQUEST['Anno']))
	$annocorrente = $_REQUEST['Anno'];
else
	$annocorrente = date('Y');
if (isset($_REQUEST['Mese']))
	$mesecorrente=$_REQUEST['Mese'];
elseif(isset($_REQUEST['Anno']))
	$mesecorrente="";
else
	$mesecorrente=date('n');
$args = array( 'category' => $IdCircolari,
		       'post_type' => array('post','circolari'),
			   'year' => $annocorrente,
			   'year' => $annocorrente,
			   'monthnum' => $mesecorrente,
			   'posts_per_page'  => -1,
			   'post_status' => 'publish');
$Circolari = get_posts($args);
if (empty($Circolari)){
	$Contenuto.='<h3>Non risultano circolari per l\'anno '.$annocorrente.' mese '.$mesecorrente.'verranno visualizzate le ultime 5 codificate</h3>';
	$args = array( 'category' => $IdCircolari,
		       'post_type' => array('post','circolari'),
			   'posts_per_page'  => 5,
			   'post_status' => 'publish');
	$Circolari = get_posts($args);
}
$Contenuto.=' <div>';
//print_r($Circolari);
foreach($Circolari as $post) {
	$visibilita=get_post_meta($post->ID, "_visibilita");
 	if(count($visibilita)==0)
 		$visibilita="p";
 	else 
 		$visibilita=$visibilita[0];
	if ((Is_Circolare_per_User($post->ID) and $visibilita=="d") or $visibilita=="p"){
		$fgs = wp_get_object_terms($post->ID, 'gruppiutenti');
		$Elenco="";
		if(!empty($fgs)){
			foreach($fgs as $fg){
				$Elenco.=$fg->name." - ";
			}
			$Elenco=substr($Elenco,0,strlen($Elenco)-3);
		}
		$Contenuto.='
		<div style="margin-bottom:5px;padding:3px;">';
		$Contenuto.='
			<h4>'.FormatDataItaliano($post->post_date).' - <a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a>
			</h4>
			<div style="height:30px;">
				<div style="display:inline;">
					<img src="'.Circolari_URL.'img/tipo.png" style="border:0;" alt="Icona tipo post" />
				</div>
				<div style="display:inline;vertical-align:top;">
					<p style="font-style:italic;font-size:0.8em;display:inline;margin-top:3px;">'.$post->post_type.'</p>
				</div>';	
		if ($post->post_type=="circolari")
			$Contenuto.='
				<div style="display:inline;">
					<img src="'.Circolari_URL.'img/destinatari.png" style="border:0;" alt="Icona destinatari"/>
				</div>
				<div style="display:inline;vertical-align:top;">
					<p style="font-style:italic;font-size:0.8em;display:inline;margin-top:3px;">'.$Elenco.'</p>
				</div>';	
		if (!post_password_required( $post->ID ))
			$riassunto=	$post->post_excerpt;
		else{
			$riassunto="";
		}
		if (!empty($post->post_password))
			$Contenuto.='
				<div style="display:inline;">
					<img src="'.Circolari_URL.'img/protetto.png" style="border:0;" alt="Icona protezione"/>
				</div>
				<div style="display:inline;vertical-align:top;">
					<p style="font-style:italic;font-size:0.8em;display:inline;margin-top:3px;">Contenuto Protetto</p>
				</div>';	
		if (Is_Circolare_per_User($post->ID))
			if (Is_Circolare_Da_Firmare($post->ID))
				if (!Is_Circolare_Firmata($post->ID)) {
					if (get_post_meta($post->ID, "_sciopero",TRUE)=="Si")
					$Tipo="Esprimere adesione";
				else
				if (get_post_meta($post->ID, "_firma",TRUE)=="Si")
					$Tipo="Firmare";
				$Contenuto.='
					<div style="display:inline;">
						<img src="'.Circolari_URL.'/img/firma.png" style="border:0;" alt="Icona firma o presa visione"/>
					</div>
					<div style="display:inline;vertical-align:top;">
						<p style="font-style:italic;font-size:0.8em;display:inline;margin-top:3px;color:red;">'.$Tipo.'</p>
					</div>';	
		}
		$Contenuto.='	
			</div>
			<div style="margin-bottom:5px;">
				<em>'.$riassunto .'</em>
			</div>
			<hr />
		</div>';
	}
}
$Contenuto.= '
		</div>
		<div style="clear:both"></div>';

return $Contenuto;
}
?>