<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap olm-wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <hr class="wp-header-end">

    <?php
    $wpcp_active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'outbound';
    $wpcp_page_name = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'outbound-links-manager';
    ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo esc_attr( $wpcp_page_name ); ?>&tab=outbound" class="nav-tab <?php echo $wpcp_active_tab == 'outbound' ? 'nav-tab-active' : ''; ?>">
            Liens sortants
        </a>
        <a href="?page=<?php echo esc_attr( $wpcp_page_name ); ?>&tab=internal" class="nav-tab <?php echo $wpcp_active_tab == 'internal' ? 'nav-tab-active' : ''; ?>">
            Liens internes
        </a>
        <a href="?page=<?php echo esc_attr( $wpcp_page_name ); ?>&tab=incoming" class="nav-tab <?php echo $wpcp_active_tab == 'incoming' ? 'nav-tab-active' : ''; ?>">
            Liens entrants
        </a>
        <a href="?page=<?php echo esc_attr( $wpcp_page_name ); ?>&tab=traffic" class="nav-tab <?php echo $wpcp_active_tab == 'traffic' ? 'nav-tab-active' : ''; ?>">
            Trafic Page
        </a>
        <a href="?page=<?php echo esc_attr( $wpcp_page_name ); ?>&tab=settings" class="nav-tab <?php echo $wpcp_active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
            Paramètres
        </a>
    </h2>

    <?php
    // Afficher les messages de suppression en masse
    if ( isset( $_GET['bulk_delete_success'] ) ) {
        $wpcp_success_count = intval( $_GET['bulk_delete_success'] );
        $wpcp_error_count = isset( $_GET['bulk_delete_error'] ) ? intval( $_GET['bulk_delete_error'] ) : 0;

        if ( $wpcp_success_count > 0 ) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html( sprintf( '%d lien(s) supprime(s) avec succes.', $wpcp_success_count ) );
            echo '</p></div>';
        }

        if ( $wpcp_error_count > 0 ) {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo esc_html( sprintf( '%d erreur(s) rencontree(s) lors de la suppression.', $wpcp_error_count ) );
            echo '</p></div>';
        }
    }
    ?>

    <!-- Statistiques -->
    <div class="olm-stats-wrapper" style="margin: 20px 0; display: flex; gap: 15px; flex-wrap: wrap;">
        <?php if ( $wpcp_active_tab == 'outbound' ): ?>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html( number_format_i18n( $stats['total_outbound_links'] ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Liens sortants trouves</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html( number_format_i18n( $stats['posts_with_outbound'] ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Pages/articles avec liens sortants</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html( number_format_i18n( $stats['unique_outbound_urls'] ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Domaines externes uniques</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #50575e;"><?php echo esc_html( number_format_i18n( $stats['total_posts_site'] ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Total pages/articles sur le site</div>
            </div>
        <?php elseif ( $wpcp_active_tab == 'traffic' ): ?>
            <?php
            $wpcp_traffic_total = isset( $stats['traffic_total_pages'] ) ? $stats['traffic_total_pages'] : 0;
            $wpcp_traffic_with = isset( $stats['traffic_pages_with_impressions'] ) ? $stats['traffic_pages_with_impressions'] : 0;
            $wpcp_traffic_without = isset( $stats['traffic_pages_without_impressions'] ) ? $stats['traffic_pages_without_impressions'] : 0;
            $wpcp_traffic_clicks = isset( $stats['traffic_total_clicks'] ) ? $stats['traffic_total_clicks'] : 0;
            $wpcp_traffic_impressions = isset( $stats['traffic_total_impressions'] ) ? $stats['traffic_total_impressions'] : 0;
            $wpcp_pct_with = $wpcp_traffic_total > 0 ? round( ( $wpcp_traffic_with / $wpcp_traffic_total ) * 100, 1 ) : 0;
            $wpcp_pct_without = $wpcp_traffic_total > 0 ? round( ( $wpcp_traffic_without / $wpcp_traffic_total ) * 100, 1 ) : 0;
            ?>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html( number_format_i18n( $wpcp_traffic_total ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Pages dans Search Console</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #46b450;"><?php echo esc_html( number_format_i18n( $wpcp_traffic_with ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Pages avec impressions</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #dc3232;"><?php echo esc_html( number_format_i18n( $wpcp_traffic_without ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Pages sans impressions</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html( number_format_i18n( $wpcp_traffic_clicks ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Clics totaux</div>
            </div>
        <?php elseif ( $wpcp_active_tab == 'incoming' ): ?>
            <?php
            $wpcp_inc_total = isset( $stats['incoming_total_pages'] ) ? $stats['incoming_total_pages'] : 0;
            $wpcp_inc_with = isset( $stats['incoming_pages_with'] ) ? $stats['incoming_pages_with'] : 0;
            $wpcp_inc_without = isset( $stats['incoming_pages_without'] ) ? $stats['incoming_pages_without'] : 0;
            $wpcp_inc_total_links = isset( $stats['incoming_total_links'] ) ? $stats['incoming_total_links'] : 0;
            $wpcp_inc_pct_with = $wpcp_inc_total > 0 ? round( ( $wpcp_inc_with / $wpcp_inc_total ) * 100, 1 ) : 0;
            $wpcp_inc_pct_without = $wpcp_inc_total > 0 ? round( ( $wpcp_inc_without / $wpcp_inc_total ) * 100, 1 ) : 0;
            ?>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html( number_format_i18n( $wpcp_inc_total ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Pages scannees</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #46b450;"><?php echo esc_html( number_format_i18n( $wpcp_inc_with ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Pages avec liens entrants</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #dc3232;"><?php echo esc_html( number_format_i18n( $wpcp_inc_without ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Pages orphelines (0 lien entrant)</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html( number_format_i18n( $wpcp_inc_total_links ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Total liens entrants</div>
            </div>
        <?php else: ?>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html( number_format_i18n( $stats['total_internal_links'] ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Liens internes trouves</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html( number_format_i18n( $stats['posts_scanned_internal'] ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Pages/articles scannes</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #dc3232;"><?php echo esc_html( number_format_i18n( $stats['posts_without_internal'] ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Pages sans liens internes</div>
            </div>
            <div class="olm-stat-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px 20px; flex: 1; min-width: 200px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 28px; font-weight: 600; color: #50575e;"><?php echo esc_html( number_format_i18n( $stats['total_posts_site'] ) ); ?></div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">Total pages/articles sur le site</div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ( $wpcp_active_tab == 'traffic' && isset( $wpcp_traffic_total ) && $wpcp_traffic_total > 0 ): ?>
    <!-- Graphique Impressions -->
    <div style="margin: 0 0 25px 0; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 500px;">
        <h3 style="margin: 0 0 15px 0; font-size: 14px; color: #1d2327;">Repartition des pages par impressions</h3>
        <div style="display: flex; align-items: center; gap: 20px;">
            <!-- Barre horizontale -->
            <div style="flex: 1;">
                <div style="display: flex; height: 30px; border-radius: 4px; overflow: hidden; background: #f0f0f1;">
                    <?php if ( $wpcp_pct_with > 0 ): ?>
                    <div style="width: <?php echo esc_attr( $wpcp_pct_with ); ?>%; background: #46b450; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 12px; min-width: 35px;">
                        <?php echo esc_html( $wpcp_pct_with ); ?>%
                    </div>
                    <?php endif; ?>
                    <?php if ( $wpcp_pct_without > 0 ): ?>
                    <div style="width: <?php echo esc_attr( $wpcp_pct_without ); ?>%; background: #dc3232; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 12px; min-width: 35px;">
                        <?php echo esc_html( $wpcp_pct_without ); ?>%
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Legende -->
                <div style="display: flex; gap: 20px; margin-top: 10px; font-size: 12px; color: #646970;">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #46b450; border-radius: 2px;"></span>
                        Avec impressions (<?php echo esc_html( number_format_i18n( $wpcp_traffic_with ) ); ?>)
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #dc3232; border-radius: 2px;"></span>
                        Sans impressions (<?php echo esc_html( number_format_i18n( $wpcp_traffic_without ) ); ?>)
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $wpcp_active_tab == 'incoming' && isset( $wpcp_inc_total ) && $wpcp_inc_total > 0 ): ?>
    <!-- Graphique Liens entrants -->
    <div style="margin: 0 0 25px 0; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 500px;">
        <h3 style="margin: 0 0 15px 0; font-size: 14px; color: #1d2327;">Repartition des pages par liens entrants</h3>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="flex: 1;">
                <div style="display: flex; height: 30px; border-radius: 4px; overflow: hidden; background: #f0f0f1;">
                    <?php if ( $wpcp_inc_pct_with > 0 ): ?>
                    <div style="width: <?php echo esc_attr( $wpcp_inc_pct_with ); ?>%; background: #46b450; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 12px; min-width: 35px;">
                        <?php echo esc_html( $wpcp_inc_pct_with ); ?>%
                    </div>
                    <?php endif; ?>
                    <?php if ( $wpcp_inc_pct_without > 0 ): ?>
                    <div style="width: <?php echo esc_attr( $wpcp_inc_pct_without ); ?>%; background: #dc3232; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 12px; min-width: 35px;">
                        <?php echo esc_html( $wpcp_inc_pct_without ); ?>%
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Legende -->
                <div style="display: flex; gap: 20px; margin-top: 10px; font-size: 12px; color: #646970;">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #46b450; border-radius: 2px;"></span>
                        Avec liens entrants (<?php echo esc_html( number_format_i18n( $wpcp_inc_with ) ); ?>)
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #dc3232; border-radius: 2px;"></span>
                        Pages orphelines (<?php echo esc_html( number_format_i18n( $wpcp_inc_without ) ); ?>)
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $wpcp_active_tab == 'outbound' ): ?>
        <div style="margin: 20px 0;">
            <button id="olm-start-scan" class="button button-primary">Lancer un nouveau scan des liens sortants</button>
            <button id="olm-purge-and-scan" class="button" style="margin-left: 10px;">Purger et rescanner</button>
            <button id="olm-check-links" class="button button-secondary" style="margin-left: 10px;">
                <span class="dashicons dashicons-admin-links" style="vertical-align: middle; margin-top: 3px;"></span>
                Vérifier les statuts HTTP
            </button>
            <button id="olm-delete-404-301" class="button" style="margin-left: 10px; background: #dc3545; color: white; border-color: #dc3545;">
                <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: 3px;"></span>
                Supprimer les liens cassés
            </button>
            <p class="description" style="margin-top: 5px; margin-bottom: 0;">
                <strong>Purger et rescanner</strong> : Supprime tous les liens de la base de données et relance un scan complet.<br>
                <strong>Vérifier les statuts HTTP</strong> : Vérifie le statut HTTP de chaque lien (200, 301, 404, etc.).<br>
                <strong>Supprimer les liens cassés</strong> : Supprime tous les liens avec erreur (301, 400, 403, 404, 410, 500+).
            </p>
            <div class="olm-scan-progress-bar">
                <div class="olm-scan-progress-value"></div>
            </div>
            <div class="olm-check-progress-bar" style="display: none; width: 100%; background-color: #f0f0f1; height: 20px; border-radius: 3px; margin-top: 10px;">
                <div class="olm-check-progress-value" style="width: 0%; height: 100%; background-color: #2271b1; transition: width 0.3s ease;"></div>
            </div>
        </div>

        <div id="olm-results">
            <form method="post">
                <?php
                // The list table instance is passed from class-admin.php
                if ( isset( $olm_list_table ) ) {
                    $olm_list_table->search_box( 'Rechercher', 'search_id' );
                    echo '<p class="description" style="margin-top: -10px; margin-bottom: 10px;">';
                    echo 'Rechercher par: URL du lien sortant, texte d\'ancre, titre d\'article, ou URL de l\'article (slug)';
                    echo '</p>';
                    $olm_list_table->display();
                }
                ?>
            </form>
        </div>
    <?php elseif ( $wpcp_active_tab == 'internal' ): ?>
        <div style="margin: 20px 0;">
            <button id="olm-start-internal-scan" class="button button-primary">Lancer un nouveau scan des liens internes</button>
            <div class="olm-internal-scan-progress-bar">
                <div class="olm-internal-scan-progress-value"></div>
            </div>
        </div>

        <div id="olm-internal-results">
            <form method="get">
                <input type="hidden" name="page" value="outbound-links-manager" />
                <input type="hidden" name="tab" value="internal" />
                <?php
                // The internal list table instance is passed from class-admin.php
                if ( isset( $olm_internal_list_table ) ) {
                    $olm_internal_list_table->search_box( 'Rechercher', 'search_id' );
                    echo '<p class="description" style="margin-top: -10px; margin-bottom: 10px;">';
                    echo 'Rechercher par: titre d\'article ou URL de l\'article (slug)';
                    echo '</p>';
                    $olm_internal_list_table->display();
                }
                ?>
            </form>
        </div>
    <?php elseif ( $wpcp_active_tab == 'incoming' ): ?>
        <div style="margin: 20px 0;">
            <h2>Liens entrants</h2>
            <p class="description">Identifiez les pages orphelines (sans aucun lien interne pointant vers elles). Ces pages sont difficiles à trouver pour les moteurs de recherche et les visiteurs.</p>
            <p class="description" style="margin-top: 5px;">
                <strong>Note :</strong> Les données proviennent du scan des liens internes. Relancez un scan depuis l'onglet "Liens internes" pour mettre à jour.
            </p>
        </div>

        <div id="olm-incoming-results">
            <form method="get">
                <input type="hidden" name="page" value="outbound-links-manager" />
                <input type="hidden" name="tab" value="incoming" />
                <?php
                if ( isset( $olm_incoming_list_table ) ) {
                    $olm_incoming_list_table->search_box( 'Rechercher', 'search_id' );
                    echo '<p class="description" style="margin-top: -10px; margin-bottom: 10px;">';
                    echo 'Rechercher par: titre d\'article ou URL de l\'article (slug)';
                    echo '</p>';
                    $olm_incoming_list_table->display();
                }
                ?>
            </form>
        </div>
    <?php elseif ( $wpcp_active_tab == 'traffic' ): ?>
        <div style="margin: 20px 0;">
            <h2>Trafic des Pages</h2>
            <p class="description">Consultez les statistiques de trafic de vos pages (Impressions, Clics, Utilisateurs, Sessions).</p>

            <button id="olm-sync-traffic" class="button button-primary">Synchroniser les données de trafic</button>
            <p class="description" style="margin-top: 5px;">
                <strong>Synchroniser</strong> : Récupère les données depuis Google Search Console et Google Analytics.
            </p>
            <div class="olm-traffic-sync-progress-bar" style="display: none; width: 100%; background-color: #f0f0f1; height: 20px; border-radius: 3px; margin-top: 10px;">
                <div class="olm-traffic-sync-progress-value" style="width: 0%; height: 100%; background-color: #2271b1; transition: width 0.3s ease;"></div>
            </div>
        </div>

        <div id="olm-traffic-results">
            <form method="get">
                <input type="hidden" name="page" value="outbound-links-manager" />
                <input type="hidden" name="tab" value="traffic" />
                <?php
                // The traffic list table instance is passed from class-admin.php
                if ( isset( $olm_traffic_list_table ) ) {
                    $olm_traffic_list_table->search_box( 'Rechercher', 'search_id' );
                    echo '<p class="description" style="margin-top: -10px; margin-bottom: 10px;">';
                    echo 'Rechercher par URL ou ID de page';
                    echo '</p>';
                    $olm_traffic_list_table->display();
                }
                ?>
            </form>
        </div>

        <!-- Modale de confirmation de suppression -->
        <div id="olm-confirm-delete-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:100000; align-items:center; justify-content:center;">
            <div style="background:#fff; border-radius:8px; padding:30px; max-width:500px; width:90%; box-shadow:0 4px 20px rgba(0,0,0,0.3); text-align:center;">
                <div style="margin-bottom:20px;">
                    <span class="dashicons dashicons-warning" style="font-size:48px; color:#d63638; width:48px; height:48px;"></span>
                </div>
                <h3 style="margin:0 0 10px; font-size:18px;">Supprimer cette page ?</h3>
                <p style="color:#50575e; margin:0 0 15px; font-size:14px; line-height:1.5;">
                    Cette action va <strong>mettre la page WordPress à la corbeille</strong> et créer une <strong>redirection 301</strong> vers l'URL ci-dessous.
                </p>
                <div style="text-align:left; margin:0 0 20px; padding:15px; background:#f0f0f1; border-radius:4px;">
                    <label for="olm-redirect-url" style="display:block; font-weight:600; margin-bottom:6px; font-size:13px; color:#1d2327;">
                        <span class="dashicons dashicons-randomize" style="font-size:16px; vertical-align:text-bottom;"></span>
                        Rediriger vers :
                    </label>
                    <input type="url" id="olm-redirect-url" value="<?php echo esc_url( home_url( '/' ) ); ?>" style="width:100%; padding:6px 8px; font-size:13px;" placeholder="<?php echo esc_url( home_url( '/' ) ); ?>" />
                    <p style="margin:6px 0 0; font-size:12px; color:#757575;">Par défaut : page d'accueil. Vous pouvez saisir une URL personnalisée.</p>
                </div>
                <div style="display:flex; gap:10px; justify-content:center;">
                    <button id="olm-confirm-delete-cancel" class="button button-large" style="min-width:120px;">Annuler</button>
                    <button id="olm-confirm-delete-ok" class="button button-large" style="min-width:120px; background:#d63638; border-color:#d63638; color:#fff;">Supprimer</button>
                </div>
            </div>
        </div>

    <?php elseif ( $wpcp_active_tab == 'settings' ): ?>
        <div style="margin: 20px 0;">
            <h2>Paramètres du scanner</h2>

            <?php
            // Afficher message de confirmation
            if ( isset( $_GET['settings-updated'] ) ) {
                echo '<div class="notice notice-success is-dismissible"><p>Parametres enregistres avec succes.</p></div>';
            }

            // Messages Google Auth
            if ( isset( $_GET['google_auth'] ) ) {
                $wpcp_google_auth = sanitize_text_field( wp_unslash( $_GET['google_auth'] ) );
                if ( $wpcp_google_auth === 'success' ) {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Connecte a Google avec succes !</strong> Vous pouvez maintenant synchroniser vos donnees dans l\'onglet Trafic Page.</p></div>';
                } elseif ( $wpcp_google_auth === 'error' ) {
                    $wpcp_error_msg = isset( $_GET['error_message'] ) ? sanitize_text_field( wp_unslash( $_GET['error_message'] ) ) : 'Erreur inconnue';
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Erreur de connexion a Google:</strong> ' . esc_html( $wpcp_error_msg ) . '</p></div>';
                }
            }
            ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'olm_save_settings', 'olm_settings_nonce' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="olm_excluded_domains">Domaines exclus</label>
                        </th>
                        <td>
                            <?php
                            $excluded_domains = get_option( 'olm_excluded_domains', "youtube.com\nyoutu.be" );
                            ?>
                            <textarea id="olm_excluded_domains" name="olm_excluded_domains" rows="10" class="large-text code" placeholder="youtube.com&#10;facebook.com&#10;twitter.com"><?php echo esc_textarea( $excluded_domains ); ?></textarea>
                            <p class="description">
                                Entrez un domaine par ligne. Les liens vers ces domaines ne seront pas enregistrés lors du scan.<br>
                                <strong>Exemple:</strong> youtube.com, facebook.com, twitter.com, etc.<br>
                                <strong>Note:</strong> Vous devez relancer un scan après avoir modifié ces paramètres.
                            </p>
                        </td>
                    </tr>
                </table>

                <h3 style="margin-top: 30px;">Configuration API Google</h3>
                <p class="description">Configurez vos identifiants Google pour récupérer les vraies données de Search Console et Analytics.</p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="olm_google_client_id">Client ID</label>
                        </th>
                        <td>
                            <?php
                            $client_id = get_option( 'olm_google_client_id', '' );
                            ?>
                            <input type="text" id="olm_google_client_id" name="olm_google_client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text" />
                            <p class="description">
                                Client ID OAuth 2.0 de votre projet Google Cloud Console
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="olm_google_client_secret">Client Secret</label>
                        </th>
                        <td>
                            <?php
                            $client_secret = get_option( 'olm_google_client_secret', '' );
                            ?>
                            <input type="password" id="olm_google_client_secret" name="olm_google_client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="regular-text" />
                            <p class="description">
                                Client Secret OAuth 2.0 de votre projet Google Cloud Console
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="olm_google_ga4_property_id">Property ID GA4</label>
                        </th>
                        <td>
                            <?php
                            $ga4_property_id = get_option( 'olm_google_ga4_property_id', '' );
                            ?>
                            <input type="text" id="olm_google_ga4_property_id" name="olm_google_ga4_property_id" value="<?php echo esc_attr( $ga4_property_id ); ?>" class="regular-text" placeholder="properties/123456789" />
                            <p class="description">
                                ID de votre propriété Google Analytics 4 (format: properties/123456789)<br>
                                <strong>Optionnel</strong> : Laissez vide si vous voulez uniquement les données Search Console
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="olm_google_search_console_url">URL Search Console</label>
                        </th>
                        <td>
                            <?php
                            $search_console_url = get_option( 'olm_google_search_console_url', '' );
                            ?>
                            <input type="text" id="olm_google_search_console_url" name="olm_google_search_console_url" value="<?php echo esc_attr( $search_console_url ); ?>" class="regular-text" placeholder="https://www.example.com" />
                            <p class="description">
                                URL exacte de votre propriété Search Console (avec ou sans www, exactement comme configuré dans Search Console)<br>
                                <strong>Important:</strong> Cette URL doit correspondre EXACTEMENT à celle de votre propriété dans Google Search Console<br>
                                <strong>Exemple:</strong> https://www.monsite.com OU https://monsite.com (selon votre configuration)
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            Statut de connexion
                        </th>
                        <td>
                            <?php
                            require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-google-api.php';
                            $google_api = new Outbound_Links_Manager_Google_API();

                            if ( $google_api->is_authenticated() ) {
                                echo '<span style="color: #46b450; font-weight: bold;">✓ Connecté à Google</span><br>';
                                echo '<button type="button" id="olm-google-disconnect" class="button" style="margin-top: 10px; margin-right: 10px;">Déconnecter</button>';
                                echo '<button type="button" id="olm-test-google-api" class="button button-secondary" style="margin-top: 10px;">🔍 Tester la connexion API</button>';
                            } elseif ( $google_api->is_configured() ) {
                                echo '<span style="color: #ffb900;">⚠ Configuré mais non authentifié</span><br>';
                                echo '<a href="' . esc_url( $google_api->get_auth_url() ) . '" class="button button-primary" style="margin-top: 10px;">Se connecter à Google</a>';
                            } else {
                                echo '<span style="color: #dc3232;">✗ Non configuré</span><br>';
                                echo '<p class="description">Enregistrez d\'abord vos identifiants ci-dessus, puis connectez-vous à Google.</p>';
                            }
                            ?>
                            <div id="olm-api-test-results" style="display: none; margin-top: 15px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                                <h4 style="margin-top: 0;">Résultats du test API</h4>
                                <div id="olm-api-test-content"></div>
                            </div>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="olm_save_settings" class="button button-primary" value="Enregistrer les paramètres" />
                </p>
            </form>

            <hr style="margin: 30px 0;">

            <h3>Informations</h3>
            <p>Les domaines exclus ne seront pas détectés comme liens sortants. Cette fonctionnalité est utile pour:</p>
            <ul style="list-style: disc; margin-left: 25px;">
                <li>Exclure les plateformes vidéo (YouTube, Vimeo, Dailymotion)</li>
                <li>Exclure les réseaux sociaux (Facebook, Twitter, LinkedIn)</li>
                <li>Exclure tout autre domaine que vous ne souhaitez pas suivre</li>
            </ul>

            <hr style="margin: 30px 0;">

            <h3>Debug d'article</h3>
            <p>Analysez en détail un article spécifique pour voir tous les liens détectés (externes, exclus et internes).</p>
            <div style="margin: 20px 0;">
                <label for="olm-debug-post-id" style="margin-right: 5px; font-weight: bold;">ID de l'article:</label>
                <input type="number" id="olm-debug-post-id" placeholder="ID" style="width: 100px;" />
                <button id="olm-debug-scan" class="button button-secondary" style="margin-left: 10px;">Analyser cet article</button>
                <p class="description" style="margin-top: 10px;">
                    Entrez l'ID d'un article pour voir tous les liens détectés et leur classification (externe, interne, exclu).
                </p>
            </div>

            <!-- Debug Results -->
            <div id="olm-debug-results" style="display: none; margin: 20px 0; background: #f0f0f1; padding: 20px; border-radius: 4px; border-left: 4px solid #2271b1;">
                <h3 style="margin-top: 0;">Résultats du debug</h3>
                <div id="olm-debug-content"></div>
            </div>

            <hr style="margin: 30px 0;">

            <h3>Gestion des commentaires</h3>
            <p>Supprimez tous les commentaires de votre site WordPress en un clic.</p>
            <div style="margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
                <p style="margin: 0 0 10px 0; color: #856404;">
                    <strong>⚠️ ATTENTION :</strong> Cette action est <strong>irréversible</strong>. Tous les commentaires et leurs métadonnées seront définitivement supprimés de la base de données.
                </p>
                <button id="olm-delete-all-comments" class="button button-secondary" style="background: #dc3545; color: white; border-color: #dc3545;">
                    <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: 3px;"></span>
                    Supprimer tous les commentaires
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Modal -->
    <div id="olm-edit-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
        <div style="background:#fff; width:500px; margin:100px auto; padding:20px; box-shadow:0 0 10px rgba(0,0,0,0.5);">
            <h2>Modifier le lien</h2>
            <form id="olm-edit-form">
                <input type="hidden" id="olm-edit-id" name="id" />
                <table class="form-table">
                    <tr>
                        <th>URL</th>
                        <td><input type="text" id="olm-edit-url" name="url" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th>Ancre</th>
                        <td><input type="text" id="olm-edit-anchor" name="anchor" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Target</th>
                        <td>
                            <select id="olm-edit-target" name="target">
                                <option value="">Défaut (_self)</option>
                                <option value="_blank">Nouvel onglet (_blank)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Attributs Hash</th>
                        <td>
                            <label><input type="checkbox" id="olm-edit-nofollow" name="nofollow" value="true" /> nofollow</label>
                            <!-- Add other checkboxes as needed -->
                        </td>
                    </tr>
                </table>
                <div style="margin-top:15px; text-align:right;">
                    <button type="button" class="button" id="olm-modal-cancel">Annuler</button>
                    <button type="submit" class="button button-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Internal Links Modal -->
    <div id="olm-internal-links-modal" class="olm-modal">
        <div class="olm-modal-content">
            <div class="olm-modal-header">
                <h2 id="olm-internal-links-title">Liens internes</h2>
                <button type="button" class="olm-modal-close" id="olm-internal-modal-close">&times;</button>
            </div>
            <div class="olm-modal-body">
                <div id="olm-internal-links-loading" style="text-align:center; padding:30px;">
                    <span class="spinner is-active" style="float:none;"></span>
                    Chargement...
                </div>
                <table id="olm-internal-links-table" class="widefat striped" style="display:none;">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>URL de destination</th>
                            <th>Texte d'ancre</th>
                        </tr>
                    </thead>
                    <tbody id="olm-internal-links-list">
                    </tbody>
                </table>
                <div id="olm-internal-links-empty" style="display:none; text-align:center; padding:30px; color:#666;">
                    Aucun lien interne trouvé.
                </div>
            </div>
            <div class="olm-modal-footer">
                <button type="button" class="button" id="olm-internal-modal-close-btn">Fermer</button>
            </div>
        </div>
    </div>

    <!-- Keywords Accordion Style -->
    <style>
        .olm-keywords-row td {
            padding: 0 !important;
            background: #f9f9f9;
        }
        .olm-keywords-accordion {
            padding: 15px 20px;
            border-top: 2px solid #2271b1;
        }
        .olm-keywords-accordion h4 {
            margin: 0 0 10px;
            font-size: 13px;
            color: #1d2327;
        }
        .olm-keywords-accordion .olm-kw-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 13px;
        }
        .olm-keywords-accordion .olm-kw-table th {
            background: #f0f0f1;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #ccd0d4;
            font-size: 12px;
            color: #1d2327;
        }
        .olm-keywords-accordion .olm-kw-table td {
            padding: 6px 10px !important;
            border-bottom: 1px solid #f0f0f1;
            background: #fff !important;
        }
        .olm-keywords-accordion .olm-kw-table tr:hover td {
            background: #f6f7f7 !important;
        }
        .olm-keywords-accordion .olm-kw-loading {
            text-align: center;
            padding: 20px;
            color: #646970;
        }
        .olm-keywords-accordion .olm-kw-empty {
            text-align: center;
            padding: 20px;
            color: #646970;
            font-style: italic;
        }
        .olm-keywords-accordion .olm-kw-error {
            padding: 10px 15px;
            background: #fcf0f1;
            border-left: 4px solid #dc3232;
            color: #dc3232;
        }
        .olm-toggle-keywords.active {
            background: #2271b1 !important;
            color: #fff !important;
            border-color: #2271b1 !important;
        }
        .olm-toggle-keywords.active .dashicons {
            color: #fff;
        }
    </style>

    <!-- Incoming Links Modal -->
    <div id="olm-incoming-links-modal" class="olm-modal">
        <div class="olm-modal-content">
            <div class="olm-modal-header">
                <h2 id="olm-incoming-links-title">Liens entrants</h2>
                <button type="button" class="olm-modal-close" id="olm-incoming-modal-close">&times;</button>
            </div>
            <div class="olm-modal-body">
                <div id="olm-incoming-links-loading" style="text-align:center; padding:30px;">
                    <span class="spinner is-active" style="float:none;"></span>
                    Chargement...
                </div>
                <table id="olm-incoming-links-table" class="widefat striped" style="display:none;">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Page source</th>
                            <th>Texte d'ancre</th>
                        </tr>
                    </thead>
                    <tbody id="olm-incoming-links-list">
                    </tbody>
                </table>
                <div id="olm-incoming-links-empty" style="display:none; text-align:center; padding:30px; color:#666;">
                    Aucun lien entrant trouvé (page orpheline).
                </div>
            </div>
            <div class="olm-modal-footer">
                <button type="button" class="button" id="olm-incoming-modal-close-btn">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log('Script chargé');

    // Test de disponibilité
    if (typeof olm_ajax === 'undefined') {
        console.error('olm_ajax non défini, création manuelle');
        window.olm_ajax = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('olm_ajax_nonce'); ?>'
        };
    }

    console.log('olm_ajax:', olm_ajax);

    // PURGE AND SCAN
    $('#olm-purge-and-scan').on('click', function (e) {
        e.preventDefault();
        console.log('Bouton purge cliqué');

        if (!confirm('ATTENTION : Cette action va supprimer TOUS les liens de la base de données et relancer un scan complet. Continuer ?')) {
            return;
        }

        var $button = $(this);
        var $scanButton = $('#olm-start-scan');
        var $progress = $('.olm-scan-progress-bar');
        var $progressBar = $('.olm-scan-progress-value');

        $button.prop('disabled', true).text('Purge en cours...');
        $scanButton.prop('disabled', true);
        $progress.show();
        $progressBar.css('width', '0%');

        // Step 1: Purge
        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_purge_links',
                security: olm_ajax.nonce
            },
            success: function (response) {
                console.log('Purge response:', response);
                if (response.success) {
                    console.log('Purge réussie, lancement du scan...');
                    $progressBar.css('width', '5%');
                    $button.text('Scan en cours...');

                    // Step 2: Start scan
                    $.ajax({
                        url: olm_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'olm_start_scan',
                            security: olm_ajax.nonce
                        },
                        success: function (response) {
                            console.log('Start scan response:', response);
                            if (response.success) {
                                processPurgeScanBatch(1, response.data.total_batches);
                            } else {
                                alert('Erreur lors de l\'initialisation du scan : ' + response.data);
                                $button.prop('disabled', false).text('Purger et rescanner');
                                $scanButton.prop('disabled', false);
                            }
                        }
                    });
                } else {
                    console.error('Erreur purge:', response.data);
                    alert('Erreur lors de la purge : ' + response.data);
                    $button.prop('disabled', false).text('Purger et rescanner');
                    $scanButton.prop('disabled', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                console.error('Response:', xhr.responseText);
                alert('Erreur AJAX lors de la purge. Consultez la console pour plus de détails.');
                $button.prop('disabled', false).text('Purger et rescanner');
                $scanButton.prop('disabled', false);
            }
        });

        function processPurgeScanBatch(currentBatch, totalBatches) {
            $.ajax({
                url: olm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'olm_process_scan_batch',
                    security: olm_ajax.nonce,
                    batch: currentBatch
                },
                success: function (response) {
                    var percentage = 5 + ((currentBatch / totalBatches) * 95);
                    $progressBar.css('width', percentage + '%');

                    if (currentBatch < totalBatches) {
                        processPurgeScanBatch(currentBatch + 1, totalBatches);
                    } else {
                        $progressBar.css('width', '100%');
                        alert('Purge et scan terminés !');
                        location.reload();
                    }
                },
                error: function () {
                    alert('Erreur lors du traitement du lot ' + currentBatch);
                }
            });
        }
    });

    // DEBUG POST SCAN
    $('#olm-debug-scan').on('click', function (e) {
        e.preventDefault();
        console.log('Bouton debug cliqué');

        var postId = $('#olm-debug-post-id').val();
        if (!postId) {
            alert('Veuillez entrer un ID d\'article');
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true).text('Analyse...');

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'olm_debug_post_scan',
                security: olm_ajax.nonce,
                post_id: postId
            },
            success: function (response) {
                console.log('Debug response:', response);
                $button.prop('disabled', false).text('Analyser');

                if (response.success) {
                    var data = response.data;
                    var html = '<div style="font-family: monospace; font-size: 13px;">';

                    // Post info
                    html += '<p><strong>Article:</strong> ' + data.post_title + ' (ID: ' + data.post_id + ')</p>';
                    html += '<p><strong>Type:</strong> ' + data.post_type + ' | <strong>Statut:</strong> <span style="color: ' + (data.post_status === 'publish' ? '#0a0' : '#c00') + '; font-weight: bold;">' + data.post_status + '</span> | <strong>Date:</strong> ' + data.post_date + '</p>';
                    html += '<p><strong>Domaine du site:</strong> ' + data.home_host + '</p>';
                    html += '<p><strong>Longueur contenu:</strong> ' + data.content_length + ' caractères</p>';
                    html += '<p><strong>Longueur totale (avec méta):</strong> ' + data.total_content_length + ' caractères</p>';
                    html += '<p><strong>Champs méta:</strong> ' + data.meta_count + ' (' + data.meta_fields.join(', ') + ')</p>';
                    html += '<hr style="margin: 15px 0;">';

                    // Links found
                    html += '<h4 style="margin: 10px 0;">Liens trouvés: ' + data.total_links_found + '</h4>';
                    html += '<p><span style="color: #2271b1; font-weight: bold;">✓ Liens externes: ' + data.external_count + '</span></p>';
                    html += '<p><span style="color: #dc3232; font-weight: bold;">🚫 Liens exclus (domaines blacklistés): ' + data.excluded_count + '</span></p>';
                    html += '<p><span style="color: #888;">✗ Liens internes (ignorés): ' + data.internal_count + '</span></p>';

                    if (data.excluded_domains) {
                        html += '<p style="font-size: 12px; color: #666;"><strong>Domaines exclus configurés:</strong> ' + data.excluded_domains.replace(/\n/g, ', ') + '</p>';
                    }

                    // External links
                    if (data.external_links.length > 0) {
                        html += '<h4 style="margin: 15px 0; color: #2271b1;">Liens externes détectés:</h4>';
                        html += '<ul style="list-style: none; padding: 0;">';
                        data.external_links.forEach(function (link) {
                            html += '<li style="margin-bottom: 8px; padding: 8px; background: #fff; border-left: 3px solid #2271b1;">';
                            html += '<strong>URL:</strong> ' + link.url + '<br>';
                            html += '<strong>Ancre:</strong> ' + link.anchor + '<br>';
                            if (link.attributes.target) html += '<strong>Target:</strong> ' + link.attributes.target + '<br>';
                            if (link.attributes.rel) html += '<strong>Rel:</strong> ' + link.attributes.rel;
                            html += '</li>';
                        });
                        html += '</ul>';
                    }

                    // Excluded links
                    if (data.excluded_links && data.excluded_links.length > 0) {
                        html += '<h4 style="margin: 15px 0; color: #dc3232;">Liens exclus (domaines blacklistés):</h4>';
                        html += '<ul style="list-style: none; padding: 0;">';
                        data.excluded_links.forEach(function (link) {
                            html += '<li style="margin-bottom: 8px; padding: 8px; background: #fff; border-left: 3px solid #dc3232;">';
                            html += '<strong>URL:</strong> ' + link.url + '<br>';
                            html += '<strong>Ancre:</strong> ' + link.anchor + '<br>';
                            html += '<span style="color: #dc3232; font-size: 11px;">⚠ Ce lien ne sera pas enregistré (domaine exclu)</span>';
                            html += '</li>';
                        });
                        html += '</ul>';
                    }

                    // Internal links
                    if (data.internal_links.length > 0) {
                        html += '<h4 style="margin: 15px 0; color: #888;">Liens internes (non enregistrés):</h4>';
                        html += '<ul style="list-style: none; padding: 0;">';
                        data.internal_links.slice(0, 5).forEach(function (link) {
                            html += '<li style="margin-bottom: 8px; padding: 8px; background: #fff; border-left: 3px solid #ccc;">';
                            html += '<strong>URL:</strong> ' + link.url + '<br>';
                            html += '<strong>Ancre:</strong> ' + link.anchor;
                            html += '</li>';
                        });
                        if (data.internal_links.length > 5) {
                            html += '<li style="color: #888;">... et ' + (data.internal_links.length - 5) + ' autres</li>';
                        }
                        html += '</ul>';
                    }

                    html += '</div>';

                    $('#olm-debug-content').html(html);
                    $('#olm-debug-results').slideDown();

                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: $('#olm-debug-results').offset().top - 100
                    }, 500);
                } else {
                    alert('Erreur: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Debug error:', xhr, status, error);
                $button.prop('disabled', false).text('Analyser');
                alert('Erreur AJAX lors du debug.');
            }
        });
    });

    // DELETE ALL COMMENTS
    $('#olm-delete-all-comments').on('click', function (e) {
        e.preventDefault();
        console.log('Bouton suppression commentaires cliqué');

        var confirmText = 'ATTENTION : Cette action va supprimer TOUS les commentaires de votre site de manière IRRÉVERSIBLE.\n\n' +
                         'Êtes-vous ABSOLUMENT SÛR de vouloir continuer ?\n\n' +
                         'Tapez "SUPPRIMER" pour confirmer :';

        var userInput = prompt(confirmText);

        if (userInput !== 'SUPPRIMER') {
            if (userInput !== null) {
                alert('Action annulée. Vous devez taper exactement "SUPPRIMER" pour confirmer.');
            }
            return;
        }

        var $button = $(this);
        var originalHtml = $button.html();

        $button.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin" style="vertical-align: middle; margin-top: 3px;"></span> Suppression en cours...');

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_delete_all_comments',
                security: olm_ajax.nonce
            },
            success: function (response) {
                console.log('Delete comments response:', response);
                $button.prop('disabled', false).html(originalHtml);

                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Erreur: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Delete comments error:', xhr, status, error);
                $button.prop('disabled', false).html(originalHtml);
                alert('Erreur AJAX lors de la suppression des commentaires.');
            }
        });
    });

    // CHECK LINKS HTTP STATUS
    $('#olm-check-links').on('click', function (e) {
        e.preventDefault();
        console.log('Bouton vérification liens cliqué');

        if (!confirm('Cette action va vérifier le statut HTTP de tous les liens sortants. Cela peut prendre plusieurs minutes. Continuer ?')) {
            return;
        }

        var $button = $(this);
        var $progress = $('.olm-check-progress-bar');
        var $progressBar = $('.olm-check-progress-value');

        $button.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin" style="vertical-align: middle; margin-top: 3px;"></span> Vérification en cours...');
        $progress.show();
        $progressBar.css('width', '0%');

        // Start check
        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_start_link_check',
                security: olm_ajax.nonce
            },
            success: function (response) {
                console.log('Start check response:', response);
                if (response.success) {
                    processCheckBatch(1, response.data.total_batches);
                } else {
                    alert('Erreur lors de l\'initialisation de la vérification : ' + response.data);
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-admin-links" style="vertical-align: middle; margin-top: 3px;"></span> Vérifier les statuts HTTP');
                    $progress.hide();
                }
            },
            error: function (xhr, status, error) {
                console.error('Check error:', xhr, status, error);
                alert('Erreur AJAX lors de la vérification.');
                $button.prop('disabled', false).html('<span class="dashicons dashicons-admin-links" style="vertical-align: middle; margin-top: 3px;"></span> Vérifier les statuts HTTP');
                $progress.hide();
            }
        });

        function processCheckBatch(currentBatch, totalBatches) {
            $.ajax({
                url: olm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'olm_process_link_check_batch',
                    security: olm_ajax.nonce,
                    batch: currentBatch
                },
                success: function (response) {
                    var percentage = (currentBatch / totalBatches) * 100;
                    $progressBar.css('width', percentage + '%');

                    if (currentBatch < totalBatches) {
                        processCheckBatch(currentBatch + 1, totalBatches);
                    } else {
                        $progressBar.css('width', '100%');
                        alert('Vérification terminée !');
                        $button.prop('disabled', false).html('<span class="dashicons dashicons-admin-links" style="vertical-align: middle; margin-top: 3px;"></span> Vérifier les statuts HTTP');
                        location.reload();
                    }
                },
                error: function () {
                    alert('Erreur lors de la vérification du lot ' + currentBatch);
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-admin-links" style="vertical-align: middle; margin-top: 3px;"></span> Vérifier les statuts HTTP');
                }
            });
        }
    });

    // DELETE LINKS WITH ERROR STATUS
    $('#olm-delete-404-301').on('click', function (e) {
        e.preventDefault();
        console.log('Bouton suppression liens cassés cliqué');

        var confirmText = 'ATTENTION : Cette action va supprimer définitivement tous les liens avec erreur :\n' +
                         '- 301 (redirection permanente)\n' +
                         '- 400, 401, 403, 404, 410 (erreurs client)\n' +
                         '- 500, 502, 503, 504 (erreurs serveur)\n\n' +
                         'Êtes-vous sûr de vouloir continuer ?\n\n' +
                         'Tapez "SUPPRIMER" pour confirmer :';

        var userInput = prompt(confirmText);

        if (userInput !== 'SUPPRIMER') {
            if (userInput !== null) {
                alert('Action annulée. Vous devez taper exactement "SUPPRIMER" pour confirmer.');
            }
            return;
        }

        var $button = $(this);
        var originalHtml = $button.html();

        $button.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin" style="vertical-align: middle; margin-top: 3px;"></span> Suppression en cours...');

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_delete_links_by_status',
                security: olm_ajax.nonce,
                statuses: [301, 302, 400, 401, 403, 404, 410, 500, 502, 503, 504]
            },
            success: function (response) {
                console.log('Delete by status response:', response);
                $button.prop('disabled', false).html(originalHtml);

                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Erreur: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Delete by status error:', xhr, status, error);
                $button.prop('disabled', false).html(originalHtml);
                alert('Erreur AJAX lors de la suppression.');
            }
        });
    });

    // SYNC TRAFFIC DATA
    $('#olm-sync-traffic').on('click', function (e) {
        e.preventDefault();
        console.log('Bouton synchronisation trafic cliqué');

        if (!confirm('Cette action va synchroniser les données de trafic depuis Google Analytics et Search Console. Continuer ?')) {
            return;
        }

        var $button = $(this);
        var $progress = $('.olm-traffic-sync-progress-bar');
        var $progressBar = $('.olm-traffic-sync-progress-value');

        $button.prop('disabled', true).text('Synchronisation en cours...');
        $progress.show();
        $progressBar.css('width', '0%');

        // Animer la barre de progression
        var progressInterval = setInterval(function() {
            var currentWidth = parseFloat($progressBar.css('width')) / parseFloat($progressBar.parent().css('width')) * 100;
            if (currentWidth < 90) {
                $progressBar.css('width', (currentWidth + 5) + '%');
            }
        }, 200);

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_sync_traffic_data',
                security: olm_ajax.nonce
            },
            success: function (response) {
                console.log('Sync traffic response:', response);
                clearInterval(progressInterval);
                $progressBar.css('width', '100%');

                setTimeout(function() {
                    $button.prop('disabled', false).text('Synchroniser les données de trafic');
                    $progress.hide();

                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Erreur: ' + (response.data ? response.data : 'Erreur inconnue'));
                    }
                }, 500);
            },
            error: function (xhr, status, error) {
                console.error('Sync traffic error:', xhr, status, error);
                clearInterval(progressInterval);
                $button.prop('disabled', false).text('Synchroniser les données de trafic');
                $progress.hide();
                alert('Erreur AJAX lors de la synchronisation.');
            }
        });
    });

    // GOOGLE DISCONNECT
    $('#olm-google-disconnect').on('click', function (e) {
        e.preventDefault();

        if (!confirm('Êtes-vous sûr de vouloir déconnecter votre compte Google ?')) {
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true).text('Déconnexion...');

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_google_disconnect',
                security: olm_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert('Déconnecté de Google avec succès');
                    location.reload();
                } else {
                    alert('Erreur: ' + (response.data ? response.data : 'Erreur inconnue'));
                    $button.prop('disabled', false).text('Déconnecter');
                }
            },
            error: function (xhr, status, error) {
                console.error('Disconnect error:', xhr, status, error);
                alert('Erreur AJAX lors de la déconnexion.');
                $button.prop('disabled', false).text('Déconnecter');
            }
        });
    });

    // TEST GOOGLE API
    $('#olm-test-google-api').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $results = $('#olm-api-test-results');
        var $content = $('#olm-api-test-content');

        $button.prop('disabled', true).text('Test en cours...');
        $results.hide();
        $content.html('');

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_test_google_api',
                security: olm_ajax.nonce
            },
            success: function (response) {
                $button.prop('disabled', false).text('🔍 Tester la connexion API');

                if (response.success) {
                    var data = response.data;
                    var html = '<table style="width: 100%; border-collapse: collapse;">';
                    html += '<tr><td style="padding: 5px; font-weight: bold; width: 250px;">URL du site WordPress:</td><td style="padding: 5px;">' + data.wordpress_site_url + '</td></tr>';
                    html += '<tr><td style="padding: 5px; font-weight: bold;">URL Search Console configurée:</td><td style="padding: 5px;"><strong>' + data.search_console_url_configured + '</strong></td></tr>';

                    if (data.total_urls_gsc) {
                        html += '<tr><td style="padding: 5px; font-weight: bold;">Total URLs trouvées dans GSC:</td><td style="padding: 5px;"><strong style="color: #46b450;">' + data.total_urls_gsc + ' URLs avec trafic</strong></td></tr>';
                    }

                    html += '<tr><td style="padding: 5px; font-weight: bold;">Page testée:</td><td style="padding: 5px;">' + data.page_testee + '</td></tr>';
                    html += '<tr><td style="padding: 5px; font-weight: bold;">URL relative:</td><td style="padding: 5px;">' + data.url_relative + '</td></tr>';
                    html += '<tr><td style="padding: 5px; font-weight: bold;">URL complète:</td><td style="padding: 5px;"><code style="background: #fffbcc; padding: 2px 5px; border-radius: 3px;">' + data.url_complete + '</code></td></tr>';
                    html += '<tr><td style="padding: 5px; font-weight: bold;">Période:</td><td style="padding: 5px;">' + data.periode + ' (' + data.date_debut + ' à ' + data.date_fin + ')</td></tr>';
                    html += '<tr><td colspan="2" style="padding: 15px 5px 5px 5px; font-weight: bold; border-top: 2px solid #ccc;">Résultats pour cette URL:</td></tr>';

                    if (data.erreur) {
                        html += '<tr><td style="padding: 5px; font-weight: bold; color: #dc3232;">Erreur:</td><td style="padding: 5px; color: #dc3232;">' + data.erreur + '</td></tr>';
                    }

                    html += '<tr><td style="padding: 5px; font-weight: bold;">Impressions:</td><td style="padding: 5px;"><strong>' + data.impressions + '</strong></td></tr>';
                    html += '<tr><td style="padding: 5px; font-weight: bold;">Clics:</td><td style="padding: 5px;"><strong>' + data.clicks + '</strong></td></tr>';
                    html += '</table>';

                    if (data.impressions === 0 && data.clicks === 0 && !data.erreur) {
                        html += '<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">';
                        html += '<strong>⚠️ Aucune donnée retournée</strong><br>';
                        html += 'Causes possibles:<br>';
                        html += '• L\'URL du site WordPress ne correspond pas à celle configurée dans Search Console<br>';
                        html += '• La page testée n\'est pas indexée ou n\'a pas reçu de trafic dans les 28 derniers jours<br>';
                        html += '• Délai de données Search Console (2-3 jours)<br>';
                        html += '• Vérifiez que l\'URL "<code>' + data.url_complete + '</code>" correspond exactement à ce qui est dans Search Console';
                        html += '</div>';
                    } else if (data.impressions > 0 || data.clicks > 0) {
                        html += '<div style="margin-top: 15px; padding: 10px; background: #d4edda; border-left: 4px solid #28a745;">';
                        html += '<strong>✅ Connexion API réussie !</strong> Des données ont été récupérées.';
                        html += '</div>';
                    }

                    $content.html(html);
                    $results.show();
                } else {
                    $content.html('<p style="color: #dc3232;">Erreur: ' + (response.data ? response.data : 'Erreur inconnue') + '</p>');
                    $results.show();
                }
            },
            error: function (xhr, status, error) {
                console.error('Test API error:', xhr, status, error);
                $button.prop('disabled', false).text('🔍 Tester la connexion API');
                $content.html('<p style="color: #dc3232;">Erreur AJAX lors du test.</p>');
                $results.show();
            }
        });
    });

    // VIEW INCOMING LINKS (pages linking TO this post)
    $(document).on('click', '.olm-view-incoming-links', function(e) {
        e.preventDefault();
        var postId = $(this).data('post-id');
        var postTitle = $(this).data('post-title');

        $('#olm-incoming-links-title').text('Liens entrants vers : ' + postTitle);
        $('#olm-incoming-links-loading').show();
        $('#olm-incoming-links-table').hide();
        $('#olm-incoming-links-empty').hide();
        $('#olm-incoming-links-modal').css('display', 'flex');

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'olm_get_incoming_links',
                security: olm_ajax.nonce,
                post_id: postId
            },
            success: function(response) {
                $('#olm-incoming-links-loading').hide();
                if (response.success && response.data.links.length > 0) {
                    var html = '';
                    response.data.links.forEach(function(link, index) {
                        var editUrl = '<?php echo admin_url( 'post.php?action=edit&post=' ); ?>' + link.source_post_id;
                        html += '<tr>';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td><a href="' + editUrl + '" target="_blank">' + (link.source_title || 'Post #' + link.source_post_id) + '</a></td>';
                        html += '<td>' + (link.anchor_text || '<em>sans ancre</em>') + '</td>';
                        html += '</tr>';
                    });
                    $('#olm-incoming-links-list').html(html);
                    $('#olm-incoming-links-table').show();
                } else {
                    $('#olm-incoming-links-empty').show();
                }
            },
            error: function() {
                $('#olm-incoming-links-loading').hide();
                $('#olm-incoming-links-empty').text('Erreur lors du chargement.').show();
            }
        });
    });

    // Close incoming links modal
    $('#olm-incoming-modal-close, #olm-incoming-modal-close-btn').on('click', function() {
        $('#olm-incoming-links-modal').hide();
    });
    $('#olm-incoming-links-modal').on('click', function(e) {
        if ($(e.target).is('#olm-incoming-links-modal')) {
            $(this).hide();
        }
    });

    // KEYWORDS ACCORDION (Incoming Links tab)
    $(document).on('click', '.olm-toggle-keywords', function(e) {
        e.preventDefault();
        var $button = $(this);
        var postId = $button.data('post-id');
        var postTitle = $button.data('post-title');
        var $row = $button.closest('tr');
        var $existingAccordion = $row.next('.olm-keywords-row');
        var colCount = $row.find('td').length;

        // Toggle: if accordion already open, close it
        if ($existingAccordion.length) {
            $existingAccordion.remove();
            $button.removeClass('active');
            return;
        }

        // Close any other open accordion
        $('.olm-keywords-row').remove();
        $('.olm-toggle-keywords').removeClass('active');

        // Mark this button as active
        $button.addClass('active');

        // Insert accordion row
        var $accordionRow = $('<tr class="olm-keywords-row"><td colspan="' + colCount + '">' +
            '<div class="olm-keywords-accordion">' +
            '<h4><span class="dashicons dashicons-search" style="font-size:16px;vertical-align:text-bottom;margin-right:5px;"></span>Mots-cl\u00e9s Search Console : ' + $('<span>').text(postTitle).html() + '</h4>' +
            '<div class="olm-kw-loading"><span class="spinner is-active" style="float:none;"></span> Chargement des mots-cl\u00e9s...</div>' +
            '</div></td></tr>');
        $row.after($accordionRow);

        // AJAX call to fetch keywords
        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'olm_get_page_keywords',
                security: olm_ajax.nonce,
                post_id: postId
            },
            success: function(response) {
                var $accordion = $accordionRow.find('.olm-keywords-accordion');
                $accordion.find('.olm-kw-loading').remove();

                if (response.success) {
                    var keywords = response.data.keywords;
                    if (keywords.length === 0) {
                        $accordion.append('<div class="olm-kw-empty">Aucun mot-cl\u00e9 trouv\u00e9 pour cette page dans Search Console (28 derniers jours).</div>');
                        return;
                    }

                    var html = '<table class="olm-kw-table">';
                    html += '<thead><tr>';
                    html += '<th style="width:40px;">#</th>';
                    html += '<th>Mot-cl\u00e9</th>';
                    html += '<th style="width:80px;text-align:center;">Clics</th>';
                    html += '<th style="width:100px;text-align:center;">Impressions</th>';
                    html += '<th style="width:70px;text-align:center;">CTR</th>';
                    html += '<th style="width:80px;text-align:center;">Position</th>';
                    html += '</tr></thead><tbody>';

                    keywords.forEach(function(kw, index) {
                        var posColor = kw.position <= 3 ? '#46b450' : (kw.position <= 10 ? '#2271b1' : (kw.position <= 20 ? '#dba617' : '#dc3232'));
                        html += '<tr>';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td><strong>' + $('<span>').text(kw.query).html() + '</strong></td>';
                        html += '<td style="text-align:center;">' + kw.clicks + '</td>';
                        html += '<td style="text-align:center;">' + kw.impressions + '</td>';
                        html += '<td style="text-align:center;">' + kw.ctr + '%</td>';
                        html += '<td style="text-align:center;"><span style="color:' + posColor + ';font-weight:600;">' + kw.position + '</span></td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table>';
                    html += '<p style="margin:8px 0 0;font-size:11px;color:#646970;">Donn\u00e9es des 28 derniers jours \u2014 URL : ' + $('<span>').text(response.data.url).html() + '</p>';
                    $accordion.append(html);
                } else {
                    $accordion.append('<div class="olm-kw-error">' + (response.data || 'Erreur inconnue') + '</div>');
                }
            },
            error: function() {
                var $accordion = $accordionRow.find('.olm-keywords-accordion');
                $accordion.find('.olm-kw-loading').remove();
                $accordion.append('<div class="olm-kw-error">Erreur de connexion lors du chargement des mots-cl\u00e9s.</div>');
            }
        });
    });
});
</script>
