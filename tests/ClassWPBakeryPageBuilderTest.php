<?php

namespace WPSL\WPBakeryPageBuilder;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use wpCloud\StatelessMedia\WPStatelessStub;

/**
 * Class ClassWPBakeryPageBuilderTest
 */
class ClassWPBakeryPageBuilderTest extends TestCase {
  const TEST_URL = 'https://test.test';
  const UPLOADS_URL = self::TEST_URL . '/uploads';
  const TEST_FILE = 'folder/image.png';
  const SRC_URL = self::TEST_URL . '/' . self::TEST_FILE;
  const DST_URL = WPStatelessStub::TEST_GS_HOST . '/' . self::TEST_FILE;
  const TEST_SIZE = 'thumbnail';
  const TEST_UPLOAD_DIR = [
    'baseurl' => self::UPLOADS_URL,
    'basedir' => '/var/www/uploads'
  ];

  // Adds Mockery expectations to the PHPUnit assertions count.
  use MockeryPHPUnitIntegration;

  public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

    // WP mocks
    Functions\when('wp_upload_dir')->justReturn( self::TEST_UPLOAD_DIR );
        
    // WP_Stateless mocks
    Filters\expectApplied('wp_stateless_file_name')
      ->andReturn( self::TEST_FILE );

    Functions\when('ud_get_stateless_media')->justReturn( WPStatelessStub::instance() );
  }
	
  public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

  public function testShouldInitHooks() {
    $wpBakeryPageBuilder = new WPBakeryPageBuilder();

    self::assertNotFalse( has_filter('vc_wpb_getimagesize', [ $wpBakeryPageBuilder, 'vc_wpb_getimagesize' ]) );
  }

  public function testShouldUpdateImageSizes() {
    $wpBakeryPageBuilder = new WPBakeryPageBuilder();
    
    // Test data
    $params = [
      'thumb_size' => self::TEST_SIZE,
    ];

    $args = [
      self::TEST_SIZE => '<img src="' . self::DST_URL . '" />',
    ];

    $metaData = [
      'sizes' => [
        self::TEST_SIZE => null,
      ],
    ];

    $fileType = [
      'type' => 'image/png',
    ];

    $expectedMetaData = [
      'sizes' => [
        self::TEST_SIZE => [
          'file' => self::TEST_FILE,
          'mime-type' => 'image/png',
          'width' => 150,
          'height' => 150,
        ],
      ],
    ];

    // Mocks
    Functions\when('wp_get_attachment_metadata')
      ->justReturn($metaData);

    Functions\when('wp_check_filetype')
      ->justReturn($fileType);

    Functions\when('wp_basename')
      ->justReturn( self::TEST_FILE );

    // Expectations
    Functions\expect('wp_update_attachment_metadata')
      ->once()
      ->with(15, $expectedMetaData);

    $wpBakeryPageBuilder->vc_wpb_getimagesize($args, 15, $params); 

    self::assertTrue(true);
  }
}

function getimagesize() {
  return [ 150, 150 ];
}