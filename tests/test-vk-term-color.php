<?php
/**
 * Class getThemeTemplate
 *
 * @package vektor-inc/vk-css-optimize
 */

use VektorInc\VK_Term_Color\VkTermColor;

class VkTermColorTest extends WP_UnitTestCase {

    // テスト用タクソノミー（各テスト共通）
    private $test_taxonomies = array(
        array(
            'name' => 'test_taxonomy_0',
            'slug' => 'test-taxonomy-0'
        )     
    );

    // テスト用ターム（各テスト共通）
    private $test_terms = array(

        'categories' => array(
            array(
                'name' => 'Test Category 0',
                'color' => '#FFFFFF',
                'id' => null
            ),
            array(
                'name' => 'Test Category 1',
                'color' => '#cccccc',
                'id' => null
            )
        ),

        'terms' => array(
            'test_taxonomy_0' => array(
                array(
                    'name' => 'Test Term 0',
                    'slug' => 'test-term-0',
                    'color' => '#000000',
                    'id' => null,
                ),
                array(
                    'name' => 'Test Term 1',
                    'slug' => 'test-term-1',
                    'color' => '#FFFF00',
                    'id' => null,
                )
            )        
        )
    );


	/**
	 * 各テストケースの実行直前に呼ばれる
	 */
	public function setUp(): void {
		parent::setUp();

        // 各テスト共通カテゴリーの登録
        $this->set_current_user( 'administrator' );
        foreach( $this->test_terms['categories'] as &$category ) {
            $category['id'] = wp_insert_category( array( 'cat_name' => $category['name'] ) );
            add_term_meta( $category['id'], 'term_color', $category['color'] );
        }

        // 各テスト共通タクソノミーの登録
        foreach( $this->test_taxonomies as $taxonomy ) {

            register_taxonomy(
                $taxonomy['name'],
                'post',
                array(
                    'label' => $taxonomy['name'],
                    'rewrite' => array( 'slug' => $taxonomy['slug'] ),
                    'hierarchical' => true,
                )
            ); 
        }

        // 各テスト共通タームの登録
        foreach ( $this->test_terms['terms'] as $taxonomy_name => &$terms ) {
            foreach ( $terms as &$term ) {
                $term_id_info = wp_insert_term(
                    $term['name'], // ターム名
                    $taxonomy_name , // タクソノミー名
                    array( 'slug' => $term['slug'] )
                );
                $term['id'] = $term_id_info['term_id'];
                add_term_meta( $term['id'], 'term_color', $term['color'] );
            }
        }    
    }

    private function set_current_user( $role )
    {
        $user = $this->factory()->user->create_and_get( array(
            'role' => $role,
        ) );

        /*
         * Set $user as the current user
         */
        wp_set_current_user( $user->ID, $user->user_login );
    }    
    
