<?php
/**
 * This file is part of the SatoshiPay WordPress plugin.
 *
 * (c) SatoshiPay <hello@satoshipay.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SatoshiPay\Command;

use WP_CLI;
use WP_CLI_Command;

/**
 * Manage fixtures.
 */
class FixtureCommand extends WP_CLI_Command
{
    /** @var integer */
    protected $count = 100;

    /**
     * switch to use external or internal text generator
     * @var bool
     */
    protected $useApiIpsum = false;

    /** @var array */
    protected $statuses = array(
        'draft',
        'publish', // create more published posts than drafts
        'publish',
        'publish',
    );

    
    /**
     * Create posts.
     *
     * ## OPTIONS
     *
     * [--count=<number>]
     * : Count of fixtures to create. Default: 100
     *
     * ## EXAMPLES
     *
     *     wp fixture create-posts --count=100
     *
     * @subcommand create-posts
     */
    public function createPosts($args, $assoc_args)
    {
        $defaults = array(
            'count' => $this->count,
        );
        // Merge default and cli arguments and create variables with the
        // corrensponding $name=value
        extract(array_merge($defaults, $assoc_args));
	    /** @var $count int */


        for ($i = 1; $i <= $count; $i++) {


        	if (!$this->generatePostIpsum($postTitle, $postContent)) {
        		continue;
	        }

            $postStatus = $this->statuses[array_rand($this->statuses)];

            $postId = wp_insert_post(
                array(
                    'post_type'     => 'post',
                    'post_title'    => $postTitle,
                    'post_status'   => $postStatus,
                    'post_content'  => $postContent,
                ),
                true
            );

            // Check for error
            if (is_wp_error($postId)) {
                WP_CLI::warning($postId);
                continue;
            }

            WP_CLI::success('Created post "' . $postTitle . '" (ID: ' . $postId . ', status: ' . $postStatus . ').');
        }
    }


	/**
	 * Creates example data
	 *
	 * based on flag $useApiIpsum it uses internal lib or external api to create the data
	 *
	 * @param $postTitle string
	 * @param $postContent string
	 *
	 * @return bool
	 */
	public function generatePostIpsum(&$postTitle, &$postContent)
	{
		if ($this->useApiIpsum) {
			$response = wp_remote_get('http://loripsum.net/api/5/plaintext');
			$responseData = wp_remote_retrieve_body($response);

			// Check for error
			if (is_wp_error($responseData)) {
				WP_CLI::error($responseData);
				return false;
			}

			// Remove first sentence because its always the same "Lorem ipsum ..."
			$postContent = preg_replace('/^[^{.?!}]+[{.?!}]/', '', $responseData);
			$postTitle = hash('sha256', uniqid());
			if (preg_match('/^([^{.\?\!}]+)[{.?!}]/', $postContent, $matches)) {
				$postTitle = substr(trim($matches[1]), 0, 50);
			}
		} else {
			if (!$this->useApiIpsum) {
				require_once __DIR__ . '/../lib/LoremIpsum.php';
				$lipsum = new \joshtronic\LoremIpsum();
			}

			$wordCount = rand(1, 10);
			$paragraphCount = rand(1, 5);

			$postTitle = [$lipsum->wordsArray($wordCount)];
			$lipsum->punctuate($postTitle);
			$postTitle = implode(' ', $postTitle);
			$postContent = $lipsum->paragraphs($paragraphCount, 'p');
		}

		return true;
	}


	/**
  	 * Check for created posts.
  	 *
  	 * ## OPTIONS
  	 *
  	 * [--count=<number>]
  	 * : Count of fixtures to create. Default: 100
  	 *
  	 * ## EXAMPLES
  	 *
  	 *     wp fixture has-posts --count=100
     *
     * @subcommand has-posts
  	 */
    public function hasPosts($args, $assoc_args)
    {
        $offsetDefaultPosts = 1;
        $countPosts = 0;

        $defaults = array(
            'count' => $this->count,
        );
        // Merge default and cli arguments and create variables with the
        // corrensponding $name=value
        extract(array_merge($defaults, $assoc_args));
	    /** @var $count int */

        foreach (wp_count_posts('post') as $post) {
            $countPosts += (int)$post;
        }

        $countPosts -= $offsetDefaultPosts;
        if ($countPosts !== (int)$count) {
            WP_CLI::halt(1);
        }
    }
}

WP_CLI::add_command('fixture', 'SatoshiPay\Command\FixtureCommand');
