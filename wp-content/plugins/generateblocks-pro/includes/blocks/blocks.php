<?php
/**
 * Register our blocks.
 *
 * @package GenerateBlocks Pro
 */

add_action( 'init', 'generateblocks_pro_register_blocks' );
/**
 * Register the GenerateBlocks Pro blocks.
 *
 * @since 1.0.0
 */
function generateblocks_pro_register_blocks() {
	if ( ! class_exists( 'GenerateBlocks_Block' ) ) {
		return;
	}

	require_once 'accordion/accordion.php';
	require_once 'tabs/tabs.php';

	register_block_type_from_metadata(
		GENERATEBLOCKS_PRO_DIR . '/dist/blocks/accordion',
		[
			'render_callback' => 'GenerateBlocks_Block_Accordion::render_block',
		]
	);

	register_block_type_from_metadata(
		GENERATEBLOCKS_PRO_DIR . '/dist/blocks/accordion-item',
		[
			'render_callback' => 'GenerateBlocks_Block_Accordion_Item::render_block',
		]
	);

	register_block_type_from_metadata(
		GENERATEBLOCKS_PRO_DIR . '/dist/blocks/accordion-toggle',
		[
			'render_callback' => 'GenerateBlocks_Block_Accordion_Toggle::render_block',
		]
	);

	register_block_type_from_metadata(
		GENERATEBLOCKS_PRO_DIR . '/dist/blocks/accordion-toggle-icon',
		[
			'render_callback' => 'GenerateBlocks_Block_Accordion_Toggle_Icon::render_block',
		]
	);

	register_block_type_from_metadata(
		GENERATEBLOCKS_PRO_DIR . '/dist/blocks/accordion-content',
		[
			'render_callback' => 'GenerateBlocks_Block_Accordion_Content::render_block',
		]
	);

	register_block_type_from_metadata(
		GENERATEBLOCKS_PRO_DIR . '/dist/blocks/tabs',
		[
			'render_callback' => 'GenerateBlocks_Block_Tabs::render_block',
		]
	);

	register_block_type_from_metadata(
		GENERATEBLOCKS_PRO_DIR . '/dist/blocks/tabs-menu',
		[
			'render_callback' => 'GenerateBlocks_Block_Tabs_Menu::render_block',
		]
	);

	register_block_type_from_metadata(
		GENERATEBLOCKS_PRO_DIR . '/dist/blocks/tab-menu-item',
		[
			'render_callback' => 'GenerateBlocks_Block_Tab_Menu_Item::render_block',
		]
	);

	register_block_type_from_metadata(
		GENERATEBLOCKS_PRO_DIR . '/dist/blocks/tab-items',
		[
			'render_callback' => 'GenerateBlocks_Block_Tab_Items::render_block',
		]
	);

	register_block_type_from_metadata(
		GENERATEBLOCKS_PRO_DIR . '/dist/blocks/tab-item',
		[
			'render_callback' => 'GenerateBlocks_Block_Tab_Item::render_block',
		]
	);
}
