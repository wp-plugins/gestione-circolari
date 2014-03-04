<?php
/**
 * Gestione Circolari
 * 
 * @package Gestione Circolari
 * @author Scimone Ignazio
 * @copyright 2011-2014
 * @since 1.0
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
if (isset($_REQUEST['Anno'])){
	$annocorrente = $_REQUEST['Anno'];
	$annoprecedente=$_REQUEST['Anno']-1;
}else{
	$annocorrente = date('Y');
	$annoprecedente=date('Y')-1;
}
$args = array( 'category' => $IdCircolari,
		       'post_type' => array('post','circolari'),
			   'year' => $annocorrente,
			   'post_status' => 'publish');
$Circolari = get_posts($args);
if (empty($Circolari)){
	$Contenuto.='<h3>Non risultano circolari per l\'anno '.$annocorrente.' verranno visualizzate quelle del '.$annoprecedente.'</h3>';
	$args = array( 'category' => $IdCircolari,
		       'post_type' => array('post','circolari'),
				'posts_per_page'  => 5,
			   'post_status' => 'publish');
	$Circolari = get_posts($args);
}
$Contenuto.=' <div>';
//print_r($Circolari);
foreach($Circolari as $post) {
	$fgs = wp_get_object_terms($post->ID, 'gruppiutenti');
	$Elenco="";
	if(!empty($fgs)){
		foreach($fgs as $fg){
			$Elenco.="<em>".$fg->name."</em> - ";
		}
		$Elenco=substr($Elenco,0,strlen($Elenco)-3);
	}
	$Contenuto.='<h4>'.FormatDataItaliano($post->post_date).' - 
	<a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a></h4>
	<div>
		<p> Tipo => <spam style="font-style: italic; font-weight: bold;font-size:0.9em;">'.$post->post_type.'</spam>';
	if ($post->post_type=="circolari")
		$Contenuto.=' Destinatari => <spam style="font-style: italic; font-weight: bold;font-size:0.9em;">'.$Elenco.'</spam>';
	$Contenuto.='	
		</p>
	</div>
	<div>
		'.$post->post_excerpt .'
	</div>';
}
$Contenuto.= '
		</div>
		<div style="clear:both"></div>';
return $Contenuto;
}
?>