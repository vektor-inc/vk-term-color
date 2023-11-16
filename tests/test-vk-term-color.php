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
    private $posts;

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
        $this->posts = array(
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
        foreach ( $this->posts as $test ) {
            $page_id = wp_insert_post( $test['data'] );
            $return = VkTermColor::get_post_single_term_info($page_id);
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
}