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
        $test_category_1 = $this->test_terms['categories'][1];

        // 期待するテキスト色は実際の関数から算出する（ハードコードの決め打ちを避ける）
        $text_color_1 = VkTermColor::get_dynamic_text_color( $test_category_1['color'] );

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
                'correct' => '<span class="btn btn-sm" style="color:#000000;background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] .'</span>'
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
                'correct' => '<span class="btn btn-sm" style="color:#FFFFFF;background-color:' . $default_color . '">Uncategorized</span>'
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
                'correct' => '<div class="vk-term-color-test-single"><a class="vk-term-color-test-single-inner" style="color:#000000;background-color:' . $test_category_0['color'] . '" href="' . site_url() . '/?cat=' . $test_category_0['id'] . '">' . $test_category_0['name'] .'</a></div>'
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
            'correct' => '<div class="vk-term-color-test-single"><span class="vk-term-color-test-single-inner" style="color:#000000;background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] .'</span></div>'
        ),
        // 対象の分類が見つからない状態（get_the_terms() が WP_Error を返す）でも致命的エラーにならず空文字が返る
        array(
            'data' => array(
                'post_title'   => 'Page Title',
                'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                'post_type'    => 'post',
                'post_status'  => 'publish',
                'post_category' => array( $test_category_0['id'] )
            ),
            'args' => array(),
            'force_wp_error' => true,
            'correct' => ''
        ),
        // グローバルの投稿が存在しない状態（ループ外・404ページ・検索結果0件等）で、投稿を指定せず呼び出しても、
        // 警告（Attempt to read property "ID" on null）を出さず、空文字列が返ること（issue #38 の再現ケース）
        array(
            'data'           => null, // 投稿は作成しない
            'args'           => array(),
            'call_param'     => '',
            'no_global_post' => true,
            'correct'        => '',
        ),
        // 投稿IDを直接指定した場合：グローバルの投稿が無くても、渡したIDの投稿からタクソノミーが
        // 自動検出され、正しく出力されること
        array(
            'data' => array(
                'post_title'    => 'Page By Id Auto Taxonomy',
                'post_content'  => '<!-- wp:paragraph --><p>This is another page.</p><!-- /wp:paragraph -->',
                'post_type'     => 'post',
                'post_status'   => 'publish',
                'post_category' => array( $test_category_1['id'] ),
            ),
            'args'           => array(),
            'call_param'     => 'own_id',
            'no_global_post' => true,
            'correct'        => '<span class="btn btn-sm" style="color:' . $text_color_1 . ';background-color:' . $test_category_1['color'] . '">' . $test_category_1['name'] . '</span>',
        ),
        );

        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_get_auto_post_single_term_html()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        global $post;
        $backup_post = $post;

        // get_the_terms() の戻り値を強制的に WP_Error にするフィルター（登録されていない taxonomy を渡した場合の再現用）
        $force_wp_error_terms = function () {
            return new \WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
        };

        // テストパターンを順々にテスト
        foreach ( $tests as $test ) {

            $post_id = null;
            if ( null !== $test['data'] ) {
                $post_id = wp_insert_post( $test['data'] );
            }

            // グローバルの投稿を準備（ケースにより、無し / 作成した投稿）
            if ( ! empty( $test['no_global_post'] ) ) {
                $post = null;
            } else {
                $post = get_post( $post_id );
            }

            // 第1引数（$post）を組み立て（指定が無いケースは従来どおり空文字＝グローバルの投稿を使う）
            $call_post = ( isset( $test['call_param'] ) && 'own_id' === $test['call_param'] ) ? $post_id : '';

            if ( ! empty( $test['force_wp_error'] ) ) {
                add_filter( 'get_the_terms', $force_wp_error_terms );
            }

            $return = VkTermColor::get_auto_post_single_term_html( $call_post, $test['args'] );

            if ( ! empty( $test['force_wp_error'] ) ) {
                remove_filter( 'get_the_terms', $force_wp_error_terms );
            }

            print 'return  :' . PHP_EOL;
            var_dump( $return );
            print PHP_EOL;
            print 'correct  :'. PHP_EOL;
            var_dump( $test['correct'] );
            print PHP_EOL;
            $this->assertSame( $test['correct'], $return );

        }

        // 他のテストへグローバルの投稿を持ち越さない
        $post = $backup_post;
	}

	/**
	 * Test case get_post_terms_html()
	 * 投稿を指定せず呼び出した場合のグローバル投稿への正規化を検証する（issue #31 の再現ケースを含む）
	 *
	 * @return void
	 */
	public function test_get_post_terms_html() {

        $test_category_0 = $this->test_terms['categories'][0];
        $test_category_1 = $this->test_terms['categories'][1];

        // 期待するテキスト色は実際の関数から算出する（ハードコードの決め打ちを避ける）
        $text_color_0 = VkTermColor::get_dynamic_text_color( $test_category_0['color'] );
        $text_color_1 = VkTermColor::get_dynamic_text_color( $test_category_1['color'] );

        // テストパターン
        $tests = array(
            // 通常呼び出し（ループ内・グローバルの投稿あり）：従来どおりの出力になること
            array(
                'data' => array(
                    'post_title'    => 'Page In Loop',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'     => 'post',
                    'post_status'   => 'publish',
                    'post_category' => array( $test_category_0['id'] ),
                ),
                'args'           => array( 'taxonomy' => 'category' ),
                'call_param'     => '', // 第1引数は空のまま、グローバルの投稿を使う
                'no_global_post' => false,
                'correct'        => '<div><span class="btn btn-sm" style="color:' . $text_color_0 . ';background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] . '</span></div>',
            ),
            // 投稿IDを渡した場合：グローバルの投稿が無くても、渡したIDの投稿が正しく使われること
            array(
                'data' => array(
                    'post_title'    => 'Page By Id',
                    'post_content' => '<!-- wp:paragraph --><p>This is another page.</p><!-- /wp:paragraph -->',
                    'post_type'     => 'post',
                    'post_status'   => 'publish',
                    'post_category' => array( $test_category_1['id'] ),
                ),
                'args'           => array( 'taxonomy' => 'category' ),
                'call_param'     => 'own_id', // 作成した投稿のIDをそのまま渡す
                'no_global_post' => true,
                'correct'        => '<div><span class="btn btn-sm" style="color:' . $text_color_1 . ';background-color:' . $test_category_1['color'] . '">' . $test_category_1['name'] . '</span></div>',
            ),
            // グローバルの投稿が存在しない状態（ループ外・404ページ・検索結果0件等）で呼び出しても、
            // 警告（Attempt to read property "ID" on null）を出さず、何も返さないこと（issue #31 の再現ケース）
            array(
                'data'           => null, // 投稿は作成しない
                'args'           => array( 'taxonomy' => 'category' ),
                'call_param'     => '',
                'no_global_post' => true,
                'correct'        => null,
            ),
            // 投稿IDを直接指定し、taxonomy を省略（自動検出）、グローバルの投稿が無い状態でも、
            // 渡した投稿のタームが正しく自動検出されて出力されること（PR#37 CodeRabbit指摘の回帰テスト）
            // get_the_taxonomies() は引数省略時にグローバルの投稿を見るため、正規化前の状態を参照すると
            // 自動検出に失敗し、本来表示されるべきタームが何も表示されなくなる不具合があった.
            array(
                'data' => array(
                    'post_title'    => 'Page By Id Auto Taxonomy',
                    'post_content' => '<!-- wp:paragraph --><p>This is another page.</p><!-- /wp:paragraph -->',
                    'post_type'     => 'post',
                    'post_status'   => 'publish',
                    'post_category' => array( $test_category_0['id'] ),
                ),
                'args'           => array(), // taxonomy 未指定 => 自動検出
                'call_param'     => 'own_id',
                'no_global_post' => true,
                'correct'        => '<div><span class="btn btn-sm" style="color:' . $text_color_0 . ';background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] . '</span></div>',
            ),
        );

        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_get_post_terms_html()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        global $post;
        $backup_post = $post;

        foreach ( $tests as $test ) {

            $post_id = null;
            if ( null !== $test['data'] ) {
                $post_id = wp_insert_post( $test['data'] );
            }

            // グローバルの投稿を準備（ケースにより、無し / 作成した投稿）
            if ( $test['no_global_post'] ) {
                $post = null;
            } else {
                $post = get_post( $post_id );
            }

            // 第1引数（$post）を組み立て
            $call_post = ( 'own_id' === $test['call_param'] ) ? $post_id : $test['call_param'];

            $return = VkTermColor::get_post_terms_html( $call_post, $test['args'] );

            print 'return  :' . PHP_EOL;
            var_dump( $return );
            print PHP_EOL;
            print 'correct  :'. PHP_EOL;
            var_dump( $test['correct'] );
            print PHP_EOL;
            $this->assertSame( $test['correct'], $return );
        }

        // 他のテストへグローバルの投稿を持ち越さない
        $post = $backup_post;
	}

   /**
     * Test case get_single_term_with_color()
     *
     * @return void
     */
	public function test_get_single_term_with_color() {

        $default_color = VkTermColor::get_default_color();

        $test_category_0 = $this->test_terms['categories'][0];
        $test_category_1 = $this->test_terms['categories'][1];

        // 期待するテキスト色は実際の関数から算出する（ハードコードの決め打ちを避ける）
        $text_color_1 = VkTermColor::get_dynamic_text_color( $test_category_1['color'] );

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
                'correct' => '<span style="color:#000000;background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] .'</span>'
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
                'correct' => '<span style="color:#FFFFFF;background-color:' . $default_color . '">Uncategorized</span>'
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
                'correct' => '<a class="vk-term-color-test-single" style="color:#000000;background-color:' . $test_category_0['color'] . '" href="' . site_url() . '/?cat=' . $test_category_0['id'] . '">' . $test_category_0['name'] .'</a>'
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
                'correct' => '<span class="vk-term-color-test-single" style="color:#000000;background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] .'</span>'
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
                'correct' => '<a style="color:#000000;background-color:' . $test_category_0['color'] . '" href="' . site_url() . '/?cat=' . $test_category_0['id'] . '">' . $test_category_0['name'] .'</a>'
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
                'correct' => '<span style="color:#000000;background-color:' . $test_category_0['color'] . '">' . $test_category_0['name'] .'</span>'
            ),
            // vk_term_color_taxonomy フックが登録されていない分類名を返した場合、
            // get_the_terms() が WP_Error を返しても致命的エラーにならず null が返る
            array(
                'data' => array(
                    'post_title'   => 'Page Title',
                    'post_content' => '<!-- wp:paragraph --><p>This is my page.</p><!-- /wp:paragraph -->',
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_category' => array( $test_category_0['id'] )
                ),
                'args' => array(),
                'force_invalid_taxonomy' => true,
                'correct' => null
            ),
            // グローバルの投稿が存在しない状態（ループ外・404ページ・検索結果0件等）で、投稿を指定せず呼び出しても、
            // 警告（Attempt to read property "ID" on null）を出さず、null が返ること（issue #38 の再現ケース）
            array(
                'data'           => null, // 投稿は作成しない
                'args'           => array(),
                'call_param'     => '',
                'no_global_post' => true,
                'correct'        => null,
            ),
            // 投稿IDを直接指定した場合：グローバルの投稿が無くても、渡したIDの投稿のタームが
            // 正しく出力されること
            array(
                'data' => array(
                    'post_title'    => 'Page By Id',
                    'post_content'  => '<!-- wp:paragraph --><p>This is another page.</p><!-- /wp:paragraph -->',
                    'post_type'     => 'post',
                    'post_status'   => 'publish',
                    'post_category' => array( $test_category_1['id'] ),
                ),
                'args'           => array(),
                'call_param'     => 'own_id',
                'no_global_post' => true,
                'correct'        => '<span style="color:' . $text_color_1 . ';background-color:' . $test_category_1['color'] . '">' . $test_category_1['name'] . '</span>',
            ),
        );

        print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_get_single_term_with_color()' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

        global $post;
        $backup_post = $post;

        // 登録されていない分類名を返すフィルター（vk_term_color_taxonomy フックの誤設定の再現用）
        $force_invalid_taxonomy = function () {
            return 'vk_term_color_nonexistent_taxonomy_for_test';
        };

        // テストパターンを順々にテスト
        foreach ( $tests as $test ) {

            $post_id = null;
            if ( null !== $test['data'] ) {
                $post_id = wp_insert_post( $test['data'] );
            }

            // グローバルの投稿を準備（ケースにより、無し / 作成した投稿）
            if ( ! empty( $test['no_global_post'] ) ) {
                $post = null;
            } else {
                $post = get_post( $post_id );
            }

            // 第1引数（$post）を組み立て（指定が無いケースは従来どおり空文字＝グローバルの投稿を使う）
            $call_post = ( isset( $test['call_param'] ) && 'own_id' === $test['call_param'] ) ? $post_id : '';

            if ( ! empty( $test['force_invalid_taxonomy'] ) ) {
                add_filter( 'vk_term_color_taxonomy', $force_invalid_taxonomy );
            }

            $return = VkTermColor::get_single_term_with_color( $call_post, $test['args'] );

            if ( ! empty( $test['force_invalid_taxonomy'] ) ) {
                remove_filter( 'vk_term_color_taxonomy', $force_invalid_taxonomy );
            }

            print 'return  :' . PHP_EOL;
            var_dump( $return );
            print PHP_EOL;
            print 'correct  :'. PHP_EOL;
            var_dump( $test['correct'] );
            print PHP_EOL;
            $this->assertSame( $test['correct'], $return );

        }

        // 他のテストへグローバルの投稿を持ち越さない
        $post = $backup_post;
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

    /**
     * Test case get_dynamic_text_color()
     * 既存の有効な6桁カラーコードの判定結果が1件も変わっていないこと（回帰防止）に加え、
     * 3桁カラーコードの判定、および不正な入力（文字列以外・桁数不正・16進数として
     * 解釈できない文字列）を渡してもPHPの警告・致命的エラーにならず、決めた既定値
     * （#FFFFFF）にフォールバックすることを検証する.
     * phpunit.xml.dist で notice/warning が例外化されているため、警告が1件でも
     * 出ればこのテスト自体が失敗する.
     *
     * @return void
     */
    public function test_get_dynamic_text_color() {

        // 既存の有効な6桁カラーコード（修正前から変わらず存在するテーブル）.
        // このテーブルの戻り値は1件も変更しない（入力確認の追加で既存の正常系を壊していないことの固定）.
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

        // 3桁カラーコード（省略形）. 対応する6桁カラーコードと同じ判定結果になることを固定する.
        $short_hand_data = array(
            '#000' => '#FFFFFF', // #000000 と同じ
            '#333' => '#FFFFFF', // #333333 と同じ
            '#999' => '#FFFFFF', // #999999 と同じ（プラグインの既定背景色）
            '#FFF' => '#000000', // #FFFFFF と同じ
            '#F00' => '#FFFFFF', // #FF0000 と同じ
            '#0F0' => '#FFFFFF', // #00FF00 と同じ
            '#00F' => '#FFFFFF', // #0000FF と同じ
            '#FF0' => '#000000', // #FFFF00 と同じ
            '#0FF' => '#000000', // #00FFFF と同じ
            '#F0F' => '#FFFFFF', // #FF00FF と同じ
        );

        // 不正な入力のテストケース（入力の型・値が様々なため input をキーではなく値として持つ）.
        $invalid_cases = array(
            array(
                'test_condition_name' => 'null を渡しても警告にならず既定値が返る',
                'input'                => null,
                'expected'             => '#FFFFFF',
            ),
            array(
                'test_condition_name' => '配列を渡しても TypeError にならず既定値が返る',
                'input'                => array( '#ff0000' ),
                'expected'             => '#FFFFFF',
            ),
            array(
                'test_condition_name' => '数値を渡しても既定値が返る',
                'input'                => 999999,
                'expected'             => '#FFFFFF',
            ),
            array(
                'test_condition_name' => '16進数として解釈できない文字列（色名）は既定値が返る',
                'input'                => 'red',
                'expected'             => '#FFFFFF',
            ),
            array(
                'test_condition_name' => '4桁（桁数不正）は青成分を0扱いにせず既定値が返る',
                'input'                => '#abcd',
                'expected'             => '#FFFFFF',
            ),
            array(
                'test_condition_name' => '8桁（桁数不正）も既定値が返る',
                'input'                => '#ff0000ff',
                'expected'             => '#FFFFFF',
            ),
            array(
                'test_condition_name' => '空文字も既定値が返る',
                'input'                => '',
                'expected'             => '#FFFFFF',
            ),
            array(
                // CodeRabbit 指摘（PR#36）の回帰テスト.
                // str_replace( '#', '', $hex ) で全ての # を除去していた旧実装だと
                // '#F#F#F' が 'FFF' になり、3桁の有効なカラーコードとして誤って通ってしまっていた.
                'test_condition_name' => '途中に # を含む壊れた値（#F#F#F）は 3桁カラーコードとして通らず既定値が返る',
                'input'                => '#F#F#F',
                'expected'             => '#FFFFFF',
            ),
            array(
                'test_condition_name' => '先頭に # が2個連続する壊れた値（##000）も既定値が返る（先頭の#は1個しか除去しない）',
                'input'                => '##000',
                'expected'             => '#FFFFFF',
            ),
        );

        // 先頭の # だけを1個除去する実装に変更した後も、通常の入力（# なし・先頭に # 1個）の
        // 判定結果が変わっていないことを確認するための回帰データ.
        $leading_hash_data = array(
            'FFFFFF' => '#000000', // # なし・6桁
            'fff'    => '#000000', // # なし・3桁
            '#999999' => '#FFFFFF', // 先頭 # 1個・6桁
            '#999'    => '#FFFFFF', // 先頭 # 1個・3桁
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
            $this->assertSame( $correct, $return, $input . ' の判定結果が変わっていないこと' );
        }

        foreach ( $short_hand_data as $input => $correct ) {
            $return = VkTermColor::get_dynamic_text_color( $input );
            print 'input(3桁), correct, return :' . PHP_EOL;
            var_dump( $input, $correct, $return );
            print PHP_EOL;
            $this->assertSame( $correct, $return, $input . ' （3桁）の判定結果' );
        }

        foreach ( $leading_hash_data as $input => $correct ) {
            $return = VkTermColor::get_dynamic_text_color( $input );
            print 'input(#なし/先頭#1個), correct, return :' . PHP_EOL;
            var_dump( $input, $correct, $return );
            print PHP_EOL;
            $this->assertSame( $correct, $return, $input . ' の判定結果（先頭の#除去を1個だけに変更後も不変）' );
        }

        foreach ( $invalid_cases as $case ) {
            $return = VkTermColor::get_dynamic_text_color( $case['input'] );
            print 'input(不正値), expected, return :' . PHP_EOL;
            var_dump( $case['input'], $case['expected'], $return );
            print PHP_EOL;
            $this->assertSame( $case['expected'], $return, $case['test_condition_name'] );
        }
    }


    /**
     * Test case term_meta_color()
     * register_meta() が term_color に対して sanitize_callback 付きで登録され、
     * 保存時のサニタイズが実際に効くことを確認する
     *
     * @return void
     */
    public function test_term_meta_color() {

        VkTermColor::term_meta_color();

        print PHP_EOL;
        print '------------------------------------' . PHP_EOL;
        print 'test_term_meta_color()' . PHP_EOL;
        print '------------------------------------' . PHP_EOL;

        // register_meta() が連想配列キーで sanitize_callback を登録していることを確認
        $registered_meta = get_registered_meta_keys( 'term' );

        $this->assertArrayHasKey( 'term_color', $registered_meta, 'term_color が register_meta() で登録されていません.' );
        $this->assertSame(
            array( VkTermColor::class, 'sanitize_hex' ),
            $registered_meta['term_color']['sanitize_callback'],
            'term_color の sanitize_callback が正しく登録されていません.'
        );

        // 保存時のサニタイズ確認用のターム
        $term_id = $this->test_terms['terms'][ $this->test_taxonomies[0]['name'] ][0]['id'];

        // テストパターン
        $tests = array(
            array(
                'test_condition_name' => '6桁の正しいカラー値は # を除いて保存される',
                'input'                => '#ff0000',
                'expected'             => 'ff0000',
            ),
            array(
                'test_condition_name' => '3桁の正しいカラー値はそのまま保存される',
                'input'                => '#abc',
                'expected'             => 'abc',
            ),
            array(
                'test_condition_name' => '16進数カラーではない値は空文字として保存される',
                'input'                => 'notacolor000',
                'expected'             => '',
            ),
            array(
                'test_condition_name' => '桁数が不正なカラー値（8桁）は空文字として保存される',
                'input'                => '#ff0000ff',
                'expected'             => '',
            ),
            array(
                'test_condition_name' => '配列値を渡しても TypeError にならず空文字として保存される',
                'input'                => array( '#ff0000' ),
                'expected'             => '',
            ),
            array(
                'test_condition_name' => '末尾に改行を付けた値は空文字として保存される（PCREの$は末尾改行の直前にもマッチするため）',
                'input'                => "abc\n",
                'expected'             => '',
            ),
        );

        foreach ( $tests as $test ) {
            update_term_meta( $term_id, 'term_color', $test['input'] );
            $actual = get_term_meta( $term_id, 'term_color', true );

            print 'input, expected, actual :' . PHP_EOL;
            var_dump( $test['input'], $test['expected'], $actual );
            print PHP_EOL;

            $this->assertSame( $test['expected'], $actual, $test['test_condition_name'] );
        }
    }

    /**
     * Test case save_term_meta_color()
     * nonce が有効でも、対象タームを編集する権限（edit_term）を持たないユーザーからの
     * 保存リクエストは拒否されることを確認する。get_term_color_taxonomies() が返す
     * 複数タクソノミー（標準の category / カスタムタクソノミー）のどちらでも
     * 同様に機能することもあわせて検証する。
     *
     * @return void
     */
    public function test_save_term_meta_color() {

        VkTermColor::term_meta_color();

        print PHP_EOL;
        print '------------------------------------' . PHP_EOL;
        print 'test_save_term_meta_color()' . PHP_EOL;
        print '------------------------------------' . PHP_EOL;

        // nonce の action 名は basename( __FILE__ ) ( = 'VkTermColor.php' ) と一致させる必要があるため、
        // 対象クラスの実ファイル名から動的に取得する（値をテスト側に決め打ちしない）.
        $nonce_action = basename( ( new ReflectionClass( VkTermColor::class ) )->getFileName() );

        // 検証用タームはケースごとに独立したものを用意する（他ケースの保存結果と混ざらないようにするため）.
        $custom_taxonomy = $this->test_taxonomies[0]['name'];

        $admin_category_term      = wp_insert_term( 'Permission Check Category Admin', 'category' );
        $admin_custom_term        = wp_insert_term( 'Permission Check Term Admin', $custom_taxonomy );
        $subscriber_category_term = wp_insert_term( 'Permission Check Category Subscriber', 'category' );
        $subscriber_custom_term   = wp_insert_term( 'Permission Check Term Subscriber', $custom_taxonomy );

        // テストパターン
        $tests = array(
            array(
                'test_condition_name' => '対象タクソノミーの編集権限を持つ administrator は category のターム色を保存できる',
                'role'                 => 'administrator',
                'term_id'              => $admin_category_term['term_id'],
                'input'                => 'aa11bb',
                'expected'             => 'aa11bb',
            ),
            array(
                'test_condition_name' => '対象タクソノミーの編集権限を持つ administrator はカスタムタクソノミーのターム色も保存できる',
                'role'                 => 'administrator',
                'term_id'              => $admin_custom_term['term_id'],
                'input'                => '00cc22',
                'expected'             => '00cc22',
            ),
            array(
                'test_condition_name' => '編集権限を持たない subscriber は、有効な nonce を持っていても category のターム色を保存できない',
                'role'                 => 'subscriber',
                'term_id'              => $subscriber_category_term['term_id'],
                'input'                => 'ff0000',
                'expected'             => '', // 未保存（＝タームに term_color メタが一度も設定されていない）ままであることを期待する.
            ),
            array(
                'test_condition_name' => '編集権限を持たない subscriber は、有効な nonce を持っていてもカスタムタクソノミーのターム色を保存できない',
                'role'                 => 'subscriber',
                'term_id'              => $subscriber_custom_term['term_id'],
                'input'                => '0000ff',
                'expected'             => '',
            ),
        );

        foreach ( $tests as $test ) {
            // 対象ロールのユーザーへ切り替えたうえで、そのユーザー自身の有効な nonce を発行する
            // （nonce は current_user_id に依存するため、切り替え後に発行しないと検証に失敗する）.
            $this->set_current_user( $test['role'] );
            $_POST['term_color_nonce'] = wp_create_nonce( $nonce_action );
            $_POST['term_color']       = $test['input'];

            VkTermColor::save_term_meta_color( $test['term_id'] );

            $actual = get_term_meta( $test['term_id'], 'term_color', true );

            print 'role, input, expected, actual :' . PHP_EOL;
            var_dump( $test['role'], $test['input'], $test['expected'], $actual );
            print PHP_EOL;

            // 保存が許可されたケースでは入力値が保存され、拒否されたケースでは未設定（空文字）のまま
            // であることを、この1つの比較で同時に検証する.
            $this->assertSame( $test['expected'], $actual, $test['test_condition_name'] );

            unset( $_POST['term_color_nonce'], $_POST['term_color'] );
        }
    }

    /**
     * Test case init()
     * init アクションの実行中（did_action('init') が真）に init() が呼ばれても、
     * term_meta_color() がその場で実行され term_color の sanitize_callback が
     * 登録されることを確認する（add_action で積むだけでは発火しないケースの検知用）
     *
     * @return void
     */
    public function test_init_registers_term_meta_color_when_init_already_fired() {
        global $wp_actions;

        print PHP_EOL;
        print '------------------------------------' . PHP_EOL;
        print 'test_init_registers_term_meta_color_when_init_already_fired()' . PHP_EOL;
        print '------------------------------------' . PHP_EOL;

        // init が既に発火済みの状態を明示的に再現する
        $before_init_count  = isset( $wp_actions['init'] ) ? $wp_actions['init'] : 0;
        $wp_actions['init'] = 1;

        try {
            $this->assertTrue( (bool) did_action( 'init' ), 'テスト前提の did_action( \'init\' ) が真になっていません.' );

            VkTermColor::init();

            $registered_meta = get_registered_meta_keys( 'term' );

            $this->assertArrayHasKey(
                'term_color',
                $registered_meta,
                'did_action(\'init\') が真の状態で init() を呼んでも term_color が register_meta() されていません.'
            );
            $this->assertSame(
                array( VkTermColor::class, 'sanitize_hex' ),
                $registered_meta['term_color']['sanitize_callback'],
                'did_action(\'init\') が真の状態で init() を呼んだ際の term_color の sanitize_callback が正しくありません.'
            );
        } finally {
            // 他のテストへ init の発火状態を持ち越さない
            $wp_actions['init'] = $before_init_count;
        }
    }

}