    /**
     * Test case get_post_single_term_info() 
     *
     * @return void
     */
	public function test_get_post_single_term_info() {
      
        $default_color = VkTermColor::get_default_color();
        
        // テストパターンでつかえるよう、わかりやすい変数名に置き換え
        $test_category_0 = $this->test_terms['categories'][0];
        $test_category_1 = $this->test_terms['categories'][1];
        $test_taxonomy_name = $this->test_taxonomies[0]['name'];
        $test_term = $this->test_terms['terms'][$test_taxonomy_name][0];

        // テストパターン
        $tests = array(
            // カテゴリのみをセットした記事の場合、該当カテゴリが返る
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array(  $test_category_0['id'] )
                ),
                'correct' => array(
                    'term_name' =>  $test_category_0['name'],
                    'color' =>  $test_category_0['color'],
                    'term_url' => site_url() . '/?cat=' .  $test_category_0['id'],
                    'text_color' => '#000000'
                )
            ), 
            // カテゴリをセットしない記事は、Uncategorized が返る
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
                    'term_url' => site_url() . '/?cat=1',
                    'text_color' => '#FFFFFF'
                )
                ),
            // カテゴリとカスタムタクソノミーをセットした記事で、表示指定をカスタムタクソノミーに指定すると、該当のタクソノミーのタームが返る
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array(  $test_category_0['id'] ),
                    'tax_input' => array ( $test_taxonomy_name =>  $test_term['id'] )
                ),
                'tax_input' => $test_taxonomy_name,
                'correct' => array(
                    'term_name' => $test_term['name'],
                    'color' => $test_term['color'],
                    'term_url' => site_url() . '/?' . $test_taxonomy_name . '=' .$test_term['slug'],
                    'text_color' => '#FFFFFF'
                )
            ),  
            // カテゴリだけをセットした記事で、表示指定をカスタムタクソノミーに指定すると、取得できず null が返る
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id']),
                ),
                'tax_input' => $test_taxonomy_name,
                'correct' => null
            ), 
            // タクソノミーだけをセットした記事で、表示指定をカテゴリーに指定すると、Uncategorized が返る
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'tax_input' => array ( $test_taxonomy_name =>  $test_term['id'] )
                ),
                'tax_input' => 'category',
                'correct' => array(
                    'term_name' => 'Uncategorized',
                    'color' => $default_color,
                    'term_url' => site_url() . '/?cat=1',
                    'text_color' => '#FFFFFF'
                )
            ),                                      
        );      
        
        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_get_post_single_term_info()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        // テストパターンを順々にテスト
        foreach ( $tests as $test ) {
            $args = array();
            $post_id = wp_insert_post( $test['data'] );
            if ( isset($test['tax_input']) ) {
                $args['taxonomy'] = $test['tax_input'];                
            }

            $return = VkTermColor::get_post_single_term_info( $post_id, $args );
            // $test['correct']は配列だが、取得できない場合は期待値がnullになる
            if ( is_array($test['correct']) ) {
                foreach( $test['correct'] as $key => $value ) {
                    print 'return  :' . PHP_EOL;
                    var_dump( $return[$key] );
                    print PHP_EOL;
                    print 'correct  :'. PHP_EOL;
                    var_dump( $value );
                    print PHP_EOL;
                    $this->assertSame( $value, $return[$key] );
                }
            } else {
                $this->assertSame( $test['correct'], $return );
            }
        }
	}
   /**
     * Test case get_post_single_term_info() 
     *
     * @return void
     */
	public function test_get_auto_post_single_term_html() {

        $default_color = VkTermColor::get_default_color();

        $test_category_0 = $this->test_terms['categories'][0];

        // テストパターン
        $tests = array(
            // 上記カテゴリをセットした投稿
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] )
                ),
                'args' => array(),
                'correct' => '<span class="btn btn-sm" style="color:#fff;background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] .'</span>'
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
                    'post_category' => array( $test_category_0['id'] )
                ),
                'args' => array(
                    'single_element'     => 'div',
                    'single_class'       => 'vk-term-color-test-single',
                    'single_inner_class' => 'vk-term-color-test-single-inner',
                    'link'               => true,
                    'color'              => false,
                ),
                'correct' => '<div class="vk-term-color-test-single"><a class="vk-term-color-test-single-inner" href="' . site_url() . '/?cat=' . $test_category_0['id'] . '">' . $test_category_0['name'] .'</a></div>'
            ),    
            // オプション指定（リンクあり、色あり）
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] )
                ),
                'args' => array(
                    'single_element'     => 'div',
                    'single_class'       => 'vk-term-color-test-single',
                    'single_inner_class' => 'vk-term-color-test-single-inner',
                    'link'               => true,
                    'color'              => true,
                ),
                'correct' => '<div class="vk-term-color-test-single"><a class="vk-term-color-test-single-inner" style="color:#fff;background-color:' . $test_category_0['color'] . '" href="' . site_url() . '/?cat=' . $test_category_0['id'] . '">' . $test_category_0['name'] .'</a></div>'
            ),  
          // オプション指定（リンクなし、色なし）
          array( 
            'data' => array(
                'post_title'   => 'Page Title',
                'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                'post_type'    => 'post',
                'post_status'  => 'publish',
                'post_category' => array( $test_category_0['id'] )
            ),
            'args' => array(
                'single_element'     => 'div',
                'single_class'       => 'vk-term-color-test-single',
                'single_inner_class' => 'vk-term-color-test-single-inner',
                'link'               => false,
                'color'              => false,
            ),
            'correct' => '<div class="vk-term-color-test-single"><span class="vk-term-color-test-single-inner">' . $test_category_0['name'] .'</span></div>'
        ),    
        // オプション指定（リンクなし、色あり）
        array( 
            'data' => array(
                'post_title'   => 'Page Title',
                'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                'post_type'    => 'post',
                'post_status'  => 'publish',
                'post_category' => array( $test_category_0['id'] )
            ),
            'args' => array(
                'single_element'     => 'div',
                'single_class'       => 'vk-term-color-test-single',
                'single_inner_class' => 'vk-term-color-test-single-inner',
                'link'               => false,
                'color'              => true,
            ),
            'correct' => '<div class="vk-term-color-test-single"><span class="vk-term-color-test-single-inner" style="color:#fff;background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] .'</span></div>'
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

        $default_color = VkTermColor::get_default_color();

        $test_category_0 = $this->test_terms['categories'][0];

        // テストパターン
        $tests = array(
            // 上記カテゴリをセットした投稿
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] )
                ),
                'args' => array(),
                'correct' => '<span style="color:#fff;background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] .'</span>'
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
                    'post_category' => array( $test_category_0['id'] )
                ),
                'args' => array(
                    'class'       => 'vk-term-color-test-single',
                    'link'               => true,
                ),
                'correct' => '<a class="vk-term-color-test-single" style="color:#fff;background-color:' . $test_category_0['color'] . '" href="' . site_url() . '/?cat=' . $test_category_0['id'] . '">' . $test_category_0['name'] .'</a>'
            ),    
            // オプション指定（リンクあり、クラスあり）
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] )
                ),
                'args' => array(
                    'class'       => 'vk-term-color-test-single',
                    'link'               => false,
                ),
                'correct' => '<span class="vk-term-color-test-single" style="color:#fff;background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] .'</span>'
            ), 
            // オプション指定（リンクあり、クラスなし）
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] )
                ),
                'args' => array(
                    'link'               => true,
                ),
                'correct' => '<a style="color:#fff;background-color:' . $test_category_0['color'] . '" href="' . site_url() . '/?cat=' . $test_category_0['id'] . '">' . $test_category_0['name'] .'</a>'
            ),    
            // オプション指定（リンクなし、クラスなし）
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] )
                ),
                'args' => array(
                    'link'               => false,
                ),
                'correct' => '<span style="color:#fff;background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] .'</span>'
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

    public function test_get_dynamic_text_color() {

        $data = array(
            '#000000' => '#FFFFFF',
            '#333333' => '#FFFFFF',
            '#666666' => '#FFFFFF',
            '#999999' => '#FFFFFF',
            '#FFFFFF' => '#000000',
            '#330000' => '#FFFFFF',
            '#660000' => '#FFFFFF',
            '#990000' => '#FFFFFF',
            '#CC0000' => '#FFFFFF',
            '#FF0000' => '#FFFFFF',
            '#003300' => '#FFFFFF',
            '#006600' => '#FFFFFF',
            '#009900' => '#FFFFFF',
            '#00CC00' => '#FFFFFF',
            '#00FF00' => '#FFFFFF',  
            '#000033' => '#FFFFFF',
            '#000066' => '#FFFFFF',
            '#000099' => '#FFFFFF',
            '#0000CC' => '#FFFFFF',
            '#0000FF' => '#FFFFFF',                      
            '#333300' => '#FFFFFF',
            '#666600' => '#FFFFFF',
            '#999900' => '#FFFFFF',
            '#CCCC00' => '#000000',
            '#FFFF00' => '#000000',
            '#003333' => '#FFFFFF',
            '#006666' => '#FFFFFF',
            '#009999' => '#FFFFFF',
            '#00CCCC' => '#FFFFFF',            
            '#00FFFF' => '#000000',
            '#330033' => '#FFFFFF',
            '#660066' => '#FFFFFF',
            '#990099' => '#FFFFFF',
            '#CC00CC' => '#FFFFFF',
            '#FF00FF' => '#FFFFFF',            
        );

        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_get_dynamic_text_color()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        foreach ( $data as $input => $correct ) {
            $return = VkTermColor::get_dynamic_text_color($input);
            print 'input, correct, return :' . PHP_EOL;
            var_dump( $input, $correct, $return );
            print PHP_EOL;       
            $this->assertSame( $correct, $return );
        }
        
    }

    /**
     * Test case get_post_multiple_terms_info() 
     *
     * @return void
     */
	public function test_get_post_multiple_terms_info() {
      
        $default_color = VkTermColor::get_default_color();
        
        // テストパターンでつかえるよう、わかりやすい変数名に置き換え
        $test_category_0 = $this->test_terms['categories'][0];
        $test_category_1 = $this->test_terms['categories'][1];
        $test_taxonomy_name = $this->test_taxonomies[0]['name'];
        $test_term_0 = $this->test_terms['terms'][$test_taxonomy_name][0];
        $test_term_1 = $this->test_terms['terms'][$test_taxonomy_name][1];

        // テストパターン
        $tests = array(
            // カテゴリのみをセットした記事の場合、該当カテゴリが返る
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] )
                ),
                'args' => array(),
                'correct' => array(
                    array(
                        'term_id' => $test_category_0['id'],
                        'term_name' => $test_category_0['name'],
                        'color' => $test_category_0['color'],
                        'term_url' => site_url() . '/?cat=' . $test_category_0['id'],
                        'text_color' => '#000000',
                        'taxonomy' => 'category'
                    )
                )
            ), 
            // カテゴリをセットしない記事は、Uncategorized が返る
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish'
                ),
                'args' => array(),
                'correct' => array(
                    array(
                        'term_id' => 1,
                        'term_name' => 'Uncategorized',
                        'color' => $default_color,
                        'term_url' => site_url() . '/?cat=1',
                        'text_color' => '#FFFFFF',
                        'taxonomy' => 'category'
                    )
                )
            ),
            // 複数カテゴリをセットした記事の場合、すべてのカテゴリが返る
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'], $test_category_1['id'] )
                ),
                'args' => array(),
                'correct' => array(
                    array(
                        'term_id' => $test_category_0['id'],
                        'term_name' => $test_category_0['name'],
                        'color' => $test_category_0['color'],
                        'term_url' => site_url() . '/?cat=' . $test_category_0['id'],
                        'text_color' => '#000000',
                        'taxonomy' => 'category'
                    ),
                    array(
                        'term_id' => $test_category_1['id'],
                        'term_name' => $test_category_1['name'],
                        'color' => $test_category_1['color'],
                        'term_url' => site_url() . '/?cat=' . $test_category_1['id'],
                        'text_color' => '#000000',
                        'taxonomy' => 'category'
                    )
                )
            ),
            // カスタムタクソノミーを指定した場合、該当のタクソノミーのすべてのタームが返る
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] ),
                    'tax_input' => array( $test_taxonomy_name => array( $test_term_0['id'], $test_term_1['id'] ) )
                ),
                'args' => array( 'taxonomy' => $test_taxonomy_name ),
                'correct' => array(
                    array(
                        'term_id' => $test_term_0['id'],
                        'term_name' => $test_term_0['name'],
                        'color' => $test_term_0['color'],
                        'term_url' => site_url() . '/?' . $test_taxonomy_name . '=' . $test_term_0['slug'],
                        'text_color' => '#FFFFFF',
                        'taxonomy' => $test_taxonomy_name
                    ),
                    array(
                        'term_id' => $test_term_1['id'],
                        'term_name' => $test_term_1['name'],
                        'color' => $test_term_1['color'],
                        'term_url' => site_url() . '/?' . $test_taxonomy_name . '=' . $test_term_1['slug'],
                        'text_color' => '#000000',
                        'taxonomy' => $test_taxonomy_name
                    )
                )
            ),
            // カテゴリだけをセットした記事で、表示指定をカスタムタクソノミーに指定すると、空配列が返る
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] ),
                ),
                'args' => array( 'taxonomy' => $test_taxonomy_name ),
                'correct' => array()
            ), 
            // タクソノミーだけをセットした記事で、表示指定をカテゴリーに指定すると、Uncategorized が返る
            array( 
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'tax_input' => array( $test_taxonomy_name => array( $test_term_0['id'] ) )
                ),
                'args' => array( 'taxonomy' => 'category' ),
                'correct' => array(
                    array(
                        'term_id' => 1,
                        'term_name' => 'Uncategorized',
                        'color' => $default_color,
                        'term_url' => site_url() . '/?cat=1',
                        'text_color' => '#FFFFFF',
                        'taxonomy' => 'category'
                    )
                )
            ),                                      
        );      
        
        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_get_post_multiple_terms_info()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        // テストパターンを順々にテスト
        foreach ( $tests as $test ) {
            $post_id = wp_insert_post( $test['data'] );
            $post = get_post( $post_id );

            $return = VkTermColor::get_post_multiple_terms_info( $post, $test['args'] );
            
            print 'Post ID: ' . $post_id . PHP_EOL;
            print 'Args: ' . PHP_EOL;
            var_dump( $test['args'] );
            if ( isset($test['args']['taxonomy']) && !empty($test['args']['taxonomy']) ) {
                print 'Taxonomy: ' . $test['args']['taxonomy'] . PHP_EOL;
                print 'Terms from get_the_terms: ' . PHP_EOL;
                var_dump( get_the_terms( $post, $test['args']['taxonomy'] ) );
            } else {
                print 'No specific taxonomy specified' . PHP_EOL;
                print 'All taxonomies: ' . PHP_EOL;
                var_dump( get_the_taxonomies( $post ) );
            }
            print 'return  :' . PHP_EOL;
            var_dump( $return );
            print PHP_EOL;
            print 'correct  :'. PHP_EOL;
            var_dump( $test['correct'] );
            print PHP_EOL;
            
            // 配列の要素数をチェック
            $this->assertSame( count($test['correct']), count($return) );
            
            // 各要素の内容をチェック
            foreach( $test['correct'] as $index => $expected_term ) {
                if ( isset($return[$index]) ) {
                    foreach( $expected_term as $key => $value ) {
                        $this->assertSame( $value, $return[$index][$key] );
                    }
                }
            }
        }
	}

    /**
     * Test case rest_get_post_terms_info() 
     *
     * @return void
     */
	public function test_rest_get_post_terms_info() {
      
        $default_color = VkTermColor::get_default_color();
        
        // テストパターンでつかえるよう、わかりやすい変数名に置き換え
        $test_category_0 = $this->test_terms['categories'][0];
        $test_taxonomy_name = $this->test_taxonomies[0]['name'];
        $test_term_0 = $this->test_terms['terms'][$test_taxonomy_name][0];

        // テストパターン
        $tests = array(
            // 正常な投稿IDでカテゴリ情報を取得
            array( 
                'post_data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] )
                ),
                'request_params' => array(
                    'post_id' => null, // テスト内で設定
                    'taxonomy' => ''
                ),
                'correct' => array(
                    array(
                        'term_id' => $test_category_0['id'],
                        'term_name' => $test_category_0['name'],
                        'color' => $test_category_0['color'],
                        'term_url' => site_url() . '/?cat=' . $test_category_0['id'],
                        'text_color' => '#000000',
                        'taxonomy' => 'category'
                    )
                )
            ), 
            // 存在しない投稿IDでエラーを返す
            array( 
                'post_data' => null,
                'request_params' => array(
                    'post_id' => 99999,
                    'taxonomy' => ''
                ),
                'correct' => 'WP_Error'
            ),
            // 特定のタクソノミーを指定してターム情報を取得
            array( 
                'post_data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'tax_input' => array( $test_taxonomy_name => array( $test_term_0['id'] ) )
                ),
                'request_params' => array(
                    'post_id' => null, // テスト内で設定
                    'taxonomy' => $test_taxonomy_name
                ),
                'correct' => array(
                    array(
                        'term_id' => $test_term_0['id'],
                        'term_name' => $test_term_0['name'],
                        'color' => $test_term_0['color'],
                        'term_url' => site_url() . '/?' . $test_taxonomy_name . '=' . $test_term_0['slug'],
                        'text_color' => '#FFFFFF',
                        'taxonomy' => $test_taxonomy_name
                    )
                )
            ),
            // タームが存在しない場合、空配列が返る
            array( 
                'post_data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish'
                ),
                'request_params' => array(
                    'post_id' => null, // テスト内で設定
                    'taxonomy' => $test_taxonomy_name
                ),
                'correct' => array('error' => 'not found')
            ),                                      
        );      
        
        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_rest_get_post_terms_info()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        // テストパターンを順々にテスト
        foreach ( $tests as $test ) {
            $post_id = null;
            if ( $test['post_data'] ) {
                $post_id = wp_insert_post( $test['post_data'] );
                $test['request_params']['post_id'] = $post_id;
            }

            // モックリクエストオブジェクトを作成
            $request = new WP_REST_Request('POST', '/vk-term-color/v1/post-terms-info');
            $request->set_param('post_id', $test['request_params']['post_id']);
            $request->set_param('taxonomy', $test['request_params']['taxonomy']);

            $return = VkTermColor::rest_get_post_terms_info($request);
            
            print 'return  :' . PHP_EOL;
            var_dump( $return );
            print PHP_EOL;
            print 'correct  :'. PHP_EOL;
            var_dump( $test['correct'] );
            print PHP_EOL;
            
            // デバッグ情報を追加
            print 'Test correct type: ' . gettype($test['correct']) . PHP_EOL;
            print 'Test correct value: ' . PHP_EOL;
            var_dump($test['correct']);
            print PHP_EOL;
            
            if ( $test['correct'] === 'WP_Error' ) {
                $this->assertInstanceOf('WP_Error', $return);
            } elseif ( is_array($test['correct']) ) {
                // 配列の要素数をチェック
                $this->assertSame( count($test['correct']), count($return) );
                
                // 各要素の内容をチェック
                foreach( $test['correct'] as $index => $expected_term ) {
                    if ( isset($return[$index]) ) {
                        foreach( $expected_term as $key => $value ) {
                            $this->assertSame( $value, $return[$index][$key] );
                        }
                    }
                }
            } else {
                // 予期しない型の場合
                $this->fail('Unexpected type for test correct: ' . gettype($test['correct']));
            }
        }
	}


}