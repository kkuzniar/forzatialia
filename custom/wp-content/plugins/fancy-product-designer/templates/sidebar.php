<?php
$products_menu = isset($_POST['productsMenu']) ? $_POST['productsMenu'] : 'Products';
$designs_menu = isset($_POST['designsMenu']) ? $_POST['designsMenu'] : 'Designs';
$edit_elements_menu = isset($_POST['editElementsMenu']) ? $_POST['editElementsMenu'] : 'Edit Elements';
$fb_photos_menu = isset($_POST['fbPhotosMenu']) ? $_POST['fbPhotosMenu'] : 'Add Photos From Facebook';
$insta_photos_menu = isset($_POST['instaPhotosMenu']) ? $_POST['instaPhotosMenu'] : 'Add Photos From Instagram';

$edit_elements_headline = isset($_POST['editElementsHeadline']) ? $_POST['editElementsHeadline'] : 'Edit Elements';
$edit_elements_dropdown_none = isset($_POST['editElementsDropdownNone']) ? $_POST['editElementsDropdownNone'] : 'None';

$section_filling = isset($_POST['sectionFilling']) ? $_POST['sectionFilling'] : 'Filling';
$section_fonts_styles = isset($_POST['sectionFontsStyles']) ? $_POST['sectionFontsStyles'] : 'Fonts & Styles';
$section_curved_text = isset($_POST['sectionCurvedText']) ? $_POST['sectionCurvedText'] : 'Curved Text';
$section_helpers = isset($_POST['sectionHelpers']) ? $_POST['sectionHelpers'] : 'Helpers';

$customize_text_align_left = isset($_POST['textAlignLeft']) ? $_POST['textAlignLeft'] : 'Align Left';
$customize_text_align_center = isset($_POST['textAlignCenter']) ? $_POST['textAlignCenter'] : 'Align Center';
$customize_text_align_right = isset($_POST['textAlignRight']) ? $_POST['textAlignRight'] : 'Align Right';
$customize_text_bold = isset($_POST['textBold']) ? $_POST['textBold'] : 'Bold';
$customize_text_italic = isset($_POST['textItalic']) ? $_POST['textItalic'] : 'Italic';

$curved_text_info = isset($_POST['curvedTextInfo']) ? $_POST['curvedTextInfo'] : 'You can only change the text when you switch to normal text.';
$curved_text_spacing = isset($_POST['curvedTextSpacing']) ? $_POST['curvedTextSpacing'] : 'Spacing';
$curved_text_radius = isset($_POST['curvedTextRadius']) ? $_POST['curvedTextRadius'] : 'Radius';
$curved_text_reverse = isset($_POST['curvedTextReverse']) ? $_POST['curvedTextReverse'] : 'Reverse';
$curved_text_toggle = isset($_POST['curvedTextToggle']) ? $_POST['curvedTextToggle'] : 'Switch between curved and normal Text';

$customize_center_h = isset($_POST['centerH']) ? $_POST['centerH'] : 'Center Horizontal';
$customize_center_c = isset($_POST['centerV']) ? $_POST['centerV'] : 'Center Vertical';
$customize_center_move_down = isset($_POST['moveDown']) ? $_POST['moveDown'] : 'Move It Down';
$customize_center_move_up = isset($_POST['moveUp']) ? $_POST['moveUp'] : 'Move It Up';
$customize_reset = isset($_POST['reset']) ? $_POST['reset'] : 'Reset To His Origin';
$customize_center_trash = isset($_POST['trash']) ? $_POST['trash'] : 'Trash';

$fb_photos_headline = isset($_POST['fbPhotosHeadline']) ? $_POST['fbPhotosHeadline'] : 'Facebook Photos';
$fb_select_album = isset($_POST['fbSelectAlbum']) ? $_POST['fbSelectAlbum'] : 'Select an album';

$insta_photos_headline = isset($_POST['instaPhotosHeadline']) ? $_POST['instaPhotosHeadline'] : 'Instagram Photos';
$insta_feed_button = isset($_POST['instaFeedButton']) ? $_POST['instaFeedButton'] : 'My Feed';
$insta_recent_images_button = isset($_POST['instaRecentImagesButton']) ? $_POST['instaRecentImagesButton'] : 'My Recent Images';
$insta_load_next = isset($_POST['instaLoadNext']) ? $_POST['instaLoadNext'] : 'Load next Stack';

?>

