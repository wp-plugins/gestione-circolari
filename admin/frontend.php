<?php
/**
 * Gestione Circolari
 * 
 * @package Gestione Circolari
 * @author Scimone Ignazio
 * @copyright 2011-2014
 * @since 2.4.3
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
			   'monthnum' => $mesecorrente,
			   'posts_per_page'  => -1,
			   'post_status' => 'publish');
$Circolari = get_posts($args);
if (empty($Circolari)){
	$Contenuto.='<h3>Non risultano circolari per '.circ_MeseLettere($mesecorrente).' '.$annocorrente.' verranno visualizzate le ultime 5</h3>';
	$args = array( 'category' => $IdCircolari,
	       'post_type' => array('post','circolari'),
		   'posts_per_page'  => 5,
		   'post_status' => 'publish');	
	$Circolari = get_posts($args);
}
$Contenuto.=' <div>';
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
		<div style="padding:3px;">';
		$numero=get_post_meta($post->ID, "_numero",TRUE);
		$anno=get_post_meta($post->ID, "_anno",TRUE);
		$Contenuto.='
			<h4><a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a>
			</h4>
			<div style="font-weight: bold;font-size:0.8em;">Del '.FormatDataItaliano($post->post_date).' Numero '.$numero.'_'.$anno.'</div> 
			<div style="min-height:30px;margin-top:5px;margin-bottom:5px; padding: 5px 3px;">
					<img src="'.Circolari_URL.'img/tipo.png" style="border:0;" alt="Icona tipo post" />
					<p style="font-style:italic;font-size:0.8em;display:inline;margin-top:3px;">'.$post->post_type.'</p>';	
		if ($post->post_type=="circolari")
			$Contenuto.='
					<img src="'.Circolari_URL.'img/gruppo.png" style="border:0;" alt="Icona destinatari"/>
					<p style="font-style:italic;font-size:0.8em;display:inline;margin-top:3px;">'.$Elenco.'</p>';
		if (!post_password_required( $post->ID ))
			$riassunto=	$post->post_excerpt;
		else{
			$riassunto="";
		}
		if (!empty($post->post_password))
			$Contenuto.='
					<img src="'.Circolari_URL.'img/protetto.png" style="border:0;" alt="Icona protezione"/>
					<p style="font-style:italic;font-size:0.8em;display:inline;margin-top:3px;">Contenuto Protetto</p>';	
//		if (Is_Circolare_per_User($post->ID))
//	echo ">>>>>>>>>>>>>>>>>>>>>>>>>><<".$post->ID."<>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>><br />";return;
			if (Is_Circolare_Da_Firmare($post->ID))
				if (!Is_Circolare_Firmata($post->ID)) {
					$ngiorni=Get_scadenzaCircolare($post->ID,"",True);					
					if(Is_Circolare_Scaduta($post->ID)){
						$Contenuto.='
							<img src="'.Circolari_URL.'/img/firmabe.png" style="border:0;" alt="Icona firma o presa visione"/>
							<p style="font-style:italic;font-size:0.8em;display:inline;margin-top:3px;color:red;">Scaduta e non Firmata</p>';						
					}else{
						switch ($ngiorni){
							case -1:							
								$entro="";							
								break;													
							case 0:
								$entro="entro OGGI";
								break;
							case 1:
								$entro="entro DOMANI";
								break;
							default:
								$entro="entro $ngiorni giorni";
								break;
						}
						if (get_post_meta($post->ID, "_sciopero",TRUE)=="Si")
							$Tipo="Esprimere adesione $entro";
						else
							if (get_post_meta($post->ID, "_firma",TRUE)=="Si")
								$Tipo="Firmare $entro";
						$Contenuto.='
							<img src="'.Circolari_URL.'/img/firmabe.png" style="border:0;" alt="Icona firma o presa visione"/>
							<p style="font-style:italic;font-size:0.8em;display:inline;margin-top:3px;color:red;">'.$Tipo.'</p>';	
				}			
			}else{
				$Contenuto.='
					<img src="'.Circolari_URL.'/img/firmabe.png" style="border:0;" alt="Icona firma o presa visione"/>
					<p style="font-style:italic;font-size:0.8em;display:inline;margin-top:3px;color:blue;">Firmata</p>';				
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