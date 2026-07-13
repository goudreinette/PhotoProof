<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Template standalone de la galerie PhotoProof
 * Hero header avec première photo en fond + grille 5 colonnes
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file, variables are locally scoped
global $post, $wpdb;

// ── 1. STATUT & BANNIÈRE ADMIN ───────────────────────────────────────────
// L'accès est déjà vérifié par PhotoProof_Expiration::check_gallery_expiration()
// sur template_redirect — ici on récupère juste le statut pour la logique du template
$row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    "SELECT status FROM {$wpdb->prefix}photoproof_galleries WHERE post_id = %d",
    $post->ID
) );

$is_admin_user   = current_user_can( 'manage_options' );
$pp_admin_notice = PhotoProof_Expiration::get_admin_notice( $post->ID );

// ── 2. RÉGLAGES ───────────────────────────────────────────────────────────
$reco_enabled   = get_option( 'photoproof_enable_recommendations' );
$reco_icon_type = get_option( 'photoproof_global_recommendation_icon', 'star' );
$icons          = array( 'dot' => '●', 'star' => '★', 'heart' => '❤', 'square' => '◆' );
$icon           = isset( $icons[ $reco_icon_type ] ) ? $icons[ $reco_icon_type ] : '★';

$selected_photos = get_post_meta( $post->ID, '_photoproof_selected_photos', true );
$selected_ids    = is_array( $selected_photos ) ? array_map( 'intval', $selected_photos ) : array();
$count_selected  = count( $selected_ids );

$custom_logo_id = get_option( 'photoproof_custom_logo' );
$site_title     = get_option( 'photoproof_custom_title', get_bloginfo( 'name' ) );

// ── 3. PHOTOS ─────────────────────────────────────────────────────────────
$query_images = new WP_Query( array(
    'post_type'      => 'attachment',
    'post_mime_type' => 'image',
    'post_status'    => 'inherit',
    'posts_per_page' => -1,
    'post_parent'    => $post->ID,
    'orderby'        => 'menu_order date',
    'order'          => 'ASC',
) );

$attachment_ids = get_post_meta($post->ID, '_photoproof_gallery_photos', true );
$attachments = [];

foreach ($attachment_ids as $id) {
    array_push($attachments, get_post($id));
}

