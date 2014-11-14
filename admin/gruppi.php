<?php
/**
 * Gestione Circolari - Funzioni Gestione Gruppi
 * 
 * @package Gestione Circolari
 * @author Scimone Ignazio
 * @copyright 2011-2014
 * @ver 2.4.3
 */
 
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { 
  die('You are not allowed to call this page directly.'); 
}

//Gestione Gruppi Utenti
add_action( 'init', 'Crea_tassonomia_GruppoUtenti');
add_filter('manage_users_sortable_columns', 'gruppi_user_sortable_columns' );
add_filter('request', 'gruppi_user_column_orderby' );
add_action('manage_users_custom_column', 'gruppi_add_custom_user_columns', 15, 3);
add_filter('manage_users_columns', 'gruppi_add_user_columns', 15, 1);
add_action( 'show_user_profile', 'visualizza_gruppo_utenti' );
add_action( 'edit_user_profile', 'visualizza_gruppo_utenti' );
add_action( 'personal_options_update', 'memorizza_gruppo_utenti' );
add_action( 'edit_user_profile_update', 'memorizza_gruppo_utenti' );

function memorizza_gruppo_utenti( $user_id ) {
	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;
	if ($_POST['gruppo'])
		update_usermeta( $user_id, 'gruppo', $_POST['gruppo'] );
}

function visualizza_gruppo_utenti( $user ) { 
 $gruppi=array();
 $gruppiutenti=get_terms('gruppiutenti', array('orderby'    => 'count','hide_empty' => 0));
 foreach($gruppiutenti as $g)
 {
 	$gruppi[$g->term_id]=$g->name;
 }
 $GruppoUtente=esc_attr( get_the_author_meta( 'gruppo', $user->ID ));
?>
	<h3>Informazioni aggiuntive</h3>
	<table class="form-table">
		<tr>
			<th><label for="gruppo">Gruppo Utente</label></th>
			<td>
<?php	     if (current_user_can('create_users'))
     	{
?>			<select name="gruppo" id="gruppo">
				<option value="0">Non Assegnato</option>
			<?php
				foreach($gruppi as $K=>$gruppo){
					echo $K."<br />";
					echo "<option ";
					if ($K==$GruppoUtente) 
						echo "selected='selected'";
					echo "value='".$K."'>$gruppo</option>\n";
				}
			?>
				</select>
				<span class="description">Per favore seleziona il gruppo di appartenenza dell'utente.</span>
<?php
		}else
			foreach($gruppi as $K=>$gruppo){
				if ($K==$GruppoUtente) 
					echo $gruppo."\n";
			}
?>
			</td>
		</tr>
	</table>
<?php }

function gruppi_add_user_columns( $defaults ) {
 	$defaults['gruppo'] = "Gruppo";
     return $defaults;
}
function gruppi_add_custom_user_columns($value, $column_name, $id) {
      if( $column_name == 'gruppo' ) {	
      	$IDGruppo=array();
	  	if(($IDG=get_the_author_meta( 'gruppo', $id ))==NULL)
	  		$IDGruppo[]=-1;
	  	else
	  		$IDGruppo[]=$IDG;
//	  	echo "U=".$id." - g=".get_the_author_meta( 'gruppo', $id )."<br />";
		$gruppiutenti=get_terms('gruppiutenti', array('hide_empty' => FALSE,'include'=>$IDGruppo));
		return $gruppiutenti[0]->name;
      }
 }

function gruppi_user_sortable_columns( $columns ) {
	$columns['gruppo'] = 'Gruppo';
	return $columns;
}

function gruppi_user_column_orderby( $vars ) {
 if ( isset( $vars['orderby'] ) && 'gruppo' == $vars['orderby'] ) {
 			$vars = array_merge( $vars, array(
			'meta_key' => 'gruppo',
			'orderby' => 'meta_value',
			'order'     => 'asc'
		) );
	}
	return $vars;
}
/**
* Tassonomia personalizzata Gruppi Utenti
* 
*/
function Crea_tassonomia_GruppoUtenti() 
{
	 register_taxonomy(
		'gruppiutenti',
		'circolari',
		array(
			'public' => true,
			'show_ui' => true,
			'show_admin_column' => true,
			'hierarchical' => true,
			'labels' => array(
				'name' => __( 'Destinatari' ),
				'singular_name' => __( 'Destinatario' ),
				'menu_name' => __( 'Gruppi Utenti' ),
				'search_items' => __( 'Cerca Gruppo' ),
				'popular_items' => __( 'Gruppo più Popolare' ),
				'all_items' => __( 'Tutti i Gruppi' ),
				'edit_item' => __( 'Modifica Gruppo' ),
				'update_item' => __( 'Aggiorna Gruppo' ),
				'add_new_item' => __( 'Aggiungi nuovo Gruppo' ),
				'new_item_name' => __( 'Nome nuovo Gruppo' ),
				'separate_items_with_commas' => __( 'Separa i Destinatari con virgole. Se la circolare è pubblica non indicare nulla' ),
				'add_or_remove_items' => __( 'Aggiungi o rimuovi Destinatari' ),
				'choose_from_most_used' => __( 'Seleziona tra i Destinatari più popolari' ),
			),
			'rewrite' => array(
				'with_front' => true,
				'slug' => 'gruppo/utente' 
			),
			'capabilities' => array(
				'manage_terms' => 'edit_users', // Using 'edit_users' cap to keep this simple.
				'edit_terms'   => 'edit_users',
				'delete_terms' => 'edit_users',
				'assign_terms' => 'read',
			)
		)
	);

}

?>