<section class="fpd-sidebar fpd-border-color fpd-secondary-bg-color fpd-clearfix">

	<!-- Navigation -->
	<div class="fpd-navigation fpd-primary-bg-color">
		<a class="fpd-nav-item fpd-secondary-text-color fpd-tooltip" data-target=".fpd-products" title="<?php echo $products_menu; ?>"><i class="fa fa-book"></i></a>
		<a class="fpd-nav-item fpd-secondary-text-color fpd-tooltip" data-target=".fpd-designs" title="<?php echo $designs_menu; ?>"><i class="fa fa-folder-open"></i></a>
		<a class="fpd-nav-item fpd-secondary-text-color fpd-tooltip" data-target=".fpd-edit-elements" title="<?php echo $edit_elements_menu; ?>"><i class="fa fa-edit"></i></a>
		<a class="fpd-nav-item fpd-secondary-text-color fpd-tooltip" data-target=".fpd-fb-user-photos" title="<?php echo $fb_photos_menu; ?>"><i class="fa fa-facebook"></i></a>
		<a class="fpd-nav-item fpd-secondary-text-color fpd-tooltip" data-target=".fpd-instagram-photos" title="<?php echo $insta_photos_menu; ?>"><i class="fa fa-instagram"></i></a>
	</div>

	<!-- Content -->
	<div class="fpd-content fpd-primary-text-color">
		<!-- Products -->
		<div class="fpd-products">
			<div class="fpd-items-wrapper fpd-border-color fpd-clearfix"></div>
		</div>
		<!-- Designs -->
		<div class="fpd-designs">
			<div class="fpd-items-wrapper fpd-border-color fpd-clearfix"></div>
		</div>
		<!-- Edit elements -->
		<div class="fpd-edit-elements">
			<div class="fpd-content-head">
				<h3><?php echo $edit_elements_headline; ?></h3>
				<div class="fpd-elements-dropdown-wrapper">
					<select class="fpd-elements-dropdown">
						<option value="none"><?php echo $edit_elements_dropdown_none; ?></option>
					</select>
				</div>
			</div>
			<!-- Toolbar -->
			<div class="fpd-toolbar fpd-clearfix">
				<div class="fpd-filling-wrapper">
					<h5><?php echo $section_filling; ?></h5>
					<div class="fpd-color-picker fpd-border-color">
						<input type="text" value="">
					</div>
					<div class="fpd-patterns-wrapper fpd-border-color">
						<div class="fpd-pattern-items fpd-clearfix"></div>
					</div>
				</div>
				<div class="fpd-text-format-section">
					<h5><?php echo $section_fonts_styles; ?></h5>
					<div>
						<textarea class="fpd-border-color"></textarea>
					</div>
					<div class="fpd-fonts-dropdown-wrapper">
						<select class="fpd-fonts-dropdown"></select>
					</div>
					<div class="fpd-text-styles">
						<span title="<?php echo $customize_text_align_left; ?>" class="fpd-align-left fpd-button fpd-tooltip"><i class="fa fa-align-left"></i></span>
						<span title="<?php echo $customize_text_align_center; ?>" class="fpd-align-center fpd-button fpd-tooltip"><i class="fa fa-align-center"></i></span>
						<span title="<?php echo $customize_text_align_right; ?>" class="fpd-align-right fpd-button fpd-tooltip"><i class="fa fa-align-right"></i></span>
						<span title="<?php echo $customize_text_bold; ?>" class="fpd-bold fpd-button fpd-tooltip"><i class="fa fa-bold"></i></span>
						<span title="<?php echo $customize_text_italic; ?>" class="fpd-italic fpd-button fpd-tooltip"><i class="fa fa-italic"></i></span>
					</div>
				</div>
				<div class="fpd-curved-text-wrapper">
					<h5><?php echo $section_curved_text; ?> <i class="fa fa-question-circle fpd-tooltip fpd-help-icon" title="<?php echo $curved_text_info; ?>"></i></h5>
					<div>
						<span title="<?php echo $curved_text_toggle; ?>" class="fpd-curve-toggle fpd-button fpd-button-submit fpd-tooltip"><i class="fa fa-check"></i></span>
						<span title="<?php echo $curved_text_reverse; ?>" class="fpd-curve-reverse fpd-curve-toggle-item fpd-button fpd-tooltip"><i class="fa fa-text-width"></i></span>
					</div>
					<div class="fpd-curve-toggle-item">
						<div class="fpd-clearfix fpd-input-group">
							<label><?php echo $curved_text_radius; ?></label>
							<input type="range" min="0" max="200" value="100" class="fpd-curved-text-radius" />
						</div>
						<div class="fpd-clearfix fpd-input-group">
							<label><?php echo $curved_text_spacing; ?></label>
							<input type="range" min="0" max="100" value="50" class="fpd-curved-text-spacing" />
						</div>
					</div>
				</div>
				<div class="fpd-helper-buttons-wrapper">
					<h5><?php echo $section_helpers; ?></h5>
					<span title="<?php echo $customize_center_h; ?>" class="fpd-center-horizontal fpd-button fpd-tooltip"><i class="fa fa-arrows-h"></i></span>
					<span title="<?php echo $customize_center_c; ?>" class="fpd-center-vertical fpd-button fpd-tooltip"><i class="fa fa-arrows-v"></i></span>
					<span title="<?php echo $customize_center_move_down; ?>" class="fpd-move-down fpd-button fpd-tooltip">
						<svg width="14px" height="14px" viewBox="0 0 128 128" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
						    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
						        <g id="128" sketch:type="MSArtboardGroup" fill="#000000">
						            <g id="bring-down" sketch:type="MSLayerGroup" transform="translate(64.000000, 64.000000) scale(1, -1) translate(-64.000000, -64.000000) ">
						                <path d="M0.178710937,-0.165039062 L0.178710937,20.1124808 L128.495657,20.1124808 L128.495657,-0.0366981049 L0.178710937,-0.165039062 Z" id="Path-1" sketch:type="MSShapeGroup"></path>
						                <path d="M63.3711191,27 L36,55 L91.0019531,55 L63.3711191,27 Z" id="Path-2" sketch:type="MSShapeGroup"></path>
						                <rect id="Rectangle-1" sketch:type="MSShapeGroup" x="54" y="48" width="20.3852293" height="80"></rect>
						            </g>
						        </g>
						    </g>
						</svg>
					</span>
					<span title="<?php echo $customize_center_move_up; ?>" class="fpd-move-up fpd-button fpd-tooltip">
						<svg class="fpd-move-up" width="14px" height="14px" viewBox="0 0 128 128" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
						  <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
					        <g id="128" sketch:type="MSArtboardGroup" fill="#000000">
					            <g id="move-up" sketch:type="MSLayerGroup">
					                <path d="M0.178710937,-0.165039062 L0.178710937,20.1124808 L128.495657,20.1124808 L128.495657,-0.0366981049 L0.178710937,-0.165039062 Z" id="Path-1" sketch:type="MSShapeGroup"></path>
					                <path d="M63.3711191,27 L36,55 L91.0019531,55 L63.3711191,27 Z" id="Path-2" sketch:type="MSShapeGroup"></path>
					                <rect id="Rectangle-1" sketch:type="MSShapeGroup" x="54" y="48" width="20.3852293" height="80"></rect>
					            </g>
					        </g>
					    </g>
						</svg>
					</span>
					<span title="<?php echo $customize_reset; ?>" class="fpd-reset fpd-button fpd-tooltip"><i class="fa fa-refresh"></i></span>
					<span title="<?php echo $customize_center_trash; ?>" class="fpd-trash fpd-button fpd-button-danger fpd-tooltip"><i class="fa fa-trash-o"></i></span>
				</div>
			</div>
		</div>
		<!-- Facebook User Photos -->
		<div class="fpd-fb-user-photos">
			<div class="fpd-fb-head fpd-content-head fpd-clearfix">
				<h3><?php echo $fb_photos_headline; ?></h3>
				<div class="fpd-clearfix">
					<div class="fpd-fb-loader fpd-clearfix">
						<fb:login-button data-max-rows="1" data-show-faces="false" data-scope="user_photos" autologoutlink="true"></fb:login-button>
						<span class="fpd-loading"></span>
					</div>
					<select class="fpd-fb-user-albums">
						<option selected="selected" disabled="disabled"><?php echo $fb_select_album; ?></option>
					</select>
				</div>
			</div>
			<div class="fpd-items-wrapper fpd-border-color fpd-clearfix"></div>
		</div>
		<!-- Facebook User Photos -->
		<div class="fpd-instagram-photos">
			<div class="fpd-insta-head fpd-content-head">
				<h3><?php echo $insta_photos_headline; ?></h3>
				<div>
					<span class="fpd-insta-feed fpd-button-submit fpd-button fpd-submit"><?php echo $insta_feed_button; ?></span>
					<span class="fpd-insta-recent-images fpd-button-submit fpd-button fpd-submit"><?php echo $insta_recent_images_button; ?></span>
					<p class="fpd-insta-load-next disabled"><?php echo $insta_load_next; ?></p>
				</div>
			</div>
			<div class="fpd-items-wrapper fpd-border-color fpd-clearfix"></div>
		</div>
	</div>
</section>