// ── 4. HERO — post thumbnail ──────────────────────────────────────────────
$hero_url = '';
if ( has_post_thumbnail( $post->ID ) ) {
    $hero_url = get_the_post_thumbnail_url( $post->ID, 'full' );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php the_title(); ?> — <?php echo esc_html( $site_title ); ?></title>
    
    <meta property="og:image" content="<?= get_the_post_thumbnail_url( $post->ID, 'large' ) ?>" />
    
    <?php wp_head(); ?>
</head>
<body class="pp-standalone">
<?php wp_body_open(); ?>

<?php if ( $pp_admin_notice ) : ?>
<div id="pp-admin-banner" style="position:fixed; top:32px; left:0; right:0; z-index:99999; background:#ffb900; color:#1e1e1e; padding:10px 20px; text-align:center; font-size:13px; font-weight:500; border-bottom:2px solid #e09800;">
    <?php echo wp_kses( $pp_admin_notice, array( 'strong' => array() ) ); ?>
</div>
<?php endif; ?>

<div class="pp-page" id="pp-page">

    <!-- ── HERO HEADER ── -->
    <header class="pp-hero" <?php if ( $hero_url ) : ?>style="--pp-hero-img: url('<?php echo esc_url( $hero_url ); ?>');"<?php endif; ?>>

        <?php if ( $hero_url ) : ?>
            <div class="pp-hero-bg"></div>
        <?php endif; ?>

        <div class="pp-hero-gradient"></div>

        <div class="pp-hero-content">
            <div class="pp-hero-left">
                <?php if ( $custom_logo_id || $site_title ) : ?>
                <div class="pp-logo-wrap">
                    <a href="<?php echo esc_url( get_home_url() ); ?>">
                        <?php if ( $custom_logo_id ) : ?>
                            <?php echo wp_get_attachment_image( $custom_logo_id, 'medium', false, array( 'class' => 'pp-logo-img' ) ); ?>
                        <?php endif; ?>
                    </a>
                </div>
                <?php endif; ?>

                <div class="pp-hero-text">
                    <?php if ( $site_title ) : ?>
                        <span class="pp-site-name"><?php echo esc_html( $site_title ); ?></span>
                    <?php endif; ?>
                    <h1 class="pp-gallery-title"><?php the_title(); ?></h1>
                </div>
            </div>

            <div class="pp-hero-right">
                <?php
                /* translators: %d: number of photographs in the gallery */
                printf( esc_html( _n( '%d photograph', '%d photographs', $query_images->found_posts, 'photoproof' ) ), absint( $query_images->found_posts ) ); ?>
            </div>
        </div>
    </header>

    <?php if ( get_the_content() ) : ?>
    <div class="pp-content-section">
        <p><?php the_content(); ?></p>
    </div>
    <?php endif; ?>

    <?php if ( $row && $row->status === 'valide' ) : ?>
    <div id="pp-locked-banner" class="pp-locked-banner">
        <span class="pp-locked-icon">✓</span>
        <?php esc_html_e( 'Selection confirmed — please contact your photographer for any modification.', 'photoproof' ); ?>

    </div>
    <?php endif; ?>

    <!-- ── GRILLE ── -->
    <?php if ( $query_images->have_posts() ) : ?>
    <?php
        // Watermark actif pour CETTE galerie ? (lu une seule fois avant la boucle)
        $watermark_active = PhotoProof_Watermark::is_watermark_active_for_gallery( $post->ID );
         $comments_enabled = (bool) get_option( 'photoproof_enable_comments' );

    ?>
<?php $pp_layout = get_option( 'photoproof_gallery_layout', 'grid' ); ?>
    <div class="pp-grid pp-layout-<?php echo esc_attr( $pp_layout ); ?>" id="pp-grid">
        <?php if ( 'masonry' === $pp_layout ) : ?>
            <div class="pp-column-sizer" aria-hidden="true"></div>
            <div class="pp-gutter-sizer" aria-hidden="true"></div>
        <?php endif; ?>
        <?php foreach ($attachments as $attachment):
            $img_id      = $attachment->ID;
            $is_reco     = get_post_meta( $img_id, '_photoproof_recommended', true );
            $is_selected = in_array( $img_id, $selected_ids, true );
            
            $img_atts = wp_get_attachment_image_src($img_id, 'full');

            $img_src       = $watermark_active
                ? PhotoProof_Watermark::get_watermarked_url( $img_id )
                : wp_get_attachment_image_url( $img_id, 'large' );
            $img_srcset    = $watermark_active ? '' : wp_get_attachment_image_srcset( $img_id, 'large' );
            $img_full      = $watermark_active
                ? PhotoProof_Watermark::get_watermarked_url( $img_id )
                : wp_get_attachment_url( $img_id );
            $img_title     = $attachment->post_title;
            $filename      = get_post_meta( $img_id, '_photoproof_target_filename', true ) ?: basename( get_attached_file( $img_id ) );
            ?>


<?php
                $existing_comment = $comments_enabled
                    ? trim( (string) get_post_meta( $img_id, '_photoproof_comment', true ) )
                    : '';
            ?>
            <div class="pp-card <?php echo esc_attr( $is_selected ? 'pp-selected' : '' ); ?>"
                 data-id="<?php echo esc_attr( $img_id ); ?>">

                <div class="pp-card-flipper">

                    <!-- ─── RECTO ─── -->
                    <div class="pp-card-front">
                        <a class="pp-card-img-wrap"
                            href="<?php echo esc_url( $img_full ); ?>"
                            onmouseover="preFetchImage(this)"
                            data-src="<?php echo esc_url( $img_full ); ?>"
                            data-pswp-width="<?= $img_atts[1] ?>" 
                            data-pswp-height="<?= $img_atts[2] ?>" 
                             data-full="<?php echo esc_url( $img_full ); ?>">
                            <img
                                class="pp-card-img"
                                
                                src="<?php echo esc_url( $img_src ); ?>"
                                <?php if ( $img_srcset ) : ?>
                                srcset="<?php echo esc_attr( $img_srcset ); ?>"
                                sizes="20vw"
                                <?php endif; ?>
                                alt="<?php echo esc_attr( $img_title ); ?>"
                                loading="lazy"
                                decoding="async">

                            <?php if ( $reco_enabled && $is_reco ) : ?>
                                <div class="pp-reco-badge">
                                    <?php //echo esc_html( $icon ); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fdfdfd" viewBox="0 0 256 256"><path d="M229.06,108.79l-48.7,42,14.88,62.79a8.4,8.4,0,0,1-12.52,9.17L128,189.09,73.28,222.74a8.4,8.4,0,0,1-12.52-9.17l14.88-62.79-48.7-42A8.46,8.46,0,0,1,31.73,94L95.64,88.8l24.62-59.6a8.36,8.36,0,0,1,15.48,0l24.62,59.6L224.27,94A8.46,8.46,0,0,1,229.06,108.79Z" opacity="0.2"></path><path d="M239.18,97.26A16.38,16.38,0,0,0,224.92,86l-59-4.76L143.14,26.15a16.36,16.36,0,0,0-30.27,0L90.11,81.23,31.08,86a16.46,16.46,0,0,0-9.37,28.86l45,38.83L53,211.75a16.38,16.38,0,0,0,24.5,17.82L128,198.49l50.53,31.08A16.4,16.4,0,0,0,203,211.75l-13.76-58.07,45-38.83A16.43,16.43,0,0,0,239.18,97.26Zm-15.34,5.47-48.7,42a8,8,0,0,0-2.56,7.91l14.88,62.8a.37.37,0,0,1-.17.48c-.18.14-.23.11-.38,0l-54.72-33.65a8,8,0,0,0-8.38,0L69.09,215.94c-.15.09-.19.12-.38,0a.37.37,0,0,1-.17-.48l14.88-62.8a8,8,0,0,0-2.56-7.91l-48.7-42c-.12-.1-.23-.19-.13-.5s.18-.27.33-.29l63.92-5.16A8,8,0,0,0,103,91.86l24.62-59.61c.08-.17.11-.25.35-.25s.27.08.35.25L153,91.86a8,8,0,0,0,6.75,4.92l63.92,5.16c.15,0,.24,0,.33.29S224,102.63,223.84,102.73Z"></path></svg>
                                </div>
                            <?php endif; ?>
                        </a>

                        <div class="pp-card-footer">
                            <span class="pp-card-name"><?php echo esc_html( $filename ); ?></span>

<?php if ( $comments_enabled ) : ?>
                                <button class="pp-comment-btn <?php echo esc_attr( $existing_comment ? 'pp-has-comment' : '' ); ?>" type="button"
                                    data-id="<?php echo esc_attr( $img_id ); ?>"
                                    data-comment="<?php echo esc_attr( $existing_comment ); ?>"
                                    aria-label="<?php esc_attr_e( 'Add a comment', 'photoproof' ); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M7.9 20L12 16H17C18.6569 16 20 14.6569 20 13V7C20 5.34315 18.6569 4 17 4H7C5.34315 4 4 5.34315 4 7V13C4 14.6569 5.34315 16 7 16V20Z"/>
                                    </svg>
                                </button>
                            <?php endif; ?>

                            <button class="pp-select-btn" type="button"
                                data-id="<?php echo esc_attr( $img_id ); ?>"
                                aria-pressed="<?php echo esc_attr( $is_selected ? 'true' : 'false' ); ?>"
                                aria-label="<?php esc_attr_e( 'Select this photo', 'photoproof' ); ?>">
                                <span class="pp-check-dot"></span>
                            </button>
                        </div>
                    </div><!-- /.pp-card-front -->

                    <!-- ─── VERSO (éditeur de commentaire) ─── -->
                    <?php if ( $comments_enabled ) : ?>
                    <div class="pp-card-back" style="background-image: url('<?php echo esc_url( $img_src ); ?>');">
                        <div class="pp-card-back-overlay"></div>
                        <div class="pp-card-back-content">
                            <label class="pp-back-label"><?php esc_html_e( 'Your note', 'photoproof' ); ?></label>
                            <textarea
                                class="pp-card-comment-input"
                                data-id="<?php echo esc_attr( $img_id ); ?>"
                                maxlength="500"
                                rows="5"
                                placeholder="<?php esc_attr_e( 'Anything to add about this photo?', 'photoproof' ); ?>"
                            ><?php echo esc_textarea( $existing_comment ); ?></textarea>
                            <div class="pp-back-footer">
                                <span class="pp-back-counter"><?php echo esc_html( strlen( $existing_comment ) ); ?>/500</span>
                                <button type="button" class="pp-back-done"><?php esc_html_e( 'Done', 'photoproof' ); ?></button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- /.pp-card-flipper -->

            </div>
        <?php endforeach; wp_reset_postdata(); ?>
    </div>
    <?php else : ?>
        <div class="pp-empty"><p><?php esc_html_e( 'No photos in the gallery yet.', 'photoproof' ); ?></p></div>
    <?php endif; ?>

    <!-- ── BARRE DE SÉLECTION ── -->
    <div class="pp-selection-bar" id="pp-selection-bar">
        <div class="pp-bar-inner">
            <span class="pp-bar-info">
                <strong id="pp-count-display"><?php echo intval( $count_selected ); ?></strong> <?php esc_html_e( 'selected', 'photoproof' ); ?>
            </span>
            <div class="pp-bar-right">
                <span class="pp-save-status" id="pp-save-status"></span>
                <button type="button" class="pp-btn-validate" id="pp-btn-validate"
                    data-confirmed="<?php esc_attr_e( 'Selection confirmed', 'photoproof' ); ?>"
                    <?php echo esc_attr( $count_selected === 0 ? 'disabled' : '' ); ?>>
                    <?php esc_html_e( 'Validate selection', 'photoproof' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ── MODAL CONFIRMATION ── -->
<div class="pp-confirm-overlay" id="pp-confirm-overlay" style="display:none;">
    <div class="pp-confirm-box">
        <div class="pp-confirm-eyebrow"><?php esc_html_e( 'Confirmation', 'photoproof' ); ?></div>
        <h2><?php esc_html_e( 'Validate your selection?', 'photoproof' ); ?></h2>
        <p><?php
        /* translators: %s: number of photographs, wrapped in a <strong> tag */
        echo wp_kses_post( sprintf( __( 'You have selected %s photograph(s). This action is final on your end.', 'photoproof' ), '<strong id="pp-confirm-count">' . intval( $count_selected ) . '</strong>' ) ); ?></p>
        <div class="pp-confirm-actions">
            <button type="button" class="pp-btn-cancel" id="pp-btn-cancel"><?php esc_html_e( 'Cancel', 'photoproof' ); ?></button>
            <button type="button" class="pp-btn-confirm" id="pp-btn-confirm"><?php esc_html_e( 'Confirm', 'photoproof' ); ?></button>
        </div>
    </div>
</div>

    <!-- ── RÉCAP FALLBACK (sans animations) ── -->
<div class="pp-recap-view" id="pp-recap-view" style="display:none;">
    <div class="pp-recap-header">
        <button class="pp-recap-back" id="pp-recap-back" type="button">
            <?php esc_html_e( '← Back to edit', 'photoproof' ); ?>
        </button>
        
        <div class="pp-recap-title-wrap">
            <p class="pp-recap-eyebrow">
                <?php esc_html_e( 'Summary', 'photoproof' ); ?>
            </p>
            <h2 class="pp-recap-title">
                <?php
                /* translators: %s: number of selected photos, wrapped in a <span> tag */
                echo wp_kses_post( sprintf( __( 'Your selection — %s photos', 'photoproof' ), '<span id="pp-recap-count">0</span>' ) ); ?>
            </h2>
        </div>

        <button type="button" class="pp-btn-recap-confirm-top" id="pp-recap-confirm">
            <?php esc_html_e( 'Confirm selection', 'photoproof' ); ?>
        </button>
    </div>
    
    <div class="pp-recap-grid" id="pp-recap-grid"></div>
</div>

    <!-- ── RÉCAP ANIMÉ — éléments statiques clonés par le JS ── -->
    <div id="pp-recap-anim-header" style="display:none;">
        <p class="pp-recap-eyebrow"><?php esc_html_e( 'Summary', 'photoproof' ); ?></p>
    </div>
    <div id="pp-recap-anim-footer" style="display:none;">
        <button class="pp-btn-recap-back" id="pp-recap-anim-back" type="button"><?php esc_html_e( '← Back to edit', 'photoproof' ); ?></button>
        <button class="pp-btn-recap-confirm" id="pp-recap-anim-confirm" type="button"><?php esc_html_e( 'Confirm selection', 'photoproof' ); ?></button>
    </div>
        <!-- ── MODAL FIN ── -->
    <div class="pp-end-overlay" id="pp-end-overlay" style="display:none;">
        <div class="pp-end-box">
            <div class="pp-end-icon">✓</div>
            <h2 class="pp-end-title"><?php esc_html_e( 'Selection confirmed', 'photoproof' ); ?></h2>
            <p class="pp-end-message"><?php esc_html_e( 'Your photographer has been notified. Contact me for any changes in your choice.', 'photoproof' ); ?></p>
            <div class="pp-end-actions">
                <a href="#" class="pp-end-link" id="pp-end-back"><?php esc_html_e( 'Back to gallery', 'photoproof' ); ?></a>
                <a href="<?php echo esc_url( home_url() ); ?>" class="pp-end-link"><?php esc_html_e( 'Home', 'photoproof' ); ?></a>
                <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="pp-end-link pp-end-logout"><?php esc_html_e( 'Log out', 'photoproof' ); ?></a>
            </div>
        </div>
    </div>

    <!-- ── LIGHTBOX ── -->
    <div class="pp-lightbox" id="pp-lightbox" style="display:none;">
        <button class="pp-lb-close" id="pp-lb-close" aria-label="<?php esc_attr_e( 'Close', 'photoproof' ); ?>">×</button>
        <button class="pp-lb-prev" id="pp-lb-prev" aria-label="<?php esc_attr_e( 'Previous', 'photoproof' ); ?>">‹</button>
        <button class="pp-lb-next" id="pp-lb-next" aria-label="<?php esc_attr_e( 'Next', 'photoproof' ); ?>">›</button>
        <div class="pp-lb-img-wrap">
            <img src="" alt="" id="pp-lb-img" class="pp-lb-img">
        </div>
        <div class="pp-lb-footer">
            <span id="pp-lb-counter"></span>
            <button type="button" class="pp-lb-select" id="pp-lb-select"><?php esc_html_e( 'Select', 'photoproof' ); ?></button>
        </div>
    </div>

</div><!-- /#pp-page -->


<!-- PhotoSwipe -->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/photoswipe/5.4.4/photoswipe.min.css" integrity="sha512-LFWtdAXHQuwUGH9cImO9blA3a3GfQNkpF2uRlhaOpSbDevNyK1rmAjs13mtpjvWyi+flP7zYWboqY+8Mkd42xA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<script type="module">
import Lightbox from 'https://cdnjs.cloudflare.com/ajax/libs/photoswipe/5.4.4/photoswipe-lightbox.esm.min.js';

const leftArrowSVGString = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="white" viewBox="0 0 256 256"><path d="M220,128a4,4,0,0,1-4,4H49.66l65.17,65.17a4,4,0,0,1-5.66,5.66l-72-72a4,4,0,0,1,0-5.66l72-72a4,4,0,0,1,5.66,5.66L49.66,124H216A4,4,0,0,1,220,128Z"></path></svg>';

const rightArrowSVGString = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="white" viewBox="0 0 256 256"><path d="M218.83,130.83l-72,72a4,4,0,0,1-5.66-5.66L206.34,132H40a4,4,0,0,1,0-8H206.34L141.17,58.83a4,4,0,0,1,5.66-5.66l72,72A4,4,0,0,1,218.83,130.83Z"></path></svg>';


const lightbox = new Lightbox({
  arrowPrevSVG: leftArrowSVGString,
  arrowNextSVG: rightArrowSVGString,
  closeSVG: '<svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="white" viewBox="0 0 256 256"><path d="M202.83,197.17a4,4,0,0,1-5.66,5.66L128,133.66,58.83,202.83a4,4,0,0,1-5.66-5.66L122.34,128,53.17,58.83a4,4,0,0,1,5.66-5.66L128,122.34l69.17-69.17a4,4,0,1,1,5.66,5.66L133.66,128Z"></path></svg>',
  zoomSVG: '<svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="white" viewBox="0 0 256 256"><path d="M148,112a4,4,0,0,1-4,4H116v28a4,4,0,0,1-8,0V116H80a4,4,0,0,1,0-8h28V80a4,4,0,0,1,8,0v28h28A4,4,0,0,1,148,112Zm78.83,114.83a4,4,0,0,1-5.66,0l-52.7-52.7a84.1,84.1,0,1,1,5.66-5.66l52.7,52.7A4,4,0,0,1,226.83,226.83ZM112,188a76,76,0,1,0-76-76A76.08,76.08,0,0,0,112,188Z"></path></svg>',
  // bgClickAction: 'close',
  gallery: '.pp-grid',
  children: '.pp-card-img-wrap',
  pswpModule: () => import('https://cdnjs.cloudflare.com/ajax/libs/photoswipe/5.4.4/photoswipe.esm.min.js')
});


lightbox.on('uiRegister', function() {
    lightbox.pswp.ui.registerElement({
        name: 'test-button',
        ariaLabel: 'Download',
        order: 9,
        isButton: true,
        html: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 256 256"><path d="M236,136v64a12,12,0,0,1-12,12H32a12,12,0,0,1-12-12V136a12,12,0,0,1,12-12H72a4,4,0,0,1,0,8H32a4,4,0,0,0-4,4v64a4,4,0,0,0,4,4H224a4,4,0,0,0,4-4V136a4,4,0,0,0-4-4H184a4,4,0,0,1,0-8h40A12,12,0,0,1,236,136Zm-110.83-5.17a4,4,0,0,0,5.66,0l48-48a4,4,0,1,0-5.66-5.66L132,118.34V24a4,4,0,0,0-8,0v94.34L82.83,77.17a4,4,0,0,0-5.66,5.66ZM196,168a8,8,0,1,0-8,8A8,8,0,0,0,196,168Z"></path></svg>',
        onClick: (event, el) => {
            const src = lightbox.pswp.currSlide.data.src
            const filename = src.split('/').pop()
            // console.log(event, el)
            // console.log(src)
            // location.assign()
            // window.open(src);
            const link = document.createElement('a');
            //link to the uploaded image
            //  in your local storage
            link.href = src;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            // if ( confirm('Do you want to toggle zoom?') ) {
                // lightbox.pswp.toggleZoom();
            // }
        }
    });
});


lightbox.init();

console.log(Lightbox)
</script>


<script>
  // window.FPConfig = {
    // delay: 0,
    // ignoreKeywords: [],
    // maxRPS: 3,
    // hoverDelay: 0,
  // };
    let preloadedCount = 0
    let alreadyPreLoadedUrls = []
    let currentlyPreloading = 0
    
    function preFetchImage(t) {
        const url = t.href
        
        if (!alreadyPreLoadedUrls.includes(url) && currentlyPreloading < 3) {
            console.log('Preloading', url)
            alreadyPreLoadedUrls.push(url)
            
            currentlyPreloading++
            let img = new Image();
            img.src = url;
            img.onload = () => {
                currentlyPreloading--
                preloadedCount++
                console.log(`Loaded ${url}, ${preloadedCount} images preloaded`)
            }    
        }
    }
  
</script>
<!-- <script defer src="https://cdnjs.cloudflare.com/ajax/libs/flying-pages/2.1.2/flying-pages.js" integrity="sha512-YsfXudALTqNufYRI++n3whGZue1s6ABnFoYue/kJz1yFCHdvBdsmES0k+q5pMVFVU0P22mXqrKfKiQSuHV+3+w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> -->


<style>

    .pswp {
        /* --pswp-bg: var(--pp-bg); */
        --pswp-icon-stroke-width: 1px;
    }
    
    button.pswp__button--test-button {
      /* background: #136912 !important; */
      /* font-size: 20px; */
      /* color: #fff; */
    }
    
    
    .pp-reco-badge {
        /* background: transparent; */
        border-radius: 0;
        background: var(--pp-bg);
        /* border-radius: 0; */
        background: #020202;
        padding: 6px;
    }
    
    
    /* Maybe allow selection still? */
    @media(hover: none) {
        .pp-grid.pp-layout-masonry .pp-card .pp-card-footer {
            display: none !important;
        }    
    }
    
    .pp-content-section {
        padding-top: 0;
        
    }
    
    .pp-hero-content {
        padding-bottom: 24px;
    }
    
    @media (min-width: 768px) {
        .pp-content-section {
            /* font-size: 16px; */
        }
    }
    
    /* .pswp__container {
        transition: transform var(--pswp-transition-duration) ease;
    } */

    .pp-select-btn {
      display: none;
    }
    
    #pp-lb-select {
        display: none;
    }
    
    :root {
        --wp--preset--color--base: var(--pp-bg) !important;
    }
    
    .pp-reco-badge {
        color: white;
        /* background: transparent; */
        /* border-radius: 0; */
    }
    
    #pp-lb-img.loading {
        /* opacity: .5; */
        animation: pulse .5s infinite alternate linear;
    }
    
    @keyframes pulse {
        from {
            opacity: .5;
        }
        
        to {
            opacity: .7;
        }
    }
</style>

<?php wp_footer(); ?>
</body>
</html>