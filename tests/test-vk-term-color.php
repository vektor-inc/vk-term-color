<?php
/**
 * Class getThemeTemplate
 *
 * @package vektor-inc/vk-css-optimize
 */

use VektorInc\VK_Term_Color\VkTermColor;

class VkTermColorTest extends WP_UnitTestCase {

    private $test_color_value = '#a1b2c3';
    private $category_name = 'parent_category';
    private $category_id = null;

	/**
	 * 各テストケースの実行直前に呼ばれる
	 */
	public function setUp(): void {
		parent::setUp();
    }
    
    /**
     * Test case get_post_single_term_info() 
     *
     * @return void
     */
	public function test_get_post_single_term_info() {

        $catarr = array(
			'cat_name' => $this->category_name,
		);
        $this->category_id  = wp_insert_category( $catarr );          
        add_term_meta( $this->category_id, 'term_color', $this->test_color_value );
  
        $default_color = VkTermColor::get_default_color();

        // テストパターン
        $tests = array(
            // 上記カテゴリをセットした投稿
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $this->category_id )
                ),
                'correct' => array(
                    'term_name' => $this->category_name,
                    'color' => $this->test_color_value,
                    'term_url' => site_url() . '/?cat=' . $this->category_id
                )
            ),
            // カテゴリをセットしない投稿
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish'
                ),
                'correct' => array(
                    'term_name' => 'Uncategorized',
                    'color' => $default_color,
                    'term_url' => site_url() . '/?cat=1'
                )
            )
        );          

        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_get_post_single_term_info()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        // テストパターンを順々にテスト
        foreach ( $tests as $test ) {
            $post_id = wp_insert_post( $test['data'] );
            $return = VkTermColor::get_post_single_term_info( $post_id );
            foreach( $test['correct'] as $key => $value ) {
                print 'return  :' . PHP_EOL;
                var_dump( $return[$key] );
                print PHP_EOL;
                print 'correct  :'. PHP_EOL;
                var_dump( $value );
                print PHP_EOL;
                $this->assertSame( $value, $return[$key] );
            }
        }
	}
   /**
     * Test case get_post_single_term_info() 
     *
     * @return void
     */
	public function test_get_auto_post_single_term_html() {

        $catarr = array(
			'cat_name' => $this->category_name,
		);
        $this->category_id  = wp_insert_category( $catarr );          
        add_term_meta( $this->category_id, 'term_color', $this->test_color_value );
  
        $default_color = VkTermColor::get_default_color();

        // テストパターン
        $tests = array(
            // 上記カテゴリをセットした投稿
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $this->category_id )
                ),
                'args' => array(),
                'correct' => '<span class="btn btn-sm" style="color:#fff;background-color:' . $this->test_color_value . '">' . $this->category_name .'</span>'
            ),
            // カテゴリをセットしない投稿
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish'
                ),
                'args' => array(),
                'correct' => '<span class="btn btn-sm" style="color:#fff;background-color:' . $default_color . '">Uncategorized</span>'
            ),
            // オプション指定（リンクあり、色なし）
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $this->category_id )
                ),
                'args' => array(
                    'single_element'     => 'div',
                    'single_class'       => 'vk-term-color-test-single',
                    'single_inner_class' => 'vk-term-color-test-single-inner',
                    'link'               => true,
                    'color'              => false,
                ),
                'correct' => '<div class="vk-term-color-test-single"><a class="vk-term-color-test-single-inner" href="' . site_url() . '/?cat=' . $this->category_id . '">' . $this->category_name .'</a></div>'
            ),    
            // オプション指定（リンクあり、色あり）
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $this->category_id )
                ),
                'args' => array(
                    'single_element'     => 'div',
                    'single_class'       => 'vk-term-color-test-single',
                    'single_inner_class' => 'vk-term-color-test-single-inner',
                    'link'               => true,
                    'color'              => true,
                ),
                'correct' => '<div class="vk-term-color-test-single"><a class="vk-term-color-test-single-inner" style="color:#fff;background-color:' . $this->test_color_value . '" href="' . site_url() . '/?cat=' . $this->category_id . '">' . $this->category_name .'</a></div>'
            ),  
          // オプション指定（リンクなし、色なし）
          array( 
            'data' => array(
                'post_title'   => 'Page Title',
                'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                'post_type'    => 'post',
                'post_status'  => 'publish',
                'post_category' => array( $this->category_id )
            ),
            'args' => array(
                'single_element'     => 'div',
                'single_class'       => 'vk-term-color-test-single',
                'single_inner_class' => 'vk-term-color-test-single-inner',
                'link'               => false,
                'color'              => false,
            ),
            'correct' => '<div class="vk-term-color-test-single"><span class="vk-term-color-test-single-inner">' . $this->category_name .'</span></div>'
        ),    
        // オプション指定（リンクなし、色あり）
        array( 
            'data' => array(
                'post_title'   => 'Page Title',
                'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                'post_type'    => 'post',
                'post_status'  => 'publish',
                'post_category' => array( $this->category_id )
            ),
            'args' => array(
                'single_element'     => 'div',
                'single_class'       => 'vk-term-color-test-single',
                'single_inner_class' => 'vk-term-color-test-single-inner',
                'link'               => false,
                'color'              => true,
            ),
            'correct' => '<div class="vk-term-color-test-single"><span class="vk-term-color-test-single-inner" style="color:#fff;background-color:' . $this->test_color_value . '">' . $this->category_name .'</span></div>'
        ),                                
        );          

        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_get_auto_post_single_term_html()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        global $post;

        // テストパターンを順々にテスト
        foreach ( $tests as $test ) {
            $post_id = wp_insert_post( $test['data'] );
            $post = get_post( $post_id );
        
            $return = VkTermColor::get_auto_post_single_term_html( '', $test['args'] );

            print 'return  :' . PHP_EOL;
            var_dump( $return );
            print PHP_EOL;
            print 'correct  :'. PHP_EOL;
            var_dump( $test['correct'] );
            print PHP_EOL;
            $this->assertSame( $test['correct'], $return );

        }
	}

   /**
     * Test case get_single_term_with_color() 
     *
     * @return void
     */
	public function test_get_single_term_with_color() {

        $catarr = array(
			'cat_name' => $this->category_name,
		);
        $this->category_id  = wp_insert_category( $catarr );          
        add_term_meta( $this->category_id, 'term_color', $this->test_color_value );
  
        $default_color = VkTermColor::get_default_color();

        // テストパターン
        $tests = array(
            // 上記カテゴリをセットした投稿
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $this->category_id )
                ),
                'args' => array(),
                'correct' => '<span style="color:#fff;background-color:' . $this->test_color_value . '">' . $this->category_name .'</span>'
            ),
            // カテゴリをセットしない投稿
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish'
                ),
                'args' => array(),
                'correct' => '<span style="color:#fff;background-color:' . $default_color . '">Uncategorized</span>'
            ),
            // オプション指定（リンクあり、クラスあり）
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $this->category_id )
                ),
                'args' => array(
                    'class'       => 'vk-term-color-test-single',
                    'link'               => true,
                ),
                'correct' => '<a class="vk-term-color-test-single" style="color:#fff;background-color:' . $this->test_color_value . '" href="' . site_url() . '/?cat=' . $this->category_id . '">' . $this->category_name .'</a>'
            ),    
            // オプション指定（リンクあり、クラスあり）
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $this->category_id )
                ),
                'args' => array(
                    'class'       => 'vk-term-color-test-single',
                    'link'               => false,
                ),
                'correct' => '<span class="vk-term-color-test-single" style="color:#fff;background-color:' . $this->test_color_value . '">' . $this->category_name .'</span>'
            ), 
            // オプション指定（リンクあり、クラスなし）
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $this->category_id )
                ),
                'args' => array(
                    'link'               => true,
                ),
                'correct' => '<a style="color:#fff;background-color:' . $this->test_color_value . '" href="' . site_url() . '/?cat=' . $this->category_id . '">' . $this->category_name .'</a>'
            ),    
            // オプション指定（リンクあり、クラスあり）
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $this->category_id )
                ),
                'args' => array(
                    'link'               => false,
                ),
                'correct' => '<span style="color:#fff;background-color:' . $this->test_color_value . '">' . $this->category_name .'</span>'
            ),                                     
        );          

        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_get_single_term_with_color()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        global $post;

        // テストパターンを順々にテスト
        foreach ( $tests as $test ) {
            $post_id = wp_insert_post( $test['data'] );
            $post = get_post( $post_id );
        
            $return = VkTermColor::get_single_term_with_color( '', $test['args'] );

            print 'return  :' . PHP_EOL;
            var_dump( $return );
            print PHP_EOL;
            print 'correct  :'. PHP_EOL;
            var_dump( $test['correct'] );
            print PHP_EOL;
            $this->assertSame( $test['correct'], $return );

        }
	}

  /**
     * Test case get_display_taxonomies_exclusion() 
     *
     * @return void
     */
	public function test_get_display_taxonomies_exclusion() {

        /*  テストパターン
            "taxonomies" ... 元のタクソノミーリスト
            "filter_exclusion" ... フィルターで除外するタクソノミー
            "exclusion" ... 本メソッドの引数で除外するタクソノミー
            "correct" ... 期待値（フィルターは既存の除外値も残し、新たな除外値を加える前提）
         */
        $tests = array(   
            array(     
                "taxonomies" => array(
                    "category" => "categoty",
                    "post_tag" => "post_tag",
                    "theme" => "theme"
                ),
                "filter_exclusion" => array( "category" ),
                "exclusion" => array(),
                "correct" => array(
                    "post_tag" => "post_tag",
                    "theme" => "theme"
                )
            ),
            array(     
                "taxonomies" => array(
                    "category" => "categoty",
                    "post_tag" => "post_tag",
                    "theme" => "theme"
                ),
                "filter_exclusion" => array( "theme" ),
                "exclusion" => array(),
                "correct" => array(
                    "category" => "categoty",
                    "post_tag" => "post_tag"
                )
            ),            
            array(     
                "taxonomies" => array(
                    "category" => "categoty",
                    "post_tag" => "post_tag",
                    "theme" => "theme"
                ),
                "exclusion" => array( "theme" ),
                "correct" => array(
                    "category" => "categoty",
                    "post_tag" => "post_tag"
                )
            ),
        );

        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_get_display_taxonomies_exclusion()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        $filter_func = null;
        foreach( $tests as $test ) {
            if ( ! is_null( $filter_func ) ) {
                remove_filter('vk_get_display_taxonomies_exclusion', $filter_func);
            }

            $filter_func = function ( $exclusion ) use ( $test ) {
                return array_merge($exclusion, $test['filter_exclusion']);
            };

            if ( isset($test['filter_exclusion']) && count($test['filter_exclusion']) > 0 ) {
                add_filter('vk_get_display_taxonomies_exclusion', $filter_func);
            }

            $return = VkTermColor::get_display_taxonomies_exclusion( $test['taxonomies'], $test['exclusion'] );
            print 'return  :' . PHP_EOL;
            var_dump( $return );
            print PHP_EOL;
            print 'correct  :'. PHP_EOL;
            var_dump( $test['correct'] );
            print PHP_EOL;
            $this->assertSame( $test['correct'], $return );
        }
    }    

}