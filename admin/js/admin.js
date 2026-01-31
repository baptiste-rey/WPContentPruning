jQuery(document).ready(function ($) {
    'use strict';

    // MANUAL SCAN
    $('#olm-start-scan').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $progress = $('.olm-scan-progress-bar');
        var $progressBar = $('.olm-scan-progress-value');

        $button.prop('disabled', true);
        $('#olm-purge-and-scan').prop('disabled', true);
        $progress.show();
        $progressBar.css('width', '5%');

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_start_scan',
                security: olm_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    processScanBatch(1, response.data.total_batches);
                } else {
                    alert('Error initializing scan: ' + response.data);
                    $button.prop('disabled', false);
                    $('#olm-purge-and-scan').prop('disabled', false);
                }
            }
        });

        function processScanBatch(currentBatch, totalBatches) {
            $.ajax({
                url: olm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'olm_process_scan_batch',
                    security: olm_ajax.nonce,
                    batch: currentBatch
                },
                success: function (response) {
                    var percentage = (currentBatch / totalBatches) * 100;
                    $progressBar.css('width', percentage + '%');

                    if (currentBatch < totalBatches) {
                        processScanBatch(currentBatch + 1, totalBatches);
                    } else {
                        $progressBar.css('width', '100%');
                        alert('Scan terminé !');
                        location.reload();
                    }
                },
                error: function () {
                    alert('Erreur lors du traitement du lot ' + currentBatch);
                }
            });
        }
    });

    // PURGE AND SCAN
    $('#olm-purge-and-scan').on('click', function (e) {
        e.preventDefault();

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

    // EDIT MODAL
    $(document).on('click', '.olm-edit-link', function (e) {
        e.preventDefault();
        var id = $(this).data('id');

        // Fetch details
        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'olm_get_link',
                security: olm_ajax.nonce,
                id: id
            },
            success: function (response) {
                if (response.success) {
                    var link = response.data;
                    $('#olm-edit-id').val(link.id);
                    $('#olm-edit-url').val(link.url);
                    $('#olm-edit-anchor').val(link.anchor_text);

                    var attrs = {};
                    try {
                        attrs = JSON.parse(link.link_attributes);
                    } catch (e) { }

                    $('#olm-edit-target').val(attrs.target || '');

                    // Checkbox handling for nofollow
                    var rels = (attrs.rel || '').split(' ');
                    $('#olm-edit-nofollow').prop('checked', rels.includes('nofollow'));

                    $('#olm-edit-modal').show();
                } else {
                    alert('Erreur lors de la récupération du lien.');
                }
            }
        });
    });

    $('#olm-modal-cancel').on('click', function () {
        $('#olm-edit-modal').hide();
    });

    $('#olm-edit-form').on('submit', function (e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=olm_update_link&security=' + olm_ajax.nonce,
            success: function (response) {
                if (response.success) {
                    alert('Lien mis à jour avec succès.');
                    $('#olm-edit-modal').hide();
                    location.reload(); // Reload to show changes
                } else {
                    alert('Erreur : ' + response.data);
                }
            }
        });
    });

    // DELETE LINK
    $(document).on('click', '.olm-delete-link', function (e) {
        e.preventDefault();
        console.log('Bouton supprimer cliqué');
        
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce lien ? Le texte de l\'ancre sera conservé.')) {
            console.log('Suppression annulée par l\'utilisateur');
            return;
        }

        var id = $(this).data('id');
        var $button = $(this);

        console.log('ID du lien à supprimer:', id);
        console.log('URL AJAX:', olm_ajax.ajax_url);
        console.log('Nonce:', olm_ajax.nonce);

        $button.prop('disabled', true).text('Suppression...');

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_delete_link',
                security: olm_ajax.nonce,
                id: id
            },
            success: function (response) {
                console.log('Réponse AJAX reçue:', response);
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert('Erreur : ' + response.data);
                    $button.prop('disabled', false).text('Supprimer');
                }
            },
            error: function (xhr, status, error) {
                console.log('Erreur AJAX:', xhr, status, error);
                console.log('Réponse:', xhr.responseText);
                alert('Erreur lors de la suppression du lien. Vérifiez la console.');
                $button.prop('disabled', false).text('Supprimer');
            }
        });
    });

    // INTERNAL LINKS SCAN
    $('#olm-start-internal-scan').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $progress = $('.olm-internal-scan-progress-bar');
        var $progressBar = $('.olm-internal-scan-progress-value');

        $button.prop('disabled', true);
        $progress.show();
        $progressBar.css('width', '5%');

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_start_internal_scan',
                security: olm_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    processInternalScanBatch(1, response.data.total_batches);
                } else {
                    alert('Error initializing internal links scan: ' + response.data);
                    $button.prop('disabled', false);
                }
            }
        });

        function processInternalScanBatch(currentBatch, totalBatches) {
            $.ajax({
                url: olm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'olm_process_internal_scan_batch',
                    security: olm_ajax.nonce,
                    batch: currentBatch
                },
                success: function (response) {
                    var percentage = (currentBatch / totalBatches) * 100;
                    $progressBar.css('width', percentage + '%');

                    if (currentBatch < totalBatches) {
                        processInternalScanBatch(currentBatch + 1, totalBatches);
                    } else {
                        $progressBar.css('width', '100%');
                        alert('Scan des liens internes terminé !');
                        location.reload();
                    }
                },
                error: function () {
                    alert('Erreur lors du traitement du lot ' + currentBatch);
                }
            });
        }
    });

    // VIEW INTERNAL LINKS MODAL
    $(document).on('click', '.olm-view-links', function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Bouton liens internes cliqué');
        
        var postId = $(this).data('post-id');
        var postTitle = $(this).data('post-title');
        
        console.log('Post ID:', postId);
        console.log('Post Title:', postTitle);
        console.log('Modal existe:', $('#olm-internal-links-modal').length);
        
        // Show modal
        $('#olm-internal-links-modal').fadeIn(200);
        $('#olm-internal-links-title').text('Liens internes - ' + postTitle);
        
        // Show loading, hide table
        $('#olm-internal-links-loading').show();
        $('#olm-internal-links-table').hide();
        $('#olm-internal-links-empty').hide();
        $('#olm-internal-links-list').empty();
        
        // Fetch links via AJAX
        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'olm_get_internal_links',
                security: olm_ajax.nonce,
                post_id: postId
            },
            success: function (response) {
                $('#olm-internal-links-loading').hide();
                
                if (response.success && response.data.links && response.data.links.length > 0) {
                    var html = '';
                    $.each(response.data.links, function(index, link) {
                        var anchorText = link.anchor_text ? link.anchor_text : '<span class="empty">(vide)</span>';
                        var anchorClass = link.anchor_text ? '' : ' empty';
                        html += '<tr>';
                        html += '<td class="olm-link-number">' + (index + 1) + '</td>';
                        html += '<td class="olm-link-url"><a href="' + link.target_url + '" target="_blank">' + link.target_url + '</a></td>';
                        html += '<td class="olm-link-anchor' + anchorClass + '">' + anchorText + '</td>';
                        html += '</tr>';
                    });
                    $('#olm-internal-links-list').html(html);
                    $('#olm-internal-links-table').fadeIn(200);
                } else {
                    $('#olm-internal-links-empty').show();
                }
            },
            error: function () {
                $('#olm-internal-links-loading').hide();
                $('#olm-internal-links-empty').text('Erreur lors du chargement des liens.').show();
            }
        });
    });

    // Close internal links modal
    $('#olm-internal-modal-close, #olm-internal-modal-close-btn').on('click', function () {
        $('#olm-internal-links-modal').fadeOut(200);
    });

    // Close modal on backdrop click
    $('#olm-internal-links-modal').on('click', function (e) {
        if (e.target === this) {
            $(this).fadeOut(200);
        }
    });

    // Close modal on ESC key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('#olm-internal-links-modal').fadeOut(200);
            $('#olm-edit-modal').hide();
        }
    });

    // DELETE TRAFFIC PAGE - Ouvrir la modale de confirmation
    var deleteTrafficId = null;
    var $deleteTrafficButton = null;
    var $deleteTrafficRow = null;

    $(document).on('click', '.olm-delete-traffic', function (e) {
        e.preventDefault();
        deleteTrafficId = $(this).data('id');
        $deleteTrafficButton = $(this);
        $deleteTrafficRow = $(this).closest('tr');
        $('#olm-confirm-delete-modal').css('display', 'flex');
    });

    // Annuler la suppression
    $('#olm-confirm-delete-cancel').on('click', function () {
        $('#olm-confirm-delete-modal').hide();
        deleteTrafficId = null;
    });

    // Fermer la modale en cliquant sur le fond
    $('#olm-confirm-delete-modal').on('click', function (e) {
        if (e.target === this) {
            $(this).hide();
            deleteTrafficId = null;
        }
    });

    // Confirmer la suppression
    $('#olm-confirm-delete-ok').on('click', function () {
        var redirectUrl = $('#olm-redirect-url').val().trim();
        $('#olm-confirm-delete-modal').hide();

        if (!deleteTrafficId) return;

        $deleteTrafficButton.prop('disabled', true).text('Suppression...');

        $.ajax({
            url: olm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'olm_delete_traffic_page',
                security: olm_ajax.nonce,
                id: deleteTrafficId,
                redirect_url: redirectUrl
            },
            success: function (response) {
                if (response.success) {
                    $deleteTrafficRow.fadeOut(300, function () {
                        $(this).remove();
                    });
                } else {
                    alert('Erreur : ' + response.data);
                    $deleteTrafficButton.prop('disabled', false).html('<span class="dashicons dashicons-trash" style="font-size: 14px; line-height: 1.8;"></span> Supprimer');
                }
                deleteTrafficId = null;
            },
            error: function () {
                alert('Erreur lors de la suppression.');
                $deleteTrafficButton.prop('disabled', false).html('<span class="dashicons dashicons-trash" style="font-size: 14px; line-height: 1.8;"></span> Supprimer');
                deleteTrafficId = null;
            }
        });
    });

    // DEBUG POST SCAN
    $('#olm-debug-scan').on('click', function (e) {
        e.preventDefault();

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
                    html += '<p><strong>Article:</strong> ' + data.post_title + ' (ID: ' + data.post_id + ', Type: ' + data.post_type + ')</p>';
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

                    // Internal links (shown for info)
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
});
