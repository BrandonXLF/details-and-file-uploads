<?php
/**
 * Tests for the Settings class.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

/**
 * Tests for the Settings class.
 */
class Settings_Tests extends Unit_Test_Case {
	public function test_get_types() {
		$this->assertEquals(
			[
				'image'    => 'Image files',
				'video'    => 'Video files',
				'audio'    => 'Audio files',
				'.jpg'     => '.jpg',
				'.gif'     => '.gif',
				'.png'     => '.png',
				'.bmp'     => '.bmp',
				'.tiff'    => '.tiff',
				'.webp'    => '.webp',
				'.avif'    => '.avif',
				'.ico'     => '.ico',
				'.heic'    => '.heic',
				'.asf'     => '.asf',
				'.wmv'     => '.wmv',
				'.wmx'     => '.wmx',
				'.wm'      => '.wm',
				'.avi'     => '.avi',
				'.divx'    => '.divx',
				'.flv'     => '.flv',
				'.mov'     => '.mov',
				'.mpeg'    => '.mpeg',
				'.mp4'     => '.mp4',
				'.ogv'     => '.ogv',
				'.webm'    => '.webm',
				'.mkv'     => '.mkv',
				'.3gp'     => '.3gp',
				'.3g2'     => '.3g2',
				'.txt'     => '.txt',
				'.csv'     => '.csv',
				'.tsv'     => '.tsv',
				'.ics'     => '.ics',
				'.rtx'     => '.rtx',
				'.css'     => '.css',
				'.htm'     => '.htm',
				'.vtt'     => '.vtt',
				'.dfxp'    => '.dfxp',
				'.mp3'     => '.mp3',
				'.aac'     => '.aac',
				'.ra'      => '.ra',
				'.wav'     => '.wav',
				'.ogg'     => '.ogg',
				'.flac'    => '.flac',
				'.mid'     => '.mid',
				'.wma'     => '.wma',
				'.wax'     => '.wax',
				'.mka'     => '.mka',
				'.rtf'     => '.rtf',
				'.js'      => '.js',
				'.pdf'     => '.pdf',
				'.swf'     => '.swf',
				'.class'   => '.class',
				'.tar'     => '.tar',
				'.zip'     => '.zip',
				'.gz'      => '.gz',
				'.rar'     => '.rar',
				'.7z'      => '.7z',
				'.exe'     => '.exe',
				'.psd'     => '.psd',
				'.xcf'     => '.xcf',
				'.doc'     => '.doc',
				'.pot'     => '.pot',
				'.wri'     => '.wri',
				'.xla'     => '.xla',
				'.mdb'     => '.mdb',
				'.mpp'     => '.mpp',
				'.docx'    => '.docx',
				'.docm'    => '.docm',
				'.dotx'    => '.dotx',
				'.dotm'    => '.dotm',
				'.xlsx'    => '.xlsx',
				'.xlsm'    => '.xlsm',
				'.xlsb'    => '.xlsb',
				'.xltx'    => '.xltx',
				'.xltm'    => '.xltm',
				'.xlam'    => '.xlam',
				'.pptx'    => '.pptx',
				'.pptm'    => '.pptm',
				'.ppsx'    => '.ppsx',
				'.ppsm'    => '.ppsm',
				'.potx'    => '.potx',
				'.potm'    => '.potm',
				'.ppam'    => '.ppam',
				'.sldx'    => '.sldx',
				'.sldm'    => '.sldm',
				'.onetoc'  => '.onetoc',
				'.oxps'    => '.oxps',
				'.xps'     => '.xps',
				'.odt'     => '.odt',
				'.odp'     => '.odp',
				'.ods'     => '.ods',
				'.odg'     => '.odg',
				'.odc'     => '.odc',
				'.odb'     => '.odb',
				'.odf'     => '.odf',
				'.wp'      => '.wp',
				'.key'     => '.key',
				'.numbers' => '.numbers',
				'.pages'   => '.pages',
				'.heif'    => '.heif',
				'.heics'   => '.heics',
				'.heifs'   => '.heifs',
			],
			Settings::get_types()
		);
	}

	public function test_register_settings() {
		global $wp_registered_settings;

		$this->assertArrayHasKey( 'cffu_fields', $wp_registered_settings );
		$this->assertArrayHasKey( 'cffu_table_title', $wp_registered_settings );
		$this->assertArrayHasKey( 'cffu_hide_notes', $wp_registered_settings );
	}
}
